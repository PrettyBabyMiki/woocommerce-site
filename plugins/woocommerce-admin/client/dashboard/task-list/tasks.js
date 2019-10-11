/**
 * External dependencies
 *
 * @format
 */

import { __ } from '@wordpress/i18n';
import { get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { getSetting } from '@woocommerce/wc-admin-settings';

/**
 * Internal dependencies
 */
import Appearance from './tasks/appearance';
import Connect from './tasks/connect';
import Products from './tasks/products';
import Shipping from './tasks/shipping';
import Tax from './tasks/tax';
import Payments from './tasks/payments';

export function getTasks( { profileItems, options, query } ) {
	const {
		customLogo,
		hasHomepage,
		hasPhysicalProducts,
		hasProducts,
		isTaxComplete,
		shippingZonesCount,
	} = getSetting( 'onboarding', {
		customLogo: '',
		hasHomePage: false,
		hasPhysicalProducts: false,
		hasProducts: false,
		isTaxComplete: false,
		shippingZonesCount: 0,
	} );

	const paymentsCompleted = get(
		options,
		[ 'woocommerce_onboarding_payments', 'completed' ],
		false
	);

	return [
		{
			key: 'connect',
			title: __( 'Connect your store to WooCommerce.com', 'woocommerce-admin' ),
			content: __(
				'Install and manage your extensions directly from your Dashboard',
				'wooocommerce-admin'
			),
			icon: 'extension',
			container: <Connect query={ query } />,
			visible: profileItems.items_purchased && ! profileItems.wccom_connected,
			completed: profileItems.wccom_connected,
		},
		{
			key: 'products',
			title: __( 'Add your first product', 'woocommerce-admin' ),
			content: __(
				'Add products manually, import from a sheet or migrate from another platform',
				'wooocommerce-admin'
			),
			icon: 'add_box',
			container: <Products />,
			completed: hasProducts,
			visible: true,
		},
		{
			key: 'appearance',
			title: __( 'Personalize your store', 'woocommerce-admin' ),
			content: __( 'Create a custom homepage and upload your logo', 'wooocommerce-admin' ),
			icon: 'palette',
			container: <Appearance />,
			completed: customLogo && hasHomepage,
			visible: true,
		},
		{
			key: 'shipping',
			title: __( 'Set up shipping', 'woocommerce-admin' ),
			content: __( 'Configure some basic shipping rates to get started', 'wooocommerce-admin' ),
			icon: 'local_shipping',
			container: <Shipping />,
			completed: shippingZonesCount > 0,
			visible: profileItems.product_types.includes( 'physical' ) || hasPhysicalProducts,
		},
		{
			key: 'tax',
			title: __( 'Set up tax', 'woocommerce-admin' ),
			content: __(
				'Choose how to configure tax rates - manually or automatically',
				'wooocommerce-admin'
			),
			icon: 'account_balance',
			container: <Tax />,
			completed: isTaxComplete,
			visible: true,
		},
		{
			key: 'payments',
			title: __( 'Set up payments', 'woocommerce-admin' ),
			content: __(
				'Select which payment providers you’d like to use and configure them',
				'wooocommerce-admin'
			),
			icon: 'payment',
			container: <Payments />,
			completed: paymentsCompleted,
			visible: true,
		},
	];
}
