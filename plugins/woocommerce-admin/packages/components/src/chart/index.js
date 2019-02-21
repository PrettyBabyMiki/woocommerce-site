/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import classNames from 'classnames';
import { Component, createRef, Fragment } from '@wordpress/element';
import { formatDefaultLocale as d3FormatDefaultLocale } from 'd3-format';
import { get, isEqual, partial, isEmpty } from 'lodash';
import Gridicon from 'gridicons';
import { IconButton, NavigableMenu, SelectControl } from '@wordpress/components';
import { interpolateViridis as d3InterpolateViridis } from 'd3-scale-chromatic';
import PropTypes from 'prop-types';
import { withViewportMatch } from '@wordpress/viewport';

/**
 * WooCommerce dependencies
 */
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ChartPlaceholder from './placeholder';
import { H, Section } from '../section';
import { D3Chart, D3Legend } from './d3chart';
import { selectionLimit } from './constants';

function getD3CurrencyFormat( symbol, position ) {
	switch ( position ) {
		case 'left_space':
			return [ symbol + ' ', '' ];
		case 'right':
			return [ '', symbol ];
		case 'right_space':
			return [ '', ' ' + symbol ];
		case 'left':
		default:
			return [ symbol, '' ];
	}
}

const currencySymbol = get( wcSettings, [ 'currency', 'symbol' ], '' );
const symbolPosition = get( wcSettings, [ 'currency', 'position' ], 'left' );

d3FormatDefaultLocale( {
	decimal: get( wcSettings, [ 'currency', 'decimal_separator' ], '.' ),
	thousands: get( wcSettings, [ 'currency', 'thousand_separator' ], ',' ),
	grouping: [ 3 ],
	currency: getD3CurrencyFormat( currencySymbol, symbolPosition ),
} );

function getOrderedKeys( props, previousOrderedKeys = [] ) {
	const updatedKeys = [
		...new Set(
			props.data.reduce( ( accum, curr ) => {
				Object.keys( curr ).forEach( key => key !== 'date' && accum.push( key ) );
				return accum;
			}, [] )
		),
	].map( ( key ) => {
		const previousKey = previousOrderedKeys.find( item => key === item.key );
		const defaultVisibleStatus = 'item-comparison' === props.mode ? false : true;
		return {
			key,
			total: props.data.reduce( ( a, c ) => a + c[ key ].value, 0 ),
			visible: previousKey ? previousKey.visible : defaultVisibleStatus,
			focus: true,
		};
	} );

	if ( 'item-comparison' === props.mode ) {
		updatedKeys.sort( ( a, b ) => b.total - a.total );
		if ( isEmpty( previousOrderedKeys ) ) {
			return updatedKeys.filter( key => key.total > 0 ).map( ( key, index ) => {
				return {
					...key,
					visible: index < selectionLimit || key.visible,
				};
			} );
		}
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
		const { data, query, isRequesting, mode } = this.props;
		if ( ! isEqual( [ ...data ].sort(), [ ...prevProps.data ].sort() ) ) {
			/**
			 * Only update the orderedKeys when data is present so that
			 * selection may persist while requesting new data.
			 */
			const orderedKeys = isRequesting && ! data.length
				? this.state.orderedKeys
				: getOrderedKeys( this.props, this.state.orderedKeys );
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				orderedKeys,
				visibleData: this.getVisibleData( data, orderedKeys ),
			} );
			/* eslint-enable react/no-did-update-set-state */
		}

		if ( 'item-comparison' === mode && ! isEqual( query, prevProps.query ) ) {
			const orderedKeys = getOrderedKeys( this.props );
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				orderedKeys,
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

	handleTypeToggle( chartType ) {
		if ( this.props.chartType !== chartType ) {
			const { path, query } = this.props;
			updateQueryString( { chartType }, path, query );
		}
	}

	handleLegendToggle( event ) {
		const { data, interactiveLegend } = this.props;
		if ( ! interactiveLegend ) {
			return;
		}
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

	getLegendPosition() {
		const { legendPosition, mode, isViewportWide } = this.props;
		if ( legendPosition ) {
			return legendPosition;
		}
		if ( isViewportWide && mode === 'time-comparison' ) {
			return 'top';
		}
		if ( isViewportWide && mode === 'item-comparison' ) {
			return 'side';
		}
		return 'bottom';
	}

	render() {
		const { interactiveLegend, orderedKeys, visibleData, width } = this.state;
		const {
			baseValue,
			chartType,
			dateParser,
			emptyMessage,
			interval,
			isRequesting,
			isViewportLarge,
			itemsLabel,
			mode,
			showHeaderControls,
			title,
			tooltipLabelFormat,
			tooltipValueFormat,
			tooltipTitle,
			valueType,
			xFormat,
			x2Format,
		} = this.props;
		let { yFormat } = this.props;

		const legendPosition = this.getLegendPosition();
		const legendDirection = legendPosition === 'top' ? 'row' : 'column';
		const chartDirection = legendPosition === 'side' ? 'row' : 'column';

		const chartHeight = this.getChartHeight();
		const legend = isRequesting ? null : (
			<D3Legend
				colorScheme={ d3InterpolateViridis }
				data={ orderedKeys }
				handleLegendHover={ this.handleLegendHover }
				handleLegendToggle={ this.handleLegendToggle }
				interactive={ interactiveLegend }
				legendDirection={ legendDirection }
				legendValueFormat={ tooltipValueFormat }
				totalLabel={ sprintf( itemsLabel, orderedKeys.length ) }
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
				yFormat = ',.0f';
				break;
			case 'currency':
				yFormat = '$.3~s';
				break;
			case 'number':
				yFormat = ',.0f';
				break;
		}
		return (
			<div className="woocommerce-chart">
				{ showHeaderControls && (
					<div className="woocommerce-chart__header">
						<H className="woocommerce-chart__title">{ title }</H>
						{ legendPosition === 'top' && legend }
						{ this.renderIntervalSelector() }
						<NavigableMenu
							className="woocommerce-chart__types"
							orientation="horizontal"
							role="menubar"
						>
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected': chartType === 'line',
								} ) }
								icon={ <Gridicon icon="line-graph" /> }
								title={ __( 'Line chart', 'wc-admin' ) }
								aria-checked={ chartType === 'line' }
								role="menuitemradio"
								tabIndex={ chartType === 'line' ? 0 : -1 }
								onClick={ partial( this.handleTypeToggle, 'line' ) }
							/>
							<IconButton
								className={ classNames( 'woocommerce-chart__type-button', {
									'woocommerce-chart__type-button-selected': chartType === 'bar',
								} ) }
								icon={ <Gridicon icon="stats-alt" /> }
								title={ __( 'Bar chart', 'wc-admin' ) }
								aria-checked={ chartType === 'bar' }
								role="menuitemradio"
								tabIndex={ chartType === 'bar' ? 0 : -1 }
								onClick={ partial( this.handleTypeToggle, 'bar' ) }
							/>
						</NavigableMenu>
					</div>
				) }
				<Section component={ false }>
					<div
						className={ classNames(
							'woocommerce-chart__body',
							`woocommerce-chart__body-${ chartDirection }`
						) }
						ref={ this.chartBodyRef }
					>
						{ legendPosition === 'side' && legend }
						{ isRequesting && (
							<Fragment>
								<span className="screen-reader-text">
									{ __( 'Your requested data is loading', 'wc-admin' ) }
								</span>
								<ChartPlaceholder height={ chartHeight } />
							</Fragment>
						) }
						{ ! isRequesting &&
							width > 0 && (
								<D3Chart
									baseValue={ baseValue }
									chartType={ chartType }
									colorScheme={ d3InterpolateViridis }
									data={ visibleData }
									dateParser={ dateParser }
									height={ chartHeight }
									emptyMessage={ emptyMessage }
									interval={ interval }
									margin={ margin }
									mode={ mode }
									orderedKeys={ orderedKeys }
									tooltipLabelFormat={ tooltipLabelFormat }
									tooltipValueFormat={ tooltipValueFormat }
									tooltipPosition={ isViewportLarge ? 'over' : 'below' }
									tooltipTitle={ tooltipTitle }
									width={ chartDirection === 'row' ? width - 320 : width }
									xFormat={ xFormat }
									x2Format={ x2Format }
									yFormat={ yFormat }
									valueType={ valueType }
								/>
							) }
					</div>
					{ ( legendPosition === 'bottom' ) && (
						<div className="woocommerce-chart__footer">{ legend }</div>
					) }
				</Section>
			</div>
		);
	}
}

Chart.propTypes = {
	/**
	 * Allowed intervals to show in a dropdown.
	 */
	allowedIntervals: PropTypes.array,
	/**
	 * Base chart value. If no data value is different than the baseValue, the
	 * `emptyMessage` will be displayed if provided.
	 */
	baseValue: PropTypes.number,
	/**
	 * Chart type of either `line` or `bar`.
	 */
	chartType: PropTypes.oneOf( [ 'bar', 'line' ] ),
	/**
	 * An array of data.
	 */
	data: PropTypes.array.isRequired,
	/**
	 * Format to parse dates into d3 time format
	 */
	dateParser: PropTypes.string.isRequired,
	/**
	 * The message to be displayed if there is no data to render. If no message is provided,
	 * nothing will be displayed.
	 */
	emptyMessage: PropTypes.string,
	/**
	 * Label describing the legend items.
	 */
	itemsLabel: PropTypes.string,
	/**
	 * `item-comparison` (default) or `time-comparison`, this is used to generate correct
	 * ARIA properties.
	 */
	mode: PropTypes.oneOf( [ 'item-comparison', 'time-comparison' ] ),
	/**
	 * Current path
	 */
	path: PropTypes.string,
	/**
	 * The query string represented in object form
	 */
	query: PropTypes.object,
	/**
	 * Whether the legend items can be activated/deactivated.
	 */
	interactiveLegend: PropTypes.bool,
	/**
	 * Interval specification (hourly, daily, weekly etc).
	 */
	interval: PropTypes.oneOf( [ 'hour', 'day', 'week', 'month', 'quarter', 'year' ] ),
	/**
	 * Information about the currently selected interval, and set of allowed intervals for the chart. See `getIntervalsForQuery`.
	 */
	intervalData: PropTypes.object,
	/**
	 * Render a chart placeholder to signify an in-flight data request.
	 */
	isRequesting: PropTypes.bool,
	/**
	 * Position the legend must be displayed in. If it's not defined, it's calculated
	 * depending on the viewport width and the mode.
	 */
	legendPosition: PropTypes.oneOf( [ 'bottom', 'side', 'top' ] ),
	/**
	 * Wether header UI controls must be displayed.
	 */
	showHeaderControls: PropTypes.bool,
	/**
	 * A title describing this chart.
	 */
	title: PropTypes.string,
	/**
	 * A datetime formatting string or overriding function to format the tooltip label.
	 */
	tooltipLabelFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * A number formatting string or function to format the value displayed in the tooltips.
	 */
	tooltipValueFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * A string to use as a title for the tooltip. Takes preference over `tooltipLabelFormat`.
	 */
	tooltipTitle: PropTypes.string,
	/**
	 * What type of data is to be displayed? Number, Average, String?
	 */
	valueType: PropTypes.string,
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
};

Chart.defaultProps = {
	baseValue: 0,
	chartType: 'line',
	data: [],
	dateParser: '%Y-%m-%dT%H:%M:%S',
	interactiveLegend: true,
	interval: 'day',
	isRequesting: false,
	mode: 'time-comparison',
	showHeaderControls: true,
	tooltipLabelFormat: '%B %d, %Y',
	tooltipValueFormat: ',',
	xFormat: '%d',
	x2Format: '%b %Y',
	yFormat: '$.3s',
};

export default withViewportMatch( {
	isViewportMobile: '< medium',
	isViewportLarge: '>= large',
	isViewportWide: '>= wide',
} )( Chart );
