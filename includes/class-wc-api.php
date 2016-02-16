<?php
/**
 * WooCommerce API
 *
 * Handles WC-API endpoint requests.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_API' ) ) :

class WC_API {

	/**
	 * WP REST API namespace/version.
	 */
	const REST_API_NAMESPACE = 'wc/v1';

	/**
	 * This is the major version for the REST API and takes
	 * first-order position in endpoint URLs.
	 *
	 * @deprecated 2.6.0
	 * @var string
	 */
	const VERSION = '3.1.0';

	/**
	 * The REST API server.
	 *
	 * @deprecated 2.6.0
	 * @var WC_API_Server
	 */
	public $server;

	/**
	 * REST API authentication class instance.
	 *
	 * @deprecated 2.6.0
	 * @var WC_API_Authentication
	 */
	public $authentication;

	/**
	 * Setup class.
	 *
	 * @since 2.0
	 * @return WC_API
	 */
	public function __construct() {
		// Include REST API classes.
		$this->rest_api_includes();

		// Add query vars.
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

		// Register API endpoints.
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );

		// Handle REST API requests.
		add_action( 'parse_request', array( $this, 'handle_rest_api_requests' ), 0 );

		// Handle wc-api endpoint requests.
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );

		// Ensure payment gateways are initialized in time for API requests.
		add_action( 'woocommerce_api_request', array( 'WC_Payment_Gateways', 'instance' ), 0 );

		// WP REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Add new query vars.
	 *
	 * @since 2.0
	 * @param array $vars
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'wc-api';
		$vars[] = 'wc-api-version'; // Deprecated since 2.6.0.
		$vars[] = 'wc-api-route'; // Deprecated since 2.6.0.

		return $vars;
	}

	/**
	 * Add new endpoints.
	 *
	 * @since 2.0
	 */
	public static function add_endpoint() {

		// REST API, deprecated since 2.6.0.
		add_rewrite_rule( '^wc-api/v([1-3]{1})/?$', 'index.php?wc-api-version=$matches[1]&wc-api-route=/', 'top' );
		add_rewrite_rule( '^wc-api/v([1-3]{1})(.*)?', 'index.php?wc-api-version=$matches[1]&wc-api-route=$matches[2]', 'top' );

		// WC API for payment gateway IPNs, etc
		add_rewrite_endpoint( 'wc-api', EP_ALL );
	}


	/**
	 * Handle REST API requests.
	 *
	 * @since 2.2
	 * @deprecated 2.6.0
	 */
	public function handle_rest_api_requests() {
		global $wp;

		if ( ! empty( $_GET['wc-api-version'] ) ) {
			$wp->query_vars['wc-api-version'] = $_GET['wc-api-version'];
		}

		if ( ! empty( $_GET['wc-api-route'] ) ) {
			$wp->query_vars['wc-api-route'] = $_GET['wc-api-route'];
		}

		// REST API request
		if ( ! empty( $wp->query_vars['wc-api-version'] ) && ! empty( $wp->query_vars['wc-api-route'] ) ) {

			define( 'WC_API_REQUEST', true );
			define( 'WC_API_REQUEST_VERSION', absint( $wp->query_vars['wc-api-version'] ) );

			// legacy v1 API request
			if ( 1 === WC_API_REQUEST_VERSION ) {
				$this->handle_v1_rest_api_request();
			} else if ( 2 === WC_API_REQUEST_VERSION ) {
				$this->handle_v2_rest_api_request();
			} else {
				$this->includes();

				$this->server = new WC_API_Server( $wp->query_vars['wc-api-route'] );

				// load API resource classes
				$this->register_resources( $this->server );

				// Fire off the request
				$this->server->serve_request();
			}

			exit;
		}
	}

	/**
	 * Include required files for REST API request.
	 *
	 * @since 2.1
	 * @deprecated 2.6.0
	 */
	public function includes() {

		// API server / response handlers
		include_once( 'api/legacy/v3/class-wc-api-exception.php' );
		include_once( 'api/legacy/v3/class-wc-api-server.php' );
		include_once( 'api/legacy/v3/interface-wc-api-handler.php' );
		include_once( 'api/legacy/v3/class-wc-api-json-handler.php' );

		// authentication
		include_once( 'api/legacy/v3/class-wc-api-authentication.php' );
		$this->authentication = new WC_API_Authentication();

		include_once( 'api/legacy/v3/class-wc-api-resource.php' );
		include_once( 'api/legacy/v3/class-wc-api-coupons.php' );
		include_once( 'api/legacy/v3/class-wc-api-customers.php' );
		include_once( 'api/legacy/v3/class-wc-api-orders.php' );
		include_once( 'api/legacy/v3/class-wc-api-products.php' );
		include_once( 'api/legacy/v3/class-wc-api-reports.php' );
		include_once( 'api/legacy/v3/class-wc-api-taxes.php' );
		include_once( 'api/legacy/v3/class-wc-api-webhooks.php' );

		// allow plugins to load other response handlers or resource classes
		do_action( 'woocommerce_api_loaded' );
	}

	/**
	 * Register available API resources.
	 *
	 * @since 2.1
	 * @deprecated 2.6.0
	 * @param WC_API_Server $server the REST server
	 */
	public function register_resources( $server ) {

		$api_classes = apply_filters( 'woocommerce_api_classes',
			array(
				'WC_API_Coupons',
				'WC_API_Customers',
				'WC_API_Orders',
				'WC_API_Products',
				'WC_API_Reports',
				'WC_API_Taxes',
				'WC_API_Webhooks',
			)
		);

		foreach ( $api_classes as $api_class ) {
			$this->$api_class = new $api_class( $server );
		}
	}


	/**
	 * Handle legacy v1 REST API requests.
	 *
	 * @since 2.2
	 * @deprecated 2.6.0
	 */
	private function handle_v1_rest_api_request() {

		// include legacy required files for v1 REST API request
		include_once( 'api/legacy/v1/class-wc-api-server.php' );
		include_once( 'api/legacy/v1/interface-wc-api-handler.php' );
		include_once( 'api/legacy/v1/class-wc-api-json-handler.php' );
		include_once( 'api/legacy/v1/class-wc-api-xml-handler.php' );

		include_once( 'api/legacy/v1/class-wc-api-authentication.php' );
		$this->authentication = new WC_API_Authentication();

		include_once( 'api/legacy/v1/class-wc-api-resource.php' );
		include_once( 'api/legacy/v1/class-wc-api-coupons.php' );
		include_once( 'api/legacy/v1/class-wc-api-customers.php' );
		include_once( 'api/legacy/v1/class-wc-api-orders.php' );
		include_once( 'api/legacy/v1/class-wc-api-products.php' );
		include_once( 'api/legacy/v1/class-wc-api-reports.php' );

		// allow plugins to load other response handlers or resource classes
		do_action( 'woocommerce_api_loaded' );

		$this->server = new WC_API_Server( $GLOBALS['wp']->query_vars['wc-api-route'] );

		// Register available resources for legacy v1 REST API request
		$api_classes = apply_filters( 'woocommerce_api_classes',
			array(
				'WC_API_Customers',
				'WC_API_Orders',
				'WC_API_Products',
				'WC_API_Coupons',
				'WC_API_Reports',
			)
		);

		foreach ( $api_classes as $api_class ) {
			$this->$api_class = new $api_class( $this->server );
		}

		// Fire off the request
		$this->server->serve_request();
	}

	/**
	 * Handle legacy v2 REST API requests.
	 *
	 * @since 2.4
	 * @deprecated 2.6.0
	 */
	private function handle_v2_rest_api_request() {
		include_once( 'api/legacy/v2/class-wc-api-exception.php' );
		include_once( 'api/legacy/v2/class-wc-api-server.php' );
		include_once( 'api/legacy/v2/interface-wc-api-handler.php' );
		include_once( 'api/legacy/v2/class-wc-api-json-handler.php' );

		include_once( 'api/legacy/v2/class-wc-api-authentication.php' );
		$this->authentication = new WC_API_Authentication();

		include_once( 'api/legacy/v2/class-wc-api-resource.php' );
		include_once( 'api/legacy/v2/class-wc-api-coupons.php' );
		include_once( 'api/legacy/v2/class-wc-api-customers.php' );
		include_once( 'api/legacy/v2/class-wc-api-orders.php' );
		include_once( 'api/legacy/v2/class-wc-api-products.php' );
		include_once( 'api/legacy/v2/class-wc-api-reports.php' );
		include_once( 'api/legacy/v2/class-wc-api-webhooks.php' );

		// allow plugins to load other response handlers or resource classes
		do_action( 'woocommerce_api_loaded' );

		$this->server = new WC_API_Server( $GLOBALS['wp']->query_vars['wc-api-route'] );

		// Register available resources for legacy v2 REST API request
		$api_classes = apply_filters( 'woocommerce_api_classes',
			array(
				'WC_API_Customers',
				'WC_API_Orders',
				'WC_API_Products',
				'WC_API_Coupons',
				'WC_API_Reports',
				'WC_API_Webhooks',
			)
		);

		foreach ( $api_classes as $api_class ) {
			$this->$api_class = new $api_class( $this->server );
		}

		// Fire off the request
		$this->server->serve_request();
	}

	/**
	 * API request - Trigger any API requests.
	 *
	 * @since   2.0
	 * @version 2.4
	 */
	public function handle_api_requests() {
		global $wp;

		if ( ! empty( $_GET['wc-api'] ) ) {
			$wp->query_vars['wc-api'] = $_GET['wc-api'];
		}

		// wc-api endpoint requests
		if ( ! empty( $wp->query_vars['wc-api'] ) ) {

			// Buffer, we won't want any output here
			ob_start();

			// No cache headers
			nocache_headers();

			// Clean the API request
			$api_request = strtolower( wc_clean( $wp->query_vars['wc-api'] ) );

			// Trigger generic action before request hook
			do_action( 'woocommerce_api_request', $api_request );

			// Is there actually something hooked into this API request? If not trigger 400 - Bad request
			status_header( has_action( 'woocommerce_api_' . $api_request ) ? 200 : 400 );

			// Trigger an action which plugins can hook into to fulfill the request
			do_action( 'woocommerce_api_' . $api_request );

			// Done, clear buffer and exit
			ob_end_clean();
			die('-1');
		}
	}

	/**
	 * Include REST API classes.
	 *
	 * @since 2.6.0
	 */
	public function rest_api_includes() {
		if ( ! class_exists( 'WP_REST_Controller' ) ) {
			include_once( 'vendor/class-wp-rest-controller.php' );
		}

		include_once( 'abstracts/abstract-wc-rest-controller.php' );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 2.6.0
	 */
	public function register_rest_routes() {
		$controllers = array();

		foreach ( $controllers as $controller ) {
			$_controller = new $controller();
			if ( ! is_subclass_of( $_controller, 'WC_REST_Controller' ) ) {
				continue;
			}

			$this->$controller = $_controller;
			$this->$controller->register_routes();
		}
	}
}

endif;

return new WC_API();
