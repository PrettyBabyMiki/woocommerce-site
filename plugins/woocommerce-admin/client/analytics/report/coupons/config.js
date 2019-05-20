/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { getCouponLabels } from 'lib/async-requests';

const COUPON_REPORT_CHART_FILTER = 'woocommerce_admin_coupon_report_chart_filter';

export const charts = applyFilters( COUPON_REPORT_CHART_FILTER, [
	{
		key: 'orders_count',
		label: __( 'Discounted Orders', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
	{
		key: 'amount',
		label: __( 'Amount', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'amount',
		type: 'currency',
	},
] );

export const filters = [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Coupons', 'woocommerce-admin' ), value: 'all' },
			{
				label: __( 'Single Coupon', 'woocommerce-admin' ),
				value: 'select_coupon',
				chartMode: 'item-comparison',
				subFilters: [
					{
						component: 'Search',
						value: 'single_coupon',
						chartMode: 'item-comparison',
						path: [ 'select_coupon' ],
						settings: {
							type: 'coupons',
							param: 'coupons',
							getLabels: getCouponLabels,
							labels: {
								placeholder: __( 'Type to search for a coupon', 'woocommerce-admin' ),
								button: __( 'Single Coupon', 'woocommerce-admin' ),
							},
						},
					},
				],
			},
			{
				label: __( 'Comparison', 'woocommerce-admin' ),
				value: 'compare-coupons',
				settings: {
					type: 'coupons',
					param: 'coupons',
					getLabels: getCouponLabels,
					labels: {
						title: __( 'Compare Coupon Codes', 'woocommerce-admin' ),
						update: __( 'Compare', 'woocommerce-admin' ),
						helpText: __( 'Check at least two coupon codes below to compare', 'woocommerce-admin' ),
					},
				},
			},
		],
	},
];
