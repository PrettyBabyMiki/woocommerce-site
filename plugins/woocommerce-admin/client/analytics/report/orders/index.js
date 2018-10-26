/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import PropTypes from 'prop-types';
import { withSelect } from '@wordpress/data';
import { get } from 'lodash';

/**
 * Internal dependencies
 */
import { EmptyContent, ReportFilters } from '@woocommerce/components';
import { filters, advancedFilterConfig } from './config';
import { getAdminLink } from 'lib/nav-utils';
import { appendTimestamp, getCurrentDates } from 'lib/date';
import { QUERY_DEFAULTS } from 'store/constants';
import { getReportChartData } from 'store/reports/utils';
import OrdersReportChart from './chart';
import OrdersReportTable from './table';

class OrdersReport extends Component {
	constructor( props ) {
		super( props );
	}

	render() {
		const {
			isTableDataError,
			isTableDataRequesting,
			orders,
			path,
			query,
			primaryData,
			secondaryData,
		} = this.props;

		if ( primaryData.isError || isTableDataError ) {
			let title, actionLabel, actionURL, actionCallback;
			if ( primaryData.isError || secondaryData.isError || isTableDataError ) {
				title = __( 'There was an error getting your stats. Please try again.', 'wc-admin' );
				actionLabel = __( 'Reload', 'wc-admin' );
				actionCallback = () => {
					// TODO Add tracking for how often an error is displayed, and the reload action is clicked.
					window.location.reload();
				};
			} else {
				title = __( 'No results could be found for this date range.', 'wc-admin' );
				actionLabel = __( 'View Orders', 'wc-admin' );
				actionURL = getAdminLink( 'edit.php?post_type=shop_order' );
			}

			return (
				<Fragment>
					<ReportFilters query={ query } path={ path } />
					<EmptyContent
						title={ title }
						actionLabel={ actionLabel }
						actionURL={ actionURL }
						actionCallback={ actionCallback }
					/>
				</Fragment>
			);
		}

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					filters={ filters }
					advancedConfig={ advancedFilterConfig }
				/>
				<OrdersReportChart query={ query } />
				<OrdersReportTable
					isRequesting={ isTableDataRequesting }
					orders={ orders }
					query={ query }
					totalRows={ get(
						primaryData,
						[ 'data', 'totals', 'orders_count' ],
						Object.keys( orders ).length
					) }
				/>
			</Fragment>
		);
	}
}

OrdersReport.propTypes = {
	params: PropTypes.object.isRequired,
	path: PropTypes.string.isRequired,
	query: PropTypes.object.isRequired,
};

export default compose(
	withSelect( ( select, props ) => {
		const { query } = props;
		const datesFromQuery = getCurrentDates( query );
		const primaryData = getReportChartData( 'orders', 'primary', query, select );

		const { getOrders, isGetOrdersError, isGetOrdersRequesting } = select( 'wc-admin' );
		const tableQuery = {
			orderby: query.orderby || 'date',
			order: query.order || 'asc',
			page: query.page || 1,
			per_page: query.per_page || QUERY_DEFAULTS.pageSize,
			after: appendTimestamp( datesFromQuery.primary.after, 'start' ),
			before: appendTimestamp( datesFromQuery.primary.before, 'end' ),
			status: [ 'processing', 'on-hold', 'completed' ],
		};
		const orders = getOrders( tableQuery );
		const isTableDataError = isGetOrdersError( tableQuery );
		const isTableDataRequesting = isGetOrdersRequesting( tableQuery );

		return {
			isTableDataError,
			isTableDataRequesting,
			orders,
			primaryData,
		};
	} )
)( OrdersReport );
