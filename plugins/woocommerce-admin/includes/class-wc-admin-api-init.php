<?php
/**
 * REST API bootstrap.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Api_Init class.
 */
class WC_Admin_Api_Init {

	/**
	 * Action hook for reducing a range of batches down to single actions.
	 */
	const QUEUE_BATCH_ACTION = 'wc-admin_queue_batches';

	/**
	 * Action hook for queuing an action after another is complete.
	 */
	const QUEUE_DEPEDENT_ACTION = 'wc-admin_queue_dependent_action';

	/**
	 * Action hook for processing a batch of customers.
	 */
	const CUSTOMERS_BATCH_ACTION = 'wc-admin_process_customers_batch';

	/**
	 * Action hook for processing a batch of orders.
	 */
	const ORDERS_BATCH_ACTION = 'wc-admin_process_orders_batch';

	/**
	 * Action hook for initializing the orders lookup batch creation.
	 */
	const ORDERS_LOOKUP_BATCH_INIT = 'wc-admin_orders_lookup_batch_init';

	/**
	 * Action hook for processing a batch of orders.
	 */
	const SINGLE_ORDER_ACTION = 'wc-admin_process_order';

	/**
	 * Queue instance.
	 *
	 * @var WC_Queue_Interface
	 */
	protected static $queue = null;

	/**
	 * Boostrap REST API.
	 */
	public function __construct() {
		// Initialize classes.
		add_action( 'plugins_loaded', array( $this, 'init_classes' ), 19 );
		// Hook in data stores.
		add_filter( 'woocommerce_data_stores', array( 'WC_Admin_Api_Init', 'add_data_stores' ) );
		// Add wc-admin report tables to list of WooCommerce tables.
		add_filter( 'woocommerce_install_get_tables', array( 'WC_Admin_Api_Init', 'add_tables' ) );
		// REST API extensions init.
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
		add_filter( 'rest_endpoints', array( 'WC_Admin_Api_Init', 'filter_rest_endpoints' ), 10, 1 );
		add_filter( 'woocommerce_debug_tools', array( 'WC_Admin_Api_Init', 'add_regenerate_tool' ) );

		// Initialize syncing hooks.
		add_action( 'wp_loaded', array( __CLASS__, 'orders_lookup_update_init' ) );

		// Initialize scheduled action handlers.
		add_action( self::QUEUE_BATCH_ACTION, array( __CLASS__, 'queue_batches' ), 10, 3 );
		add_action( self::QUEUE_DEPEDENT_ACTION, array( __CLASS__, 'queue_dependent_action' ), 10, 3 );
		add_action( self::CUSTOMERS_BATCH_ACTION, array( __CLASS__, 'customer_lookup_process_batch' ) );
		add_action( self::ORDERS_BATCH_ACTION, array( __CLASS__, 'orders_lookup_process_batch' ) );
		add_action( self::ORDERS_LOOKUP_BATCH_INIT, array( __CLASS__, 'orders_lookup_batch_init' ) );
		add_action( self::SINGLE_ORDER_ACTION, array( __CLASS__, 'orders_lookup_process_order' ) );

		// Add currency symbol to orders endpoint response.
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( __CLASS__, 'add_currency_symbol_to_order_response' ) );
	}

	/**
	 * Get queue instance.
	 *
	 * @return WC_Queue_Interface
	 */
	public static function queue() {
		if ( is_null( self::$queue ) ) {
			self::$queue = WC()->queue();
		}

		return self::$queue;
	}

	/**
	 * Set queue instance.
	 *
	 * @param WC_Queue_Interface $queue Queue instance.
	 */
	public static function set_queue( $queue ) {
		self::$queue = $queue;
	}

	/**
	 * Init classes.
	 */
	public function init_classes() {
		// Interfaces.
		require_once dirname( __FILE__ ) . '/interfaces/class-wc-admin-reports-data-store-interface.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-query.php';

		// Common date time code.
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-interval.php';

		// Exceptions.
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-parameter-exception.php';

		// WC Class extensions.
		require_once dirname( __FILE__ ) . '/class-wc-admin-order.php';

		// Segmentation.
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-segmenting.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-orders-stats-segmenting.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-products-stats-segmenting.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-coupons-stats-segmenting.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-taxes-stats-segmenting.php';

		// Query classes for reports.
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-revenue-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-orders-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-orders-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-products-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-variations-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-products-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-categories-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-taxes-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-taxes-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-coupons-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-coupons-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-downloads-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-downloads-stats-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-customers-query.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-reports-customers-stats-query.php';

		// Data stores.
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-orders-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-orders-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-products-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-variations-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-products-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-categories-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-taxes-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-taxes-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-coupons-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-coupons-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-downloads-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-downloads-stats-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-customers-data-store.php';
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-reports-customers-stats-data-store.php';

		// Data triggers.
		require_once dirname( __FILE__ ) . '/data-stores/class-wc-admin-notes-data-store.php';

		// CRUD classes.
		require_once dirname( __FILE__ ) . '/class-wc-admin-note.php';
		require_once dirname( __FILE__ ) . '/class-wc-admin-notes.php';
	}

	/**
	 * Init REST API.
	 */
	public function rest_api_init() {
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-admin-notes-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-coupons-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-customers-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-data-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-data-countries-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-data-download-ips-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-orders-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-products-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-product-categories-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-product-reviews-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-product-variations-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-setting-options-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-system-status-tools-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-categories-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-coupons-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-coupons-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-customers-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-customers-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-downloads-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-downloads-files-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-downloads-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-orders-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-orders-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-products-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-variations-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-products-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-performance-indicators-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-revenue-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-taxes-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-taxes-stats-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-reports-stock-controller.php';
		require_once dirname( __FILE__ ) . '/api/class-wc-admin-rest-taxes-controller.php';

		$controllers = apply_filters(
			'woocommerce_admin_rest_controllers',
			array(
				'WC_Admin_REST_Admin_Notes_Controller',
				'WC_Admin_REST_Coupons_Controller',
				'WC_Admin_REST_Customers_Controller',
				'WC_Admin_REST_Data_Controller',
				'WC_Admin_REST_Data_Countries_Controller',
				'WC_Admin_REST_Data_Download_Ips_Controller',
				'WC_Admin_REST_Orders_Controller',
				'WC_Admin_REST_Products_Controller',
				'WC_Admin_REST_Product_Categories_Controller',
				'WC_Admin_REST_Product_Reviews_Controller',
				'WC_Admin_REST_Product_Variations_Controller',
				'WC_Admin_REST_Reports_Controller',
				'WC_Admin_REST_Setting_Options_Controller',
				'WC_Admin_REST_System_Status_Tools_Controller',
				'WC_Admin_REST_Reports_Products_Controller',
				'WC_Admin_REST_Reports_Variations_Controller',
				'WC_Admin_REST_Reports_Products_Stats_Controller',
				'WC_Admin_REST_Reports_Revenue_Stats_Controller',
				'WC_Admin_REST_Reports_Orders_Controller',
				'WC_Admin_REST_Reports_Orders_Stats_Controller',
				'WC_Admin_REST_Reports_Categories_Controller',
				'WC_Admin_REST_Reports_Taxes_Controller',
				'WC_Admin_REST_Reports_Taxes_Stats_Controller',
				'WC_Admin_REST_Reports_Coupons_Controller',
				'WC_Admin_REST_Reports_Coupons_Stats_Controller',
				'WC_Admin_REST_Reports_Stock_Controller',
				'WC_Admin_REST_Reports_Downloads_Controller',
				'WC_Admin_REST_Reports_Downloads_Stats_Controller',
				'WC_Admin_REST_Reports_Customers_Controller',
				'WC_Admin_REST_Reports_Customers_Stats_Controller',
				'WC_Admin_REST_Taxes_Controller',
			)
		);

		// The performance indicators controller must be registered last, after other /stats endpoints have been registered.
		$controllers[] = 'WC_Admin_REST_Reports_Performance_Indicators_Controller';

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}

	/**
	 * Filter REST API endpoints.
	 *
	 * @param array $endpoints List of endpoints.
	 * @return array
	 */
	public static function filter_rest_endpoints( $endpoints ) {
		// Override GET /wc/v4/system_status/tools.
		if ( isset( $endpoints['/wc/v4/system_status/tools'] )
			&& isset( $endpoints['/wc/v4/system_status/tools'][1] )
			&& isset( $endpoints['/wc/v4/system_status/tools'][0] )
			&& $endpoints['/wc/v4/system_status/tools'][1]['callback'][0] instanceof WC_Admin_REST_System_Status_Tools_Controller
		) {
			$endpoints['/wc/v4/system_status/tools'][0] = $endpoints['/wc/v4/system_status/tools'][1];
		}
		// // Override GET & PUT for /wc/v4/system_status/tools.
		if ( isset( $endpoints['/wc/v4/system_status/tools/(?P<id>[\w-]+)'] )
			&& isset( $endpoints['/wc/v4/system_status/tools/(?P<id>[\w-]+)'][3] )
			&& isset( $endpoints['/wc/v4/system_status/tools/(?P<id>[\w-]+)'][2] )
			&& $endpoints['/wc/v4/system_status/tools/(?P<id>[\w-]+)'][2]['callback'][0] instanceof WC_Admin_REST_System_Status_Tools_Controller
			&& $endpoints['/wc/v4/system_status/tools/(?P<id>[\w-]+)'][3]['callback'][0] instanceof WC_Admin_REST_System_Status_Tools_Controller
		) {
			$endpoints['/wc/v4/system_status/tools/(?P<id>[\w-]+)'][0] = $endpoints['/wc/v4/system_status/tools/(?P<id>[\w-]+)'][2];
			$endpoints['/wc/v4/system_status/tools/(?P<id>[\w-]+)'][1] = $endpoints['/wc/v4/system_status/tools/(?P<id>[\w-]+)'][3];
		}

		// Override GET /wc/v4/reports.
		if ( isset( $endpoints['/wc/v4/reports'] )
			&& isset( $endpoints['/wc/v4/reports'][1] )
			&& isset( $endpoints['/wc/v4/reports'][0] )
			&& $endpoints['/wc/v4/reports'][1]['callback'][0] instanceof WC_Admin_REST_Reports_Controller
		) {
			$endpoints['/wc/v4/reports'][0] = $endpoints['/wc/v4/reports'][1];
		}

		// Override /wc/v4/coupons.
		if ( isset( $endpoints['/wc/v4/coupons'] )
			&& isset( $endpoints['/wc/v4/coupons'][3] )
			&& isset( $endpoints['/wc/v4/coupons'][2] )
			&& $endpoints['/wc/v4/coupons'][2]['callback'][0] instanceof WC_Admin_REST_Orders_Controller
			&& $endpoints['/wc/v4/coupons'][3]['callback'][0] instanceof WC_Admin_REST_Orders_Controller
		) {
			$endpoints['/wc/v4/coupons'][0] = $endpoints['/wc/v4/coupons'][2];
			$endpoints['/wc/v4/coupons'][1] = $endpoints['/wc/v4/coupons'][3];
		}

		// Override /wc/v4/customers.
		if ( isset( $endpoints['/wc/v4/customers'] )
			&& isset( $endpoints['/wc/v4/customers'][3] )
			&& isset( $endpoints['/wc/v4/customers'][2] )
			&& $endpoints['/wc/v4/customers'][2]['callback'][0] instanceof WC_Admin_REST_Customers_Controller
			&& $endpoints['/wc/v4/customers'][3]['callback'][0] instanceof WC_Admin_REST_Customers_Controller
		) {
			$endpoints['/wc/v4/customers'][0] = $endpoints['/wc/v4/customers'][2];
			$endpoints['/wc/v4/customers'][1] = $endpoints['/wc/v4/customers'][3];
		}

		// Override /wc/v4/orders/$id.
		if ( isset( $endpoints['/wc/v4/orders/(?P<id>[\d]+)'] )
			&& isset( $endpoints['/wc/v4/orders/(?P<id>[\d]+)'][5] )
			&& isset( $endpoints['/wc/v4/orders/(?P<id>[\d]+)'][4] )
			&& isset( $endpoints['/wc/v4/orders/(?P<id>[\d]+)'][3] )
			&& $endpoints['/wc/v4/orders/(?P<id>[\d]+)'][3]['callback'][0] instanceof WC_Admin_REST_Orders_Controller
			&& $endpoints['/wc/v4/orders/(?P<id>[\d]+)'][4]['callback'][0] instanceof WC_Admin_REST_Orders_Controller
			&& $endpoints['/wc/v4/orders/(?P<id>[\d]+)'][5]['callback'][0] instanceof WC_Admin_REST_Orders_Controller
		) {
			$endpoints['/wc/v4/orders/(?P<id>[\d]+)'][0] = $endpoints['/wc/v4/orders/(?P<id>[\d]+)'][3];
			$endpoints['/wc/v4/orders/(?P<id>[\d]+)'][1] = $endpoints['/wc/v4/orders/(?P<id>[\d]+)'][4];
			$endpoints['/wc/v4/orders/(?P<id>[\d]+)'][2] = $endpoints['/wc/v4/orders/(?P<id>[\d]+)'][5];
		}

		// Override /wc/v4/orders.
		if ( isset( $endpoints['/wc/v4/orders'] )
			&& isset( $endpoints['/wc/v4/orders'][3] )
			&& isset( $endpoints['/wc/v4/orders'][2] )
			&& $endpoints['/wc/v4/orders'][2]['callback'][0] instanceof WC_Admin_REST_Orders_Controller
			&& $endpoints['/wc/v4/orders'][3]['callback'][0] instanceof WC_Admin_REST_Orders_Controller
		) {
			$endpoints['/wc/v4/orders'][0] = $endpoints['/wc/v4/orders'][2];
			$endpoints['/wc/v4/orders'][1] = $endpoints['/wc/v4/orders'][3];
		}

		// Override /wc/v4/data.
		if ( isset( $endpoints['/wc/v4/data'] )
			&& isset( $endpoints['/wc/v4/data'][1] )
			&& $endpoints['/wc/v4/data'][1]['callback'][0] instanceof WC_Admin_REST_Data_Controller
		) {
			$endpoints['/wc/v4/data'][0] = $endpoints['/wc/v4/data'][1];
		}

		// Override /wc/v4/products.
		if ( isset( $endpoints['/wc/v4/products'] )
			&& isset( $endpoints['/wc/v4/products'][3] )
			&& isset( $endpoints['/wc/v4/products'][2] )
			&& $endpoints['/wc/v4/products'][2]['callback'][0] instanceof WC_Admin_REST_Products_Controller
			&& $endpoints['/wc/v4/products'][3]['callback'][0] instanceof WC_Admin_REST_Products_Controller
		) {
			$endpoints['/wc/v4/products'][0] = $endpoints['/wc/v4/products'][2];
			$endpoints['/wc/v4/products'][1] = $endpoints['/wc/v4/products'][3];
		}

		// Override /wc/v4/products/$id.
		if ( isset( $endpoints['/wc/v4/products/(?P<id>[\d]+)'] )
			&& isset( $endpoints['/wc/v4/products/(?P<id>[\d]+)'][5] )
			&& isset( $endpoints['/wc/v4/products/(?P<id>[\d]+)'][4] )
			&& isset( $endpoints['/wc/v4/products/(?P<id>[\d]+)'][3] )
			&& $endpoints['/wc/v4/products/(?P<id>[\d]+)'][3]['callback'][0] instanceof WC_Admin_REST_Products_Controller
			&& $endpoints['/wc/v4/products/(?P<id>[\d]+)'][4]['callback'][0] instanceof WC_Admin_REST_Products_Controller
			&& $endpoints['/wc/v4/products/(?P<id>[\d]+)'][5]['callback'][0] instanceof WC_Admin_REST_Products_Controller
		) {
			$endpoints['/wc/v4/products/(?P<id>[\d]+)'][0] = $endpoints['/wc/v4/products/(?P<id>[\d]+)'][3];
			$endpoints['/wc/v4/products/(?P<id>[\d]+)'][1] = $endpoints['/wc/v4/products/(?P<id>[\d]+)'][4];
			$endpoints['/wc/v4/products/(?P<id>[\d]+)'][2] = $endpoints['/wc/v4/products/(?P<id>[\d]+)'][5];
		}

		// Override /wc/v4/products/categories.
		if ( isset( $endpoints['/wc/v4/products/categories'] )
			&& isset( $endpoints['/wc/v4/products/categories'][3] )
			&& isset( $endpoints['/wc/v4/products/categories'][2] )
			&& $endpoints['/wc/v4/products/categories'][2]['callback'][0] instanceof WC_Admin_REST_Product_categories_Controller
			&& $endpoints['/wc/v4/products/categories'][3]['callback'][0] instanceof WC_Admin_REST_Product_categories_Controller
		) {
			$endpoints['/wc/v4/products/categories'][0] = $endpoints['/wc/v4/products/categories'][2];
			$endpoints['/wc/v4/products/categories'][1] = $endpoints['/wc/v4/products/categories'][3];
		}

		// Override /wc/v4/products/reviews.
		if ( isset( $endpoints['/wc/v4/products/reviews'] )
			&& isset( $endpoints['/wc/v4/products/reviews'][3] )
			&& isset( $endpoints['/wc/v4/products/reviews'][2] )
			&& $endpoints['/wc/v4/products/reviews'][2]['callback'][0] instanceof WC_Admin_REST_Product_Reviews_Controller
			&& $endpoints['/wc/v4/products/reviews'][3]['callback'][0] instanceof WC_Admin_REST_Product_Reviews_Controller
		) {
			$endpoints['/wc/v4/products/reviews'][0] = $endpoints['/wc/v4/products/reviews'][2];
			$endpoints['/wc/v4/products/reviews'][1] = $endpoints['/wc/v4/products/reviews'][3];
		}

		// Override /wc/v4/products/$product_id/variations.
		if ( isset( $endpoints['products/(?P<product_id>[\d]+)/variations'] )
			&& isset( $endpoints['products/(?P<product_id>[\d]+)/variations'][3] )
			&& isset( $endpoints['products/(?P<product_id>[\d]+)/variations'][2] )
			&& $endpoints['products/(?P<product_id>[\d]+)/variations'][2]['callback'][0] instanceof WC_Admin_REST_Product_Variations_Controller
			&& $endpoints['products/(?P<product_id>[\d]+)/variations'][3]['callback'][0] instanceof WC_Admin_REST_Product_Variations_Controller
		) {
			$endpoints['products/(?P<product_id>[\d]+)/variations'][0] = $endpoints['products/(?P<product_id>[\d]+)/variations'][2];
			$endpoints['products/(?P<product_id>[\d]+)/variations'][1] = $endpoints['products/(?P<product_id>[\d]+)/variations'][3];
		}

		// Override /wc/v4/taxes.
		if ( isset( $endpoints['/wc/v4/taxes'] )
			&& isset( $endpoints['/wc/v4/taxes'][3] )
			&& isset( $endpoints['/wc/v4/taxes'][2] )
			&& $endpoints['/wc/v4/taxes'][2]['callback'][0] instanceof WC_Admin_REST_Orders_Controller
			&& $endpoints['/wc/v4/taxes'][3]['callback'][0] instanceof WC_Admin_REST_Orders_Controller
		) {
			$endpoints['/wc/v4/taxes'][0] = $endpoints['/wc/v4/taxes'][2];
			$endpoints['/wc/v4/taxes'][1] = $endpoints['/wc/v4/taxes'][3];
		}

		// Override /wc/v4/settings/$group_id.
		if ( isset( $endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'] )
			&& isset( $endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][5] )
			&& isset( $endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][4] )
			&& isset( $endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][3] )
			&& $endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][3]['callback'][0] instanceof WC_Admin_REST_Setting_Options_Controller
			&& $endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][4]['callback'][0] instanceof WC_Admin_REST_Setting_Options_Controller
			&& $endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][5]['callback'][0] instanceof WC_Admin_REST_Setting_Options_Controller
		) {
			$endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][0] = $endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][3];
			$endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][1] = $endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][4];
			$endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][2] = $endpoints['/wc/v4/settings/(?P<group_id>[\w-]+)'][5];
		}

		return $endpoints;
	}

	/**
	 * Regenerate data for reports.
	 */
	public static function regenerate_report_data() {
		// Add registered customers to the lookup table before updating order stats
		// so that the orders can be associated with the `customer_id` column.
		self::customer_lookup_batch_init();
		// Queue orders lookup to occur after customers lookup generation is done.
		self::queue_dependent_action( self::ORDERS_LOOKUP_BATCH_INIT, array(), self::CUSTOMERS_BATCH_ACTION );
	}

	/**
	 * Adds regenerate tool.
	 *
	 * @param array $tools List of tools.
	 * @return array
	 */
	public static function add_regenerate_tool( $tools ) {
		return array_merge(
			$tools,
			array(
				'rebuild_stats' => array(
					'name'     => __( 'Rebuild reports data', 'wc-admin' ),
					'button'   => __( 'Rebuild reports', 'wc-admin' ),
					'desc'     => __( 'This tool will rebuild all of the information used by the reports.', 'wc-admin' ),
					'callback' => array( 'WC_Admin_Api_Init', 'regenerate_report_data' ),
				),
			)
		);
	}

	/**
	 * Schedule an action to process a single Order.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function schedule_single_order_process( $order_id ) {
		if ( 'shop_order' !== get_post_type( $order_id ) ) {
			return;
		}

		// This can get called multiple times for a single order, so we look
		// for existing pending jobs for the same order to avoid duplicating efforts.
		$existing_jobs = self::queue()->search(
			array(
				'status'   => 'pending',
				'per_page' => 1,
				'claimed'  => false,
				'search'   => "[{$order_id}]",
			)
		);

		if ( $existing_jobs ) {
			$existing_job = current( $existing_jobs );

			// Bail out if there's a pending single order action, or a pending dependent action.
			if (
				( self::SINGLE_ORDER_ACTION === $existing_job->get_hook() ) ||
				(
					self::QUEUE_DEPEDENT_ACTION === $existing_job->get_hook() &&
					in_array( self::SINGLE_ORDER_ACTION, $existing_job->get_args() )
				)
			) {
				return;
			}
		}

		// We want to ensure that customer lookup updates are scheduled before order updates.
		self::queue_dependent_action( self::SINGLE_ORDER_ACTION, array( $order_id ), self::CUSTOMERS_BATCH_ACTION );
	}

	/**
	 * Attach order lookup update hooks.
	 */
	public static function orders_lookup_update_init() {
		// Activate WC_Order extension.
		WC_Admin_Order::add_filters();

		add_action( 'save_post_shop_order', array( __CLASS__, 'schedule_single_order_process' ) );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'schedule_single_order_process' ) );

		WC_Admin_Reports_Orders_Stats_Data_Store::init();
		WC_Admin_Reports_Customers_Data_Store::init();
		WC_Admin_Reports_Coupons_Data_Store::init();
		WC_Admin_Reports_Products_Data_Store::init();
		WC_Admin_Reports_Taxes_Data_Store::init();
	}

	/**
	 * Init order/product lookup tables update (in batches).
	 */
	public static function orders_lookup_batch_init() {
		$batch_size  = self::get_batch_size( self::ORDERS_BATCH_ACTION );
		$order_query = new WC_Order_Query(
			array(
				'return'   => 'ids',
				'limit'    => 1,
				'paginate' => true,
			)
		);
		$result      = $order_query->get_orders();

		if ( 0 === $result->total ) {
			return;
		}

		$num_batches = ceil( $result->total / $batch_size );

		self::queue_batches( 1, $num_batches, self::ORDERS_BATCH_ACTION );
	}

	/**
	 * Process a batch of orders to update (stats and products).
	 *
	 * @param int $batch_number Batch number to process (essentially a query page number).
	 * @return void
	 */
	public static function orders_lookup_process_batch( $batch_number ) {
		$batch_size  = self::get_batch_size( self::ORDERS_BATCH_ACTION );
		$order_query = new WC_Order_Query(
			array(
				'return'  => 'ids',
				'limit'   => $batch_size,
				'page'    => $batch_number,
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		);
		$order_ids = $order_query->get_orders();

		foreach ( $order_ids as $order_id ) {
			self::orders_lookup_process_order( $order_id );
		}
	}

	/**
	 * Process a single order to update lookup tables for.
	 * If an error is encountered in one of the updates, a retry action is scheduled.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function orders_lookup_process_order( $order_id ) {
		$result = array_sum(
			array(
				WC_Admin_Reports_Orders_Stats_Data_Store::sync_order( $order_id ),
				WC_Admin_Reports_Products_Data_Store::sync_order_products( $order_id ),
				WC_Admin_Reports_Coupons_Data_Store::sync_order_coupons( $order_id ),
				WC_Admin_Reports_Taxes_Data_Store::sync_order_taxes( $order_id ),
			)
		);

		// If all updates were either skipped or successful, we're done.
		// The update methods return -1 for skip, or a boolean success indicator.
		if ( 4 === absint( $result ) ) {
			return;
		}

		// Otherwise assume an error occurred and reschedule.
		self::schedule_single_order_process( $order_id );
	}

	/**
	 * Returns the batch size for regenerating reports.
	 * Note: can differ per batch action.
	 *
	 * @param string $action Single batch action name.
	 * @return int Batch size.
	 */
	public static function get_batch_size( $action ) {
		$batch_sizes = array(
			self::QUEUE_BATCH_ACTION     => 100,
			self::CUSTOMERS_BATCH_ACTION => 25,
			self::ORDERS_BATCH_ACTION    => 10,
		);
		$batch_size  = isset( $batch_sizes[ $action ] ) ? $batch_sizes[ $action ] : 25;

		/**
		 * Filter the batch size for regenerating a report table.
		 *
		 * @param int    $batch_size Batch size.
		 * @param string $action Batch action name.
		 */
		return apply_filters( 'wc_admin_report_regenerate_batch_size', $batch_size, $action );
	}

	/**
	 * Queue a large number of batch jobs, respecting the batch size limit.
	 * Reduces a range of batches down to "single batch" jobs.
	 *
	 * @param int    $range_start Starting batch number.
	 * @param int    $range_end Ending batch number.
	 * @param string $single_batch_action Action to schedule for a single batch.
	 * @return void
	 */
	public static function queue_batches( $range_start, $range_end, $single_batch_action ) {
		$batch_size       = self::get_batch_size( self::QUEUE_BATCH_ACTION );
		$range_size       = 1 + ( $range_end - $range_start );
		$action_timestamp = time() + 5;

		if ( $range_size > $batch_size ) {
			// If the current batch range is larger than a single batch,
			// split the range into $queue_batch_size chunks.
			$chunk_size = ceil( $range_size / $batch_size );

			for ( $i = 0; $i < $batch_size; $i++ ) {
				$batch_start = $range_start + ( $i * $chunk_size );
				$batch_end   = min( $range_end, $range_start + ( $chunk_size * ( $i + 1 ) ) - 1 );

				self::queue()->schedule_single(
					$action_timestamp,
					self::QUEUE_BATCH_ACTION,
					array( $batch_start, $batch_end, $single_batch_action )
				);
			}
		} else {
			// Otherwise, queue the single batches.
			for ( $i = $range_start; $i <= $range_end; $i++ ) {
				self::queue()->schedule_single( $action_timestamp, $single_batch_action, array( $i ) );
			}
		}
	}

	/**
	 * Queue an action to run after another.
	 *
	 * @param string $action Action to run after prerequisite.
	 * @param array  $action_args Action arguments.
	 * @param string $prerequisite_action Prerequisite action.
	 */
	public static function queue_dependent_action( $action, $action_args, $prerequisite_action ) {
		$blocking_jobs = self::queue()->search(
			array(
				'status'   => 'pending',
				'orderby'  => 'date',
				'order'    => 'DESC',
				'per_page' => 1,
				'claimed'  => false,
				'search'   => $prerequisite_action, // search is used instead of hook to find queued batch creation.
			)
		);

		if ( $blocking_jobs ) {
			$blocking_job       = current( $blocking_jobs );
			$after_blocking_job = $blocking_job->get_schedule()->next()->getTimestamp() + 5;

			self::queue()->schedule_single(
				$after_blocking_job,
				self::QUEUE_DEPEDENT_ACTION,
				array( $action, $action_args, $prerequisite_action )
			);
		} else {
			self::queue()->schedule_single( time() + 5, $action, $action_args );
		}
	}

	/**
	 * Init customer lookup table update (in batches).
	 */
	public static function customer_lookup_batch_init() {
		$batch_size     = self::get_batch_size( self::CUSTOMERS_BATCH_ACTION );
		$customer_query = new WP_User_Query(
			array(
				'fields'  => 'ID',
				'number'  => 1,
			)
		);
		$total_customers = $customer_query->get_total();

		if ( 0 === $total_customers ) {
			return;
		}

		$num_batches     = ceil( $total_customers / $batch_size );

		self::queue_batches( 1, $num_batches, self::CUSTOMERS_BATCH_ACTION );
	}

	/**
	 * Process a batch of customers to update.
	 *
	 * @param int $batch_number Batch number to process (essentially a query page number).
	 * @return void
	 */
	public static function customer_lookup_process_batch( $batch_number ) {
		$batch_size     = self::get_batch_size( self::CUSTOMERS_BATCH_ACTION );
		$customer_query = new WP_User_Query(
			array(
				'fields'  => 'ID',
				'orderby' => 'ID',
				'order'   => 'ASC',
				'number'  => $batch_size,
				'paged'   => $batch_number,
			)
		);

		$customer_ids = $customer_query->get_results();

		foreach ( $customer_ids as $customer_id ) {
			// @todo: schedule single customer update if this fails?
			WC_Admin_Reports_Customers_Data_Store::update_registered_customer( $customer_id );
		}
	}

	/**
	 * Adds data stores.
	 *
	 * @param array $data_stores List of data stores.
	 * @return array
	 */
	public static function add_data_stores( $data_stores ) {
		return array_merge(
			$data_stores,
			array(
				'report-revenue-stats'  => 'WC_Admin_Reports_Orders_Stats_Data_Store',
				'report-orders'         => 'WC_Admin_Reports_Orders_Data_Store',
				'report-orders-stats'   => 'WC_Admin_Reports_Orders_Stats_Data_Store',
				'report-products'       => 'WC_Admin_Reports_Products_Data_Store',
				'report-variations'     => 'WC_Admin_Reports_Variations_Data_Store',
				'report-products-stats' => 'WC_Admin_Reports_Products_Stats_Data_Store',
				'report-categories'     => 'WC_Admin_Reports_Categories_Data_Store',
				'report-taxes'          => 'WC_Admin_Reports_Taxes_Data_Store',
				'report-taxes-stats'    => 'WC_Admin_Reports_Taxes_Stats_Data_Store',
				'report-coupons'        => 'WC_Admin_Reports_Coupons_Data_Store',
				'report-coupons-stats'  => 'WC_Admin_Reports_Coupons_Stats_Data_Store',
				'report-downloads'      => 'WC_Admin_Reports_Downloads_Data_Store',
				'report-downloads-stats' => 'WC_Admin_Reports_Downloads_Stats_Data_Store',
				'admin-note'             => 'WC_Admin_Notes_Data_Store',
				'report-customers'       => 'WC_Admin_Reports_Customers_Data_Store',
				'report-customers-stats' => 'WC_Admin_Reports_Customers_Stats_Data_Store',
			)
		);
	}

	/**
	 * Adds new tables.
	 *
	 * @param array $wc_tables List of WooCommerce tables.
	 * @return array
	 */
	public static function add_tables( $wc_tables ) {
		global $wpdb;

		return array_merge(
			$wc_tables,
			array(
				// @todo: will this work on multisite?
				"{$wpdb->prefix}wc_order_stats",
				"{$wpdb->prefix}wc_order_product_lookup",
				"{$wpdb->prefix}wc_order_tax_lookup",
				"{$wpdb->prefix}wc_order_coupon_lookup",
				"{$wpdb->prefix}woocommerce_admin_notes",
				"{$wpdb->prefix}woocommerce_admin_note_actions",
				"{$wpdb->prefix}wc_customer_lookup",
			)
		);
	}

	/**
	 * Get database schema.
	 *
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
		CREATE TABLE {$wpdb->prefix}wc_order_stats (
			order_id bigint(20) unsigned NOT NULL,
			date_created timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
			num_items_sold int(11) UNSIGNED DEFAULT 0 NOT NULL,
			gross_total double DEFAULT 0 NOT NULL,
			coupon_total double DEFAULT 0 NOT NULL,
			refund_total double DEFAULT 0 NOT NULL,
			tax_total double DEFAULT 0 NOT NULL,
			shipping_total double DEFAULT 0 NOT NULL,
			net_total double DEFAULT 0 NOT NULL,
			returning_customer boolean DEFAULT 0 NOT NULL,
			status varchar(200) NOT NULL,
			customer_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (order_id),
			KEY date_created (date_created),
			KEY customer_id (customer_id),
			KEY status (status)
		  ) $collate;
		  CREATE TABLE {$wpdb->prefix}wc_order_product_lookup (
			order_item_id BIGINT UNSIGNED NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			variation_id BIGINT UNSIGNED NOT NULL,
			customer_id BIGINT UNSIGNED NULL,
			date_created timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
			product_qty INT UNSIGNED NOT NULL,
			product_net_revenue double DEFAULT 0 NOT NULL,
			product_gross_revenue double DEFAULT 0 NOT NULL,
			coupon_amount double DEFAULT 0 NOT NULL,
			tax_amount double DEFAULT 0 NOT NULL,
			shipping_amount double DEFAULT 0 NOT NULL,
			shipping_tax_amount double DEFAULT 0 NOT NULL,
			refund_amount double DEFAULT 0 NOT NULL,
			PRIMARY KEY  (order_item_id),
			KEY order_id (order_id),
			KEY product_id (product_id),
			KEY customer_id (customer_id),
			KEY date_created (date_created)
		  ) $collate;
		  CREATE TABLE {$wpdb->prefix}wc_order_tax_lookup (
		  	order_id BIGINT UNSIGNED NOT NULL,
		  	tax_rate_id BIGINT UNSIGNED NOT NULL,
		  	date_created timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  	shipping_tax double DEFAULT 0 NOT NULL,
		  	order_tax double DEFAULT 0 NOT NULL,
		  	total_tax double DEFAULT 0 NOT NULL,
		  	PRIMARY KEY (order_id, tax_rate_id),
		  	KEY tax_rate_id (tax_rate_id),
		  	KEY date_created (date_created)
		  ) $collate;
		  CREATE TABLE {$wpdb->prefix}wc_order_coupon_lookup (
			order_id BIGINT UNSIGNED NOT NULL,
			coupon_id BIGINT UNSIGNED NOT NULL,
			date_created timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
			discount_amount double DEFAULT 0 NOT NULL,
			PRIMARY KEY (order_id, coupon_id),
			KEY coupon_id (coupon_id),
			KEY date_created (date_created)
		  ) $collate;
			CREATE TABLE {$wpdb->prefix}woocommerce_admin_notes (
				note_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name varchar(255) NOT NULL,
				type varchar(20) NOT NULL,
				locale varchar(20) NOT NULL,
				title longtext NOT NULL,
				content longtext NOT NULL,
				icon varchar(200) NOT NULL,
				content_data longtext NULL default null,
				status varchar(200) NOT NULL,
				source varchar(200) NOT NULL,
				date_created datetime NOT NULL default '0000-00-00 00:00:00',
				date_reminder datetime NULL default null,
				PRIMARY KEY (note_id)
				) $collate;
			CREATE TABLE {$wpdb->prefix}woocommerce_admin_note_actions (
				action_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				note_id BIGINT UNSIGNED NOT NULL,
				name varchar(255) NOT NULL,
				label varchar(255) NOT NULL,
				query longtext NOT NULL,
				PRIMARY KEY (action_id),
				KEY note_id (note_id)
				) $collate;
			CREATE TABLE {$wpdb->prefix}wc_customer_lookup (
				customer_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED DEFAULT NULL,
				username varchar(60) DEFAULT '' NOT NULL,
				first_name varchar(255) NOT NULL,
				last_name varchar(255) NOT NULL,
				email varchar(100) NOT NULL,
				date_last_active timestamp NULL default null,
				date_registered timestamp NULL default null,
				country char(2) DEFAULT '' NOT NULL,
				postcode varchar(20) DEFAULT '' NOT NULL,
				city varchar(100) DEFAULT '' NOT NULL,
				PRIMARY KEY (customer_id),
				UNIQUE KEY user_id (user_id),
				KEY email (email)
				) $collate;
			";

		return $tables;
	}

	/**
	 * Create database tables.
	 */
	public static function create_db_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( self::get_schema() );
	}

	/**
	 * Install plugin.
	 */
	public static function install() {
		// Create tables.
		self::create_db_tables();

		// Initialize report tables.
		add_action( 'woocommerce_after_register_post_type', array( __CLASS__, 'regenerate_report_data' ), 20 );
	}

	/**
	 * Add the currency symbol (in addition to currency code) to each Order
	 * object in REST API responses. For use in formatCurrency().
	 *
	 * @param {WP_REST_Response} $response REST response object.
	 * @returns {WP_REST_Response}
	 */
	public static function add_currency_symbol_to_order_response( $response ) {
		$response_data   = $response->get_data();
		$currency_code   = $response_data['currency'];
		$currency_symbol = get_woocommerce_currency_symbol( $currency_code );
		$response_data['currency_symbol'] = html_entity_decode( $currency_symbol );
		$response->set_data( $response_data );

		return $response;
	}
}

new WC_Admin_Api_Init();
