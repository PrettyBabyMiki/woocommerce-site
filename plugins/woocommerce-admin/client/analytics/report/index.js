/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Component, Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import { find } from 'lodash';

/**
 * Internal dependencies
 */
import ExampleReport from './example';
import Header from 'layout/header';
import OrdersReport from './orders';
import ProductsReport from './products';
import RevenueReport from './revenue';
import useFilters from 'components/higher-order/use-filters';

const REPORTS_FILTER = 'woocommerce-reports-list';

const getReports = () => {
	const reports = applyFilters( REPORTS_FILTER, [
		{
			report: 'revenue',
			title: __( 'Revenue', 'wc-admin' ),
			component: RevenueReport,
		},
		{
			report: 'products',
			title: __( 'Products', 'wc-admin' ),
			component: ProductsReport,
		},
		{
			report: 'orders',
			title: __( 'Orders', 'wc-admin' ),
			component: OrdersReport,
		},
		{
			report: 'test',
			title: __( 'Example', 'wc-admin' ),
			component: ExampleReport,
		},
	] );

	return reports;
};

class Report extends Component {
	constructor() {
		super( ...arguments );

		this.state = {
			hasError: false,
		};
	}

	componentDidCatch( error ) {
		this.setState( {
			hasError: true,
		} );
		/* eslint-disable no-console */
		console.warn( error );
		/* eslint-enable no-console */
	}

	render() {
		if ( this.state.hasError ) {
			return null;
		}

		const { params } = this.props;
		const report = find( getReports(), { report: params.report } );
		if ( ! report ) {
			return null;
		}
		const Container = report.component;
		return (
			<Fragment>
				<Header sections={ [ [ '/analytics', __( 'Analytics', 'wc-admin' ) ], report.title ] } />
				<Container { ...this.props } />
			</Fragment>
		);
	}
}

Report.propTypes = {
	params: PropTypes.object.isRequired,
};

export default useFilters( REPORTS_FILTER )( Report );
