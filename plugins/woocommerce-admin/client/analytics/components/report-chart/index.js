/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { format as formatDate } from '@wordpress/date';
import { get, isEqual } from 'lodash';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import {
	getAllowedIntervalsForQuery,
	getCurrentDates,
	getDateFormatsForInterval,
	getIntervalForQuery,
	getChartTypeForQuery,
	getPreviousDate,
} from 'lib/date';
import { Chart } from '@woocommerce/components';
import { CURRENCY } from '@woocommerce/wc-admin-settings';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import {
	getReportChartData,
	getTooltipValueFormat,
} from 'wc-api/reports/utils';
import ReportError from 'analytics/components/report-error';
import withSelect from 'wc-api/with-select';
import { getChartMode, getSelectedFilter } from './utils';

/**
 * Component that renders the chart in reports.
 */
export class ReportChart extends Component {
	shouldComponentUpdate( nextProps ) {
		if (
			nextProps.isRequesting !== this.props.isRequesting ||
			nextProps.primaryData.isRequesting !==
				this.props.primaryData.isRequesting ||
			nextProps.secondaryData.isRequesting !==
				this.props.secondaryData.isRequesting ||
			! isEqual( nextProps.query, this.props.query )
		) {
			return true;
		}

		return false;
	}

	getItemChartData() {
		const { primaryData, selectedChart } = this.props;
		const chartData = primaryData.data.intervals.map( function( interval ) {
			const intervalData = {};
			interval.subtotals.segments.forEach( function( segment ) {
				if ( segment.segment_label ) {
					const label = intervalData[ segment.segment_label ]
						? segment.segment_label +
						  ' (#' +
						  segment.segment_id +
						  ')'
						: segment.segment_label;
					intervalData[ segment.segment_id ] = {
						label,
						value: segment.subtotals[ selectedChart.key ] || 0,
					};
				}
			} );
			return {
				date: formatDate( 'Y-m-d\\TH:i:s', interval.date_start ),
				...intervalData,
			};
		} );
		return chartData;
	}

	getTimeChartData() {
		const { query, primaryData, secondaryData, selectedChart, defaultDateRange } = this.props;
		const currentInterval = getIntervalForQuery( query );
		const { primary, secondary } = getCurrentDates( query, defaultDateRange );

		const chartData = primaryData.data.intervals.map( function(
			interval,
			index
		) {
			const secondaryDate = getPreviousDate(
				interval.date_start,
				primary.after,
				secondary.after,
				query.compare,
				currentInterval
			);

			const secondaryInterval = secondaryData.data.intervals[ index ];
			return {
				date: formatDate( 'Y-m-d\\TH:i:s', interval.date_start ),
				primary: {
					label: `${ primary.label } (${ primary.range })`,
					labelDate: interval.date_start,
					value: interval.subtotals[ selectedChart.key ] || 0,
				},
				secondary: {
					label: `${ secondary.label } (${ secondary.range })`,
					labelDate: secondaryDate.format( 'YYYY-MM-DD HH:mm:ss' ),
					value:
						( secondaryInterval &&
							secondaryInterval.subtotals[
								selectedChart.key
							] ) ||
						0,
				},
			};
		} );

		return chartData;
	}

	getTimeChartTotals() {
		const { primaryData, secondaryData, selectedChart } = this.props;

		return {
			primary: get(
				primaryData,
				[ 'data', 'totals', selectedChart.key ],
				null
			),
			secondary: get(
				secondaryData,
				[ 'data', 'totals', selectedChart.key ],
				null
			),
		};
	}

	renderChart( mode, isRequesting, chartData, legendTotals ) {
		const {
			emptySearchResults,
			filterParam,
			interactiveLegend,
			itemsLabel,
			legendPosition,
			path,
			query,
			selectedChart,
			showHeaderControls,
			primaryData,
		} = this.props;
		const currentInterval = getIntervalForQuery( query );
		const allowedIntervals = getAllowedIntervalsForQuery( query );
		const formats = getDateFormatsForInterval(
			currentInterval,
			primaryData.data.intervals.length
		);
		const emptyMessage = emptySearchResults
			? __( 'No data for the current search', 'woocommerce-admin' )
			: __( 'No data for the selected date range', 'woocommerce-admin' );
		return (
			<Chart
				allowedIntervals={ allowedIntervals }
				data={ chartData }
				dateParser={ '%Y-%m-%dT%H:%M:%S' }
				emptyMessage={ emptyMessage }
				filterParam={ filterParam }
				interactiveLegend={ interactiveLegend }
				interval={ currentInterval }
				isRequesting={ isRequesting }
				itemsLabel={ itemsLabel }
				legendPosition={ legendPosition }
				legendTotals={ legendTotals }
				mode={ mode }
				path={ path }
				query={ query }
				screenReaderFormat={ formats.screenReaderFormat }
				showHeaderControls={ showHeaderControls }
				title={ selectedChart.label }
				tooltipLabelFormat={ formats.tooltipLabelFormat }
				tooltipTitle={
					( mode === 'time-comparison' && selectedChart.label ) ||
					null
				}
				tooltipValueFormat={ getTooltipValueFormat(
					selectedChart.type
				) }
				chartType={ getChartTypeForQuery( query ) }
				valueType={ selectedChart.type }
				xFormat={ formats.xFormat }
				x2Format={ formats.x2Format }
				currency={ CURRENCY }
			/>
		);
	}

	renderItemComparison() {
		const { isRequesting, primaryData } = this.props;

		if ( primaryData.isError ) {
			return <ReportError isError />;
		}

		const isChartRequesting = isRequesting || primaryData.isRequesting;
		const chartData = this.getItemChartData();

		return this.renderChart(
			'item-comparison',
			isChartRequesting,
			chartData
		);
	}

	renderTimeComparison() {
		const { isRequesting, primaryData, secondaryData } = this.props;

		if ( ! primaryData || primaryData.isError || secondaryData.isError ) {
			return <ReportError isError />;
		}

		const isChartRequesting =
			isRequesting ||
			primaryData.isRequesting ||
			secondaryData.isRequesting;
		const chartData = this.getTimeChartData();
		const legendTotals = this.getTimeChartTotals();

		return this.renderChart(
			'time-comparison',
			isChartRequesting,
			chartData,
			legendTotals
		);
	}

	render() {
		const { mode } = this.props;
		if ( mode === 'item-comparison' ) {
			return this.renderItemComparison();
		}
		return this.renderTimeComparison();
	}
}

ReportChart.propTypes = {
	/**
	 * Filters available for that report.
	 */
	filters: PropTypes.array,
	/**
	 * Whether there is an API call running.
	 */
	isRequesting: PropTypes.bool,
	/**
	 * Label describing the legend items.
	 */
	itemsLabel: PropTypes.string,
	/**
	 * Allows specifying properties different from the `endpoint` that will be used
	 * to limit the items when there is an active search.
	 */
	limitProperties: PropTypes.array,
	/**
	 * `items-comparison` (default) or `time-comparison`, this is used to generate correct
	 * ARIA properties.
	 */
	mode: PropTypes.string,
	/**
	 * Current path
	 */
	path: PropTypes.string.isRequired,
	/**
	 * Primary data to display in the chart.
	 */
	primaryData: PropTypes.object,
	/**
	 * The query string represented in object form.
	 */
	query: PropTypes.object.isRequired,
	/**
	 * Secondary data to display in the chart.
	 */
	secondaryData: PropTypes.object,
	/**
	 * Properties of the selected chart.
	 */
	selectedChart: PropTypes.shape( {
		/**
		 * Key of the selected chart.
		 */
		key: PropTypes.string.isRequired,
		/**
		 * Chart label.
		 */
		label: PropTypes.string.isRequired,
		/**
		 * Order query argument.
		 */
		order: PropTypes.oneOf( [ 'asc', 'desc' ] ),
		/**
		 * Order by query argument.
		 */
		orderby: PropTypes.string,
		/**
		 * Number type for formatting.
		 */
		type: PropTypes.oneOf( [ 'average', 'number', 'currency' ] ).isRequired,
	} ).isRequired,
};

ReportChart.defaultProps = {
	isRequesting: false,
	primaryData: {
		data: {
			intervals: [],
		},
		isError: false,
		isRequesting: false,
	},
	secondaryData: {
		data: {
			intervals: [],
		},
		isError: false,
		isRequesting: false,
	},
};

export default compose(
	withSelect( ( select, props ) => {
		const {
			endpoint,
			filters,
			isRequesting,
			limitProperties,
			query,
			advancedFilters,
		} = props;
		const limitBy = limitProperties || [ endpoint ];
		const selectedFilter = getSelectedFilter( filters, query );
		const filterParam = get( selectedFilter, [ 'settings', 'param' ] );
		const chartMode = props.mode || getChartMode( selectedFilter, query ) || 'time-comparison';
		const { woocommerce_default_date_range: defaultDateRange } = select(
			SETTINGS_STORE_NAME
		).getSetting( 'wc_admin', 'wcAdminSettings' );

		const newProps = {
			mode: chartMode,
			filterParam,
			defaultDateRange,
		};

		if ( isRequesting ) {
			return newProps;
		}

		const hasLimitByParam = limitBy.some(
			( item ) => query[ item ] && query[ item ].length
		);

		if ( query.search && ! hasLimitByParam ) {
			return {
				...newProps,
				emptySearchResults: true,
			};
		}

		const primaryData = getReportChartData( {
			endpoint,
			dataType: 'primary',
			query,
			select,
			limitBy,
			filters,
			advancedFilters,
			defaultDateRange,
		} );

		if ( chartMode === 'item-comparison' ) {
			return {
				...newProps,
				primaryData,
			};
		}

		const secondaryData = getReportChartData( {
			endpoint,
			dataType: 'secondary',
			query,
			select,
			limitBy,
			filters,
			advancedFilters,
			defaultDateRange,
		} );
		return {
			...newProps,
			primaryData,
			secondaryData,
		};
	} )
)( ReportChart );
