/** @format */
/**
 * External dependencies
 */
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { getCustomerLabels, getProductLabels } from 'lib/async-requests';

export const charts = [
	{
		key: 'download_count',
		label: __( 'Downloads', 'wc-admin' ),
		type: 'number',
	},
];

export const filters = [
	{
		label: __( 'Show', 'wc-admin' ),
		staticParams: [],
		param: 'filter',
		showFilters: () => true,
		filters: [
			{ label: __( 'All Downloads', 'wc-admin' ), value: 'all' },
			{ label: __( 'Advanced Filters', 'wc-admin' ), value: 'advanced' },
		],
	},
];

/*eslint-disable max-len*/
export const advancedFilters = {
	title: _x(
		'Downloads Match {{select /}} Filters',
		'A sentence describing filters for Downloads. See screen shot for context: https://cloudup.com/ccxhyH2mEDg',
		'wc-admin'
	),
	filters: {
		product: {
			labels: {
				add: __( 'Product', 'wc-admin' ),
				placeholder: __( 'Search', 'wc-admin' ),
				remove: __( 'Remove product filter', 'wc-admin' ),
				rule: __( 'Select a product filter match', 'wc-admin' ),
				/* translators: A sentence describing a Product filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
				title: __( '{{title}}Product{{/title}} {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select product', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to products including a given product(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
					label: _x( 'Includes', 'products', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to products excluding a products(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
					label: _x( 'Excludes', 'products', 'wc-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'products',
				getLabels: getProductLabels,
			},
		},
		customer: {
			labels: {
				add: __( 'Username', 'wc-admin' ),
				placeholder: __( 'Search customer username', 'wc-admin' ),
				remove: __( 'Remove customer username filter', 'wc-admin' ),
				rule: __( 'Select a customer username filter match', 'wc-admin' ),
				/* translators: A sentence describing a customer username filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
				title: __( '{{title}}Username{{/title}} {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select customer username', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to customer usernames including a given username(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
					label: _x( 'Includes', 'customer usernames', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to customer usernames excluding a given username(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
					label: _x( 'Excludes', 'customer usernames', 'wc-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'usernames',
				getLabels: getCustomerLabels,
			},
		},
		order: {
			labels: {
				add: __( 'Order number', 'wc-admin' ),
				placeholder: __( 'Search order number', 'wc-admin' ),
				remove: __( 'Remove order number filter', 'wc-admin' ),
				rule: __( 'Select a order number filter match', 'wc-admin' ),
				/* translators: A sentence describing a order number filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
				title: __( '{{title}}Order number{{/title}} {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select order number', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to order numbers including a given order(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
					label: _x( 'Includes', 'order numbers', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to order numbers excluding a given order(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
					label: _x( 'Excludes', 'order numbers', 'wc-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'orders',
				getLabels: async value => {
					const orderIds = value.split( ',' );
					return await orderIds.map( orderId => ( {
						id: orderId,
						label: '#' + orderId,
					} ) );
				},
			},
		},
		ip_address: {
			labels: {
				add: __( 'IP Address', 'wc-admin' ),
				placeholder: __( 'Search IP address', 'wc-admin' ),
				remove: __( 'Remove IP address filter', 'wc-admin' ),
				rule: __( 'Select an IP address filter match', 'wc-admin' ),
				/* translators: A sentence describing a order number filter. See screen shot for context: https://cloudup.com/ccxhyH2mEDg */
				title: __( '{{title}}IP Address{{/title}} {{rule /}} {{filter /}}', 'wc-admin' ),
				filter: __( 'Select IP address', 'wc-admin' ),
			},
			rules: [
				{
					value: 'includes',
					/* translators: Sentence fragment, logical, "Includes" refers to IP addresses including a given address(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
					label: _x( 'Includes', 'IP addresses', 'wc-admin' ),
				},
				{
					value: 'excludes',
					/* translators: Sentence fragment, logical, "Excludes" refers to IP addresses excluding a given address(s). Screenshot for context: https://cloudup.com/ccxhyH2mEDg */
					label: _x( 'Excludes', 'IP addresses', 'wc-admin' ),
				},
			],
			input: {
				component: 'Search',
				type: 'downloadIps',
				getLabels: async value => {
					const ips = value.split( ',' );
					return await ips.map( ip => {
						return {
							id: ip,
							label: ip,
						};
					} );
				},
			},
		},
	},
};
/*eslint-enable max-len*/
