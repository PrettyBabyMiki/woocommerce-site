/** @format */
/**
 * External dependencies
 */
import { __, _n } from '@wordpress/i18n';
import { Component } from '@wordpress/element';
import { map } from 'lodash';

/**
 * WooCommerce dependencies
 */
import { Link } from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getTaxCode } from './utils';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';

export default class TaxesReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Tax Code', 'wc-admin' ),
				// @todo It should be the tax code, not the ID
				key: 'tax_rate_id',
				required: true,
				isLeftAligned: true,
				isSortable: true,
			},
			{
				label: __( 'Rate', 'wc-admin' ),
				key: 'rate',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Total Tax', 'wc-admin' ),
				key: 'total_tax',
				isSortable: true,
			},
			{
				label: __( 'Order Tax', 'wc-admin' ),
				key: 'order_tax',
				isSortable: true,
			},
			{
				label: __( 'Shipping Tax', 'wc-admin' ),
				key: 'shipping_tax',
				isSortable: true,
			},
			{
				label: __( 'Orders', 'wc-admin' ),
				key: 'orders_count',
				required: true,
				defaultSort: true,
				isSortable: true,
				isNumeric: true,
			},
		];
	}

	getRowsContent( taxes ) {
		return map( taxes, tax => {
			const { order_tax, orders_count, tax_rate, tax_rate_id, total_tax, shipping_tax } = tax;

			// @todo Must link to the tax detail report
			const taxLink = (
				<Link href="" type="wc-admin">
					{ getTaxCode( tax ) }
				</Link>
			);

			return [
				{
					display: taxLink,
					value: tax_rate_id,
				},
				{
					display: tax_rate.toFixed( 2 ) + '%',
					value: tax_rate,
				},
				{
					display: formatCurrency( total_tax ),
					value: getCurrencyFormatDecimal( total_tax ),
				},
				{
					display: formatCurrency( order_tax ),
					value: getCurrencyFormatDecimal( order_tax ),
				},
				{
					display: formatCurrency( shipping_tax ),
					value: getCurrencyFormatDecimal( shipping_tax ),
				},
				{
					display: numberFormat( orders_count ),
					value: orders_count,
				},
			];
		} );
	}

	getSummary( totals ) {
		const {
			tax_codes = 0,
			total_tax = 0,
			order_tax = 0,
			shipping_tax = 0,
			orders_count = 0,
		} = totals;
		return [
			{
				label: _n( 'tax code', 'tax codes', tax_codes, 'wc-admin' ),
				value: numberFormat( tax_codes ),
			},
			{
				label: __( 'total tax', 'wc-admin' ),
				value: formatCurrency( total_tax ),
			},
			{
				label: __( 'order tax', 'wc-admin' ),
				value: formatCurrency( order_tax ),
			},
			{
				label: __( 'shipping tax', 'wc-admin' ),
				value: formatCurrency( shipping_tax ),
			},
			{
				label: _n( 'order', 'orders', orders_count, 'wc-admin' ),
				value: numberFormat( orders_count ),
			},
		];
	}

	render() {
		const { isRequesting, query } = this.props;

		return (
			<ReportTable
				compareBy="taxes"
				endpoint="taxes"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				isRequesting={ isRequesting }
				itemIdField="tax_rate_id"
				query={ query }
				searchBy="taxes"
				tableQuery={ {
					orderby: query.orderby || 'tax_rate_id',
				} }
				title={ __( 'Taxes', 'wc-admin' ) }
				columnPrefsKey="taxes_report_columns"
			/>
		);
	}
}
