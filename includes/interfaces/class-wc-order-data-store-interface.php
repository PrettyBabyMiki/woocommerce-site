<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Data Store Interface
 *
 * Functions that must be defined by order store classes.
 *
 * @version  2.7.0
 * @category Interface
 * @author   WooThemes
 */
interface WC_Order_Data_Store_Interface {
	/**
	 * Get amount already refunded.
	 *
	 * @param  WC_Order
	 * @return string
	 */
	public function get_total_refunded( $order );

	/**
	 * Get the total tax refunded.
	 *
	 * @param  WC_Order
	 * @return float
	 */
	public function get_total_tax_refunded( $order );

	/**
	 * Get the total shipping refunded.
	 *
	 * @param  WC_Order
	 * @return float
	 */
	public function get_total_shipping_refunded( $order );

	/**
	 * Finds an Order ID based on an order key.
	 *
	 * @param string $order_key An order key has generated by
	 * @return int The ID of an order, or 0 if the order could not be found
	 */
	public function get_order_id_by_order_key( $order_key );

	/**
	 * Return count of orders with a specific status.
	 * @param  string $status
	 * @return int
	 */
	public function get_order_count( $status );

	/**
	 * Get all orders matching the passed in args.
	 *
	 * @see    wc_get_orders()
	 * @param  array $args
	 * @return array of orders
	 */
	public function get_orders( $args = array() );

	/**
	 * Get unpaid orders after a certain date,
	 * @param  int timestamp $date
	 * @return array
	 */
	public function get_unpaid_orders( $date );

	/**
	 * Search order data for a term and return ids.
	 * @param  string $term
	 * @return array of ids
	 */
	public function search_orders( $term );

	/**
	 * Gets information about whether permissions were generated yet.
	 * @param WC_Order $order
	 * @return bool
	 */
	public function get_download_permissions_granted( $order );

	/**
	 * Stores information about whether permissions were generated yet.
	 * @param WC_Order $order
	 * @param bool $set
	 */
	public function set_download_permissions_granted( $order, $set );

	/**
	 * Gets information about whether sales were recorded.
	 * @param WC_Order $order
	 * @return bool
	 */
	public function get_recorded_sales( $order );

	/**
	 * Stores information about whether sales were recorded.
	 * @param WC_Order $order
	 * @param bool $set
	 */
	public function set_recorded_sales( $order, $set );

	/**
	 * Gets information about whether coupon counts were updated.
	 * @param WC_Order $order
	 * @return bool
	 */
	public function get_recorded_coupon_usage_counts( $order );

	/**
	 * Stores information about whether coupon counts were updated.
	 * @param WC_Order $order
	 * @param bool $set
	 */
	public function set_recorded_coupon_usage_counts( $order, $set );

	/**
	 * Get the order type based on Order ID.
	 * @param  int $order_id
	 * @return string
	 */
	public function get_order_type( $order_id );
}
