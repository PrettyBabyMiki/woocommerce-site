/** @format */
/**
 * External dependencies
 */
import { __, _n, _x } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map, get } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Link } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';
import { isLowStock } from './utils';

export default class VariationsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Product / Variation Title', 'woocommerce-admin' ),
				key: 'name',
				required: true,
				isLeftAligned: true,
			},
			{
				label: __( 'SKU', 'woocommerce-admin' ),
				key: 'sku',
				hiddenByDefault: true,
			},
			{
				label: __( 'Items Sold', 'woocommerce-admin' ),
				key: 'items_sold',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'N. Revenue', 'woocommerce-admin' ),
				screenReaderLabel: __( 'Net Revenue', 'woocommerce-admin' ),
				key: 'net_revenue',
				required: true,
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Orders', 'woocommerce-admin' ),
				key: 'orders_count',
				isSortable: true,
				isNumeric: true,
			},
			'yes' === wcSettings.manageStock
				? {
						label: __( 'Status', 'woocommerce-admin' ),
						key: 'stock_status',
					}
				: null,
			'yes' === wcSettings.manageStock
				? {
						label: __( 'Stock', 'woocommerce-admin' ),
						key: 'stock',
						isNumeric: true,
					}
				: null,
		].filter( Boolean );
	}

	getRowsContent( data = [] ) {
		const { stockStatuses } = wcSettings;
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );

		return map( data, row => {
			const { items_sold, net_revenue, orders_count, product_id } = row;
			const extended_info = row.extended_info || {};
			const { stock_status, stock_quantity, low_stock_amount, sku } = extended_info;
			const name = get( row, [ 'extended_info', 'name' ], '' );
			const ordersLink = getNewPath( persistedQuery, '/analytics/orders', {
				filter: 'advanced',
				product_includes: query.products,
			} );
			const editPostLink = `post.php?post=${ product_id }&action=edit`;

			return [
				{
					display: (
						<Link href={ editPostLink } type="wp-admin">
							{ name }
						</Link>
					),
					value: name,
				},
				{
					display: sku,
					value: sku,
				},
				{
					display: numberFormat( items_sold ),
					value: items_sold,
				},
				{
					display: formatCurrency( net_revenue ),
					value: getCurrencyFormatDecimal( net_revenue ),
				},
				{
					display: (
						<Link href={ ordersLink } type="wc-admin">
							{ orders_count }
						</Link>
					),
					value: orders_count,
				},
				'yes' === wcSettings.manageStock
					? {
							display: isLowStock( stock_status, stock_quantity, low_stock_amount ) ? (
								<Link href={ editPostLink } type="wp-admin">
									{ _x( 'Low', 'Indication of a low quantity', 'woocommerce-admin' ) }
								</Link>
							) : (
								stockStatuses[ stock_status ]
							),
							value: stockStatuses[ stock_status ],
						}
					: null,
				'yes' === wcSettings.manageStock
					? {
							display: stock_quantity,
							value: stock_quantity,
						}
					: null,
			].filter( Boolean );
		} );
	}

	getSummary( totals ) {
		const { variations_count = 0, items_sold = 0, net_revenue = 0, orders_count = 0 } = totals;
		return [
			{
				label: _n( 'variation sold', 'variations sold', variations_count, 'woocommerce-admin' ),
				value: numberFormat( variations_count ),
			},
			{
				label: _n( 'item sold', 'items sold', items_sold, 'woocommerce-admin' ),
				value: numberFormat( items_sold ),
			},
			{
				label: __( 'net revenue', 'woocommerce-admin' ),
				value: formatCurrency( net_revenue ),
			},
			{
				label: _n( 'orders', 'orders', orders_count, 'woocommerce-admin' ),
				value: numberFormat( orders_count ),
			},
		];
	}

	render() {
		const { baseSearchQuery, isRequesting, query, filters } = this.props;

		const labels = {
			helpText: __( 'Check at least two variations below to compare', 'woocommerce-admin' ),
			placeholder: __( 'Search by variation name or SKU', 'woocommerce-admin' ),
		};

		return (
			<ReportTable
				baseSearchQuery={ baseSearchQuery }
				compareBy={ 'variations' }
				compareParam={ 'filter-variations' }
				endpoint="variations"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				isRequesting={ isRequesting }
				itemIdField="variation_id"
				labels={ labels }
				query={ query }
				getSummary={ this.getSummary }
				searchBy="variations"
				tableQuery={ {
					orderby: query.orderby || 'items_sold',
					order: query.order || 'desc',
					extended_info: true,
					products: query.products,
					variations: query.variations,
				} }
				title={ __( 'Variations', 'woocommerce-admin' ) }
				columnPrefsKey="variations_report_columns"
				filters={ filters }
			/>
		);
	}
}
