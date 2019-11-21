/** @format */
/**
 * Internal dependencies
 */
import { AdvancedFilters } from '@woocommerce/components';
import Currency from '@woocommerce/currency';

const ORDER_STATUSES = {
	cancelled: 'Cancelled',
	completed: 'Completed',
	failed: 'Failed',
	'on-hold': 'On hold',
	pending: 'Pending payment',
	processing: 'Processing',
	refunded: 'Refunded',
};

const siteLocale = 'en_US';
const siteCurrency = new Currency(); // pass site currency settings.

const path = ( new URL( document.location ) ).searchParams.get( 'path' ) || '/devdocs';
const query = {
	component: 'advanced-filters',
};

const advancedFilters = {
	title: 'Orders Match {{select /}} Filters',
	filters: {
		status: {
			labels: {
				add: 'Order Status',
				remove: 'Remove order status filter',
				rule: 'Select an order status filter match',
				title: '{{title}}Order Status{{/title}} {{rule /}} {{filter /}}',
				filter: 'Select an order status',
			},
			rules: [
				{
					value: 'is',
					label: 'Is',
				},
				{
					value: 'is_not',
					label: 'Is Not',
				},
			],
			input: {
				component: 'SelectControl',
				options: Object.keys( ORDER_STATUSES ).map( key => ( {
					value: key,
					label: ORDER_STATUSES[ key ],
				} ) ),
			},
		},
		product: {
			labels: {
				add: 'Products',
				placeholder: 'Search products',
				remove: 'Remove products filter',
				rule: 'Select a product filter match',
				title: '{{title}}Product{{/title}} {{rule /}} {{filter /}}',
				filter: 'Select products',
			},
			rules: [
				{
					value: 'includes',
					label: 'Includes',
				},
				{
					value: 'excludes',
					label: 'Excludes',
				},
			],
			input: {
				component: 'Search',
				type: 'products',
				getLabels: () => Promise.resolve( [] ),
			},
		},
		customer: {
			labels: {
				add: 'Customer Type',
				remove: 'Remove customer filter',
				rule: 'Select a customer filter match',
				title: '{{title}}Customer is{{/title}} {{filter /}}',
				filter: 'Select a customer type',
			},
			input: {
				component: 'SelectControl',
				options: [
					{ value: 'new', label: 'New' },
					{ value: 'returning', label: 'Returning' },
				],
				defaultOption: 'new',
			},
		},
		quantity: {
			labels: {
				add: 'Item Quantity',
				remove: 'Remove item quantity filter',
				rule: 'Select an item quantity filter match',
				title: '{{title}}Item Quantity is{{/title}} {{rule /}} {{filter /}}',
			},
			rules: [
				{
					value: 'lessthan',
					label: 'Less Than',
				},
				{
					value: 'morethan',
					label: 'More Than',
				},
				{
					value: 'between',
					label: 'Between',
				},
			],
			input: {
				component: 'Number',
			},
		},
		subtotal: {
			labels: {
				add: 'Subtotal',
				remove: 'Remove subtotal filter',
				rule: 'Select a subtotal filter match',
				title: '{{title}}Subtotal is{{/title}} {{rule /}} {{filter /}}',
			},
			rules: [
				{
					value: 'lessthan',
					label: 'Less Than',
				},
				{
					value: 'morethan',
					label: 'More Than',
				},
				{
					value: 'between',
					label: 'Between',
				},
			],
			input: {
				component: 'Number',
				type: 'currency',
			},
		},
	},
};

export default () => (
	<AdvancedFilters
		siteLocale={ siteLocale }
        path={ path }
        query={ query }
        filterTitle="Orders"
		config={ advancedFilters }
		currency={ siteCurrency }
    />
);
