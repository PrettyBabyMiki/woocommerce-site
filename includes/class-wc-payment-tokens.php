<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Payment Tokens
 *
 * An API for storing and managing tokens for gateways and customers.
 *
 * @class 		WC_Payment_Tokens
 * @since		2.6.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author		WooThemes
 */
class WC_Payment_Tokens {

	/**
	 * Makes sure that the passed data is suitable for storing a token
	 * @param  array $args  An array of arguments (can be meta or core token field) to validate
	 * @return boolean      True if the passed data is valid
	 */
	public static function validate( $args ) {
		if ( empty( $args['token'] ) ) {
			return false;
		}

		if ( empty( $args['type'] ) ) {
			return false;
		}

		// If 'token' and 'type' are present, validate that any specific token types (credit card for example)
		// contain their neccesary fields.
		$is_valid = true;
		$token_class = 'WC_Payment_Token_' . $args['type'];
		if ( method_exists( $token_class, 'validate' ) ) {
			$is_valid = $token_class::validate( $args );
		}

		return apply_filters( 'woocommerce_payment_token_validate', $is_valid, $args );
	}

	/**
	 * Creates, stores in the database, and returns a new payment token object
	 * @param  array $args  An array of arguments (meta or core token fields)
	 * @return WC_Payment_Token
	 */
	public static function create( $args ) {
		if ( ! self::validate( $args ) ) {
			return; // @todo throw an error
		}

		global $wpdb;
		unset( $args['token_id'] );

		// We need to separate our meta fields since they are stored in a separate table
		$core_fields = self::get_token_core_fields();
		$core_fields_to_create = array();
		foreach ( $core_fields as $core_field ) {
			if ( isset( $args[ $core_field ] ) ) {
				$core_fields_to_create[ $core_field ] = $args[ $core_field ];
				unset( $args[ $core_field ] );
			}
		}

		// Store the main token in the database
		$wpdb->insert( $wpdb->prefix . 'woocommerce_payment_tokens', $core_fields_to_create );
		$token_id = $wpdb->insert_id;

		// Insert any other meta and associate it with the new token
		foreach ( $args as $meta_key => $meta_value ) {
			$wpdb->insert( $wpdb->prefix . 'woocommerce_payment_token_meta', array(
				'meta_key'   => $meta_key,
				'meta_value' => $meta_value,
				'token_id'   => $token_id
			) );
		}

		do_action( 'woocommerce_payment_token_created', $token_id );
		return self::generate_token( $token_id, $core_fields_to_create );
	}

	/**
	 * Updates (stores in the database), and returns a payment token object
	 * @param int               $token_id Token ID
	 * @param  WC_Payment_Token $token    An existing payment token object (updated fields can be changed with set_)
	 * @return WC_Payment_Token Updated token
	 */
	public static function update( $token_id, $token ) {
		$args = $token->__data_format();
		unset( $args['token_id'] );
		if ( ! self::validate( $args ) ) {
			return; // @todo throw an error
		}

		// We need to separate our meta fields since they are stored in a separate table
		$core_fields = self::get_token_core_fields();
		$core_fields_to_create = array();
		foreach ( $core_fields as $core_field ) {
			if ( isset( $args[ $core_field ] ) ) {
				$core_fields_to_update[ $core_field ] = $args[ $core_field ];
				unset( $args[ $core_field ] );
			}
		}

		global $wpdb;

		// Update our core fields
		$wpdb->update( $wpdb->prefix . 'woocommerce_payment_tokens', $core_fields_to_update, array( 'token_id' => $token_id ) );

		// Grab a list of our existing meta, and update if we have a matching value in $args, otherise, create a new meta row
		$existing_meta = $wpdb->get_col( $wpdb->prepare(
			"SELECT meta_key FROM {$wpdb->prefix}woocommerce_payment_token_meta WHERE token_id = %d", $token_id
		) );
		foreach ( $args as $meta_key => $meta_value ) {
			if ( in_array( $meta_key, $existing_meta ) ) {
				$wpdb->update( $wpdb->prefix . 'woocommerce_payment_token_meta', array(
					'meta_value' => $meta_value
				), array( 'token_id'   => $token_id, 'meta_key'   => $meta_key ) );
			} else {
				$wpdb->insert( $wpdb->prefix . 'woocommerce_payment_token_meta', array(
					'meta_key'   => $meta_key,
					'meta_value' => $meta_value,
					'token_id'   => $token_id
				) );
			}
		}

		do_action( 'woocommerce_payment_token_updated', $token_id );
		return self::generate_token( $token_id, $core_fields_to_update );
	}

	/**
	 * Remove a payment token from the database
	 * @param int $token_id Token ID
	 */
	public static function delete( $token_id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokens', array( 'token_id' => $token_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_token_meta', array( 'token_id' => $token_id ), array( '%d' ) );
		do_action( 'woocommerce_payment_token_deleted', $token_id );
	}

	/**
	 * Returns an array of payment token objects associated with the passed customer ID
	 * @param int $customer_id Customer ID
	 * @return array Array of token objects
	 */
	public static function get_customer_tokens( $customer_id ) {
		if ( $customer_id < 1 ) {
			return array();
		}

		global $wpdb;

		$token_results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE customer_id = %d",
			$customer_id
		) );

		if ( empty( $token_results ) ) {
			return array();
		}

		$tokens = array();
		foreach ( $token_results as $token_result ) {
			$_token = self::generate_token( $token_result->token_id, (array) $token_result );
			if ( ! empty( $_token ) ) {
				$tokens[$token_result->token_id] = $_token;
			}
		}

		return $tokens;
	}

	/**
	 * Returns an array of payment token objects associated with the passed order ID
	 * @param int $order_id Order ID
	 * @return array Array of token objects
	 */
	public static function get_order_tokens( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return array();
		}

		$wc_tokens_meta = get_post_meta( $order_id, '_wc_payment_tokens', true );

		if ( empty ( $wc_tokens_meta ) ) {
			return array();
		}

		global $wpdb;

		$wc_tokens_meta_string = implode( ',', array_map( 'intval', $wc_tokens_meta ) );
		$token_results = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id IN ( {$wc_tokens_meta_string} )"
		);

		if ( empty( $token_results ) ) {
			return array();
		}

		$tokens = array();
		foreach ( $token_results as $token_result ) {
			$_token = self::generate_token( $token_result->token_id, (array) $token_result );
			if ( ! empty( $_token ) ) {
				$tokens[$token_result->token_id] = $_token;
			}
		}

		return $tokens;
	}

	/**
	 * Returns an array of fields used in all payment tokens
	 * @return array Core fields
	 */
	private static function get_token_core_fields() {
		return apply_filters( 'woocommerce_payment_token_core_fields', array( 'gateway_id', 'token', 'customer_id', 'type', 'is_default' ) );
	}

	/**
	 * Generates a token object
	 * @param  int $token_id        ID of the token being returned
	 * @param  array $token_result  Internal data values for the token
	 * @return WC_Payment_Token
	 */
	private static function generate_token( $token_id, $token_result ) {
		global $wpdb;
		$token_class = 'WC_Payment_Token_' . $token_result['type'];
		if ( class_exists( $token_class ) ) {
			$meta = array();
			$meta_rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT meta_key,meta_value FROM {$wpdb->prefix}woocommerce_payment_token_meta WHERE token_id = %d",
				$token_id
			) );
			if ( ! empty( $meta_rows ) ) {
				foreach( $meta_rows as $meta_row ) {
					$meta[$meta_row->meta_key] = $meta_row->meta_value;
				}
			}
			return new $token_class( $token_id, $token_result, $meta );
		}
	}

}
