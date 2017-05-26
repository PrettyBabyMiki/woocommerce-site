<?php
/**
 * WooCommerce API Authentication Class
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.1.0
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_API_Authentication {

	/**
	 * Setup class
	 *
	 * @since 2.1
	 * @return WC_API_Authentication
	 */
	public function __construct() {

		// To disable authentication, hook into this filter at a later priority and return a valid WP_User
		add_filter( 'woocommerce_api_check_authentication', array( $this, 'authenticate' ), 0 );
	}

	/**
	 * Authenticate the request. The authentication method varies based on whether the request was made over SSL or not.
	 *
	 * @since 2.1
	 * @param WP_User $user
	 * @return null|WP_Error|WP_User
	 */
	public function authenticate( $user ) {

		// Allow access to the index by default
		if ( '/' === WC()->api->server->path ) {
			return new WP_User( 0 );
		}

		try {
			if ( is_ssl() ) {
				$keys = $this->perform_ssl_authentication();
			} else {
				$keys = $this->perform_oauth_authentication();
			}

			// Check API key-specific permission
			$this->check_api_key_permissions( $keys['permissions'] );

			$user = $this->get_user_by_id( $keys['user_id'] );

			$this->update_api_key_last_access( $keys['key_id'] );

		} catch ( Exception $e ) {
			$user = new WP_Error( 'woocommerce_api_authentication_error', $e->getMessage(), array( 'status' => $e->getCode() ) );
		}

		return $user;
	}

	/**
	 * SSL-encrypted requests are not subject to sniffing or man-in-the-middle
	 * attacks, so the request can be authenticated by simply looking up the user
	 * associated with the given consumer key and confirming the consumer secret
	 * provided is valid
	 *
	 * @since 2.1
	 * @return array
	 * @throws Exception
	 */
	private function perform_ssl_authentication() {
		$params = WC()->api->server->params['GET'];

		// if the $_GET parameters are present, use those first
		if ( ! empty( $params['consumer_key'] ) && ! empty( $params['consumer_secret'] ) ) {
			$keys = $this->get_keys_by_consumer_key( $params['consumer_key'] );

			if ( ! $this->is_consumer_secret_valid( $keys['consumer_secret'], $params['consumer_secret'] ) ) {
				throw new Exception( __( 'Consumer secret is invalid.', 'woocommerce' ), 401 );
			}

			return $keys;
		}

		// if the above is not present, we will do full basic auth
		if ( empty( $_SERVER['PHP_AUTH_USER'] ) || empty( $_SERVER['PHP_AUTH_PW'] ) ) {
			$this->exit_with_unauthorized_headers();
		}

		$keys = $this->get_keys_by_consumer_key( $_SERVER['PHP_AUTH_USER'] );

		if ( ! $this->is_consumer_secret_valid( $keys['consumer_secret'], $_SERVER['PHP_AUTH_PW'] ) ) {
			$this->exit_with_unauthorized_headers();
		}

		return $keys;
	}

	/**
	 * If the consumer_key and consumer_secret $_GET parameters are NOT provided
	 * and the Basic auth headers are either not present or the consumer secret does not match the consumer
	 * key provided, then return the correct Basic headers and an error message.
	 *
	 * @since 2.4
	 */
	private function exit_with_unauthorized_headers() {
		$auth_message = __( 'WooCommerce API. Use a consumer key in the username field and a consumer secret in the password field.', 'woocommerce' );
		header( 'WWW-Authenticate: Basic realm="' . $auth_message . '"' );
		header( 'HTTP/1.0 401 Unauthorized' );
		throw new Exception( __( 'Consumer Secret is invalid.', 'woocommerce' ), 401 );
	}

	/**
	 * Perform OAuth 1.0a "one-legged" (http://oauthbible.com/#oauth-10a-one-legged) authentication for non-SSL requests
	 *
	 * This is required so API credentials cannot be sniffed or intercepted when making API requests over plain HTTP
	 *
	 * This follows the spec for simple OAuth 1.0a authentication (RFC 5849) as closely as possible, with two exceptions:
	 *
	 * 1) There is no token associated with request/responses, only consumer keys/secrets are used
	 *
	 * 2) The OAuth parameters are included as part of the request query string instead of part of the Authorization header,
	 *    This is because there is no cross-OS function within PHP to get the raw Authorization header
	 *
	 * @link http://tools.ietf.org/html/rfc5849 for the full spec
	 * @since 2.1
	 * @return array
	 * @throws Exception
	 */
	private function perform_oauth_authentication() {

		$params = WC()->api->server->params['GET'];

		$param_names = array( 'oauth_consumer_key', 'oauth_timestamp', 'oauth_nonce', 'oauth_signature', 'oauth_signature_method' );

		// Check for required OAuth parameters
		foreach ( $param_names as $param_name ) {

			if ( empty( $params[ $param_name ] ) ) {
				throw new Exception( sprintf( __( '%s parameter is missing', 'woocommerce' ), $param_name ), 404 );
			}
		}

		// Fetch WP user by consumer key
		$keys = $this->get_keys_by_consumer_key( $params['oauth_consumer_key'] );

		// Perform OAuth validation
		$this->check_oauth_signature( $keys, $params );
		$this->check_oauth_timestamp_and_nonce( $keys, $params['oauth_timestamp'], $params['oauth_nonce'] );

		// Authentication successful, return user
		return $keys;
	}

	/**
	 * Return the keys for the given consumer key
	 *
	 * @since 2.4.0
	 * @param string $consumer_key
	 * @return array
	 * @throws Exception
	 */
	private function get_keys_by_consumer_key( $consumer_key ) {
		global $wpdb;

		$consumer_key = wc_api_hash( sanitize_text_field( $consumer_key ) );

		$keys = $wpdb->get_row( $wpdb->prepare( "
			SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE consumer_key = '%s'
		", $consumer_key ), ARRAY_A );

		if ( empty( $keys ) ) {
			throw new Exception( __( 'Consumer key is invalid.', 'woocommerce' ), 401 );
		}

		return $keys;
	}

	/**
	 * Get user by ID
	 *
	 * @since  2.4.0
	 *
	 * @param  int $user_id
	 *
	 * @return WP_User
	 * @throws Exception
	 */
	private function get_user_by_id( $user_id ) {
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			throw new Exception( __( 'API user is invalid', 'woocommerce' ), 401 );
		}

		return $user;
	}

	/**
	 * Check if the consumer secret provided for the given user is valid
	 *
	 * @since 2.1
	 * @param string $keys_consumer_secret
	 * @param string $consumer_secret
	 * @return bool
	 */
	private function is_consumer_secret_valid( $keys_consumer_secret, $consumer_secret ) {
		return hash_equals( $keys_consumer_secret, $consumer_secret );
	}

	/**
	 * Verify that the consumer-provided request signature matches our generated signature, this ensures the consumer
	 * has a valid key/secret
	 *
	 * @param array $keys
	 * @param array $params the request parameters
	 * @throws Exception
	 */
	private function check_oauth_signature( $keys, $params ) {
		$http_method = strtoupper( WC()->api->server->method );

		$server_path = WC()->api->server->path;

		// if the requested URL has a trailingslash, make sure our base URL does as well
		if ( isset( $_SERVER['REDIRECT_URL'] ) && '/' === substr( $_SERVER['REDIRECT_URL'], -1 ) ) {
			$server_path .= '/';
		}

		$base_request_uri = rawurlencode( untrailingslashit( get_woocommerce_api_url( '' ) ) . $server_path );

		// Get the signature provided by the consumer and remove it from the parameters prior to checking the signature
		$consumer_signature = rawurldecode( str_replace( ' ', '+', $params['oauth_signature'] ) );
		unset( $params['oauth_signature'] );

		// Sort parameters
		if ( ! uksort( $params, 'strcmp' ) ) {
			throw new Exception( __( 'Invalid signature - failed to sort parameters.', 'woocommerce' ), 401 );
		}

		// Normalize parameter key/values
		$params = $this->normalize_parameters( $params );
		$query_parameters = array();
		foreach ( $params as $param_key => $param_value ) {
			if ( is_array( $param_value ) ) {
				foreach ( $param_value as $param_key_inner => $param_value_inner ) {
					$query_parameters[] = $param_key . '%255B' . $param_key_inner . '%255D%3D' . $param_value_inner;
				}
			} else {
				$query_parameters[] = $param_key . '%3D' . $param_value; // join with equals sign
			}
		}
		$query_string = implode( '%26', $query_parameters ); // join with ampersand

		$string_to_sign = $http_method . '&' . $base_request_uri . '&' . $query_string;

		if ( 'HMAC-SHA1' !== $params['oauth_signature_method'] && 'HMAC-SHA256' !== $params['oauth_signature_method'] ) {
			throw new Exception( __( 'Invalid signature - signature method is invalid.', 'woocommerce' ), 401 );
		}

		$hash_algorithm = strtolower( str_replace( 'HMAC-', '', $params['oauth_signature_method'] ) );

		$secret = $keys['consumer_secret'] . '&';
		$signature = base64_encode( hash_hmac( $hash_algorithm, $string_to_sign, $secret, true ) );

		if ( ! hash_equals( $signature, $consumer_signature ) ) {
			throw new Exception( __( 'Invalid signature - provided signature does not match.', 'woocommerce' ), 401 );
		}
	}

	/**
	 * Normalize each parameter by assuming each parameter may have already been
	 * encoded, so attempt to decode, and then re-encode according to RFC 3986
	 *
	 * Note both the key and value is normalized so a filter param like:
	 *
	 * 'filter[period]' => 'week'
	 *
	 * is encoded to:
	 *
	 * 'filter%5Bperiod%5D' => 'week'
	 *
	 * This conforms to the OAuth 1.0a spec which indicates the entire query string
	 * should be URL encoded
	 *
	 * @since 2.1
	 * @see rawurlencode()
	 * @param array $parameters un-normalized pararmeters
	 * @return array normalized parameters
	 */
	private function normalize_parameters( $parameters ) {
		$keys = WC_API_Authentication::urlencode_rfc3986( array_keys( $parameters ) );
		$values = WC_API_Authentication::urlencode_rfc3986( array_values( $parameters ) );
		$parameters = array_combine( $keys, $values );
		return $parameters;
	}

	/**
	 * Encodes a value according to RFC 3986. Supports multidimensional arrays.
	 *
	 * @since 2.4
	 * @param  string|array $value The value to encode
	 * @return string|array        Encoded values
	 */
	public static function urlencode_rfc3986( $value ) {
		if ( is_array( $value ) ) {
			return array_map( array( 'WC_API_Authentication', 'urlencode_rfc3986' ), $value );
		} else {
			// Percent symbols (%) must be double-encoded
			return str_replace( '%', '%25', rawurlencode( rawurldecode( $value ) ) );
		}
	}

	/**
	 * Verify that the timestamp and nonce provided with the request are valid. This prevents replay attacks where
	 * an attacker could attempt to re-send an intercepted request at a later time.
	 *
	 * - A timestamp is valid if it is within 15 minutes of now
	 * - A nonce is valid if it has not been used within the last 15 minutes
	 *
	 * @param array $keys
	 * @param int $timestamp the unix timestamp for when the request was made
	 * @param string $nonce a unique (for the given user) 32 alphanumeric string, consumer-generated
	 * @throws Exception
	 */
	private function check_oauth_timestamp_and_nonce( $keys, $timestamp, $nonce ) {
		global $wpdb;

		$valid_window = 15 * 60; // 15 minute window

		if ( ( $timestamp < time() - $valid_window ) || ( $timestamp > time() + $valid_window ) ) {
			throw new Exception( __( 'Invalid timestamp.', 'woocommerce' ), 401 );
		}

		$used_nonces = maybe_unserialize( $keys['nonces'] );

		if ( empty( $used_nonces ) ) {
			$used_nonces = array();
		}

		if ( in_array( $nonce, $used_nonces ) ) {
			throw new Exception( __( 'Invalid nonce - nonce has already been used.', 'woocommerce' ), 401 );
		}

		$used_nonces[ $timestamp ] = $nonce;

		// Remove expired nonces
		foreach ( $used_nonces as $nonce_timestamp => $nonce ) {
			if ( $nonce_timestamp < ( time() - $valid_window ) ) {
				unset( $used_nonces[ $nonce_timestamp ] );
			}
		}

		$used_nonces = maybe_serialize( $used_nonces );

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_api_keys',
			array( 'nonces' => $used_nonces ),
			array( 'key_id' => $keys['key_id'] ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Check that the API keys provided have the proper key-specific permissions to either read or write API resources
	 *
	 * @param string $key_permissions
	 * @throws Exception if the permission check fails
	 */
	public function check_api_key_permissions( $key_permissions ) {
		switch ( WC()->api->server->method ) {

			case 'HEAD':
			case 'GET':
				if ( 'read' !== $key_permissions && 'read_write' !== $key_permissions ) {
					throw new Exception( __( 'The API key provided does not have read permissions.', 'woocommerce' ), 401 );
				}
				break;

			case 'POST':
			case 'PUT':
			case 'PATCH':
			case 'DELETE':
				if ( 'write' !== $key_permissions && 'read_write' !== $key_permissions ) {
					throw new Exception( __( 'The API key provided does not have write permissions.', 'woocommerce' ), 401 );
				}
				break;
		}
	}

	/**
	 * Updated API Key last access datetime
	 *
	 * @since 2.4.0
	 *
	 * @param int $key_id
	 */
	private function update_api_key_last_access( $key_id ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'woocommerce_api_keys',
			array( 'last_access' => current_time( 'mysql' ) ),
			array( 'key_id' => $key_id ),
			array( '%s' ),
			array( '%d' )
		);
	}
}
