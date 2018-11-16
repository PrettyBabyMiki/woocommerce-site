/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';

/**
 * WooCommerce dependencies
 */
import { Card } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import Chart from './index';
import dummyOrders from './test/fixtures/dummy-hour';

class WidgetCharts extends Component {
	render() {
		return (
			<Card title={ __( 'Test Categories', 'wc-admin' ) }>
				<Chart
					data={ dummyOrders }
					tooltipFormat={ 'Hour of %H' }
					type={ 'bar' }
					xFormat={ '%H' }
				/>
			</Card>
		);
	}
}

export default WidgetCharts;
