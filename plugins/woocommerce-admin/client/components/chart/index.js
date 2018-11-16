/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { Component, createRef } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { formatDefaultLocale as d3FormatDefaultLocale } from 'd3-format';
import { get, isEqual, partial } from 'lodash';
import Gridicon from 'gridicons';
import { IconButton, NavigableMenu, SelectControl } from '@wordpress/components';
import { interpolateViridis as d3InterpolateViridis } from 'd3-scale-chromatic';
import PropTypes from 'prop-types';
import { withViewportMatch } from '@wordpress/viewport';

/**
 * WooCommerce dependencies
 */
import { updateQueryString } from '@woocommerce/navigation';
import { H, Section } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import D3Chart from './charts';
import Legend from './legend';

d3FormatDefaultLocale( {
	decimal: '.',
	thousands: ',',
	grouping: [ 3 ],
	currency: [ decodeEntities( get( wcSettings, 'currency.symbol', '' ) ), '' ],
} );

function getOrderedKeys( props ) {
	const updatedKeys = [
		...new Set(
			props.data.reduce( ( accum, curr ) => {
				Object.keys( curr ).forEach( key => key !== 'date' && accum.push( key ) );
				return accum;
			}, [] )
		),
	].map( key => ( {
		key,
		total: props.data.reduce( ( a, c ) => a + c[ key ].value, 0 ),
		visible: true,
		focus: true,
	} ) );
	if ( props.layout === 'comparison' ) {
		updatedKeys.sort( ( a, b ) => b.total - a.total );
	}
	return updatedKeys;
}

/**
 * A chart container using d3, to display timeseries data with an interactive legend.
 */
class Chart extends Component {
	constructor( props ) {
		super( props );
		this.chartBodyRef = createRef();
		this.state = {
			data: props.data,
			orderedKeys: getOrderedKeys( props ),
			type: props.type,
			visibleData: [ ...props.data ],
			width: 0,
		};
		this.handleTypeToggle = this.handleTypeToggle.bind( this );
		this.handleLegendToggle = this.handleLegendToggle.bind( this );
		this.handleLegendHover = this.handleLegendHover.bind( this );
		this.updateDimensions = this.updateDimensions.bind( this );
		this.getVisibleData = this.getVisibleData.bind( this );
		this.setInterval = this.setInterval.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { data } = this.props;
		const orderedKeys = getOrderedKeys( this.props );
		if ( ! isEqual( [ ...data ].sort(), [ ...prevProps.data ].sort() ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				orderedKeys: orderedKeys,
				visibleData: this.getVisibleData( data, orderedKeys ),
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	componentDidMount() {
		this.updateDimensions();
		window.addEventListener( 'resize', this.updateDimensions );
	}

	componentWillUnmount() {
		window.removeEventListener( 'resize', this.updateDimensions );
	}

	handleTypeToggle( type ) {
		if ( this.state.type !== type ) {
			this.setState( { type } );
		}
	}

	handleLegendToggle( event ) {
		const { data } = this.props;
		const orderedKeys = this.state.orderedKeys.map( d => ( {
			...d,
			visible: d.key === event.target.id ? ! d.visible : d.visible,
		} ) );
		const copyEvent = { ...event }; // can't pass a synthetic event into the hover handler
		this.setState(
			{
				orderedKeys,
				visibleData: this.getVisibleData( data, orderedKeys ),
			},
			() => {
				this.handleLegendHover( copyEvent );
			}
		);
	}

	handleLegendHover( event ) {
		const hoverTarget = this.state.orderedKeys.filter( d => d.key === event.target.id )[ 0 ];
		this.setState( {
			orderedKeys: this.state.orderedKeys.map( d => {
				let enterFocus = d.key === event.target.id ? true : false;
				enterFocus = ! hoverTarget.visible ? true : enterFocus;
				return {
					...d,
					focus: event.type === 'mouseleave' || event.type === 'blur' ? true : enterFocus,
				};
			} ),
		} );
	}

	updateDimensions() {
		this.setState( {
			width: this.chartBodyRef.current.offsetWidth,
		} );
	}

	getVisibleData( data, orderedKeys ) {
		const visibleKeys = orderedKeys.filter( d => d.visible );
		return data.map( d => {
			const newRow = { date: d.date };
			visibleKeys.forEach( row => {
				newRow[ row.key ] = d[ row.key ];
			} );
			return newRow;
		} );
	}

	setInterval( interval ) {
		const { path, query } = this.props;
		updateQueryString( { interval }, path, query );
	}

	renderIntervalSelector() {
		const { interval, allowedIntervals } = this.props;
		if ( ! allowedIntervals || allowedIntervals.length < 1 ) {
			return null;
		}

		const intervalLabels = {
			hour: __( 'By hour', 'wc-admin' ),
			day: __( 'By day', 'wc-admin' ),
			week: __( 'By week', 'wc-admin' ),
			month: __( 'By month', 'wc-admin' ),
			quarter: __( 'By quarter', 'wc-admin' ),
			year: __( 'By year', 'wc-admin' ),
		};

		return (
			<SelectControl
				className="woocommerce-chart__interval-select"
				value={ interval }
				options={ allowedIntervals.map( allowedInterval => ( {
					value: allowedInterval,
					label: intervalLabels[ allowedInterval ],
				} ) ) }
				onChange={ this.setInterval }
			/>
		);
	}

	getChartHeight() {
		const { isViewportLarge, isViewportMobile } = this.props;

		if ( isViewportMobile ) {
			return 180;
		}

		if ( isViewportLarge ) {
			return 300;
		}

		return 220;
	}

	render() {
		const { orderedKeys, type, visibleData, width } = this.state;
		const {
			dateParser,
			itemsLabel,
			layout,
			mode,
			pointLabelFormat,
			isViewportLarge,
			isViewportWide,
			title,
			tooltipFormat,
			tooltipTitle,
			xFormat,
			x2Format,
			interval,
			valueType,
		} = this.props;
		let { yFormat } = this.props;
		const legendDirection = layout === 'standard' && isViewportWide ? 'row' : 'column';
		const chartDirection = layout === 'comparison' && isViewportWide ? 'row' : 'column';
		const chartHeight = this.getChartHeight();
		const legend = (
			<Legend
				colorScheme={ d3InterpolateViridis }
				data={ orderedKeys }
				handleLegendHover={ this.handleLegendHover }
				handleLegendToggle={ this.handleLegendToggle }
				legendDirection={ legendDirection }
				itemsLabel={ itemsLabel }
				valueType={ valueType }
			/>
		);
		const margin = {
			bottom: 50,
			left: 80,
			right: 30,
			top: 0,
		};

		switch ( valueType ) {
			case 'average':
				yFormat = '.0f';
				break;
			case 'currency':
				yFormat = '$.3s';
				break;
			case 'number':
				yFormat = '.0f';
				break;
		}
		return (
			<div className="woocommerce-chart">
				<div className="woocommerce-chart__header">
					<H className="woocommerce-chart__title">{ title }</H>
					{ isViewportWide && legendDirection === 'row' && legend }
					{ this.renderIntervalSelector() }
					<NavigableMenu
						className="woocommerce-chart__types"
						orientation="horizontal"
						role="menubar"
					>
						<IconButton
							className={ classNames( 'woocommerce-chart__type-button', {
								'woocommerce-chart__type-button-selected': type === 'line',
							} ) }
							icon={ <Gridicon icon="line-graph" /> }
							title={ __( 'Line chart', 'wc-admin' ) }
							aria-checked={ type === 'line' }
							role="menuitemradio"
							tabIndex={ type === 'line' ? 0 : -1 }
							onClick={ partial( this.handleTypeToggle, 'line' ) }
						/>
						<IconButton
							className={ classNames( 'woocommerce-chart__type-button', {
								'woocommerce-chart__type-button-selected': type === 'bar',
							} ) }
							icon={ <Gridicon icon="stats-alt" /> }
							title={ __( 'Bar chart', 'wc-admin' ) }
							aria-checked={ type === 'bar' }
							role="menuitemradio"
							tabIndex={ type === 'bar' ? 0 : -1 }
							onClick={ partial( this.handleTypeToggle, 'bar' ) }
						/>
					</NavigableMenu>
				</div>
				<Section component={ false }>
					<div
						className={ classNames(
							'woocommerce-chart__body',
							`woocommerce-chart__body-${ chartDirection }`
						) }
						ref={ this.chartBodyRef }
					>
						{ isViewportWide && legendDirection === 'column' && legend }
						{ width > 0 && (
							<D3Chart
								colorScheme={ d3InterpolateViridis }
								data={ visibleData }
								dateParser={ dateParser }
								height={ chartHeight }
								margin={ margin }
								mode={ mode }
								orderedKeys={ orderedKeys }
								pointLabelFormat={ pointLabelFormat }
								tooltipFormat={ tooltipFormat }
								tooltipPosition={ isViewportLarge ? 'over' : 'below' }
								tooltipTitle={ tooltipTitle }
								type={ type }
								interval={ interval }
								width={ chartDirection === 'row' ? width - 320 : width }
								xFormat={ xFormat }
								x2Format={ x2Format }
								yFormat={ yFormat }
								valueType={ valueType }
							/>
						) }
					</div>
					{ ! isViewportWide && <div className="woocommerce-chart__footer">{ legend }</div> }
				</Section>
			</div>
		);
	}
}

Chart.propTypes = {
	/**
	 * An array of data.
	 */
	data: PropTypes.array.isRequired,
	/**
	 * Format to parse dates into d3 time format
	 */
	dateParser: PropTypes.string.isRequired,
	/**
	 * Current path
	 */
	path: PropTypes.string,
	/**
	 * Date format of the point labels (might be used in tooltips and ARIA properties).
	 */
	pointLabelFormat: PropTypes.string,
	/**
	 * The query string represented in object form
	 */
	query: PropTypes.object,
	/**
	 * A datetime formatting string to format the date displayed as the title of the toolip
	 * if `tooltipTitle` is missing, passed to d3TimeFormat.
	 */
	tooltipFormat: PropTypes.string,
	/**
	 * A string to use as a title for the tooltip. Takes preference over `tooltipFormat`.
	 */
	tooltipTitle: PropTypes.string,
	/**
	 * A datetime formatting string, passed to d3TimeFormat.
	 */
	xFormat: PropTypes.string,
	/**
	 * A datetime formatting string, passed to d3TimeFormat.
	 */
	x2Format: PropTypes.string,
	/**
	 * A number formatting string, passed to d3Format.
	 */
	yFormat: PropTypes.string,
	/**
	 * `standard` (default) legend layout in the header or `comparison` moves legend layout
	 * to the left or 'compact' has the legend below
	 */
	layout: PropTypes.oneOf( [ 'standard', 'comparison', 'compact' ] ),
	/**
	 * `item-comparison` (default) or `time-comparison`, this is used to generate correct
	 * ARIA properties.
	 */
	mode: PropTypes.oneOf( [ 'item-comparison', 'time-comparison' ] ),
	/**
	 * A title describing this chart.
	 */
	title: PropTypes.string,
	/**
	 * Chart type of either `line` or `bar`.
	 */
	type: PropTypes.oneOf( [ 'bar', 'line' ] ),
	/**
	 * Information about the currently selected interval, and set of allowed intervals for the chart. See `getIntervalsForQuery`.
	 */
	intervalData: PropTypes.object,
	/**
	 * Interval specification (hourly, daily, weekly etc).
	 */
	interval: PropTypes.oneOf( [ 'hour', 'day', 'week', 'month', 'quarter', 'year' ] ),
	/**
	 * Allowed intervals to show in a dropdown.
	 */
	allowedIntervals: PropTypes.array,
	/**
	 * What type of data is to be displayed? Number, Average, String?
	 */
	valueType: PropTypes.string,
};

Chart.defaultProps = {
	data: [],
	dateParser: '%Y-%m-%dT%H:%M:%S',
	tooltipFormat: '%B %d, %Y',
	xFormat: '%d',
	x2Format: '%b %Y',
	yFormat: '$.3s',
	layout: 'standard',
	mode: 'item-comparison',
	type: 'line',
	interval: 'day',
};

export default withViewportMatch( {
	isViewportMobile: '< medium',
	isViewportLarge: '>= large',
	isViewportWide: '>= wide',
} )( Chart );
