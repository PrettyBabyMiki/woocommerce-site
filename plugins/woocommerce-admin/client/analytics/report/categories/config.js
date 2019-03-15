/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getCategoryLabels } from 'lib/async-requests';

export const charts = [
	{
		key: 'items_sold',
		label: __( 'Items Sold', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'items_sold',
		type: 'number',
	},
	{
		key: 'net_revenue',
		label: __( 'Net Revenue', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'net_revenue',
		type: 'currency',
	},
	{
		key: 'orders_count',
		label: __( 'Orders Count', 'woocommerce-admin' ),
		order: 'desc',
		orderby: 'orders_count',
		type: 'number',
	},
];

export const filters = [
	{
		label: __( 'Show', 'woocommerce-admin' ),
		staticParams: [],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Categories', 'woocommerce-admin' ), value: 'all' },
			{
				label: __( 'Single Category', 'woocommerce-admin' ),
				value: 'select_category',
				chartMode: 'item-comparison',
				subFilters: [
					{
						component: 'Search',
						value: 'single_category',
						chartMode: 'item-comparison',
						path: [ 'select_category' ],
						settings: {
							type: 'categories',
							param: 'categories',
							getLabels: getCategoryLabels,
							labels: {
								placeholder: __( 'Type to search for a category', 'woocommerce-admin' ),
								button: __( 'Single Category', 'woocommerce-admin' ),
							},
						},
					},
				],
			},
			{
				label: __( 'Comparison', 'woocommerce-admin' ),
				value: 'compare-categories',
				chartMode: 'item-comparison',
				settings: {
					type: 'categories',
					param: 'categories',
					getLabels: getCategoryLabels,
					labels: {
						helpText: __( 'Check at least two categories below to compare', 'woocommerce-admin' ),
						placeholder: __( 'Search for categories to compare', 'woocommerce-admin' ),
						title: __( 'Compare Categories', 'woocommerce-admin' ),
						update: __( 'Compare', 'woocommerce-admin' ),
					},
				},
			},
		],
	},
];
