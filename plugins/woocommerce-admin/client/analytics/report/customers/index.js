/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * WooCommerce dependencies
 */
import { ReportFilters } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { dateRangeFilter, filters } from './config';
import CustomersReportTable from './table';

export default class CustomersReport extends Component {
	render() {
		const { query, path } = this.props;

		return (
			<Fragment>
				<ReportFilters
					query={ query }
					path={ path }
					dateRangeFilter={ dateRangeFilter }
					filters={ filters }
				/>
				<CustomersReportTable query={ query } />
			</Fragment>
		);
	}
}

CustomersReport.propTypes = {
	query: PropTypes.object.isRequired,
};
