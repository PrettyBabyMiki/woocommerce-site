<?php
/**
 * Handle data for the current customers session.
 * Implements the WC_Session abstract class
 *
 * Long term plan will be, if https://github.com/ericmann/wp-session-manager/ gains traction
 * in WP core, this will be switched out to use it and maintain backwards compatibility :)
 *
 * Partly based on WP SESSION by Eric Mann.
 *
 * @class 		WC_Session_Handler
 * @version		2.0.0
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Session_Handler extends WC_Session {

	/** customer_id */
	private $_customer_id;

	/** cookie name */
	private $_cookie;

	/** session expiration timestamp */
	private $_session_expiration;

	/** cookie expiration timestamp */
	private $_expiration;

	/**
	 * Constructor for the session class.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->_cookie = 'wc_session_cookie_' . COOKIEHASH;

		if ( $cookie = $this->get_session_cookie() ) {
			$this->_customer_id        = $cookie[0];
			$this->_expiration         = $cookie[1];
			$this->_session_expiration = $cookie[2];

			// Update session if its close to expiring
			if ( time() > $this->_session_expiration ) {
				$this->set_expiration();
				update_option( '_wc_session_expires_' . $this->_customer_id, $this->_expiration );
			}

		} else {
			$this->set_expiration();
			$this->_customer_id = $this->generate_customer_id();
		}

		$this->_data = $this->get_session_data();

    	// Set/renew our cookie
    	$to_hash      = $this->_customer_id . $this->_expiration;
    	$cookie_hash  = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
    	$cookie_value = $this->_customer_id . '||' . $this->_expiration . '||' . $this->_session_expiration . '||' . $cookie_hash;

    	setcookie( $this->_cookie, $cookie_value, $this->_expiration, COOKIEPATH, COOKIE_DOMAIN, false, true );

    	// Actions
    	add_action( 'shutdown', array( $this, 'save_data' ), 20 );
    }

    /**
     * set_expiration function.
     *
     * @access private
     * @return void
     */
    private function set_expiration() {
	    $this->_session_expiration = time() + intval( apply_filters( 'wc_session_expiration', 60 * 60 * 47 ) ); // 47 Hours
		$this->_expiration  = time() + intval( apply_filters( 'wc_session_expiration', 60 * 60 * 48 ) ); // 48 Hours
    }

	/**
	 * generate_customer_id function.
	 *
	 * @access private
	 * @return mixed
	 */
	private function generate_customer_id() {
		if ( is_user_logged_in() )
			return get_current_user_id();
		else
			return wp_generate_password( 32 );
	}

	/**
	 * get_session_cookie function.
	 *
	 * @access private
	 * @return mixed
	 */
	private function get_session_cookie() {
		if ( ! isset( $_COOKIE[ $this->_cookie ] ) )
			return false;

		list( $customer_id, $cookie_expiration, $session_expiration, $cookie_hash ) = explode( '||', $_COOKIE[ $this->_cookie ] );

		// Validate hash
		$to_hash = $customer_id . $cookie_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( $hash != $cookie_hash )
			return false;

		return array( $customer_id, $cookie_expiration, $session_expiration, $cookie_hash );
	}

	/**
	 * get_session_data function.
	 *
	 * @access private
	 * @return array
	 */
	private function get_session_data() {
		return get_option( '_wc_session_' . $this->_customer_id, array() );
	}

    /**
     * save_data function.
     *
     * @access public
     * @return void
     */
    public function save_data() {
    	// Dirty if something changed - prevents saving nothing new
    	if ( $this->_dirty ) {
	    	if ( false === get_option( '_wc_session_' . $this->_customer_id ) ) {
		    	add_option( '_wc_session_' . $this->_customer_id, $this->_data, '', 'no' );
		    	add_option( '_wc_session_expires_' . $this->_customer_id, $this->_expiration, '', 'no' );
	    	} else {
		    	update_option( '_wc_session_' . $this->_customer_id, $this->_data );
	    	}
	    }
    }
}