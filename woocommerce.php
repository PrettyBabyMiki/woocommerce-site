<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: http://www.woothemes.com/woocommerce/
 * Description: An e-commerce toolkit that helps you sell anything. Beautifully.
 * Version: 2.3-bleeding
 * Author: WooThemes
 * Author URI: http://woothemes.com
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerce
 * @category Core
 * @author WooThemes
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WooCommerce' ) ) :

/**
 * Main WooCommerce Class
 *
 * @class WooCommerce
 * @version	2.1.0
 */
final class WooCommerce {

	/**
	 * @var string
	 */
	public $version = '2.3.0';

	/**
	 * @var WooCommerce The single instance of the class
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * @var WC_Session session
	 */
	public $session = null;

	/**
	 * @var WC_Query $query
	 */
	public $query = null;

	/**
	 * @var WC_Product_Factory $product_factory
	 */
	public $product_factory = null;

	/**
	 * @var WC_Countries $countries
	 */
	public $countries = null;

	/**
	 * @var WC_Integrations $integrations
	 */
	public $integrations = null;

	/**
	 * @var WC_Cart $cart
	 */
	public $cart = null;

	/**
	 * @var WC_Customer $customer
	 */
	public $customer = null;

	/**
	 * @var WC_Order_Factory $order_factory
	 */
	public $order_factory = null;

	/**
	 * Main WooCommerce Instance
	 *
	 * Ensures only one instance of WooCommerce is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @see WC()
	 * @return WooCommerce - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '2.1' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.1
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '2.1' );
	}

	/**
	 * WooCommerce Constructor.
	 * @access public
	 * @return WooCommerce
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();

		// Init WC API
		$this->api = new WC_API();

		// Hooks
		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'WC_Shortcodes', 'init' ) );
		add_action( 'widgets_init', array( $this, 'include_widgets' ) );

		// Loaded action
		do_action( 'woocommerce_loaded' );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( method_exists( $this, $key ) ) {
			return $this->$key();
		}

		switch( $key ) {
			case 'template_url':
				_deprecated_argument( 'Woocommerce->template_url', '2.1', 'Use WC()->template_path()' );
				return $this->template_path();
			case 'messages':
				_deprecated_argument( 'Woocommerce->messages', '2.1', 'Use wc_get_notices' );
				return wc_get_notices( 'success' );
			case 'errors':
				_deprecated_argument( 'Woocommerce->errors', '2.1', 'Use wc_get_notices' );
				return wc_get_notices( 'error' );
		}
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Define WC Constants
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir();

		$this->define( 'WC_PLUGIN_FILE', __FILE__ );
		$this->define( 'WC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'WC_VERSION', $this->version );
		$this->define( 'WOOCOMMERCE_VERSION', $this->version );
		$this->define( 'WC_ROUNDING_PRECISION', 4 );
		$this->define( 'WC_TAX_ROUNDING_MODE', 'yes' === get_option( 'woocommerce_prices_include_tax', 'no' ) ? 2 : 1 );
		$this->define( 'WC_DELIMITER', '|' );
		$this->define( 'WC_LOG_DIR', $upload_dir['basedir'] . '/wc-logs/' );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	private function includes() {
		include_once( 'includes/class-wc-autoloader.php' );
		include_once( 'includes/wc-core-functions.php' );
		include_once( 'includes/class-wc-install.php' );
		include_once( 'includes/class-wc-download-handler.php' );
		include_once( 'includes/class-wc-comments.php' );
		include_once( 'includes/class-wc-post-data.php' );

		if ( is_admin() ) {
			include_once( 'includes/admin/class-wc-admin.php' );
		}

		if ( defined( 'DOING_AJAX' ) ) {
			$this->ajax_includes();
		}

		if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
			$this->frontend_includes();
		}

		// Query class
		$this->query = include( 'includes/class-wc-query.php' );                // The main query class

		// Post types
		include_once( 'includes/class-wc-post-types.php' );                     // Registers post types

		// API Class
		include_once( 'includes/class-wc-api.php' );

		// Include abstract classes
		include_once( 'includes/abstracts/abstract-wc-product.php' );           // Products
		include_once( 'includes/abstracts/abstract-wc-order.php' );             // Orders
		include_once( 'includes/abstracts/abstract-wc-settings-api.php' );      // Settings API (for gateways, shipping, and integrations)
		include_once( 'includes/abstracts/abstract-wc-shipping-method.php' );   // A Shipping method
		include_once( 'includes/abstracts/abstract-wc-payment-gateway.php' );   // A Payment gateway
		include_once( 'includes/abstracts/abstract-wc-integration.php' );       // An integration with a service

		// Classes (used on all pages)
		include_once( 'includes/class-wc-product-factory.php' );                // Product factory
		include_once( 'includes/class-wc-countries.php' );                      // Defines countries and states
		include_once( 'includes/class-wc-integrations.php' );                   // Loads integrations
		include_once( 'includes/class-wc-cache-helper.php' );                   // Cache Helper

		// Download/update languages
		include_once( 'includes/class-wc-language-pack-upgrader.php' );
	}

	/**
	 * Include required ajax files.
	 */
	public function ajax_includes() {
		include_once( 'includes/class-wc-ajax.php' );                           // Ajax functions for admin and the front-end
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		// Functions
		include_once( 'includes/wc-cart-functions.php' );
		include_once( 'includes/wc-notice-functions.php' );

		// Classes
		include_once( 'includes/abstracts/abstract-wc-session.php' );
		include_once( 'includes/class-wc-session-handler.php' );
		include_once( 'includes/wc-template-hooks.php' );
		include_once( 'includes/class-wc-template-loader.php' );                // Template Loader
		include_once( 'includes/class-wc-frontend-scripts.php' );               // Frontend Scripts
		include_once( 'includes/class-wc-form-handler.php' );                   // Form Handlers
		include_once( 'includes/class-wc-cart.php' );                           // The main cart class
		include_once( 'includes/class-wc-tax.php' );                            // Tax class
		include_once( 'includes/class-wc-customer.php' );                       // Customer class
		include_once( 'includes/class-wc-shortcodes.php' );                     // Shortcodes class
		include_once( 'includes/class-wc-https.php' );                          // https Helper
	}

	/**
	 * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
			include_once( 'includes/wc-template-functions.php' );
		}
	}

	/**
	 * Include core widgets
	 */
	public function include_widgets() {
		include_once( 'includes/abstracts/abstract-wc-widget.php' );
		include_once( 'includes/widgets/class-wc-widget-cart.php' );
		include_once( 'includes/widgets/class-wc-widget-layered-nav-filters.php' );
		include_once( 'includes/widgets/class-wc-widget-layered-nav.php' );
		include_once( 'includes/widgets/class-wc-widget-price-filter.php' );
		include_once( 'includes/widgets/class-wc-widget-product-categories.php' );
		include_once( 'includes/widgets/class-wc-widget-product-search.php' );
		include_once( 'includes/widgets/class-wc-widget-product-tag-cloud.php' );
		include_once( 'includes/widgets/class-wc-widget-products.php' );
		include_once( 'includes/widgets/class-wc-widget-recent-reviews.php' );
		include_once( 'includes/widgets/class-wc-widget-recently-viewed.php' );
		include_once( 'includes/widgets/class-wc-widget-top-rated-products.php' );
	}

	/**
	 * Init WooCommerce when WordPress Initialises.
	 */
	public function init() {
		// Before init action
		do_action( 'before_woocommerce_init' );

		// Set up localisation
		$this->load_plugin_textdomain();

		// Template debug mode
		$status_options = get_option( 'woocommerce_status_options', array() );
		if ( ! empty( $status_options['template_debug_mode'] ) && current_user_can( 'manage_options' ) ) {
			$this->define( 'WC_TEMPLATE_DEBUG_MODE', true );
		} else {
			$this->define( 'WC_TEMPLATE_DEBUG_MODE', false );
		}

		// Load class instances
		$this->product_factory = new WC_Product_Factory();                      // Product Factory to create new product instances
		$this->order_factory   = new WC_Order_Factory();                        // Order Factory to create new order instances
		$this->countries       = new WC_Countries();                            // Countries class
		$this->integrations    = new WC_Integrations();                         // Integrations class

		// Classes/actions loaded for the frontend and for ajax requests
		if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
			// Session class, handles session data for users - can be overwritten if custom handler is needed
			$session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );

			// Class instances
			$this->session  = new $session_class();
			$this->cart     = new WC_Cart();                                    // Cart class, stores the cart contents
			$this->customer = new WC_Customer();                                // Customer class, handles data such as customer location
		}

		// Email Actions
		$email_actions = apply_filters( 'woocommerce_email_actions', array(
			'woocommerce_low_stock',
			'woocommerce_no_stock',
			'woocommerce_product_on_backorder',
			'woocommerce_order_status_pending_to_processing',
			'woocommerce_order_status_pending_to_completed',
			'woocommerce_order_status_pending_to_cancelled',
			'woocommerce_order_status_pending_to_on-hold',
			'woocommerce_order_status_failed_to_processing',
			'woocommerce_order_status_failed_to_completed',
			'woocommerce_order_status_on-hold_to_processing',
			'woocommerce_order_status_on-hold_to_cancelled',
			'woocommerce_order_status_completed',
			'woocommerce_new_customer_note',
			'woocommerce_created_customer'
		) );

		foreach ( $email_actions as $action ) {
			add_action( $action, array( $this, 'send_transactional_email' ), 10, 10 );
		}

		// webhooks
		$this->load_webhooks();

		// Init action
		do_action( 'woocommerce_init' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Admin Locales are found in:
	 * 		- WP_LANG_DIR/woocommerce/woocommerce-admin-LOCALE.mo
	 * 		- WP_LANG_DIR/plugins/woocommerce-admin-LOCALE.mo
	 *
	 * Frontend/global Locales found in:
	 * 		- WP_LANG_DIR/woocommerce/woocommerce-LOCALE.mo
	 * 	 	- woocommerce/i18n/languages/woocommerce-LOCALE.mo (which if not found falls back to:)
	 * 	 	- WP_LANG_DIR/plugins/woocommerce-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce' );

		if ( is_admin() ) {
			load_textdomain( 'woocommerce', WP_LANG_DIR . '/woocommerce/woocommerce-admin-' . $locale . '.mo' );
			load_textdomain( 'woocommerce', WP_LANG_DIR . '/plugins/woocommerce-admin-' . $locale . '.mo' );
		}

		load_textdomain( 'woocommerce', WP_LANG_DIR . '/woocommerce/woocommerce-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce', false, plugin_basename( dirname( __FILE__ ) ) . "/i18n/languages" );
	}

	/**
	 * Ensure theme and server variable compatibility and setup image sizes.
	 */
	public function setup_environment() {
		/**
		 * @deprecated 2.2 Use WC()->template_path()
		 */
		$this->define( 'WC_TEMPLATE_PATH', $this->template_path() );

		$this->add_thumbnail_support();
		$this->add_image_sizes();
		$this->fix_server_vars();
	}

	/**
	 * Ensure post thumbnail support is turned on
	 */
	private function add_thumbnail_support() {
		if ( ! current_theme_supports( 'post-thumbnails' ) ) {
			add_theme_support( 'post-thumbnails' );
		}
		add_post_type_support( 'product', 'thumbnail' );
	}

	/**
	 * Add WC Image sizes to WP
	 *
	 * @since 2.3
	 */
	private function add_image_sizes() {
		$shop_thumbnail = wc_get_image_size( 'shop_thumbnail' );
		$shop_catalog	= wc_get_image_size( 'shop_catalog' );
		$shop_single	= wc_get_image_size( 'shop_single' );

		add_image_size( 'shop_thumbnail', $shop_thumbnail['width'], $shop_thumbnail['height'], $shop_thumbnail['crop'] );
		add_image_size( 'shop_catalog', $shop_catalog['width'], $shop_catalog['height'], $shop_catalog['crop'] );
		add_image_size( 'shop_single', $shop_single['width'], $shop_single['height'], $shop_single['crop'] );
	}

	/**
	 * Fix `$_SERVER` variables for various setups.
	 *
	 * Note: Removed IIS handling due to wp_fix_server_vars()
	 *
	 * @since 2.3
	 */
	private function fix_server_vars() {
		// NGINX Proxy
		if ( ! isset( $_SERVER['REMOTE_ADDR'] ) && isset( $_SERVER['HTTP_REMOTE_ADDR'] ) ) {
			$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_REMOTE_ADDR'];
		}

		if ( ! isset( $_SERVER['HTTPS'] ) ) {
			if ( ! empty( $_SERVER['HTTP_HTTPS'] ) ) {
				$_SERVER['HTTPS'] = $_SERVER['HTTP_HTTPS'];
			} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ) {
				$_SERVER['HTTPS'] = '1';
			}
		}
	}

	/** Helper functions ******************************************************/

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'woocommerce_template_path', 'woocommerce/' );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Return the WC API URL for a given request
	 *
	 * @param string $request
	 * @param mixed $ssl (default: null)
	 * @return string
	 */
	public function api_request_url( $request, $ssl = null ) {
		if ( is_null( $ssl ) ) {
			$scheme = parse_url( home_url(), PHP_URL_SCHEME );
		} elseif ( $ssl ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		if ( get_option( 'permalink_structure' ) ) {
			return esc_url_raw( trailingslashit( home_url( '/wc-api/' . $request, $scheme ) ) );
		} else {
			return esc_url_raw( add_query_arg( 'wc-api', $request, trailingslashit( home_url( '', $scheme ) ) ) );
		}
	}

	/**
	 * Init the mailer and call the notifications for the current filter.
	 *
	 * @internal param array $args (default: array())
	 */
	public function send_transactional_email() {
		$this->mailer();
		$args = func_get_args();
		do_action_ref_array( current_filter() . '_notification', $args );
	}

	/**
	 * Load & enqueue active webhooks
	 *
	 * @since 2.2
	 */
	public function load_webhooks() {

		$args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_webhook',
			'post_status' => 'publish',
		);

		$query = new WP_Query( $args );

		if ( ! empty( $query->posts ) ) {

			foreach ( $query->posts as $id ) {
				$webhook = new WC_Webhook( $id );
				$webhook->enqueue();
			}
		}
	}

	/** Load Instances on demand **********************************************/

	/**
	 * Get Checkout Class.
	 *
	 * @return WC_Checkout
	 */
	public function checkout() {
		return WC_Checkout::instance();
	}

	/**
	 * Get gateways class
	 *
	 * @return WC_Payment_Gateways
	 */
	public function payment_gateways() {
		return WC_Payment_Gateways::instance();
	}

	/**
	 * Get shipping class
	 *
	 * @return WC_Shipping
	 */
	public function shipping() {
		return WC_Shipping::instance();
	}

	/**
	 * Email Class.
	 *
	 * @return WC_Emails
	 */
	public function mailer() {
		return WC_Emails::instance();
	}

	/** Deprecated methods *********************************************************/

	/**
	 * @deprecated 2.1.0
	 * @param $image_size
	 * @return array
	 */
	public function get_image_size( $image_size ) {
		_deprecated_function( 'Woocommerce->get_image_size', '2.1', 'wc_get_image_size()' );
		return wc_get_image_size( $image_size );
	}

	/**
	 * @deprecated 2.1.0
	 * @return WC_Logger
	 */
	public function logger() {
		_deprecated_function( 'Woocommerce->logger', '2.1', 'new WC_Logger()' );
		return new WC_Logger();
	}

	/**
	 * @deprecated 2.1.0
	 * @return WC_Validation
	 */
	public function validation() {
		_deprecated_function( 'Woocommerce->validation', '2.1', 'new WC_Validation()' );
		return new WC_Validation();
	}

	/**
	 * @deprecated 2.1.0
	 * @param $post
	 * @return WC_Product
	 */
	public function setup_product_data( $post ) {
		_deprecated_function( 'Woocommerce->setup_product_data', '2.1', 'wc_setup_product_data' );
		return wc_setup_product_data( $post );
	}

	/**
	 * @deprecated 2.1.0
	 * @param $content
	 * @return string
	 */
	public function force_ssl( $content ) {
		_deprecated_function( 'Woocommerce->force_ssl', '2.1', 'WC_HTTPS::force_https_url' );
		return WC_HTTPS::force_https_url( $content );
	}

	/**
	 * @deprecated 2.1.0
	 * @param int $post_id
	 */
	public function clear_product_transients( $post_id = 0 ) {
		_deprecated_function( 'Woocommerce->clear_product_transients', '2.1', 'wc_delete_product_transients' );
		wc_delete_product_transients( $post_id );
	}

	/**
	 * @deprecated 2.1.0 Access via the WC_Inline_Javascript_Helper helper
	 * @param $code
	 */
	public function add_inline_js( $code ) {
		_deprecated_function( 'Woocommerce->add_inline_js', '2.1', 'wc_enqueue_js' );
		wc_enqueue_js( $code );
	}

	/**
	 * @deprecated 2.1.0
	 * @param      $action
	 * @param bool $referer
	 * @param bool $echo
	 * @return string
	 */
	public function nonce_field( $action, $referer = true , $echo = true ) {
		_deprecated_function( 'Woocommerce->nonce_field', '2.1', 'wp_nonce_field' );
		return wp_nonce_field('woocommerce-' . $action, '_wpnonce', $referer, $echo );
	}

	/**
	 * @deprecated 2.1.0
	 * @param        $action
	 * @param string $url
	 * @return string
	 */
	public function nonce_url( $action, $url = '' ) {
		_deprecated_function( 'Woocommerce->nonce_url', '2.1', 'wp_nonce_url' );
		return wp_nonce_url( $url , 'woocommerce-' . $action );
	}

	/**
	 * @deprecated 2.1.0
	 * @param        $action
	 * @param string $method
	 * @param bool   $error_message
	 * @return bool
	 */
	public function verify_nonce( $action, $method = '_POST', $error_message = false ) {
		_deprecated_function( 'Woocommerce->verify_nonce', '2.1', 'wp_verify_nonce' );
		if ( ! isset( $method[ '_wpnonce' ] ) ) {
			return false;
		}
		return wp_verify_nonce( $method[ '_wpnonce' ], 'woocommerce-' . $action );
	}

	/**
	 * @deprecated 2.1.0
	 * @param       $function
	 * @param array $atts
	 * @param array $wrapper
	 * @return string
	 */
	public function shortcode_wrapper( $function, $atts = array(), $wrapper = array( 'class' => 'woocommerce', 'before' => null, 'after' => null ) ) {
		_deprecated_function( 'Woocommerce->shortcode_wrapper', '2.1', 'WC_Shortcodes::shortcode_wrapper' );
		return WC_Shortcodes::shortcode_wrapper( $function, $atts, $wrapper );
	}

	/**
	 * @deprecated 2.1.0
	 * @return object
	 */
	public function get_attribute_taxonomies() {
		_deprecated_function( 'Woocommerce->get_attribute_taxonomies', '2.1', 'wc_get_attribute_taxonomies' );
		return wc_get_attribute_taxonomies();
	}

	/**
	 * @deprecated 2.1.0
	 * @param $name
	 * @return string
	 */
	public function attribute_taxonomy_name( $name ) {
		_deprecated_function( 'Woocommerce->attribute_taxonomy_name', '2.1', 'wc_attribute_taxonomy_name' );
		return wc_attribute_taxonomy_name( $name );
	}

	/**
	 * @deprecated 2.1.0
	 * @param $name
	 * @return string
	 */
	public function attribute_label( $name ) {
		_deprecated_function( 'Woocommerce->attribute_label', '2.1', 'wc_attribute_label' );
		return wc_attribute_label( $name );
	}

	/**
	 * @deprecated 2.1.0
	 * @param $name
	 * @return string
	 */
	public function attribute_orderby( $name ) {
		_deprecated_function( 'Woocommerce->attribute_orderby', '2.1', 'wc_attribute_orderby' );
		return wc_attribute_orderby( $name );
	}

	/**
	 * @deprecated 2.1.0
	 * @return array
	 */
	public function get_attribute_taxonomy_names() {
		_deprecated_function( 'Woocommerce->get_attribute_taxonomy_names', '2.1', 'wc_get_attribute_taxonomy_names' );
		return wc_get_attribute_taxonomy_names();
	}

	/**
	 * @deprecated 2.1.0
	 * @return array
	 */
	public function get_coupon_discount_types() {
		_deprecated_function( 'Woocommerce->get_coupon_discount_types', '2.1', 'wc_get_coupon_types' );
		return wc_get_coupon_types();
	}

	/**
	 * @deprecated 2.1.0
	 * @param string $type
	 * @return string
	 */
	public function get_coupon_discount_type( $type = '' ) {
		_deprecated_function( 'Woocommerce->get_coupon_discount_type', '2.1', 'wc_get_coupon_type' );
		return wc_get_coupon_type( $type );
	}

	/**
	 * @deprecated 2.1.0
	 * @param $class
	 */
	public function add_body_class( $class ) {
		_deprecated_function( 'Woocommerce->add_body_class', '2.1' );
	}

	/**
	 * @deprecated 2.1.0
	 * @param $classes
	 */
	public function output_body_class( $classes ) {
		_deprecated_function( 'Woocommerce->output_body_class', '2.1' );
	}

	/**
	 * @deprecated 2.1.0
	 * @param $error
	 */
	public function add_error( $error ) {
		_deprecated_function( 'Woocommerce->add_error', '2.1', 'wc_add_notice' );
		wc_add_notice( $error, 'error' );
	}

	/**
	 * @deprecated 2.1.0
	 * @param $message
	 */
	public function add_message( $message ) {
		_deprecated_function( 'Woocommerce->add_message', '2.1', 'wc_add_notice' );
		wc_add_notice( $message );
	}

	/**
	 * @deprecated 2.1.0
	 */
	public function clear_messages() {
		_deprecated_function( 'Woocommerce->clear_messages', '2.1', 'wc_clear_notices' );
		wc_clear_notices();
	}

	/**
	 * @deprecated 2.1.0
	 * @return int
	 */
	public function error_count() {
		_deprecated_function( 'Woocommerce->error_count', '2.1', 'wc_notice_count' );
		return wc_notice_count( 'error' );
	}

	/**
	 * @deprecated 2.1.0
	 * @return int
	 */
	public function message_count() {
		_deprecated_function( 'Woocommerce->message_count', '2.1', 'wc_notice_count' );
		return wc_notice_count( 'message' );
	}

	/**
	 * @deprecated 2.1.0
	 * @return mixed
	 */
	public function get_errors() {
		_deprecated_function( 'Woocommerce->get_errors', '2.1', 'wc_get_notices( "error" )' );
		return wc_get_notices( 'error' );
	}

	/**
	 * @deprecated 2.1.0
	 * @return mixed
	 */
	public function get_messages() {
		_deprecated_function( 'Woocommerce->get_messages', '2.1', 'wc_get_notices( "success" )' );
		return wc_get_notices( 'success' );
	}

	/**
	 * @deprecated 2.1.0
	 */
	public function show_messages() {
		_deprecated_function( 'Woocommerce->show_messages', '2.1', 'wc_print_notices()' );
		wc_print_notices();
	}

	/**
	 * @deprecated 2.1.0
	 */
	public function set_messages() {
		_deprecated_function( 'Woocommerce->set_messages', '2.1' );
	}
}

endif;

/**
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @since  2.1
 * @return WooCommerce
 */
function WC() {
	return WooCommerce::instance();
}

// Global for backwards compatibility.
$GLOBALS['woocommerce'] = WC();
