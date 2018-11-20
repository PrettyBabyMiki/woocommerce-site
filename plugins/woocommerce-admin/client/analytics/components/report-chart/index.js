/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { format as formatDate } from '@wordpress/date';
import { withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import { find, get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { flattenFilters } from '@woocommerce/navigation';
import {
	getAllowedIntervalsForQuery,
	getCurrentDates,
	getDateFormatsForInterval,
	getIntervalForQuery,
	getChartTypeForQuery,
	getPreviousDate,
} from '@woocommerce/date';

/**
 * Internal dependencies
 */
import { Chart, ChartPlaceholder } from 'components';
import { getReportChartData } from 'store/reports/utils';
import ReportError from 'analytics/components/report-error';

export const DEFAULT_FILTER = 'all';

export class ReportChart extends Component {
	getSelectedFilter( filters, query ) {
		if ( filters.length === 0 ) {
			return null;
		}

		const filterConfig = filters.pop();

		if ( filterConfig.showFilters( query ) ) {
			const allFilters = flattenFilters( filterConfig.filters );
			const value = query[ filterConfig.param ] || DEFAULT_FILTER;
			const selectedFilter = find( allFilters, { value } );
			const selectedFilterParam = get( selectedFilter, [ 'settings', 'param' ] );

			if ( ! selectedFilterParam || Object.keys( query ).includes( selectedFilterParam ) ) {
				return selectedFilter;
			}
		}

		return this.getSelectedFilter( filters, query );
	}

	getChartMode() {
		const { filters, query } = this.props;
		if ( ! filters ) {
			return null;
		}
		const clonedFilters = filters.slice( 0 );
		const selectedFilter = this.getSelectedFilter( clonedFilters, query );

		return get( selectedFilter, [ 'chartMode' ] );
	}

	render() {
		const { query, itemsLabel, path, primaryData, secondaryData, selectedChart } = this.props;

		if ( primaryData.isError || secondaryData.isError ) {
			return <ReportError isError />;
		}

		if ( primaryData.isRequesting || secondaryData.isRequesting ) {
			return (
				<Fragment>
					<span className="screen-reader-text">
						{ __( 'Your requested data is loading', 'wc-admin' ) }
					</span>
					<ChartPlaceholder />
				</Fragment>
			);
		}

		const currentInterval = getIntervalForQuery( query );
		const allowedIntervals = getAllowedIntervalsForQuery( query );
		const formats = getDateFormatsForInterval( currentInterval, primaryData.data.intervals.length );
		const { primary, secondary } = getCurrentDates( query );
		const primaryKey = `${ primary.label } (${ primary.range })`;
		const secondaryKey = `${ secondary.label } (${ secondary.range })`;

		const chartData = primaryData.data.intervals.map( function( interval, index ) {
			const secondaryDate = getPreviousDate(
				formatDate( 'Y-m-d', interval.date_start ),
				primary.after,
				secondary.after,
				query.compare,
				currentInterval
			);

			const secondaryInterval = secondaryData.data.intervals[ index ];
			return {
				date: formatDate( 'Y-m-d\\TH:i:s', interval.date_start ),
				[ primaryKey ]: {
					labelDate: interval.date_start,
					value: interval.subtotals[ selectedChart.key ] || 0,
				},
				[ secondaryKey ]: {
					labelDate: secondaryDate,
					value: ( secondaryInterval && secondaryInterval.subtotals[ selectedChart.key ] ) || 0,
				},
			};
		} );
		const mode = this.getChartMode();
		const layout = mode === 'item-comparison' ? 'comparison' : 'standard';

		return (
			<Chart
				path={ path }
				query={ query }
				data={ chartData }
				title={ selectedChart.label }
				interval={ currentInterval }
				type={ getChartTypeForQuery( query ) }
				allowedIntervals={ allowedIntervals }
				itemsLabel={ itemsLabel }
				layout={ layout }
				mode={ mode }
				pointLabelFormat={ formats.pointLabelFormat }
				tooltipTitle={ selectedChart.label }
				xFormat={ formats.xFormat }
				x2Format={ formats.x2Format }
				dateParser={ '%Y-%m-%dT%H:%M:%S' }
				valueType={ selectedChart.type }
			/>
		);
	}
}

ReportChart.propTypes = {
	filters: PropTypes.array,
	itemsLabel: PropTypes.string,
	path: PropTypes.string.isRequired,
	primaryData: PropTypes.object.isRequired,
	query: PropTypes.object.isRequired,
	secondaryData: PropTypes.object.isRequired,
	selectedChart: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const { query, endpoint } = props;
		const primaryData = getReportChartData( endpoint, 'primary', query, select );
		const secondaryData = getReportChartData( endpoint, 'secondary', query, select );
		return {
			primaryData,
			secondaryData,
		};
	} )
)( ReportChart );
