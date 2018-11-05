/** @format */
/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * WooCommerce dependencies
 */
import { stringifyQuery } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { NAMESPACE } from 'store/constants';

export default {
	async getOrders( state, query ) {
		try {
			const orders = await apiFetch( { path: NAMESPACE + 'orders' + stringifyQuery( query ) } );
			dispatch( 'wc-admin' ).setOrders( orders, query );
		} catch ( error ) {
			dispatch( 'wc-admin' ).setOrdersError( query );
		}
	},
};
