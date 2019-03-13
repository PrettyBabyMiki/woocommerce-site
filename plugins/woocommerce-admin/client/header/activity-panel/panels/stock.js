/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component } from '@wordpress/element';

/**
 * Internal dependencies
 */
import ActivityHeader from '../activity-header';

class StockPanel extends Component {
	render() {
		return <ActivityHeader title={ __( 'Stock', 'woocommerce-admin' ) } />;
	}
}

export default StockPanel;
