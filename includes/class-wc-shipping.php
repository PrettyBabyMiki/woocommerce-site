<?php
/**
 * WooCommerce Shipping Class
 *
 * Handles shipping and loads shipping methods via hooks.
 *
 * @class 		WC_Shipping
 * @version		2.6.0
 * @package		WooCommerce/Classes/Shipping
 * @category	Class
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Shipping
 */
class WC_Shipping {

	/** @var bool True if shipping is enabled. */
	public $enabled					= false;

	/** @var array Stores methods loaded into woocommerce. */
	public $shipping_methods;

	/** @var float Stores the cost of shipping */
	public $shipping_total 			= 0;

	/**  @var array Stores an array of shipping taxes. */
	public $shipping_taxes			= array();

	/** @var array Stores the shipping classes. */
	public $shipping_classes		= array();

	/** @var array Stores packages to ship and to get quotes for. */
	public $packages				= array();

	/**
	 * @var WC_Shipping The single instance of the class
	 * @since 2.1
	 */
	protected static $_instance = null;

	/**
	 * Main WC_Shipping Instance.
	 *
	 * Ensures only one instance of WC_Shipping is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @return WC_Shipping Main instance
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
	 * Initialize shipping.
	 */
	public function __construct() {
		$this->enabled = 'yes' === get_option( 'woocommerce_calc_shipping' );

		if ( $this->enabled ) {
			$this->init();
		}
	}

    /**
     * init function.
     */
    public function init() {
		do_action( 'woocommerce_shipping_init' );
	}

	/**
	 * Shipping methods register themselves by returning their main class name through the woocommerce_shipping_methods filter.
	 * @return array
	 */
	public function get_shipping_method_class_names() {
		// Unique Method ID => Method Class name
		return apply_filters( 'woocommerce_shipping_methods', array(
			'flat_rate'              => 'WC_Shipping_Flat_Rate',
			'free_shipping'          => 'WC_Shipping_Free_Shipping',
			'international_delivery' => 'WC_Shipping_International_Delivery',
			'local_delivery'         => 'WC_Shipping_Local_Delivery',
			'local_pickup'           => 'WC_Shipping_Local_Pickup'
		) );
	}

	/**
	 * Load shipping methods.
	 *
	 * Loads all shipping methods which are hooked in. If a $package is passed some methods may add themselves conditionally.
	 *
	 * Methods are sorted into their user-defined order after being loaded.
	 *
	 * @param array $package
	 * @return array
	 */
	public function load_shipping_methods( $package = array() ) {
		if ( $package ) {
			// Register methods for the current customer's zone
			$shipping_zone          = WC_Shipping_Zones::get_zone_matching_package( $package );
			$this->shipping_methods = $shipping_zone->get_shipping_methods();
		} else {
			$this->shipping_methods = array();
		}

		foreach ( $this->get_shipping_method_class_names() as $method_id => $method_class ) {
			$this->register_shipping_method( $method_class );
		}

		// Methods can register themselves manually through this hook
		do_action( 'woocommerce_load_shipping_methods', $package );

		// Return loaded methods
		return $this->get_shipping_methods();
	}

	/**
	 * Register a shipping method.
	 *
	 * @param object|string $method Either the name of the method's class, or an instance of the method's class.
	 */
	public function register_shipping_method( $method ) {
		if ( ! is_object( $method ) ) {
			$method = new $method();
		}
		$this->shipping_methods[ $method->id ] = $method;
	}

	/**
	 * Unregister shipping methods.
	 */
	public function unregister_shipping_methods() {
		$this->shipping_methods = array();
	}

	/**
	 * get_shipping_methods function.
	 *
	 * Returns all registered shipping methods for usage.
	 *
	 * @access public
	 * @return array
	 */
	public function get_shipping_methods() {
		if ( is_null( $this->shipping_methods ) ) {
			$this->load_shipping_methods();
		}
		return $this->shipping_methods;
	}

	/**
	 * get_shipping_classes function.
	 *
	 * Load shipping classes taxonomy terms.
	 *
	 * @access public
	 * @return array
	 */
	public function get_shipping_classes() {
		if ( empty( $this->shipping_classes ) ) {
			$classes                = get_terms( 'product_shipping_class', array( 'hide_empty' => '0' ) );
			$this->shipping_classes = $classes && ! is_wp_error( $classes ) ? $classes : array();
		}
		return $this->shipping_classes;
	}

	/**
	 * Get the default method.
	 * @param  array  $available_methods
	 * @param  boolean $current_chosen_method
	 * @return string
	 */
	private function get_default_method( $available_methods, $current_chosen_method = false ) {
		$selection_priority = get_option( 'woocommerce_shipping_method_selection_priority', array() );

		if ( ! empty( $available_methods ) ) {

			// Is a method already chosen?
			if ( ! empty( $current_chosen_method ) && ! isset( $available_methods[ $current_chosen_method ] ) ) {
				foreach ( $available_methods as $method_key => $method ) {
					if ( strpos( $method->id, $current_chosen_method ) === 0 ) {
						return $method->id;
					}
				}
			}

			// Order by priorities and costs
			$prioritized_methods = array();

			foreach ( $available_methods as $method_key => $method ) {
				// Some IDs contain : if they have multiple rates so use $method->method_id
				$priority  = isset( $selection_priority[ $method->method_id ] ) ? absint( $selection_priority[ $method->method_id ] ): 1;

				if ( empty( $prioritized_methods[ $priority ] ) ) {
					$prioritized_methods[ $priority ] = array();
				}

				$prioritized_methods[ $priority ][ $method_key ] = $method->cost;
			}

			ksort( $prioritized_methods );
			$prioritized_methods = current( $prioritized_methods );
			asort( $prioritized_methods );

			return current( array_keys( $prioritized_methods ) );
		}

		return false;
	}

	/**
	 * Calculate shipping costs.
	 *
	 * Calculate shipping for (multiple) packages of cart items.
	 *
	 * @param array $packages multi-dimensional array of cart items to calc shipping for
	 */
	public function calculate_shipping( $packages = array() ) {
		$this->shipping_total = null;
		$this->shipping_taxes = array();
		$this->packages       = array();

		if ( ! $this->enabled || empty( $packages ) ) {
			return;
		}

		// Calculate costs for passed packages
		$package_keys 		= array_keys( $packages );
		$package_keys_size 	= sizeof( $package_keys );

		for ( $i = 0; $i < $package_keys_size; $i ++ ) {
			$this->packages[ $package_keys[ $i ] ] = $this->calculate_shipping_for_package( $packages[ $package_keys[ $i ] ] );
		}

		// Get all chosen methods
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		$method_counts  = WC()->session->get( 'shipping_method_counts' );

		// Get chosen methods for each package
		foreach ( $this->packages as $i => $package ) {
			$chosen_method    = false;
			$method_count     = false;

			if ( ! empty( $chosen_methods[ $i ] ) ) {
				$chosen_method = $chosen_methods[ $i ];
			}

			if ( ! empty( $method_counts[ $i ] ) ) {
				$method_count = $method_counts[ $i ];
			}

			// Get available methods for package
			$available_methods = $package['rates'];

			if ( sizeof( $available_methods ) > 0 ) {

				// If not set, not available, or available methods have changed, set to the DEFAULT option
				if ( empty( $chosen_method ) || ! isset( $available_methods[ $chosen_method ] ) || $method_count != sizeof( $available_methods ) ) {
					$chosen_method        = apply_filters( 'woocommerce_shipping_chosen_method', $this->get_default_method( $available_methods, $chosen_method ), $available_methods );
					$chosen_methods[ $i ] = $chosen_method;
					$method_counts[ $i ]  = sizeof( $available_methods );
					do_action( 'woocommerce_shipping_method_chosen', $chosen_method );
				}

				// Store total costs
				if ( $chosen_method ) {
					$rate = $available_methods[ $chosen_method ];

					// Merge cost and taxes - label and ID will be the same
					$this->shipping_total += $rate->cost;

					if ( ! empty( $rate->taxes ) && is_array( $rate->taxes ) ) {
						foreach ( array_keys( $this->shipping_taxes + $rate->taxes ) as $key ) {
							$this->shipping_taxes[ $key ] = ( isset( $rate->taxes[$key] ) ? $rate->taxes[$key] : 0 ) + ( isset( $this->shipping_taxes[$key] ) ? $this->shipping_taxes[$key] : 0 );
						}
					}
				}
			}
		}

		// Save all chosen methods (array)
		WC()->session->set( 'chosen_shipping_methods', $chosen_methods );
		WC()->session->set( 'shipping_method_counts', $method_counts );
	}

	/**
	 * Calculate shipping rates for a package,
	 *
	 * Calculates each shipping methods cost. Rates are stored in the session based on the package hash to avoid re-calculation every page load.
	 *
	 * @param array $package cart items
	 * @return array
	 */
	public function calculate_shipping_for_package( $package = array() ) {
		if ( ! $this->enabled || ! $package ) {
			return false;
		}

		// Check if we need to recalculate shipping for this package
		$package_hash   = 'wc_ship_' . md5( json_encode( $package ) . WC_Cache_Helper::get_transient_version( 'shipping' ) );
		$status_options = get_option( 'woocommerce_status_options', array() );
		$stored_rates   = WC()->session->get( 'shipping_for_package' );

		if ( ! is_array( $stored_rates ) || $package_hash !== $stored_rates['package_hash'] || ! empty( $status_options['shipping_debug_mode'] ) ) {
			// Calculate shipping method rates
			$package['rates'] = array();

			foreach ( $this->load_shipping_methods( $package ) as $shipping_method ) {

				if ( $shipping_method->supports( 'shipping-zones' ) && ! $shipping_method->get_instance_id() ) {
					continue;
				}


				if ( $shipping_method->is_available( $package ) && ( empty( $package['ship_via'] ) || in_array( $shipping_method->id, $package['ship_via'] ) ) ) {

					// Reset Rates
					$shipping_method->rates = array();

					// Calculate Shipping for package
					$shipping_method->calculate_shipping( $package );

					// Place rates in package array
					if ( ! empty( $shipping_method->rates ) && is_array( $shipping_method->rates ) ) {
						foreach ( $shipping_method->rates as $rate ) {
							$package['rates'][ $rate->id ] = $rate;
						}
					}
				}
			}

			// Filter the calculated rates
			$package['rates'] = apply_filters( 'woocommerce_package_rates', $package['rates'], $package );

			// Store in session to avoid recalculation
			WC()->session->set( 'shipping_for_package', array(
				'package_hash' => $package_hash,
				'rates'        => $package['rates']
			) );
		} else {
			$package['rates'] = $stored_rates['rates'];
		}

		return $package;
	}

	/**
	 * Get packages.
	 *
	 * @return array
	 */
	public function get_packages() {
		return $this->packages;
	}

	/**
	 * Reset shipping.
	 *
	 * Reset the totals for shipping as a whole.
	 */
	public function reset_shipping() {
		unset( WC()->session->chosen_shipping_methods );
		$this->shipping_total = null;
		$this->shipping_taxes = array();
		$this->packages = array();
	}

	/**
	 * Process admin options.
	 *
	 * Saves options on the shipping setting page.
	 */
	public function process_admin_options() {
		$method_order       = isset( $_POST['method_order'] ) ? $_POST['method_order'] : '';
		$method_priority    = isset( $_POST['method_priority'] ) ? $_POST['method_priority'] : '';
		$order              = array();
		$selection_priority = array();

		if ( is_array( $method_order ) && sizeof( $method_order ) > 0 ) {
			$loop = 0;
			foreach ( $method_order as $method_id ) {
				$order[ $method_id ]              = $loop;
				$selection_priority[ $method_id ] = absint( $method_priority[ $method_id ] );
				$loop ++;
			}
		}

		update_option( 'woocommerce_shipping_method_selection_priority', $selection_priority );
		update_option( 'woocommerce_shipping_method_order', $order );
	}

	/**
	 * @deprecated 2.6.0 Was previously used to determine sort order of methods, but this is now controlled by zones and thus unused.
	 */
	public function sort_shipping_methods() {
		_deprecated_function( 'sort_shipping_methods', '2.6', '' );
		return $this->shipping_methods;
	}
}
