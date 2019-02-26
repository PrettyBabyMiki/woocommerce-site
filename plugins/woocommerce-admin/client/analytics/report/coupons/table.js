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
import { Date, Link } from '@woocommerce/components';
import { defaultTableDateFormat } from '@woocommerce/date';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';
import { numberFormat } from '@woocommerce/number';

/**
 * Internal dependencies
 */
import ReportTable from 'analytics/components/report-table';

export default class CouponsReportTable extends Component {
	constructor() {
		super();

		this.getHeadersContent = this.getHeadersContent.bind( this );
		this.getRowsContent = this.getRowsContent.bind( this );
		this.getSummary = this.getSummary.bind( this );
	}

	getHeadersContent() {
		return [
			{
				label: __( 'Coupon Code', 'wc-admin' ),
				key: 'code',
				required: true,
				isLeftAligned: true,
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
			{
				label: __( 'Amount Discounted', 'wc-admin' ),
				key: 'amount',
				isSortable: true,
				isNumeric: true,
			},
			{
				label: __( 'Created', 'wc-admin' ),
				key: 'created',
			},
			{
				label: __( 'Expires', 'wc-admin' ),
				key: 'expires',
			},
			{
				label: __( 'Type', 'wc-admin' ),
				key: 'type',
			},
		];
	}

	getRowsContent( coupons ) {
		const { query } = this.props;
		const persistedQuery = getPersistedQuery( query );

		return map( coupons, coupon => {
			const { amount, coupon_id, extended_info, orders_count } = coupon;
			const { code, date_created, date_expires, discount_type } = extended_info;

			const couponUrl = getNewPath( persistedQuery, '/analytics/coupons', {
				filter: 'single_coupon',
				coupons: coupon_id,
			} );
			const couponLink = (
				<Link href={ couponUrl } type="wc-admin">
					{ code }
				</Link>
			);

			const ordersUrl = getNewPath( persistedQuery, '/analytics/orders', {
				filter: 'advanced',
				coupon_includes: coupon_id,
			} );
			const ordersLink = (
				<Link href={ ordersUrl } type="wc-admin">
					{ numberFormat( orders_count ) }
				</Link>
			);

			return [
				{
					display: couponLink,
					value: code,
				},
				{
					display: ordersLink,
					value: orders_count,
				},
				{
					display: formatCurrency( amount ),
					value: getCurrencyFormatDecimal( amount ),
				},
				{
					display: <Date date={ date_created } visibleFormat={ defaultTableDateFormat } />,
					value: date_created,
				},
				{
					display: date_expires ? (
						<Date date={ date_expires } visibleFormat={ defaultTableDateFormat } />
					) : (
						__( 'N/A', 'wc-admin' )
					),
					value: date_expires,
				},
				{
					display: this.getCouponType( discount_type ),
					value: discount_type,
				},
			];
		} );
	}

	getSummary( totals ) {
		const { coupons_count = 0, orders_count = 0, amount = 0 } = totals;
		return [
			{
				label: _n( 'coupon', 'coupons', coupons_count, 'wc-admin' ),
				value: numberFormat( coupons_count ),
			},
			{
				label: _n( 'order', 'orders', orders_count, 'wc-admin' ),
				value: numberFormat( orders_count ),
			},
			{
				label: __( 'amount discounted', 'wc-admin' ),
				value: formatCurrency( amount ),
			},
		];
	}

	getCouponType( discount_type ) {
		const couponTypes = {
			percent: __( 'Percentage', 'wc-admin' ),
			fixed_cart: __( 'Fixed cart', 'wc-admin' ),
			fixed_product: __( 'Fixed product', 'wc-admin' ),
		};
		return couponTypes[ discount_type ];
	}

	render() {
		const { isRequesting, query } = this.props;

		return (
			<ReportTable
				compareBy="coupons"
				endpoint="coupons"
				getHeadersContent={ this.getHeadersContent }
				getRowsContent={ this.getRowsContent }
				getSummary={ this.getSummary }
				isRequesting={ isRequesting }
				itemIdField="coupon_id"
				query={ query }
				searchBy="coupons"
				tableQuery={ {
					orderby: query.orderby || 'orders_count',
					order: query.order || 'desc',
					extended_info: true,
				} }
				title={ __( 'Coupons', 'wc-admin' ) }
				columnPrefsKey="coupons_report_columns"
			/>
		);
	}
}
