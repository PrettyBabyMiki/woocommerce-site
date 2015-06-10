<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles refunds
 */
abstract class WC_Gateway_Paypal_Response {

	/** @var bool Sandbox mode */
	protected $sandbox = false;

	/**
	 * Get the order from the PayPal 'Custom' variable
	 *
	 * @param  string $raw_custom JSON Data passed back by PayPal
	 * @return bool|WC_Order object
	 */
	protected function get_paypal_order( $raw_custom ) {
		$custom = json_decode( $raw_custom );

		// We want an object
		if ( is_object( $custom ) ) {
			// We have the data in the correct format, so get the order
			$order_id  = $custom->order_id;
			$order_key = $custom->order_key;

			if ( ! $order = wc_get_order( $order_id ) ) {
				// We have an invalid $order_id, probably because invoice_prefix has changed
				$order_id 	= wc_get_order_id_by_order_key( $order_key );
				$order 		= wc_get_order( $order_id );
			}

			if ( ! $order || $order->order_key !== $order_key ) {
				WC_Gateway_Paypal::log( 'Error: Order Keys do not match.' );
				return false;
			}

		// Fallback to filter to allow 3rd parties to retrieve the order
		} elseif ( ! $order = apply_filters( 'woocommerce_get_paypal_order', false, $raw_custom ) ) {
			WC_Gateway_Paypal::log( 'Error: Order ID and key were not found in "custom".' );
			return false;
		}

		return $order;
	}

	/**
	 * Complete order, add transaction ID and note
	 * @param  WC_Order $order
	 * @param  string $txn_id
	 * @param  string $note
	 */
	protected function payment_complete( $order, $txn_id = '', $note = '' ) {
		$order->add_order_note( $note );
		$order->payment_complete( $txn_id );
	}

	/**
	 * Hold order and add note
	 * @param  WC_Order $order
	 * @param  string $reason
	 */
	protected function payment_on_hold( $order, $reason = '' ) {
		$order->update_status( 'on-hold', $reason );
		$order->reduce_order_stock();
		WC()->cart->empty_cart();
	}
}
