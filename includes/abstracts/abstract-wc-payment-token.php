<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( WC_ABSPATH . 'includes/legacy/abstract-wc-legacy-payment-token.php' );

/**
 * WooCommerce Payment Token.
 *
 * Representation of a general payment token to be extended by individuals types of tokens
 * examples: Credit Card, eCheck.
 *
 * @class 		WC_Payment_Token
 * @version     2.7.0
 * @since		2.6.0
 * @package		WooCommerce/Abstracts
 * @category	Abstract Class
 * @author		WooThemes
 */
 abstract class WC_Payment_Token extends WC_Legacy_Payment_Token {

	/**
	 * Token Data (stored in the payment_tokens table).
	 * @var array
	 */
	protected $data = array(
		 'gateway_id' => '',
		 'token'      => '',
		 'is_default' => false,
		 'user_id'    => 0,
		 'type'       => '',
	);

	/**
	 * Initialize a payment token.
	 *
	 * These fields are accepted by all payment tokens:
	 * is_default   - boolean Optional - Indicates this is the default payment token for a user
	 * token        - string  Required - The actual token to store
	 * gateway_id   - string  Required - Identifier for the gateway this token is associated with
	 * user_id      - int     Optional - ID for the user this token is associated with. 0 if this token is not associated with a user
	 *
	 * @since 2.6.0
	 * @param mixed $token
	 */
	public function __construct( $token = '' ) {
		// Set token type (cc, echeck)
		if ( ! empty( $this->type ) ) {
			$this->set_type( $this->type );
		}

		if ( is_numeric( $token ) ) {
			$this->set_id( $token );
		} elseif ( is_object( $token ) ) {
			$token_id = $token->get_id();
			if ( ! empty( $token_id ) ) {
				$this->set_id( $token->get_id() );
			}
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( 'payment-token' );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/*
	 |--------------------------------------------------------------------------
	 | Getters
	 |--------------------------------------------------------------------------
	 */

	/**
	 * Returns the raw payment token.
	 *
	 * @since  2.6.0
	 * @param  string $context
	 * @return string Raw token
	 */
	public function get_token( $context = 'view' ) {
		return $this->get_prop( 'token', $context );
	}

	/**
	 * Returns the type of this payment token (CC, eCheck, or something else).
	 *
	 * @since  2.6.0
	 * @param  string $context
	 * @return string Payment Token Type (CC, eCheck)
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * Get type to display to user.
	 * Get's overwritten by child classes.
	 *
	 * @since  2.6.0
	 * @param  string $context
	 * @return string
	 */
	public function get_display_name( $context = 'view' ) {
		return $this->get_type( $context );
	}

	/**
	 * Returns the user ID associated with the token or false if this token is not associated.
	 *
	 * @since 2.6.0
	 * @param  string $context
	 * @return int User ID if this token is associated with a user or 0 if no user is associated
	 */
	public function get_user_id( $context = 'view' ) {
		return $this->get_prop( 'user_id', $context );
	}

	/**
	 * Returns the ID of the gateway associated with this payment token.
	 *
	 * @since 2.6.0
	 * @param  string $context
	 * @return string Gateway ID
	 */
	public function get_gateway_id( $context = 'view' ) {
		return $this->get_prop( 'gateway_id', $context );
	}

	/**
	 * Returns the ID of the gateway associated with this payment token.
	 *
	 * @since 2.6.0
	 * @param  string $context
	 * @return string Gateway ID
	 */
	public function get_is_default( $context = 'view' ) {
		return $this->get_prop( 'is_default', $context );
	}

	/*
	 |--------------------------------------------------------------------------
	 | Setters
	 |--------------------------------------------------------------------------
	 */

	/**
	 * Set the raw payment token.
	 *
	 * @since 2.6.0
	 * @param string $token
	 */
	public function set_token( $token ) {
		$this->set_prop( 'token', $token );
	}

	/**
	 * Sets the type of this payment token (CC, eCheck, or something else).
	 *
	 * @since 2.7.0
	 * @param string Payment Token Type (CC, eCheck)
	 */
	public function set_type( $type ) {
		return $this->set_prop( 'type', $type );
	}

	/**
	 * Set the user ID for the user associated with this order.
	 *
	 * @since 2.6.0
	 * @param int $user_id
	 */
	public function set_user_id( $user_id ) {
		$this->set_prop( 'user_id', absint( $user_id ) );
	}

	/**
	 * Set the gateway ID.
	 *
	 * @since 2.6.0
	 * @param string $gateway_id
	 */
	public function set_gateway_id( $gateway_id ) {
		$this->set_prop( 'gateway_id', $gateway_id );
	}

	/**
	 * Marks the payment as default or non-default.
	 * @since 2.6.0
	 * @param boolean $is_default True or false
	 */
	public function set_default( $is_default ) {
		$this->set_prop( 'is_default', (bool) $is_default );
	}

	/*
	 |--------------------------------------------------------------------------
	 | Other Methods
	 |--------------------------------------------------------------------------
	 */

	/**
	 * Returns if the token is marked as default.
	 *
	 * @since 2.6.0
	 * @return boolean True if the token is default
	 */
	public function is_default() {
		return (bool) $this->get_prop( 'is_default', 'view' );
	}

	/**
	 * Validate basic token info (token and type are required).
	 * @since 2.6.0
	 * @return boolean True if the passed data is valid
	 */
	public function validate() {
		$token = $this->get_prop( 'token', 'edit' );
		if ( empty( $token ) ) {
			return false;
		}

		$type = $this->get_prop( 'type', 'edit' );
		if ( empty( $type ) ) {
			return false;
		}

		return true;
	}

}
