/**
 * External dependencies
 */

import { __, sprintf } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import {
	getHistory,
	getNewPath,
	updateQueryString,
} from '@woocommerce/navigation';
import { Fragment } from '@wordpress/element';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import Appearance from './tasks/appearance';
import { getCategorizedOnboardingProducts } from '../dashboard/utils';
import Products from './tasks/products';
import Shipping from './tasks/shipping';
import Tax from './tasks/tax';
import Payments from './tasks/payments';
import { installActivateAndConnectWcpay } from './tasks/payments/methods';

export function recordTaskViewEvent(
	taskName,
	isJetpackConnected,
	activePlugins,
	installedPlugins
) {
	recordEvent( 'task_view', {
		task_name: taskName,
		wcs_installed: installedPlugins.includes( 'woocommerce-services' ),
		wcs_active: activePlugins.includes( 'woocommerce-services' ),
		jetpack_installed: installedPlugins.includes( 'jetpack' ),
		jetpack_active: activePlugins.includes( 'jetpack' ),
		jetpack_connected: isJetpackConnected,
	} );
}

export function getAllTasks( {
	activePlugins,
	countryCode,
	createNotice,
	installAndActivatePlugins,
	installedPlugins,
	isJetpackConnected,
	onboardingStatus,
	profileItems,
	query,
	toggleCartModal,
} ) {
	const {
		hasPaymentGateway,
		hasPhysicalProducts,
		hasProducts,
		isAppearanceComplete,
		isTaxComplete,
		shippingZonesCount,
		wcPayIsConnected,
	} = {
		hasPaymentGateway: false,
		hasPhysicalProducts: false,
		hasProducts: false,
		isAppearanceComplete: false,
		isTaxComplete: false,
		shippingZonesCount: 0,
		wcPayIsConnected: false,
		...onboardingStatus,
	};

	const groupedProducts = getCategorizedOnboardingProducts(
		profileItems,
		installedPlugins
	);
	const { products, remainingProducts, uniqueItemsList } = groupedProducts;

	const woocommercePaymentsInstalled =
		installedPlugins.indexOf( 'woocommerce-payments' ) !== -1;
	const {
		completed: profilerCompleted,
		product_types: productTypes,
	} = profileItems;

	let purchaseAndInstallText = __( 'Add paid extensions to my store' );

	if ( uniqueItemsList.length === 1 ) {
		const { name: itemName } = uniqueItemsList[ 0 ];
		const purchaseAndInstallFormat = __(
			'Add %s to my store',
			'woocommerce-admin'
		);
		purchaseAndInstallText = sprintf( purchaseAndInstallFormat, itemName );
	}

	const tasks = [
		{
			key: 'store_details',
			title: __( 'Store details', 'woocommerce-admin' ),
			container: null,
			onClick: () => {
				recordEvent( 'tasklist_click', {
					task_name: 'store_details',
				} );
				getHistory().push( getNewPath( {}, '/setup-wizard', {} ) );
			},
			completed: profilerCompleted,
			visible: true,
			time: __( '4 minutes', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'purchase',
			title: purchaseAndInstallText,
			container: null,
			onClick: () => {
				recordEvent( 'tasklist_click', {
					task_name: 'purchase',
				} );
				return remainingProducts.length ? toggleCartModal() : null;
			},
			visible: products.length,
			completed: products.length && ! remainingProducts.length,
			time: __( '2 minutes', 'woocommerce-admin' ),
			isDismissable: true,
			type: 'setup',
		},
		{
			key: 'products',
			title: __( 'Add my products', 'woocommerce-admin' ),
			container: <Products />,
			onClick: () => {
				recordEvent( 'tasklist_click', {
					task_name: 'products',
				} );
				updateQueryString( { task: 'products' } );
			},
			completed: hasProducts,
			visible: true,
			time: __( '1 minute per product', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'woocommerce-payments',
			title: __( 'Set up WooCommerce Payments', 'woocommerce-admin' ),
			container: <Fragment />,
			completed: wcPayIsConnected,
			onClick: async ( e ) => {
				if ( e.target.nodeName === 'A' ) {
					// This is a nested link, so don't activate the task.
					return false;
				}

				await new Promise( ( resolve, reject ) => {
					// This task doesn't have a view, so the recordEvent call
					// in TaskDashboard.recordTaskView() is never called. So
					// record it here.
					recordTaskViewEvent(
						'wcpay',
						isJetpackConnected,
						activePlugins,
						installedPlugins
					);
					recordEvent( 'tasklist_click', {
						task_name: 'woocommerce-payments',
					} );
					return installActivateAndConnectWcpay(
						resolve,
						reject,
						createNotice,
						installAndActivatePlugins
					);
				} );
			},
			visible:
				window.wcAdminFeatures.wcpay &&
				woocommercePaymentsInstalled &&
				countryCode === 'US',
			additionalInfo: __(
				'By setting up, you are agreeing to the <a href="https://wordpress.com/tos/" target="_blank">Terms of Service</a>',
				'woocommerce-admin'
			),
			time: __( '2 minutes', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'payments',
			title: __( 'Set up payments', 'woocommerce-admin' ),
			container: <Payments />,
			completed: hasPaymentGateway,
			onClick: () => {
				recordEvent( 'tasklist_click', {
					task_name: 'payments',
				} );
				updateQueryString( { task: 'payments' } );
			},
			visible: ! woocommercePaymentsInstalled || countryCode !== 'US',
			time: __( '2 minutes', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'tax',
			title: __( 'Set up tax', 'woocommerce-admin' ),
			container: <Tax />,
			onClick: () => {
				recordEvent( 'tasklist_click', {
					task_name: 'tax',
				} );
				updateQueryString( { task: 'tax' } );
			},
			completed: isTaxComplete,
			visible: true,
			time: __( '1 minute', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'shipping',
			title: __( 'Set up shipping', 'woocommerce-admin' ),
			container: <Shipping />,
			onClick: () => {
				recordEvent( 'tasklist_click', {
					task_name: 'shipping',
				} );
				updateQueryString( { task: 'shipping' } );
			},
			completed: shippingZonesCount > 0,
			visible:
				( productTypes && productTypes.includes( 'physical' ) ) ||
				hasPhysicalProducts,
			time: __( '1 minute', 'woocommerce-admin' ),
			type: 'setup',
		},
		{
			key: 'appearance',
			title: __( 'Personalize my store', 'woocommerce-admin' ),
			container: <Appearance />,
			onClick: () => {
				recordEvent( 'tasklist_click', {
					task_name: 'appearance',
				} );
				updateQueryString( { task: 'appearance' } );
			},
			completed: isAppearanceComplete,
			visible: true,
			time: __( '2 minutes', 'woocommerce-admin' ),
			type: 'setup',
		},
	];

	return applyFilters(
		'woocommerce_admin_onboarding_task_list',
		tasks,
		query
	);
}
