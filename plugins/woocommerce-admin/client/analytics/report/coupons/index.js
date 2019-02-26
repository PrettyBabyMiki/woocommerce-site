/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import { ReportFilters } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { charts, filters } from './config';
import CouponsReportTable from './table';
import getSelectedChart from 'lib/get-selected-chart';
import ReportChart from 'analytics/components/report-chart';
import ReportSummary from 'analytics/components/report-summary';

export default class CouponsReport extends Component {
	getChartMeta() {
		const { query } = this.props;
		const isCompareView = [ 'top_orders', 'top_discount', 'compare-coupons' ].includes(
			query.filter
		);

		const mode = isCompareView ? 'item-comparison' : 'time-comparison';
		const itemsLabel = __( '%d coupons', 'wc-admin' );

		return {
			itemsLabel,
			mode,
		};
	}

	render() {
		const { isRequesting, query, path } = this.props;
		const { mode, itemsLabel } = this.getChartMeta();

		const chartQuery = {
			...query,
		};

		if ( 'item-comparison' === mode ) {
			chartQuery.segmentby = 'coupon';
		}

		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } />
				<ReportSummary
					charts={ charts }
					endpoint="coupons"
					query={ chartQuery }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<ReportChart
					filters={ filters }
					charts={ charts }
					mode={ mode }
					endpoint="coupons"
					path={ path }
					query={ chartQuery }
					itemsLabel={ itemsLabel }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<CouponsReportTable isRequesting={ isRequesting } query={ query } />
			</Fragment>
		);
	}
}

CouponsReport.propTypes = {
	query: PropTypes.object.isRequired,
};
