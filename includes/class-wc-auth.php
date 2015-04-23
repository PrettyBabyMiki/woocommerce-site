<?php
/**
 * WooCommerce Auth
 *
 * Handles wc-auth endpoint requests
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Auth' ) ) :

class WC_Auth {

	/**
	 * Version
	 */
	const VERSION = 1;

	/**
	 * Setup class
	 *
	 * @since 2.4.0
	 */
	public function __construct() {
		// Add query vars
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

		// Register auth endpoint
		add_action( 'init', array( __CLASS__, 'add_endpoint' ), 0 );

		// Handle auth requests
		add_action( 'parse_request', array( $this, 'handle_auth_requests' ), 0 );
	}

	/**
	 * Add query vars
	 *
	 * @since  2.4.0
	 *
	 * @param  $vars
	 *
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'wc-auth-version';
		$vars[] = 'wc-auth-route';

		return $vars;
	}

	/**
	 * Add auth endpoint
	 *
	 * @since 2.4.0
	 */
	public static function add_endpoint() {
		add_rewrite_rule( '^wc-auth/v([1]{1})/(.*)?', 'index.php?wc-auth-version=$matches[1]&wc-auth-route=$matches[2]', 'top' );
	}

	/**
	 * Get scope name.
	 *
	 * @since 2.4.0
	 *
	 * @param  string $scope
	 *
	 * @return string
	 */
	protected function get_i18n_scope( $scope ) {
		$permissions = array(
			'read'       => __( 'Read', 'woocommerce' ),
			'write'      => __( 'Write', 'woocommerce' ),
			'read_write' => __( 'Read/Write', 'woocommerce' ),
		);

		return $permissions[ $scope ];
	}

	/**
	 * Build auth urls
	 *
	 * @since  2.4.0
	 *
	 * @param  array $data
	 * @param  string $endpoint
	 *
	 * @return string
	 */
	protected function build_url( $data, $endpoint ) {
		$url = wc_get_endpoint_url( 'wc-auth/v' . self::VERSION, $endpoint, get_home_url( '/' ) );

		return add_query_arg( array(
			'app_name'     => wc_clean( $data['app_name'] ),
			'user_id'      => wc_clean( $data['user_id'] ),
			'return_url'   => urlencode( $data['return_url'] ),
			'callback_url' => urlencode( $data['callback_url'] ),
			'scope'        => wc_clean( $data['scope'] ),
		), $url );
	}

	/**
	 * Make validation
	 */
	protected function make_validation() {
		$params = array(
			'app_name',
			'user_id',
			'return_url',
			'callback_url',
			'scope'
		);

		foreach ( $params as $param ) {
			if ( empty( $_REQUEST[ $param ] ) ) {
				throw new Exception( sprintf( __( 'Missing parameter %s', 'woocommerce' ), $param ) );
			}
		}

		if ( ! in_array( $_REQUEST['scope'], array( 'read', 'write', 'read_write' ) ) ) {
			throw new Exception( sprintf( __( 'Invalid scope %s', 'woocommerce' ), wc_clean( $_REQUEST['scope'] ) ) );
		}

		foreach ( array( 'return_url', 'callback_url' ) as $param ) {
			if ( false === filter_var( urldecode( $param ), FILTER_VALIDATE_URL ) ) {
				throw new Exception( sprintf( __( 'The %s is not a valid URL', 'woocommerce' ), $param ) );
			}
		}

		if ( 0 !== stripos( urldecode( $_REQUEST['callback_url'] ), 'https://' ) ) {
			throw new Exception( __( 'The callback_url need to be over SSL', 'woocommerce' ) );
		}
	}

	/**
	 * Get auth username.
	 *
	 * @param  string $app_name
	 * @param  string $user_id
	 *
	 * @return string
	 */
	protected function get_auth_username( $app_name, $user_id ) {
		return 'auth-user_' . sanitize_title( urldecode( $app_name ) . '_' . urldecode( $user_id ) );
	}

	/**
	 * Create auth user.
	 *
	 * @since  2.4.0
	 *
	 * @param  string $app_name
	 * @param  string $id
	 * @param  string $scope
	 *
	 * @return array
	 */
	protected function create_auth_user( $app_name, $id, $scope ) {
		$description = sprintf( __( 'Generic user created to grant API %s access to %s.', 'woocommerce' ), $this->get_i18n_scope( $scope ), wc_clean( $app_name ) );
		$user_login  = $this->get_auth_username( $app_name, $id );

		$userdata = array(
			'user_login'  => $user_login,
			'user_pass'   => wp_generate_password(),
			'description' => $description,
			'role'        => 'administrator' // @TODO: Need to review this role!
		);

		$user_id = wp_insert_user( $userdata );

		if ( is_wp_error( $user_id ) ) {
			throw new Exception( $user_id->get_error_message() );
		}

		// Created API keys.
		$consumer_key    = 'ck_' . hash( 'md5', $user_login . date( 'U' ) . mt_rand() );
		$consumer_secret = 'cs_' . hash( 'md5', $user_id . date( 'U' ) . mt_rand() );
		$permissions     = ( in_array( $scope, array( 'read', 'write', 'read_write' ) ) ) ? $scope : 'read';
		update_user_meta( $user_id, 'woocommerce_api_consumer_key', $consumer_key );
		update_user_meta( $user_id, 'woocommerce_api_consumer_secret', $consumer_secret );
		update_user_meta( $user_id, 'woocommerce_api_key_permissions', $permissions );

		return array(
			'user_id'         => $id,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'key_permissions' => $permissions
		);
	}

	/**
	 * Post consumer data.
	 *
	 * @since  2.4.0
	 *
	 * @param  array  $consumer_data
	 * @param  string $url
	 *
	 * @return bool
	 */
	protected function post_consumer_data( $consumer_data, $url ) {
		$params = array(
			'body'      => json_encode( $consumer_data ),
			'sslverify' => true,
			'timeout'   => 60,
			'headers'   => array(
				'Content-Type' => 'application/xml;charset=' . get_bloginfo( 'charset' ),
			)
		);

		$response = wp_remote_post( esc_url_raw( urldecode( $url ) ), $params );

		if ( is_wp_error( $response ) ) {
			throw new Exception( $response->get_error_message() );
		} else if ( 200 != $response['response']['code'] ) {
			throw new Exception( __( 'An error occurred in the request and at the time were unable to send the consumer data', 'woocommerce' ) );
		}

		return true;
	}

	/**
	 * Handle auth requests
	 *
	 * @since 2.4.0
	 */
	public function handle_auth_requests() {
		global $wp;

		if ( ! empty( $_GET['wc-auth-version'] ) ) {
			$wp->query_vars['wc-auth-version'] = $_GET['wc-auth-version'];
		}

		if ( ! empty( $_GET['wc-auth-route'] ) ) {
			$wp->query_vars['wc-auth-route'] = $_GET['wc-auth-route'];
		}

		// wc-auth endpoint requests
		if ( ! empty( $wp->query_vars['wc-auth-version'] ) && ! empty( $wp->query_vars['wc-auth-route'] ) ) {
			ob_start();

			try {
				$method = strtolower( wc_clean( $wp->query_vars['wc-auth-route'] ) );
				$this->make_validation();

				// Login endpoint
				if ( 'login' == $method && ! is_user_logged_in() ) {
					wc_get_template( 'auth/form-login.php', array(
						'app_name'     => $_REQUEST['app_name'],
						'return_url'   => add_query_arg( array( 'success' => 0, 'user_id' => wc_clean( $_REQUEST['user_id'] ) ), urldecode( $_REQUEST['return_url'] ) ),
						'redirect_url' => $this->build_url( $_REQUEST, 'authorize' ),
					) );

					exit;

				// Redirect with user is logged in
				} else if ( 'login' == $method && is_user_logged_in() ) {
					wp_redirect( esc_url_raw( $this->build_url( $_REQUEST, 'authorize' ) ) );
					exit;

				// Redirect with user is not logged in and trying to access the authorize endpoint
				} else if ( 'authorize' == $method && ! is_user_logged_in() ) {
					wp_redirect( esc_url_raw( $this->build_url( $_REQUEST, 'login' ) ) );
					exit;

				// Authorize endpoint
				} else if ( 'authorize' == $method && current_user_can( 'manage_woocommerce' ) ) {
					wc_get_template( 'auth/form-grant-access.php', array(
						'app_name'    => $_REQUEST['app_name'],
						'return_url'  => add_query_arg( array( 'success' => 0, 'user_id' => wc_clean( $_REQUEST['user_id'] ) ), urldecode( $_REQUEST['return_url'] ) ),
						'scope'       => $this->get_i18n_scope( wc_clean( $_REQUEST['scope'] ) ),
						'granted_url' => wp_nonce_url( $this->build_url( $_REQUEST, 'access_granted' ), 'wc_auth_grant_access', 'wc_auth_nonce' ),
						'logout_url'  => wp_logout_url( $this->build_url( $_REQUEST, 'login' ) )
					) );

					exit;

				// Granted access endpoint
				} else if ( 'access_granted' == $method && current_user_can( 'manage_woocommerce' ) ) {
					if ( ! isset( $_GET['wc_auth_nonce'] ) || ! wp_verify_nonce( $_GET['wc_auth_nonce'], 'wc_auth_grant_access' ) ) {
						throw new Exception( __( 'Invalid nonce verification', 'woocommerce' ) );
					}

					$consumer_data = $this->create_auth_user( $_REQUEST['app_name'], $_REQUEST['user_id'], $_REQUEST['scope'] );
					$response      = $this->post_consumer_data( $consumer_data, $_REQUEST['callback_url'] );

					if ( $response ) {
						wp_redirect( esc_url_raw( add_query_arg( array( 'success' => 1, 'user_id' => wc_clean( $_REQUEST['user_id'] ) ), urldecode( $_REQUEST['return_url'] ) ) ) );
						exit;
					}
				}

				wp_die( __( 'You do not have permissions to access this page!' ), __( 'Access Denied', 'woocommerce' ), array( 'response' => 401 ) );
			} catch ( Exception $e ) {
				wp_die( sprintf( __( 'Error: %s', 'woocommerce' ), $e->getMessage() ), __( 'Access Denied', 'woocommerce' ), array( 'response' => 401 ) );
			}
		}
	}
}

endif;

return new WC_Auth();
