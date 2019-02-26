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
import getSelectedChart from 'lib/get-selected-chart';
import ReportChart from 'analytics/components/report-chart';
import ReportSummary from 'analytics/components/report-summary';
import TaxesReportTable from './table';

export default class TaxesReport extends Component {
	getChartMeta() {
		const { query } = this.props;
		const isCompareTaxView = 'compare-taxes' === query.filter;
		const mode = isCompareTaxView ? 'item-comparison' : 'time-comparison';
		const itemsLabel = __( '%d taxes', 'wc-admin' );

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
			chartQuery.segmentby = 'tax_rate_id';
		}
		return (
			<Fragment>
				<ReportFilters query={ query } path={ path } filters={ filters } />
				<ReportSummary
					charts={ charts }
					endpoint="taxes"
					query={ chartQuery }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<ReportChart
					filters={ filters }
					charts={ charts }
					mode={ mode }
					endpoint="taxes"
					query={ chartQuery }
					path={ path }
					itemsLabel={ itemsLabel }
					selectedChart={ getSelectedChart( query.chart, charts ) }
				/>
				<TaxesReportTable isRequesting={ isRequesting } query={ query } />
			</Fragment>
		);
	}
}
TaxesReport.propTypes = {
	query: PropTypes.object.isRequired,
};
