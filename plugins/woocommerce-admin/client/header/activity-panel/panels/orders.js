/** @format */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Component, Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import Gridicon from 'gridicons';
import PropTypes from 'prop-types';
import { noop } from 'lodash';
import interpolateComponents from 'interpolate-components';

/**
 * WooCommerce dependencies
 */
import {
	EllipsisMenu,
	EmptyContent,
	Flag,
	Link,
	MenuTitle,
	MenuItem,
	OrderStatus,
	Section,
} from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';
import { getAdminLink, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { ActivityCard, ActivityCardPlaceholder } from '../activity-card';
import ActivityHeader from '../activity-header';
import ActivityOutboundLink from '../activity-outbound-link';
import { DEFAULT_ACTIONABLE_STATUSES, QUERY_DEFAULTS } from 'wc-api/constants';
import withSelect from 'wc-api/with-select';

class OrdersPanel extends Component {
	renderEmptyCard() {
		const { hasNonActionableOrders } = this.props;
		if ( hasNonActionableOrders ) {
			return (
				<ActivityCard
					className="woocommerce-empty-review-activity-card"
					title={ __( 'You have no orders to fulfill', 'woocommerce-admin' ) }
					icon={ <Gridicon icon="checkmark" size={ 48 } /> }
				>
					{ __( "Good job, you've fulfilled all of your new orders!", 'woocommerce-admin' ) }
				</ActivityCard>
			);
		}

		return (
			<ActivityCard
				className="woocommerce-empty-review-activity-card"
				title={ __( 'You have no orders to fulfill', 'woocommerce-admin' ) }
				icon={ <Gridicon icon="time" size={ 48 } /> }
				actions={
					<Button
						href="https://docs.woocommerce.com/document/managing-orders/"
						isDefault
						target="_blank"
					>
						{ __( 'Learn more', 'woocommerce-admin' ) }
					</Button>
				}
			>
				{ __(
					"You're still waiting for your customers to make their first orders. " +
						'While you wait why not learn how to manage orders?',
					'woocommerce-admin'
				) }
			</ActivityCard>
		);
	}

	renderOrders() {
		const { orders } = this.props;

		if ( orders.length === 0 ) {
			return this.renderEmptyCard();
		}

		const getCustomerString = order => {
			const extended_info = order.extended_info || {};
			const { first_name, last_name } = extended_info.customer || {};

			if ( ! first_name && ! last_name ) {
				return '';
			}

			const name = [ first_name, last_name ].join( ' ' );
			return sprintf(
				__(
					/* translators: describes who placed an order, e.g. Order #123 placed by John Doe */
					'placed by {{customerLink}}%(customerName)s{{/customerLink}}',
					'woocommerce-admin'
				),
				{
					customerName: name,
				}
			);
		};

		const orderCardTitle = order => {
			const { extended_info, order_id } = order;
			const { customer } = extended_info || {};
			const customerUrl = customer.customer_id
				? getNewPath( {}, '/analytics/customers', {
						filter: 'single_customer',
						customers: customer.customer_id,
					} )
				: null;

			return (
				<Fragment>
					{ interpolateComponents( {
						mixedString: sprintf(
							__(
								'Order {{orderLink}}#%(orderNumber)s{{/orderLink}} %(customerString)s {{destinationFlag/}}',
								'woocommerce-admin'
							),
							{
								orderNumber: order_id,
								customerString: getCustomerString( order ),
							}
						),
						components: {
							orderLink: <Link href={ 'post.php?action=edit&post=' + order_id } type="wp-admin" />,
							destinationFlag: customer.country ? (
								<Flag code={ customer.country } round={ false } />
							) : null,
							customerLink: customerUrl ? <Link href={ customerUrl } type="wc-admin" /> : <span />,
						},
					} ) }
				</Fragment>
			);
		};

		const cards = [];
		orders.forEach( order => {
			const extended_info = order.extended_info || {};
			const productsCount =
				extended_info && extended_info.products ? extended_info.products.length : 0;

			const total = order.gross_total;
			const refundValue = order.refund_total;
			const remainingTotal = getCurrencyFormatDecimal( total ) + refundValue;

			cards.push(
				<ActivityCard
					key={ order.order_id }
					className="woocommerce-order-activity-card"
					title={ orderCardTitle( order ) }
					date={ order.date_created_gmt }
					subtitle={
						<div>
							<span>
								{ sprintf(
									_n( '%d product', '%d products', productsCount, 'woocommerce-admin' ),
									productsCount
								) }
							</span>
							{ refundValue ? (
								<span>
									<s>{ formatCurrency( total ) }</s> { formatCurrency( remainingTotal ) }
								</span>
							) : (
								<span>{ formatCurrency( total ) }</span>
							) }
						</div>
					}
					actions={
						<Button
							isDefault
							href={ getAdminLink( 'post.php?action=edit&post=' + order.order_id ) }
						>
							{ __( 'Begin fulfillment' ) }
						</Button>
					}
				>
					<OrderStatus order={ order } />
				</ActivityCard>
			);
		} );
		return (
			<Fragment>
				{ cards }
				<ActivityOutboundLink href={ 'edit.php?post_type=shop_order' }>
					{ __( 'Manage all orders', 'woocommerce-admin' ) }
				</ActivityOutboundLink>
			</Fragment>
		);
	}

	render() {
		const { orders, isRequesting, isError, orderStatuses } = this.props;

		if ( isError ) {
			if ( ! orderStatuses.length ) {
				return (
					<EmptyContent
						title={ __(
							"You currently don't have any actionable statuses. " +
								'To display orders here, select orders that require further review in settings.',
							'woocommerce-admin'
						) }
						actionLabel={ __( 'Settings', 'woocommerce-admin' ) }
						actionURL={ getAdminLink( 'admin.php?page=wc-admin#/analytics/settings' ) }
					/>
				);
			}

			const title = __(
				'There was an error getting your orders. Please try again.',
				'woocommerce-admin'
			);
			const actionLabel = __( 'Reload', 'woocommerce-admin' );
			const actionCallback = () => {
				// @todo Add tracking for how often an error is displayed, and the reload action is clicked.
				window.location.reload();
			};

			return (
				<Fragment>
					<EmptyContent
						title={ title }
						actionLabel={ actionLabel }
						actionURL={ null }
						actionCallback={ actionCallback }
					/>
				</Fragment>
			);
		}

		const menu = (
			<EllipsisMenu label="Demo Menu">
				<MenuTitle>Test</MenuTitle>
				<MenuItem onInvoke={ noop }>Test</MenuItem>
			</EllipsisMenu>
		);

		const title =
			isRequesting || orders.length
				? __( 'Orders', 'woocommerce-admin' )
				: __( 'No orders to ship', 'woocommerce-admin' );

		return (
			<Fragment>
				<ActivityHeader title={ title } menu={ menu } />
				<Section>
					{ isRequesting ? (
						<ActivityCardPlaceholder
							className="woocommerce-order-activity-card"
							hasAction
							hasDate
							lines={ 2 }
						/>
					) : (
						this.renderOrders()
					) }
				</Section>
			</Fragment>
		);
	}
}

OrdersPanel.propTypes = {
	orders: PropTypes.array.isRequired,
	isError: PropTypes.bool,
	isRequesting: PropTypes.bool,
};

OrdersPanel.defaultProps = {
	orders: [],
	isError: false,
	isRequesting: false,
};

export default compose(
	withSelect( ( select, props ) => {
		const { hasActionableOrders } = props;
		const { getReportItems, getReportItemsError, isReportItemsRequesting } = select( 'wc-api' );
		const orderStatuses =
			wcSettings.wcAdminSettings.woocommerce_actionable_order_statuses ||
			DEFAULT_ACTIONABLE_STATUSES;

		if ( ! orderStatuses.length ) {
			return { orders: [], isError: true, isRequesting: false, orderStatuses };
		}

		if ( hasActionableOrders ) {
			const ordersQuery = {
				page: 1,
				per_page: QUERY_DEFAULTS.pageSize,
				status_is: orderStatuses,
				extended_info: true,
			};

			const orders = getReportItems( 'orders', ordersQuery ).data;
			const isError = Boolean( getReportItemsError( 'orders', ordersQuery ) );
			const isRequesting = isReportItemsRequesting( 'orders', ordersQuery );

			return { orders, isError, isRequesting, orderStatuses };
		}

		const allOrdersQuery = {
			page: 1,
			per_page: 0,
		};

		const totalNonActionableOrders = getReportItems( 'orders', allOrdersQuery ).totalResults;
		const isError = Boolean( getReportItemsError( 'orders', allOrdersQuery ) );
		const isRequesting = isReportItemsRequesting( 'orders', allOrdersQuery );

		return {
			hasNonActionableOrders: totalNonActionableOrders > 0,
			isError,
			isRequesting,
			orderStatuses,
		};
	} )
)( OrdersPanel );
