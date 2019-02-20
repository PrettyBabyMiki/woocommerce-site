/** @format */

/**
 * External dependencies
 */
import { Component, createRef } from '@wordpress/element';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { timeFormat as d3TimeFormat } from 'd3-time-format';

/**
 * Internal dependencies
 */
import D3Base from './d3base';
import {
	getOrderedKeys,
	getUniqueDates,
	getFormatter,
	isDataEmpty,
} from './utils/index';
import {
	getXScale,
	getXGroupScale,
	getXLineScale,
	getYMax,
	getYScale,
} from './utils/scales';
import { drawAxis } from './utils/axis';
import { drawBars } from './utils/bar-chart';
import { drawLines } from './utils/line-chart';
import { getColor } from './utils/color';
import ChartTooltip from './utils/tooltip';
import { selectionLimit } from '../constants';

/**
 * A simple D3 line and bar chart component for timeseries data in React.
 */
class D3Chart extends Component {
	constructor( props ) {
		super( props );
		this.drawChart = this.drawChart.bind( this );
		this.getParams = this.getParams.bind( this );
		this.tooltipRef = createRef();
	}

	getFormatParams() {
		const { xFormat, x2Format, yFormat } = this.props;

		return {
			xFormat: getFormatter( xFormat, d3TimeFormat ),
			x2Format: getFormatter( x2Format, d3TimeFormat ),
			yFormat: getFormatter( yFormat ),
		};
	}

	getScaleParams( uniqueDates ) {
		const { data, height, margin, orderedKeys, chartType } = this.props;

		const adjHeight = height - margin.top - margin.bottom;
		const adjWidth = this.getWidth() - margin.left - margin.right;
		const yMax = getYMax( data );
		const yScale = getYScale( adjHeight, yMax );

		if ( chartType === 'line' ) {
			return {
				xScale: getXLineScale( uniqueDates, adjWidth ),
				yMax,
				yScale,
			};
		}

		const compact = this.shouldBeCompact();
		const xScale = getXScale( uniqueDates, adjWidth, compact );

		return {
			xGroupScale: getXGroupScale( orderedKeys, xScale, compact ),
			xScale,
			yMax,
			yScale,
		};
	}

	getParams( uniqueDates ) {
		const { chartType, colorScheme, data, interval, mode, orderedKeys } = this.props;
		const newOrderedKeys = orderedKeys || getOrderedKeys( data );
		const visibleKeys = newOrderedKeys.filter( key => key.visible );
		const colorKeys = newOrderedKeys.length > selectionLimit ? visibleKeys : newOrderedKeys;

		return {
			getColor: getColor( colorKeys, colorScheme ),
			interval,
			mode,
			chartType,
			uniqueDates,
			visibleKeys,
		};
	}

	createTooltip( chart, getColorFunction, visibleKeys ) {
		const { tooltipLabelFormat, tooltipPosition, tooltipTitle, tooltipValueFormat } = this.props;

		const tooltip = new ChartTooltip();
		tooltip.ref = this.tooltipRef.current;
		tooltip.chart = chart;
		tooltip.position = tooltipPosition;
		tooltip.title = tooltipTitle;
		tooltip.labelFormat = getFormatter( tooltipLabelFormat, d3TimeFormat );
		tooltip.valueFormat = getFormatter( tooltipValueFormat );
		tooltip.visibleKeys = visibleKeys;
		tooltip.getColor = getColorFunction;
		this.tooltip = tooltip;
	}

	drawChart( node ) {
		const { data, dateParser, margin, chartType } = this.props;
		const uniqueDates = getUniqueDates( data, dateParser );
		const formats = this.getFormatParams();
		const params = this.getParams( uniqueDates );
		const scales = this.getScaleParams( uniqueDates );

		const g = node
			.attr( 'id', 'chart' )
			.append( 'g' )
			.attr( 'transform', `translate(${ margin.left }, ${ margin.top })` );

		this.createTooltip( g.node(), params.getColor, params.visibleKeys );

		drawAxis( g, params, scales, formats, margin );
		chartType === 'line' && drawLines( g, data, params, scales, formats, this.tooltip );
		chartType === 'bar' && drawBars( g, data, params, scales, formats, this.tooltip );
	}

	shouldBeCompact() {
		const {	data, margin, chartType, width } = this.props;
		if ( chartType !== 'bar' ) {
			return false;
		}
		const widthWithoutMargins = width - margin.left - margin.right;
		const columnsPerDate = data && data.length ? Object.keys( data[ 0 ] ).length - 1 : 0;
		const minimumWideWidth = data.length * ( columnsPerDate + 1 );

		return widthWithoutMargins < minimumWideWidth;
	}

	getWidth() {
		const {	data, margin, chartType, width } = this.props;
		if ( chartType !== 'bar' ) {
			return width;
		}
		const columnsPerDate = data && data.length ? Object.keys( data[ 0 ] ).length - 1 : 0;
		const minimumWidth = this.shouldBeCompact() ? data.length * columnsPerDate : data.length * ( columnsPerDate + 1 );

		return Math.max( width, minimumWidth + margin.left + margin.right );
	}

	getEmptyMessage() {
		const { baseValue, data, emptyMessage } = this.props;

		if ( emptyMessage && isDataEmpty( data, baseValue ) ) {
			return (
				<div className="d3-chart__empty-message">{ emptyMessage }</div>
			);
		}
	}

	render() {
		const { className, data, height, orderedKeys, chartType } = this.props;
		const computedWidth = this.getWidth();
		return (
			<div
				className={ classNames( 'd3-chart__container', className ) }
				style={ { height } }
			>
				{ this.getEmptyMessage() }
				<div className="d3-chart__tooltip" ref={ this.tooltipRef } />
				<D3Base
					className={ classNames( className ) }
					data={ data }
					drawChart={ this.drawChart }
					height={ height }
					orderedKeys={ orderedKeys }
					tooltip={ this.tooltip }
					chartType={ chartType }
					width={ computedWidth }
				/>
			</div>
		);
	}
}

D3Chart.propTypes = {
	/**
	 * Base chart value. If no data value is different than the baseValue, the
	 * `emptyMessage` will be displayed if provided.
	 */
	baseValue: PropTypes.number,
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
	/**
	 * A chromatic color function to be passed down to d3.
	 */
	colorScheme: PropTypes.func,
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
	 * Height of the `svg`.
	 */
	height: PropTypes.number,
	/**
	 * Interval specification (hourly, daily, weekly etc.)
	 */
	interval: PropTypes.oneOf( [ 'hour', 'day', 'week', 'month', 'quarter', 'year' ] ),
	/**
	 * Margins for axis and chart padding.
	 */
	margin: PropTypes.shape( {
		bottom: PropTypes.number,
		left: PropTypes.number,
		right: PropTypes.number,
		top: PropTypes.number,
	} ),
	/**
	 * `items-comparison` (default) or `time-comparison`, this is used to generate correct
	 * ARIA properties.
	 */
	mode: PropTypes.oneOf( [ 'item-comparison', 'time-comparison' ] ),
	/**
	 * The list of labels for this chart.
	 */
	orderedKeys: PropTypes.array,
	/**
	 * A datetime formatting string or overriding function to format the tooltip label.
	 */
	tooltipLabelFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * A number formatting string or function to format the value displayed in the tooltips.
	 */
	tooltipValueFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * The position where to render the tooltip can be `over` the chart or `below` the chart.
	 */
	tooltipPosition: PropTypes.oneOf( [ 'below', 'over' ] ),
	/**
	 * A string to use as a title for the tooltip. Takes preference over `tooltipFormat`.
	 */
	tooltipTitle: PropTypes.string,
	/**
	 * Chart type of either `line` or `bar`.
	 */
	chartType: PropTypes.oneOf( [ 'bar', 'line' ] ),
	/**
	 * Width of the `svg`.
	 */
	width: PropTypes.number,
	/**
	 * A datetime formatting string or function, passed to d3TimeFormat.
	 */
	xFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * A datetime formatting string or function, passed to d3TimeFormat.
	 */
	x2Format: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	/**
	 * A number formatting string or function, passed to d3Format.
	 */
	yFormat: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
};

D3Chart.defaultProps = {
	baseValue: 0,
	data: [],
	dateParser: '%Y-%m-%dT%H:%M:%S',
	height: 200,
	margin: {
		bottom: 30,
		left: 40,
		right: 0,
		top: 20,
	},
	mode: 'time-comparison',
	tooltipPosition: 'over',
	tooltipLabelFormat: '%B %d, %Y',
	tooltipValueFormat: ',',
	chartType: 'line',
	width: 600,
	xFormat: '%Y-%m-%d',
	x2Format: '',
	yFormat: '.3s',
};

export default D3Chart;
