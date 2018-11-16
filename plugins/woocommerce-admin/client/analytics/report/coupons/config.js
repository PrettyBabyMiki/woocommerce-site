/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getRequestByIdString } from 'lib/async-requests';
import { NAMESPACE } from 'store/constants';

export const charts = [
	{
		key: 'discounted_orders',
		label: __( 'Discounted Orders', 'wc-admin' ),
		type: 'number',
	},
	{
		key: 'coupons',
		label: __( 'Gross Discounted', 'wc-admin' ),
		type: 'currency',
	},
];

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Coupons', 'wc-admin' ), value: 'all' },
			{
				label: __( 'Comparison', 'wc-admin' ),
				value: 'compare-coupons',
				settings: {
					type: 'coupons',
					param: 'coupons',
					getLabels: getRequestByIdString( NAMESPACE + 'coupons', coupon => ( {
						id: coupon.id,
						label: coupon.code,
					} ) ),
					labels: {
						title: __( 'Compare Coupon Codes', 'wc-admin' ),
						update: __( 'Compare', 'wc-admin' ),
					},
				},
			},
			{ label: __( 'Top Coupons by Discounted Orders', 'wc-admin' ), value: 'top_orders' },
			{ label: __( 'Top Coupons by Gross Discounted', 'wc-admin' ), value: 'top_discount' },
		],
	},
];
