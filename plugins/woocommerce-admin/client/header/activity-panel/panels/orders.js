/** @format */
/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect } from '@wordpress/data';
import PropTypes from 'prop-types';
import { noop } from 'lodash';

/**
 * WooCommerce dependencies
 */
import {
	EllipsisMenu,
	Gravatar,
	Flag,
	MenuTitle,
	MenuItem,
	OrderStatus,
	Section,
} from '@woocommerce/components';
import { formatCurrency, getCurrencyFormatDecimal } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import { ActivityCard } from '../activity-card';
import ActivityHeader from '../activity-header';
import ActivityOutboundLink from '../activity-outbound-link';
import { getOrderRefundTotal } from 'lib/order-values';
import { QUERY_DEFAULTS } from 'store/constants';

function OrdersPanel( { orders, isRequesting } ) {
	const menu = (
		<EllipsisMenu label="Demo Menu">
			<MenuTitle>Test</MenuTitle>
			<MenuItem onInvoke={ noop }>Test</MenuItem>
		</EllipsisMenu>
	);

	const gravatarWithFlag = ( order, address ) => {
		return (
			<div className="woocommerce-layout__activity-panel-avatar-flag-overlay">
				<Flag order={ order } />
				<Gravatar user={ address.email } />
			</div>
		);
	};

	return (
		<Fragment>
			<ActivityHeader title={ __( 'Orders', 'wc-admin' ) } menu={ menu } />
			<Section>
				{ isRequesting ? (
					<p>Loading</p>
				) : (
					<Fragment>
						{ orders.map( ( order, i ) => {
							// We want the billing address, but shipping can be used as a fallback.
							const address = { ...order.shipping, ...order.billing };
							const name = `${ address.first_name } ${ address.last_name }`;
							const productsCount = order.line_items.reduce(
								( total, line ) => total + line.quantity,
								0
							);

							const total = order.total;
							const refundValue = getOrderRefundTotal( order );
							const remainingTotal = getCurrencyFormatDecimal( order.total ) + refundValue;

							return (
								<ActivityCard
									key={ i }
									className="woocommerce-order-activity-card"
									title={ sprintf( __( '%s placed order #%d', 'wc-admin' ), name, order.id ) }
									icon={ gravatarWithFlag( order, address ) }
									date={ order.date_created }
									subtitle={
										<div>
											<span>
												{ sprintf(
													_n( '%d product', '%d products', productsCount, 'wc-admin' ),
													productsCount
												) }
											</span>
											{ refundValue ? (
												<span>
													<s>{ formatCurrency( total, order.currency ) }</s>{' '}
													{ formatCurrency( remainingTotal, order.currency ) }
												</span>
											) : (
												<span>{ formatCurrency( total, order.currency ) }</span>
											) }
										</div>
									}
									actions={
										<Button isDefault onClick={ noop }>
											Begin fulfillment
										</Button>
									}
								>
									<OrderStatus order={ order } />
								</ActivityCard>
							);
						} ) }
						<ActivityOutboundLink href={ 'edit.php?post_type=shop_order' }>
							{ __( 'Manage all orders' ) }
						</ActivityOutboundLink>
					</Fragment>
				) }
			</Section>
		</Fragment>
	);
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
	withSelect( select => {
		const { getOrders, isGetOrdersError, isGetOrdersRequesting } = select( 'wc-admin' );
		const ordersQuery = {
			page: 1,
			per_page: QUERY_DEFAULTS.pageSize,
		};

		const orders = getOrders( ordersQuery );
		const isError = isGetOrdersError( ordersQuery );
		const isRequesting = isGetOrdersRequesting( ordersQuery );

		return { orders, isError, isRequesting };
	} )
)( OrdersPanel );
