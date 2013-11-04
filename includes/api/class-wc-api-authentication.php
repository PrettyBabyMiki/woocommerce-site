<?php
/**
 * WooCommerce API Authentication Class
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_API_Authentication {

	/**
	 * Setup class
	 *
	 * @since 2.1
	 * @return WC_API_Authentication
	 */
	public function __construct() {

		// this filter can be removed in order to provide unauthenticated access to the API for testing, etc
		add_filter( 'json_check_authentication', array( $this, 'authenticate' ) );

		add_filter( 'json_index', array( $this, 'maybe_declare_ssl_support' ) );

		// TODO: provide API key based permissions check using $args = apply_filters( 'json_dispatch_args', $args, $callback );
		// TODO: allow unauthenticated access to /products endpoint
	}

	/**
	 * Add "supports_ssl" capabilities to API index so consumers can determine the proper authentication method
	 *
	 * @since 2.1
	 * @param array $capabilities
	 * @return array
	 */
	public function maybe_declare_ssl_support( $capabilities ) {

		$capabilities['supports_ssl'] = ( 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) );

		return $capabilities;
	}

	/**
	 * Authenticate the request. The authentication method varies based on whether the request was made over SSL or not.
	 *
	 * @since 2.1
	 * @param WP_User $user
	 * @return null|WP_Error|WP_User
	 */
	public function authenticate( $user ) {

		// allow access to the index by default
		if ( '/' === WC()->api->server->path )
			return null;

		try {

			if ( is_ssl() )
				$user = $this->perform_ssl_authentication();
			else
				$user = $this->perform_oauth_authentication();

		} catch ( Exception $e ) {

			$user = new WP_Error( 'wc_api_authentication_error', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		return $user;
	}

	/**
	 * SSL-encrypted requests are not subject to sniffing or man-in-the-middle attacks, so the request can be authenticated
	 * by simply looking up the user associated with the given consumer key and confirming the secret key provided is valid
	 *
	 * @since 2.1
	 * @return mixed
	 * @throws Exception
	 */
	private function perform_ssl_authentication() {

		if ( empty( $_SERVER['PHP_AUTH_USER'] ) )
			throw new Exception( __( 'Consumer Key is missing', 'woocommerce' ), 404 );

		if ( empty( $_SERVER['PHP_AUTH_PW'] ) )
			throw new Exception( __( 'Secret Key is missing', 'woocommerce' ), 404 );

		$consumer_key = $_SERVER['PHP_AUTH_USER'];
		$secret_key   = $_SERVER['PHP_AUTH_PW'];

		$user = $this->get_user_by_consumer_key( $consumer_key );

		if ( ! $this->is_secret_key_valid( $user, $secret_key ) )
			throw new Exception( __( 'Secret Key is invalid', 'woocommerce'), 401 );

		return $user;
	}

	/**
	 * Perform OAuth 1.0a "one-legged" (http://oauthbible.com/#oauth-10a-one-legged) authentication for non-SSL requests
	 *
	 * This is required so API credentials cannot be sniffed or intercepted when making API requests over plain HTTP
	 *
	 * This follows the spec for simple OAuth 1.0a authentication (RFC 5849) as closely as possible, with two exceptions:
	 *
	 * 1) There is no token associated with request/responses, only consumer/secret keys are used
	 *
	 * 2) The OAuth parameters are included as part of the request query string instead of part of the Authorization header,
	 *    This is because there is no cross-OS function within PHP to get the raw Authorization header
	 *
	 * @TODO create consumer documentation for generating nonce/signatures for requests
	 *
	 * @link http://tools.ietf.org/html/rfc5849 for the full spec
	 * @since 2.1
	 * @return WP_User
	 * @throws Exception
	 */
	private function perform_oauth_authentication() {

		$params = WC()->api->server->params['GET'];

		$param_names =  array( 'oauth_consumer_key', 'oauth_timestamp', 'oauth_nonce', 'oauth_signature', 'oauth_signature_method' );

		// check for required OAuth parameters
		foreach ( $param_names as $param_name ) {

			if ( empty( $params ) )
				throw new Exception( sprintf( __( '%s parameter is missing', 'woocommerce' ), $param_name ) );
		}

		// fetch WP user by consumer key
		$user = $this->get_user_by_consumer_key( $params['oauth_consumer_key'] );

		// perform OAuth validation
		$this->check_oauth_signature( $user, $params );
		$this->check_oauth_timestamp_and_nonce( $user, $params['oauth_timestamp'], $params['oauth_nonce'] );

		// remove oauth params before further parsing
		foreach( $param_names as $param_name ) {
			unset( WC()->api->server->params[ $param_name ] );
		}

		// authentication successful, return user
		return $user;
	}

	/**
	 * Return the user for the given consumer key
	 *
	 * @since 2.1
	 * @param string $consumer_key
	 * @return WP_User
	 * @throws Exception
	 */
	private function get_user_by_consumer_key( $consumer_key ) {

		$user_query = new WP_User_Query(
			array(
				'meta_key' => 'woocommerce_api_consumer_key',
				'meta_value' => $consumer_key,
			)
		);

		$users = $user_query->get_results();

		if ( empty( $users[0] ) )
			throw new Exception( __( 'Consumer Key is invalid', 'woocommerce' ), 401 );

		return $users[0];
	}

	/**
	 * Check if the secret key provided for the given user is valid
	 *
	 * @since 2.1
	 * @param WP_User $user
	 * @param $secret_key
	 * @return bool
	 */
	private function is_secret_key_valid( WP_User $user, $secret_key ) {

		// TODO: consider hashing secret key prior to storing it using wp_hash_password(), but this would prevent user from seeing it more than once
		return $user->woocommerce_api_secret_key === $secret_key;
	}

	/**
	 * Verify that the consumer-provided request signature matches our generated signature, this ensures the consumer
	 * has a valid key/secret key
	 *
	 * @param WP_User $user
	 * @param array $params the request parameters
	 * @throws Exception
	 */
	private function check_oauth_signature( $user, $params ) {

		$http_method = strtoupper( WC()->api->server->method );

		$base_request_uri = rawurlencode( get_home_url( null, parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ), 'http' ) );

		// get the signature provided by the consumer and remove it from the parameters prior to checking the signature
		$consumer_signature = rawurldecode( $params['oauth_signature'] );
		unset( $params['oauth_signature'] );

		// normalize parameter key/values
		array_walk( $params, array( $this, 'normalize_parameters' ) );

		// sort parameters
		if ( ! uksort( $params, 'strcmp' ) )
			throw new Exception( __( 'Invalid Signature - failed to sort parameters', 'woocommerce' ), 401 );

		// form query string
		$query_params = array();
		foreach ( $params as $param_key => $param_value ) {

			$query_params[] = $param_key . '%3D' . $param_value;
		}
		$query_string = implode( '%26', $query_params );

		$string_to_sign = $http_method . '&' . $base_request_uri . '&' . $query_string;

		if ( $params['oauth_signature_method'] !== 'HMAC-SHA1' && $params['oauth_signature_method'] !== 'HMAC-SHA256' )
			throw new Exception( __( 'Invalid Signature - signature method is invalid', 'woocommerce' ), 401 );

		$hash_algorithm = strtolower( str_replace( 'HMAC-', '', $params['oauth_signature_method'] ) );

		$signature = base64_encode( hash_hmac( $hash_algorithm, $string_to_sign, $user->woocommerce_api_secret_key, true ) );

		if ( $signature !== $consumer_signature )
			throw new Exception( __( 'Invalid Signature - provided signature does not match', 'woocommerce' ), 401 );
	}

	/**
	 * Normalize each parameter by assuming each parameter may have already been encoded, so attempt to decode, and then
	 * re-encode according to RFC 3986
	 *
	 * @since 2.1
	 * @see rawurlencode()
	 * @param $key
	 * @param $value
	 */
	private function normalize_parameters( &$key, &$value ) {

		$key = rawurlencode( rawurldecode( $key ) );
		$value = rawurlencode( rawurldecode( $value ) );
	}

	/**
	 * Verify that the timestamp and nonce provided with the request are valid. This prevents replay attacks where
	 * an attacker could attempt to re-send an intercepted request at a later time.
	 *
	 * - A timestamp is valid if it is within 15 minutes of now
	 * - A nonce is valid if it has not been used within the last 15 minutes
	 *
	 * @param WP_User $user
	 * @param int $timestamp the unix timestamp for when the request was made
	 * @param string $nonce a unique (for the given user) 32 alphanumeric string, consumer-generated
	 * @throws Exception
	 */
	private function check_oauth_timestamp_and_nonce( $user, $timestamp, $nonce ) {

		$valid_window = 15 * 60; // 15 minute window

		if ( ( $timestamp < time() - $valid_window ) ||  ( $timestamp > time() + $valid_window ) )
			throw new Exception( __( 'Invalid timestamp', 'woocommerce' ) );

		$used_nonces = $user->woocommerce_api_nonces;

		if ( empty( $used_nonces ) )
			$used_nonces = array();

		if ( in_array( $nonce, $used_nonces ) )
			throw new Exception( __( 'Invalid nonce - nonce has already been used', 'woocommerce' ) );

		$used_nonces[ $timestamp ] = $nonce;

		// remove expired nonces
		foreach( $used_nonces as $nonce_timestamp => $nonce ) {

			if ( $nonce_timestamp < $valid_window )
				unset( $used_nonces[ $nonce_timestamp ] );
		}

		update_user_meta( $user->ID, 'woocommerce_api_nonces', $used_nonces );
	}

}
