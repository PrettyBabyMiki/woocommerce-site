<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Payment Token Data Store: Custom Table.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Payment_Token_Data_Store extends WC_Data_Store_WP implements WC_Payment_Token_Data_Store_Interface, WC_Object_Data_Store_Interface {

	/**
	 * Meta type. Payment tokens are a new object type.
	 * @var string
	 */
	protected $meta_type = 'payment_token';

	/**
	 * Create a new payment token in the database.
	 *
	 * @since 2.7.0
	 * @param WC_Payment_Token $token
	 */
	public function create( &$token ) {
		if ( false === $token->validate() ) {
			throw new Exception( __( 'Invalid or missing payment token fields.', 'woocommerce' ) );
		}

		global $wpdb;
		if ( ! $token->is_default() && $token->get_user_id() > 0 ) {
			$default_token = WC_Payment_Tokens::get_customer_default_token( $token->get_user_id() );
			if ( is_null( $default_token ) ) {
				$token->set_default( true );
			}
		}

		$payment_token_data = array(
			'gateway_id' => $token->get_gateway_id( 'edit' ),
			'token'      => $token->get_token( 'edit' ),
			'user_id'    => $token->get_user_id( 'edit' ),
			'type'       => $token->get_type( 'edit' ),
		);

		$wpdb->insert( $wpdb->prefix . 'woocommerce_payment_tokens', $payment_token_data );
		$token_id = $wpdb->insert_id;
		$token->set_id( $token_id );
		$token->save_meta_data();
		$token->apply_changes();

		// Make sure all other tokens are not set to default
		if ( $token->is_default() && $token->get_user_id() > 0 ) {
			WC_Payment_Tokens::set_users_default( $token->get_user_id(), $token_id );
		}

		do_action( 'woocommerce_payment_token_created', $token_id );
	}

	/**
	 * Update a payment token.
	 *
	 * @since 2.7.0
	 * @param WC_Payment_Token $token
	 */
	public function update( &$token ) {
		if ( false === $token->validate() ) {
			throw new Exception( __( 'Invalid or missing payment token fields.', 'woocommerce' ) );
		}

		global $wpdb;

		$payment_token_data = array(
			'gateway_id' => $token->get_gateway_id( 'edit' ),
			'token'      => $token->get_token( 'edit' ),
			'user_id'    => $token->get_user_id( 'edit' ),
			'type'       => $token->get_type( 'edit' ),
		);

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_payment_tokens',
			$payment_token_data,
			array( 'token_id' => $token->get_id( 'edit' ) )
		);

		$token->save_meta_data();
		$token->apply_changes();

		// Make sure all other tokens are not set to default
		if ( $token->is_default() && $token->get_user_id() > 0 ) {
			WC_Payment_Tokens::set_users_default( $token->get_user_id(), $token->get_id() );
		}

		do_action( 'woocommerce_payment_token_updated', $token->get_id() );
	}

	/**
	 * Remove a payment token from the database.
	 *
	 * @since 2.7.0
	 * @param WC_Payment_Token $token
	 * @param bool $force_delete
	 */
	public function delete( &$token, $force_delete = false ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokens', array( 'token_id' => $token->get_id() ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'woocommerce_payment_tokenmeta', array( 'payment_token_id' => $token->get_id() ), array( '%d' ) );
		do_action( 'woocommerce_payment_token_deleted', $token->get_id(), $token );
	}

	/**
	 * Read a token from the database.
	 *
	 * @since 2.7.0
	 * @param WC_Payment_Token $token
	 */
	public function read( &$token ) {
		global $wpdb;
		if ( $data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d LIMIT 1;", $token->get_id() ) ) ) {
			$token->set_props( array(
				'token'      => $data->token,
				'user_id'    => $data->user_id,
				'gateway_id' => $data->gateway_id,
				'default'    => $data->is_default,
			) );
			$token->read_meta_data();
			$token->set_object_read( true );
			do_action( 'woocommerce_payment_token_loaded', $token );
		} else {
			throw new Exception( __( 'Invalid payment token.', 'woocommerce' ) );
		}
	}

	/**
	 * Returns an array of objects (stdObject) matching specific token critera.
	 * Accepts token_id, user_id, gateway_id, and type.
	 * Each object should contain the fields token_id, gateway_id, token, user_id, type, is_default.
	 *
	 * @since 2.7.0
	 * @param array $args
	 * @return array
	 */
	public function get_tokens( $args ) {
		global $wpdb;
		$args = wp_parse_args( $args, array(
			'token_id'   => '',
			'user_id'    => '',
			'gateway_id' => '',
			'type'       => '',
		) );

		$sql   = "SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens";
		$where = array( '1=1' );

		if ( $args['token_id'] ) {
			$token_ids = array_map( 'absint', is_array( $args['token_id'] ) ? $args['token_id'] : array( $args['token_id'] ) );
			$where[]   = "token_id IN ('" . implode( "','", array_map( 'esc_sql', $token_ids ) ) . "')";
		}

		if ( $args['user_id'] ) {
			$where[] = 'user_id = ' . absint( $args['user_id'] );
		}

		if ( $args['gateway_id'] ) {
			$gateway_ids = array( $args['gateway_id'] );
		} else {
			$gateways    = WC_Payment_Gateways::instance();
			$gateway_ids = $gateways->get_payment_gateway_ids();
		}

		$gateway_ids[] = '';
		$where[]       = "gateway_id IN ('" . implode( "','", array_map( 'esc_sql', $gateway_ids ) ) . "')";

		if ( $args['type'] ) {
			$where[] = 'type = ' . esc_sql( $args['type'] );
		}

		$token_results = $wpdb->get_results( $sql . ' WHERE ' . implode( ' AND ', $where ) );

		return $token_results;
	}

	/**
	 * Returns an stdObject of a token for a user's default token.
	 * Should contain the fields token_id, gateway_id, token, user_id, type, is_default.
	 *
	 * @since 2.7.0
	 * @param id $user_id
	 * @return object
	 */
	public function get_users_default_token( $user_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE user_id = %d AND is_default = 1",
			$user_id
		) );
	}

	/**
	 * Returns an stdObject of a token.
	 * Should contain the fields token_id, gateway_id, token, user_id, type, is_default.
	 *
	 * @since 2.7.0
	 * @param id $token_id
	 * @return object
	 */
	public function get_token_by_id( $token_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d",
			$token_id
		) );
	}

	/**
	 * Returns metadata for a specific payment token.
	 *
	 * @since 2.7.0
	 * @param id $token_id
	 * @return array
	 */
	public function get_metadata( $token_id ) {
		return get_metadata( 'payment_token', $token_id );
	}

	/**
	 * Get a token's type by ID.
	 *
	 * @since 2.7.0
	 * @param id $token_id
	 * @return string
	 */
	public function get_token_type_by_id( $token_id ) {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare(
			"SELECT type FROM {$wpdb->prefix}woocommerce_payment_tokens WHERE token_id = %d",
			$token_id
		) );
	}

	/**
	 * Update's a tokens default status in the database. Used for quickly
	 * looping through tokens and setting their statuses instead of creating a bunch
	 * of objects.
	 *
	 * @since 2.7.0
	 * @param id $token_id
	 * @return string
	 */
	public function set_default_status( $token_id, $status = true ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'woocommerce_payment_tokens',
			array( 'is_default' => $status ),
			array(
				'token_id' => $token_id,
			)
		);
	}

}
