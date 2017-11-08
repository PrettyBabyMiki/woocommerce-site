<?php
/**
 * WooCommerce cart
 *
 * The WooCommerce cart class stores cart data and active coupons as well as handling customer sessions and some cart related urls.
 * The cart class also has a price calculation function which calls upon other classes to calculate totals.
 *
 * @version		2.1.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( WC_ABSPATH . 'includes/legacy/class-wc-legacy-cart.php' );
include_once( WC_ABSPATH . 'includes/class-wc-cart-session.php' );

/**
 * WC_Cart class.
 */
class WC_Cart extends WC_Legacy_Cart {

	/**
	 * Contains an array of cart items.
	 *
	 * @var array
	 */
	public $cart_contents = array();

	/**
	 * Contains an array of removed cart items so we can restore them if needed.
	 *
	 * @var array
	 */
	public $removed_cart_contents = array();

	/**
	 * Contains an array of coupon codes applied to the cart.
	 *
	 * @var array
	 */
	public $applied_coupons = array();

	/**
	 * Are prices in the cart displayed inc or excl tax.
	 *
	 * @var string
	 */
	public $tax_display_cart;

	/**
	 * This stores the chosen shipping methods for the cart item packages.
	 *
	 * @var array
	 */
	protected $shipping_methods;

	/**
	 * Total defaults used to reset.
	 *
	 * @var array
	 */
	protected $default_totals = array(
		'subtotal'            => 0,
		'subtotal_tax'        => 0,
		'shipping_total'      => 0,
		'shipping_tax'        => 0,
		'shipping_taxes'      => array(),
		'discount_total'      => 0,
		'discount_tax'        => 0,
		'cart_contents_total' => 0,
		'cart_contents_tax'   => 0,
		'cart_contents_taxes' => array(),
		'fee_total'           => 0,
		'fee_tax'             => 0,
		'fee_taxes'           => array(),
		'total'               => 0,
		'total_tax'           => 0,
	);
	/**
	 * Store calculated totals.
	 *
	 * @var array
	 */
	protected $totals = array();

	/**
	 * Reference to the cart session handling class.
	 *
	 * @var WC_Cart_Session
	 */
	protected $session;

	/**
	 * Reference to the cart fees API class.
	 *
	 * @var WC_Cart_Fees
	 */
	protected $fees_api;

	/**
	 * Constructor for the cart class. Loads options and hooks in the init method.
	 */
	public function __construct() {
		$this->session          = new WC_Cart_Session( $this );
		$this->fees_api         = new WC_Cart_Fees( $this );
		$this->tax_display_cart = get_option( 'woocommerce_tax_display_cart' );

		// Register hooks for the objects.
		$this->session->init();
		$this->fees_api->init();

		add_action( 'woocommerce_add_to_cart', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_applied_coupon', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_items' ), 1 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_coupons' ), 1 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'check_customer_coupons' ), 1 );
	}

	/**
	 * When cloning, ensure object properties are handled.
	 *
	 * These properties store a reference to the cart, so we use new instead of clone.
	 */
	public function __clone() {
		$this->session  = new WC_Cart_Session( $this );
		$this->fees_api = new WC_Cart_Fees( $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters.
	|--------------------------------------------------------------------------
	|
	| Methods to retrieve class properties and avoid direct access.
	*/

	/**
	 * Gets cart contents.
	 *
	 * @since 3.2.0
	 * @return array of applied coupons
	 */
	public function get_cart_contents() {
		return (array) $this->cart_contents;
	}

	/**
	 * Return items removed from the cart.
	 *
	 * @since 3.2.0
	 * @return array
	 */
	public function get_removed_cart_contents() {
		return (array) $this->removed_cart_contents;
	}

	/**
	 * Gets the array of applied coupon codes.
	 *
	 * @return array of applied coupons
	 */
	public function get_applied_coupons() {
		return (array) $this->applied_coupons;
	}

	/**
	 * Return all calculated coupon totals.
	 *
	 * @since 3.2.0
	 * @return array
	 */
	public function get_coupon_discount_totals() {
		return (array) $this->coupon_discount_totals;
	}
	/**
	 * Return all calculated coupon tax totals.
	 *
	 * @since 3.2.0
	 * @return array
	 */
	public function get_coupon_discount_tax_totals() {
		return (array) $this->coupon_discount_tax_totals;
	}

	/**
	 * Return all calculated totals.
	 *
	 * @since 3.2.0
	 * @return array
	 */
	public function get_totals() {
		return empty( $this->totals ) ? $this->default_totals : $this->totals;
	}

	/**
	 * Get a total.
	 *
	 * @since 3.2.0
	 * @param string $key Key of element in $totals array.
	 * @return mixed
	 */
	protected function get_totals_var( $key ) {
		return isset( $this->totals[ $key ] ) ? $this->totals[ $key ] : $this->default_totals[ $key ];
	}

	/**
	 * Get subtotal.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_subtotal() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'subtotal' ) );
	}

	/**
	 * Get subtotal.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_subtotal_tax() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'subtotal_tax' ) );
	}

	/**
	 * Get discount_total.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_discount_total() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'discount_total' ) );
	}

	/**
	 * Get discount_tax.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_discount_tax() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'discount_tax' ) );
	}

	/**
	 * Get shipping_total.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_shipping_total() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'shipping_total' ) );
	}

	/**
	 * Get shipping_tax.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_shipping_tax() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'shipping_tax' ) );
	}

	/**
	 * Gets cart total. This is the total of items in the cart, but after discounts. Subtotal is before discounts.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_cart_contents_total() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'cart_contents_total' ) );
	}

	/**
	 * Gets cart tax amount.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_cart_contents_tax() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'cart_contents_tax' ) );
	}

	/**
	 * Gets cart total after calculation.
	 *
	 * @since 3.2.0
	 * @param string $context If the context is view, the value will be formatted for display. This keeps it compatible with pre-3.2 versions.
	 * @return float
	 */
	public function get_total( $context = 'view' ) {
		$total = apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'total' ) );
		return 'view' === $context ? apply_filters( 'woocommerce_cart_total', wc_price( $total ) ) : $total;
	}

	/**
	 * Get total tax amount.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_total_tax() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'total_tax' ) );
	}

	/**
	 * Get total fee amount.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_fee_total() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'fee_total' ) );
	}

	/**
	 * Get total fee tax amount.
	 *
	 * @since 3.2.0
	 * @return float
	 */
	public function get_fee_tax() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'fee_tax' ) );
	}

	/**
	 * Get taxes.
	 *
	 * @since 3.2.0
	 */
	public function get_shipping_taxes() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'shipping_taxes' ) );
	}

	/**
	 * Get taxes.
	 *
	 * @since 3.2.0
	 */
	public function get_cart_contents_taxes() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'cart_contents_taxes' ) );
	}

	/**
	 * Get taxes.
	 *
	 * @since 3.2.0
	 */
	public function get_fee_taxes() {
		return apply_filters( 'woocommerce_cart_' . __FUNCTION__, $this->get_totals_var( 'fee_taxes' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters.
	|--------------------------------------------------------------------------
	|
	| Methods to set class properties and avoid direct access.
	*/

	/**
	 * Sets the contents of the cart.
	 *
	 * @param array $value Cart array.
	 */
	public function set_cart_contents( $value ) {
		$this->cart_contents = (array) $value;
	}

	/**
	 * Set items removed from the cart.
	 *
	 * @since 3.2.0
	 * @param array $value Item array.
	 */
	public function set_removed_cart_contents( $value = array() ) {
		$this->removed_cart_contents = (array) $value;
	}

	/**
	 * Sets the array of applied coupon codes.
	 *
	 * @param array $value List of applied coupon codes.
	 */
	public function set_applied_coupons( $value = array() ) {
		$this->applied_coupons = (array) $value;
	}

	/**
	 * Return all calculated coupon totals.
	 *
	 * @since 3.2.0
	 * @param array $value Value to set.
	 */
	public function set_coupon_discount_totals( $value = array() ) {
		$this->coupon_discount_totals = (array) $value;
	}
	/**
	 * Return all calculated coupon tax totals.
	 *
	 * @since 3.2.0
	 * @param array $value Value to set.
	 */
	public function set_coupon_discount_tax_totals( $value = array() ) {
		$this->coupon_discount_tax_totals = (array) $value;
	}

	/**
	 * Set all calculated totals.
	 *
	 * @since 3.2.0
	 * @param array $value Value to set.
	 */
	public function set_totals( $value = array() ) {
		$this->totals = wp_parse_args( $value, $this->default_totals );
	}

	/**
	 * Set subtotal.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_subtotal( $value ) {
		$this->totals['subtotal'] = wc_format_decimal( $value );
	}

	/**
	 * Set subtotal.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_subtotal_tax( $value ) {
		$this->totals['subtotal_tax'] = wc_round_tax_total( $value );
	}

	/**
	 * Set discount_total.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_discount_total( $value ) {
		$this->totals['discount_total'] = wc_cart_round_discount( $value, wc_get_price_decimals() );
	}

	/**
	 * Set discount_tax.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_discount_tax( $value ) {
		$this->totals['discount_tax'] = wc_format_decimal( $value );
	}

	/**
	 * Set shipping_total.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_shipping_total( $value ) {
		$this->totals['shipping_total'] = wc_format_decimal( $value );
	}

	/**
	 * Set shipping_tax.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_shipping_tax( $value ) {
		$this->totals['shipping_tax'] = wc_round_tax_total( $value );
	}

	/**
	 * Set cart_contents_total.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_cart_contents_total( $value ) {
		$this->totals['cart_contents_total'] = wc_format_decimal( $value );
	}

	/**
	 * Set cart tax amount.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_cart_contents_tax( $value ) {
		$this->totals['cart_contents_tax'] = wc_round_tax_total( $value );
	}

	/**
	 * Set cart total.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_total( $value ) {
		$this->totals['total'] = wc_format_decimal( $value );
	}

	/**
	 * Set total tax amount.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_total_tax( $value ) {
		$this->totals['total_tax'] = wc_round_tax_total( $value );
	}

	/**
	 * Set fee amount.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_fee_total( $value ) {
		$this->totals['fee_total'] = wc_format_decimal( $value );
	}

	/**
	 * Set fee tax.
	 *
	 * @since 3.2.0
	 * @param string $value Value to set.
	 */
	public function set_fee_tax( $value ) {
		$this->totals['fee_tax'] = wc_round_tax_total( $value );
	}

	/**
	 * Set taxes.
	 *
	 * @since 3.2.0
	 * @param array $value Tax values.
	 */
	public function set_shipping_taxes( $value ) {
		$this->totals['shipping_taxes'] = (array) $value;
	}

	/**
	 * Set taxes.
	 *
	 * @since 3.2.0
	 * @param array $value Tax values.
	 */
	public function set_cart_contents_taxes( $value ) {
		$this->totals['cart_contents_taxes'] = (array) $value;
	}

	/**
	 * Set taxes.
	 *
	 * @since 3.2.0
	 * @param array $value Tax values.
	 */
	public function set_fee_taxes( $value ) {
		$this->totals['fee_taxes'] = (array) $value;
	}

	/*
	|--------------------------------------------------------------------------
	| Helper methods.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns the cart and shipping taxes, merged.
	 *
	 * @return array merged taxes
	 */
	public function get_taxes() {
		return apply_filters( 'woocommerce_cart_get_taxes', wc_array_merge_recursive_numeric( $this->get_shipping_taxes(), $this->get_cart_contents_taxes(), $this->get_fee_taxes() ), $this );
	}

	/**
	 * Returns the contents of the cart in an array.
	 *
	 * @return array contents of the cart
	 */
	public function get_cart() {
		if ( ! did_action( 'wp_loaded' ) ) {
			wc_doing_it_wrong( __FUNCTION__, __( 'Get cart should not be called before the wp_loaded action.', 'woocommerce' ), '2.3' );
		}
		if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
			$this->session->get_cart_from_session();
		}
		return array_filter( $this->get_cart_contents() );
	}

	/**
	 * Returns a specific item in the cart.
	 *
	 * @param string $item_key Cart item key.
	 * @return array Item data
	 */
	public function get_cart_item( $item_key ) {
		return isset( $this->cart_contents[ $item_key ] ) ? $this->cart_contents[ $item_key ] : array();
	}

	/**
	 * Checks if the cart is empty.
	 *
	 * @return bool
	 */
	public function is_empty() {
		return 0 === count( $this->get_cart() );
	}

	/**
	 * Empties the cart and optionally the persistent cart too.
	 *
	 * @param bool $clear_persistent_cart Should the persistant cart be cleared too. Defaults to true.
	 */
	public function empty_cart( $clear_persistent_cart = true ) {
		$this->cart_contents              = array();
		$this->removed_cart_contents      = array();
		$this->shipping_methods           = array();
		$this->coupon_discount_totals     = array();
		$this->coupon_discount_tax_totals = array();
		$this->applied_coupons            = array();
		$this->totals                     = $this->default_totals;

		if ( $clear_persistent_cart ) {
			$this->session->persistent_cart_destroy();
		}

		do_action( 'woocommerce_cart_emptied' );
	}

	/**
	 * Get number of items in the cart.
	 *
	 * @return int
	 */
	public function get_cart_contents_count() {
		return apply_filters( 'woocommerce_cart_contents_count', array_sum( wp_list_pluck( $this->get_cart(), 'quantity' ) ) );
	}

	/**
	 * Get weight of items in the cart.
	 *
	 * @since 2.5.0
	 * @return int
	 */
	public function get_cart_contents_weight() {
		$weight = 0;

		foreach ( $this->get_cart() as $cart_item_key => $values ) {
			$weight += (float) $values['data']->get_weight() * $values['quantity'];
		}

		return apply_filters( 'woocommerce_cart_contents_weight', $weight );
	}

	/**
	 * Get cart items quantities - merged so we can do accurate stock checks on items across multiple lines.
	 *
	 * @return array
	 */
	public function get_cart_item_quantities() {
		$quantities = array();

		foreach ( $this->get_cart() as $cart_item_key => $values ) {
			$product = $values['data'];
			$quantities[ $product->get_stock_managed_by_id() ] = isset( $quantities[ $product->get_stock_managed_by_id() ] ) ? $quantities[ $product->get_stock_managed_by_id() ] + $values['quantity'] : $values['quantity'];
		}

		return $quantities;
	}

	/**
	 * Check all cart items for errors.
	 */
	public function check_cart_items() {
		$return = true;
		$result = $this->check_cart_item_validity();

		if ( is_wp_error( $result ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );
			$return = false;
		}

		$result = $this->check_cart_item_stock();

		if ( is_wp_error( $result ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );
			$return = false;
		}

		return $return;

	}

	/**
	 * Check cart coupons for errors.
	 */
	public function check_cart_coupons() {
		foreach ( $this->get_applied_coupons() as $code ) {
			$coupon = new WC_Coupon( $code );

			if ( ! $coupon->is_valid() ) {
				$coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_INVALID_REMOVED );
				$this->remove_coupon( $code );
			}
		}
	}

	/**
	 * Looks through cart items and checks the posts are not trashed or deleted.
	 *
	 * @return bool|WP_Error
	 */
	public function check_cart_item_validity() {
		$return = true;

		foreach ( $this->get_cart() as $cart_item_key => $values ) {
			$product = $values['data'];

			if ( ! $product || ! $product->exists() || 'trash' === $product->get_status() ) {
				$this->set_quantity( $cart_item_key, 0 );
				$return = new WP_Error( 'invalid', __( 'An item which is no longer available was removed from your cart.', 'woocommerce' ) );
			}
		}

		return $return;
	}

	/**
	 * Looks through the cart to check each item is in stock. If not, add an error.
	 *
	 * @return bool|WP_Error
	 */
	public function check_cart_item_stock() {
		global $wpdb;

		$error               = new WP_Error();
		$product_qty_in_cart = $this->get_cart_item_quantities();

		foreach ( $this->get_cart() as $cart_item_key => $values ) {
			$product = $values['data'];

			/**
			 * Check stock based on stock-status.
			 */
			if ( ! $product->is_in_stock() ) {
				/* translators: %s: product name */
				$error->add( 'out-of-stock', sprintf( __( 'Sorry, "%s" is not in stock. Please edit your cart and try again. We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name() ) );
				return $error;
			}

			if ( ! $product->managing_stock() ) {
				continue;
			}

			/**
			 * Check stock based on all items in the cart.
			 */
			if ( ! $product->has_enough_stock( $product_qty_in_cart[ $product->get_stock_managed_by_id() ] ) ) {
				/* translators: 1: product name 2: quantity in stock */
				$error->add( 'out-of-stock', sprintf( __( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order (%2$s in stock). Please edit your cart and try again. We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name(), wc_format_stock_quantity_for_display( $product->get_stock_quantity(), $product ) ) );
				return $error;
			}

			/**
			 * Finally consider any held stock, from pending orders.
			 */
			if ( get_option( 'woocommerce_hold_stock_minutes' ) > 0 && ! $product->backorders_allowed() ) {
				$order_id   = isset( WC()->session->order_awaiting_payment ) ? absint( WC()->session->order_awaiting_payment ) : 0;
				$held_stock = $wpdb->get_var(
					$wpdb->prepare( "
						SELECT SUM( order_item_meta.meta_value ) AS held_qty
						FROM {$wpdb->posts} AS posts
						LEFT JOIN {$wpdb->prefix}woocommerce_order_items as order_items ON posts.ID = order_items.order_id
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
						LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta2 ON order_items.order_item_id = order_item_meta2.order_item_id
						WHERE 	order_item_meta.meta_key   = '_qty'
						AND 	order_item_meta2.meta_key  = %s AND order_item_meta2.meta_value  = %d
						AND 	posts.post_type            IN ( '" . implode( "','", wc_get_order_types() ) . "' )
						AND 	posts.post_status          = 'wc-pending'
						AND		posts.ID                   != %d;",
						'variation' === get_post_type( $product->get_stock_managed_by_id() ) ? '_variation_id' : '_product_id',
						$product->get_stock_managed_by_id(),
						$order_id
					)
				);

				if ( $product->get_stock_quantity() < ( $held_stock + $product_qty_in_cart[ $product->get_stock_managed_by_id() ] ) ) {
					/* translators: 1: product name 2: minutes */
					$error->add( 'out-of-stock', sprintf( __( 'Sorry, we do not have enough "%1$s" in stock to fulfill your order right now. Please try again in %2$d minutes or edit your cart and try again. We apologize for any inconvenience caused.', 'woocommerce' ), $product->get_name(), get_option( 'woocommerce_hold_stock_minutes' ) ) );
					return $error;
				}
			}
		}

		return true;
	}

	/**
	 * Gets and formats a list of cart item data + variations for display on the frontend.
	 *
	 * @param array $cart_item Cart item object.
	 * @param bool  $flat Should the data be returned flat or in a list.
	 * @return string
	 */
	public function get_item_data( $cart_item, $flat = false ) {
		$item_data = array();

		// Variation values are shown only if they are not found in the title as of 3.0.
		// This is because variation titles display the attributes.
		if ( $cart_item['data']->is_type( 'variation' ) && is_array( $cart_item['variation'] ) ) {
			foreach ( $cart_item['variation'] as $name => $value ) {
				$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

				if ( taxonomy_exists( $taxonomy ) ) {
					// If this is a term slug, get the term's nice name.
					$term = get_term_by( 'slug', $value, $taxonomy );
					if ( ! is_wp_error( $term ) && $term && $term->name ) {
						$value = $term->name;
					}
					$label = wc_attribute_label( $taxonomy );
				} else {
					// If this is a custom option slug, get the options name.
					$value = apply_filters( 'woocommerce_variation_option_name', $value );
					$label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $cart_item['data'] );
				}

				// Check the nicename against the title.
				if ( '' === $value || wc_is_attribute_in_product_name( $value, $cart_item['data']->get_name() ) ) {
					continue;
				}

				$item_data[] = array(
					'key'   => $label,
					'value' => $value,
				);
			}
		}

		// Filter item data to allow 3rd parties to add more to the array.
		$item_data = apply_filters( 'woocommerce_get_item_data', $item_data, $cart_item );

		// Format item data ready to display.
		foreach ( $item_data as $key => $data ) {
			// Set hidden to true to not display meta on cart.
			if ( ! empty( $data['hidden'] ) ) {
				unset( $item_data[ $key ] );
				continue;
			}
			$item_data[ $key ]['key']     = ! empty( $data['key'] ) ? $data['key'] : $data['name'];
			$item_data[ $key ]['display'] = ! empty( $data['display'] ) ? $data['display'] : $data['value'];
		}

		// Output flat or in list format.
		if ( count( $item_data ) > 0 ) {
			ob_start();

			if ( $flat ) {
				foreach ( $item_data as $data ) {
					echo esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['display'] ) . "\n";
				}
			} else {
				wc_get_template( 'cart/cart-item-data.php', array( 'item_data' => $item_data ) );
			}

			return ob_get_clean();
		}

		return '';
	}

	/**
	 * Gets cross sells based on the items in the cart.
	 *
	 * @return array cross_sells (item ids)
	 */
	public function get_cross_sells() {
		$cross_sells = array();
		$in_cart     = array();
		if ( ! $this->is_empty() ) {
			foreach ( $this->get_cart() as $cart_item_key => $values ) {
				if ( $values['quantity'] > 0 ) {
					$cross_sells = array_merge( $values['data']->get_cross_sell_ids(), $cross_sells );
					$in_cart[]   = $values['product_id'];
				}
			}
		}
		$cross_sells = array_diff( $cross_sells, $in_cart );
		return apply_filters( 'woocommerce_cart_crosssell_ids', wp_parse_id_list( $cross_sells ), $this );
	}

	/**
	 * Gets the url to remove an item from the cart.
	 *
	 * @param string $cart_item_key contains the id of the cart item.
	 * @return string url to page
	 */
	public function get_remove_url( $cart_item_key ) {
		$cart_page_url = wc_get_page_permalink( 'cart' );
		return apply_filters( 'woocommerce_get_remove_url', $cart_page_url ? wp_nonce_url( add_query_arg( 'remove_item', $cart_item_key, $cart_page_url ), 'woocommerce-cart' ) : '' );
	}

	/**
	 * Gets the url to re-add an item into the cart.
	 *
	 * @param  string $cart_item_key Cart item key to undo.
	 * @return string url to page
	 */
	public function get_undo_url( $cart_item_key ) {
		$cart_page_url = wc_get_page_permalink( 'cart' );

		$query_args = array(
			'undo_item' => $cart_item_key,
		);

		return apply_filters( 'woocommerce_get_undo_url', $cart_page_url ? wp_nonce_url( add_query_arg( $query_args, $cart_page_url ), 'woocommerce-cart' ) : '', $cart_item_key );
	}

	/**
	 * Get taxes, merged by code, formatted ready for output.
	 *
	 * @return array
	 */
	public function get_tax_totals() {
		$taxes      = $this->get_taxes();
		$tax_totals = array();

		foreach ( $taxes as $key => $tax ) {
			$code = WC_Tax::get_rate_code( $key );

			if ( $code || $key === apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) ) {
				if ( ! isset( $tax_totals[ $code ] ) ) {
					$tax_totals[ $code ] = new stdClass();
					$tax_totals[ $code ]->amount = 0;
				}
				$tax_totals[ $code ]->tax_rate_id       = $key;
				$tax_totals[ $code ]->is_compound       = WC_Tax::is_compound( $key );
				$tax_totals[ $code ]->label             = WC_Tax::get_rate_label( $key );
				$tax_totals[ $code ]->amount           += wc_round_tax_total( $tax );
				$tax_totals[ $code ]->formatted_amount  = wc_price( wc_round_tax_total( $tax_totals[ $code ]->amount ) );
			}
		}

		if ( apply_filters( 'woocommerce_cart_hide_zero_taxes', true ) ) {
			$amounts    = array_filter( wp_list_pluck( $tax_totals, 'amount' ) );
			$tax_totals = array_intersect_key( $tax_totals, $amounts );
		}

		return apply_filters( 'woocommerce_cart_tax_totals', $tax_totals, $this );
	}

	/**
	 * Get all tax classes for items in the cart.
	 *
	 * @return array
	 */
	public function get_cart_item_tax_classes() {
		$found_tax_classes = array();

		foreach ( WC()->cart->get_cart() as $item ) {
			if ( $item['data'] && ( $item['data']->is_taxable() || $item['data']->is_shipping_taxable() ) ) {
				$found_tax_classes[] = $item['data']->get_tax_class();
			}
		}

		return array_unique( $found_tax_classes );
	}

	/**
	 * Determines the value that the customer spent and the subtotal
	 * displayed, used for things like coupon validation.
	 *
	 * Since the coupon lines are displayed based on the TAX DISPLAY value
	 * of cart, this is used to determine the spend.
	 *
	 * If cart totals are shown including tax, use the subtotal.
	 * If cart totals are shown excluding tax, use the subtotal ex tax
	 * (tax is shown after coupons).
	 *
	 * @since 2.6.0
	 * @return string
	 */
	public function get_displayed_subtotal() {
		return 'incl' === $this->tax_display_cart ? $this->get_subtotal() + $this->get_subtotal_tax() : $this->get_subtotal();
	}

	/**
	 * Check if product is in the cart and return cart item key.
	 *
	 * Cart item key will be unique based on the item and its properties, such as variations.
	 *
	 * @param mixed $cart_id id of product to find in the cart.
	 * @return string cart item key
	 */
	public function find_product_in_cart( $cart_id = false ) {
		if ( false !== $cart_id ) {
			if ( is_array( $this->cart_contents ) && isset( $this->cart_contents[ $cart_id ] ) ) {
				return $cart_id;
			}
		}
		return '';
	}

	/**
	 * Generate a unique ID for the cart item being added.
	 *
	 * @param int   $product_id - id of the product the key is being generated for.
	 * @param int   $variation_id of the product the key is being generated for.
	 * @param array $variation data for the cart item.
	 * @param array $cart_item_data other cart item data passed which affects this items uniqueness in the cart.
	 * @return string cart item key
	 */
	public function generate_cart_id( $product_id, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		$id_parts = array( $product_id );

		if ( $variation_id && 0 !== $variation_id ) {
			$id_parts[] = $variation_id;
		}

		if ( is_array( $variation ) && ! empty( $variation ) ) {
			$variation_key = '';
			foreach ( $variation as $key => $value ) {
				$variation_key .= trim( $key ) . trim( $value );
			}
			$id_parts[] = $variation_key;
		}

		if ( is_array( $cart_item_data ) && ! empty( $cart_item_data ) ) {
			$cart_item_data_key = '';
			foreach ( $cart_item_data as $key => $value ) {
				if ( is_array( $value ) || is_object( $value ) ) {
					$value = http_build_query( $value );
				}
				$cart_item_data_key .= trim( $key ) . trim( $value );

			}
			$id_parts[] = $cart_item_data_key;
		}

		return apply_filters( 'woocommerce_cart_id', md5( implode( '_', $id_parts ) ), $product_id, $variation_id, $variation, $cart_item_data );
	}

	/**
	 * Add a product to the cart.
	 *
	 * @throws Exception Plugins can throw an exception to prevent adding to cart.
	 * @param int   $product_id contains the id of the product to add to the cart.
	 * @param int   $quantity contains the quantity of the item to add.
	 * @param int   $variation_id ID of the variation being added to the cart.
	 * @param array $variation attribute values.
	 * @param array $cart_item_data extra cart item data we want to pass into the item.
	 * @return string|bool $cart_item_key
	 */
	public function add_to_cart( $product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		try {
			$product_id   = absint( $product_id );
			$variation_id = absint( $variation_id );

			// Ensure we don't add a variation to the cart directly by variation ID.
			if ( 'product_variation' === get_post_type( $product_id ) ) {
				$variation_id = $product_id;
				$product_id   = wp_get_post_parent_id( $variation_id );
			}

			$product_data = wc_get_product( $variation_id ? $variation_id : $product_id );
			$quantity     = apply_filters( 'woocommerce_add_to_cart_quantity', $quantity, $product_id );

			if ( $quantity <= 0 || ! $product_data || 'trash' === $product_data->get_status() ) {
				return false;
			}

			// Load cart item data - may be added by other plugins.
			$cart_item_data = (array) apply_filters( 'woocommerce_add_cart_item_data', $cart_item_data, $product_id, $variation_id );

			// Generate a ID based on product ID, variation ID, variation data, and other cart item data.
			$cart_id        = $this->generate_cart_id( $product_id, $variation_id, $variation, $cart_item_data );

			// Find the cart item key in the existing cart.
			$cart_item_key  = $this->find_product_in_cart( $cart_id );

			// Force quantity to 1 if sold individually and check for existing item in cart.
			if ( $product_data->is_sold_individually() ) {
				$quantity      = apply_filters( 'woocommerce_add_to_cart_sold_individually_quantity', 1, $quantity, $product_id, $variation_id, $cart_item_data );
				$found_in_cart = apply_filters( 'woocommerce_add_to_cart_sold_individually_found_in_cart', $cart_item_key && $this->cart_contents[ $cart_item_key ]['quantity'] > 0, $product_id, $variation_id, $cart_item_data, $cart_id );

				if ( $found_in_cart ) {
					/* translators: %s: product name */
					throw new Exception( sprintf( '<a href="%s" class="button wc-forward">%s</a> %s', wc_get_cart_url(), __( 'View cart', 'woocommerce' ), sprintf( __( 'You cannot add another "%s" to your cart.', 'woocommerce' ), $product_data->get_name() ) ) );
				}
			}

			if ( ! $product_data->is_purchasable() ) {
				throw new Exception( __( 'Sorry, this product cannot be purchased.', 'woocommerce' ) );
			}

			// Stock check - only check if we're managing stock and backorders are not allowed.
			if ( ! $product_data->is_in_stock() ) {
				throw new Exception( sprintf( __( 'You cannot add &quot;%s&quot; to the cart because the product is out of stock.', 'woocommerce' ), $product_data->get_name() ) );
			}

			if ( ! $product_data->has_enough_stock( $quantity ) ) {
				/* translators: 1: product name 2: quantity in stock */
				throw new Exception( sprintf( __( 'You cannot add that amount of &quot;%1$s&quot; to the cart because there is not enough stock (%2$s remaining).', 'woocommerce' ), $product_data->get_name(), wc_format_stock_quantity_for_display( $product_data->get_stock_quantity(), $product_data ) ) );
			}

			// Stock check - this time accounting for whats already in-cart.
			if ( $product_data->managing_stock() ) {
				$products_qty_in_cart = $this->get_cart_item_quantities();

				if ( isset( $products_qty_in_cart[ $product_data->get_stock_managed_by_id() ] ) && ! $product_data->has_enough_stock( $products_qty_in_cart[ $product_data->get_stock_managed_by_id() ] + $quantity ) ) {
					throw new Exception( sprintf(
						'<a href="%s" class="button wc-forward">%s</a> %s',
						wc_get_cart_url(),
						__( 'View Cart', 'woocommerce' ),
						sprintf( __( 'You cannot add that amount to the cart &mdash; we have %1$s in stock and you already have %2$s in your cart.', 'woocommerce' ), wc_format_stock_quantity_for_display( $product_data->get_stock_quantity(), $product_data ), wc_format_stock_quantity_for_display( $products_qty_in_cart[ $product_data->get_stock_managed_by_id() ], $product_data ) )
					) );
				}
			}

			// If cart_item_key is set, the item is already in the cart.
			if ( $cart_item_key ) {
				$new_quantity = $quantity + $this->cart_contents[ $cart_item_key ]['quantity'];
				$this->set_quantity( $cart_item_key, $new_quantity, false );
			} else {
				$cart_item_key = $cart_id;

				// Add item after merging with $cart_item_data - hook to allow plugins to modify cart item.
				$this->cart_contents[ $cart_item_key ] = apply_filters( 'woocommerce_add_cart_item', array_merge( $cart_item_data, array(
					'key'          => $cart_item_key,
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
					'variation'    => $variation,
					'quantity'     => $quantity,
					'data'         => $product_data,
				) ), $cart_item_key );
			}

			do_action( 'woocommerce_add_to_cart', $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );

			return $cart_item_key;

		} catch ( Exception $e ) {
			if ( $e->getMessage() ) {
				wc_add_notice( $e->getMessage(), 'error' );
			}
			return false;
		}
	}

	/**
	 * Remove a cart item.
	 *
	 * @since  2.3.0
	 * @param  string $cart_item_key Cart item key to remove from the cart.
	 * @return bool
	 */
	public function remove_cart_item( $cart_item_key ) {
		if ( isset( $this->cart_contents[ $cart_item_key ] ) ) {
			$this->removed_cart_contents[ $cart_item_key ] = $this->cart_contents[ $cart_item_key ];

			unset( $this->removed_cart_contents[ $cart_item_key ]['data'] );

			do_action( 'woocommerce_remove_cart_item', $cart_item_key, $this );

			unset( $this->cart_contents[ $cart_item_key ] );

			do_action( 'woocommerce_cart_item_removed', $cart_item_key, $this );

			return true;
		}
		return false;
	}

	/**
	 * Restore a cart item.
	 *
	 * @param  string $cart_item_key Cart item key to restore to the cart.
	 * @return bool
	 */
	public function restore_cart_item( $cart_item_key ) {
		if ( isset( $this->removed_cart_contents[ $cart_item_key ] ) ) {
			$restore_item                                  = $this->removed_cart_contents[ $cart_item_key ];
			$this->cart_contents[ $cart_item_key ]         = $restore_item;
			$this->cart_contents[ $cart_item_key ]['data'] = wc_get_product( $restore_item['variation_id'] ? $restore_item['variation_id'] : $restore_item['product_id'] );

			do_action( 'woocommerce_restore_cart_item', $cart_item_key, $this );

			unset( $this->removed_cart_contents[ $cart_item_key ] );

			do_action( 'woocommerce_cart_item_restored', $cart_item_key, $this );

			return true;
		}
		return false;
	}

	/**
	 * Set the quantity for an item in the cart.
	 *
	 * @param string $cart_item_key	contains the id of the cart item.
	 * @param int    $quantity contains the quantity of the item.
	 * @param bool   $refresh_totals whether or not to calculate totals after setting the new qty.
	 * @return bool
	 */
	public function set_quantity( $cart_item_key, $quantity = 1, $refresh_totals = true ) {
		if ( 0 === $quantity || $quantity < 0 ) {
			do_action( 'woocommerce_before_cart_item_quantity_zero', $cart_item_key, $this );
			unset( $this->cart_contents[ $cart_item_key ] );
		} else {
			$old_quantity = $this->cart_contents[ $cart_item_key ]['quantity'];
			$this->cart_contents[ $cart_item_key ]['quantity'] = $quantity;
			do_action( 'woocommerce_after_cart_item_quantity_update', $cart_item_key, $quantity, $old_quantity, $this );
		}

		if ( $refresh_totals ) {
			$this->calculate_totals();
		}

		return true;
	}

	/**
	 * Get cart's owner.
	 *
	 * @since  3.2.0
	 * @return WC_Customer
	 */
	public function get_customer() {
		return WC()->customer;
	}

	/**
	 * Calculate totals for the items in the cart.
	 *
	 * @uses WC_Cart_Totals
	 */
	public function calculate_totals() {
		$this->reset_totals();

		if ( $this->is_empty() ) {
			$this->session->set_session();
			return;
		}

		do_action( 'woocommerce_before_calculate_totals', $this );

		new WC_Cart_Totals( $this );

		do_action( 'woocommerce_after_calculate_totals', $this );
	}

	/**
	 * Looks at the totals to see if payment is actually required.
	 *
	 * @return bool
	 */
	public function needs_payment() {
		return apply_filters( 'woocommerce_cart_needs_payment', 0 < $this->get_total( 'edit' ), $this );
	}

	/*
	 * Shipping related functions.
	 */

	/**
	 * Uses the shipping class to calculate shipping then gets the totals when its finished.
	 */
	public function calculate_shipping() {
		$this->shipping_methods = $this->needs_shipping() ? $this->get_chosen_shipping_methods( WC()->shipping->calculate_shipping( $this->get_shipping_packages() ) ) : array();

		$shipping_taxes = wp_list_pluck( $this->shipping_methods, 'taxes' );
		$merged_taxes = array();
		foreach ( $shipping_taxes as $taxes ) {
			foreach ( $taxes as $tax_id => $tax_amount ) {
				if ( ! isset( $merged_taxes[ $tax_id ] ) ) {
					$merged_taxes[ $tax_id ] = 0;
				}
				$merged_taxes[ $tax_id ] += $tax_amount;
			}
		}

		$this->set_shipping_total( array_sum( wp_list_pluck( $this->shipping_methods, 'cost' ) ) );
		$this->set_shipping_tax( array_sum( $merged_taxes ) );
		$this->set_shipping_taxes( $merged_taxes );

		return $this->shipping_methods;
	}

	/**
	 * Given a set of packages with rates, get the chosen ones only.
	 *
	 * @since 3.2.0
	 * @param array $calculated_shipping_packages Array of packages.
	 * @return array
	 */
	protected function get_chosen_shipping_methods( $calculated_shipping_packages = array() ) {
		$chosen_methods = array();
		// Get chosen methods for each package to get our totals.
		foreach ( $calculated_shipping_packages as $key => $package ) {
			$chosen_method          = wc_get_chosen_shipping_method_for_package( $key, $package );
			if ( $chosen_method ) {
				$chosen_methods[ $key ] = $package['rates'][ $chosen_method ];
			}
		}
		return $chosen_methods;
	}

	/**
	 * Filter items needing shipping callback.
	 *
	 * @since  3.0.0
	 * @param  array $item Item to check for shipping.
	 * @return bool
	 */
	protected function filter_items_needing_shipping( $item ) {
		$product = $item['data'];
		return $product && $product->needs_shipping();
	}

	/**
	 * Get only items that need shipping.
	 *
	 * @since  3.0.0
	 * @return array
	 */
	protected function get_items_needing_shipping() {
		return array_filter( $this->get_cart(), array( $this, 'filter_items_needing_shipping' ) );
	}

	/**
	 * Get packages to calculate shipping for.
	 *
	 * This lets us calculate costs for carts that are shipped to multiple locations.
	 *
	 * Shipping methods are responsible for looping through these packages.
	 *
	 * By default we pass the cart itself as a package - plugins can change this.
	 * through the filter and break it up.
	 *
	 * @since 1.5.4
	 * @return array of cart items
	 */
	public function get_shipping_packages() {
		return apply_filters( 'woocommerce_cart_shipping_packages',
			array(
				array(
					'contents'        => $this->get_items_needing_shipping(),
					'contents_cost'   => array_sum( wp_list_pluck( $this->get_items_needing_shipping(), 'line_total' ) ),
					'applied_coupons' => $this->get_applied_coupons(),
					'user'            => array(
						'ID' => get_current_user_id(),
					),
					'destination'     => array(
						'country'   => $this->get_customer()->get_shipping_country(),
						'state'     => $this->get_customer()->get_shipping_state(),
						'postcode'  => $this->get_customer()->get_shipping_postcode(),
						'city'      => $this->get_customer()->get_shipping_city(),
						'address'   => $this->get_customer()->get_shipping_address(),
						'address_2' => $this->get_customer()->get_shipping_address_2(),
					),
					'cart_subtotal'       => $this->get_displayed_subtotal(),
				),
			)
		);
	}

	/**
	 * Looks through the cart to see if shipping is actually required.
	 *
	 * @return bool whether or not the cart needs shipping
	 */
	public function needs_shipping() {
		if ( ! wc_shipping_enabled() || 0 === wc_get_shipping_method_count( true ) ) {
			return false;
		}
		$needs_shipping = false;

		foreach ( $this->get_cart_contents() as $cart_item_key => $values ) {
			if ( $values['data']->needs_shipping() ) {
				$needs_shipping = true;
				break;
			}
		}

		return apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );
	}

	/**
	 * Should the shipping address form be shown.
	 *
	 * @return bool
	 */
	public function needs_shipping_address() {
		return apply_filters( 'woocommerce_cart_needs_shipping_address', true === $this->needs_shipping() && ! wc_ship_to_billing_address_only() );
	}

	/**
	 * Sees if the customer has entered enough data to calc the shipping yet.
	 *
	 * @return bool
	 */
	public function show_shipping() {
		if ( ! wc_shipping_enabled() || ! $this->get_cart_contents() ) {
			return false;
		}

		if ( 'yes' === get_option( 'woocommerce_shipping_cost_requires_address' ) ) {
			if ( ! $this->get_customer()->has_calculated_shipping() ) {
				if ( ! $this->get_customer()->get_shipping_country() || ( ! $this->get_customer()->get_shipping_state() && ! $this->get_customer()->get_shipping_postcode() ) ) {
					return false;
				}
			}
		}

		return apply_filters( 'woocommerce_cart_ready_to_calc_shipping', true );
	}

	/**
	 * Gets the shipping total (after calculation).
	 *
	 * @return string price or string for the shipping total
	 */
	public function get_cart_shipping_total() {
		if ( 0 < $this->get_shipping_total() ) {

			if ( 'excl' === $this->tax_display_cart ) {
				$return = wc_price( $this->shipping_total );

				if ( $this->shipping_tax_total > 0 && wc_prices_include_tax() ) {
					$return .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}

				return $return;
			} else {
				$return = wc_price( $this->shipping_total + $this->shipping_tax_total );

				if ( $this->shipping_tax_total > 0 && ! wc_prices_include_tax() ) {
					$return .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}

				return $return;
			}
		} else {
			return __( 'Free!', 'woocommerce' );
		}
	}

	/**
	 * Check for user coupons (now that we have billing email). If a coupon is invalid, add an error.
	 *
	 * Checks two types of coupons:
	 *  1. Where a list of customer emails are set (limits coupon usage to those defined).
	 *  2. Where a usage_limit_per_user is set (limits coupon usage to a number based on user ID and email).
	 *
	 * @param array $posted Post data.
	 */
	public function check_customer_coupons( $posted ) {
		foreach ( $this->get_applied_coupons() as $code ) {
			$coupon = new WC_Coupon( $code );

			if ( $coupon->is_valid() ) {

				// Get user and posted emails to compare.
				$current_user = wp_get_current_user();
				$check_emails = array_unique( array_filter( array_map( 'strtolower', array_map( 'sanitize_email', array(
					$posted['billing_email'],
					$current_user->user_email,
				) ) ) ) );

				// Limit to defined email addresses.
				$restrictions = $coupon->get_email_restrictions();

				if ( is_array( $restrictions ) && 0 < count( $restrictions ) && 0 === count( array_intersect( $check_emails, $restrictions ) ) ) {
					$coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_NOT_YOURS_REMOVED );
					$this->remove_coupon( $code );
				}

				// Usage limits per user - check against billing and user email and user ID.
				$limit_per_user = $coupon->get_usage_limit_per_user();

				if ( 0 < $limit_per_user ) {
					$used_by         = $coupon->get_used_by();
					$usage_count     = 0;
					$user_id_matches = array( get_current_user_id() );

					// Check usage against emails.
					foreach ( $check_emails as $check_email ) {
						$usage_count      += count( array_keys( $used_by, $check_email, true ) );
						$user              = get_user_by( 'email', $check_email );
						$user_id_matches[] = $user ? $user->ID : 0;
					}

					// Check against billing emails of existing users.
					$users_query = new WP_User_Query( array(
						'fields'       => 'ID',
						'meta_query'   => array(
							array(
								'key'      => '_billing_email',
								'value'    => $check_emails,
								'compare'  => 'IN',
							),
						),
					) );

					$user_id_matches = array_unique( array_filter( array_merge( $user_id_matches, $users_query->get_results() ) ) );

					foreach ( $user_id_matches as $user_id ) {
						$usage_count += count( array_keys( $used_by, $user_id ) );
					}

					if ( $usage_count >= $coupon->get_usage_limit_per_user() ) {
						$coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_USAGE_LIMIT_REACHED );
						$this->remove_coupon( $code );
					}
				}
			}
		}
	}

	/**
	 * Returns whether or not a discount has been applied.
	 *
	 * @param string $coupon_code Coupon code to check.
	 * @return bool
	 */
	public function has_discount( $coupon_code = '' ) {
		return $coupon_code ? in_array( wc_format_coupon_code( $coupon_code ), $this->applied_coupons, true ) : count( $this->applied_coupons ) > 0;
	}

	/**
	 * Applies a coupon code passed to the method.
	 *
	 * @param string $coupon_code - The code to apply.
	 * @return bool	True if the coupon is applied, false if it does not exist or cannot be applied.
	 */
	public function apply_coupon( $coupon_code ) {
		// Coupons are globally disabled.
		if ( ! wc_coupons_enabled() ) {
			return false;
		}

		// Sanitize coupon code.
		$coupon_code = wc_format_coupon_code( $coupon_code );

		// Get the coupon.
		$the_coupon = new WC_Coupon( $coupon_code );

		// Prevent adding coupons by post ID.
		if ( $the_coupon->get_code() !== $coupon_code ) {
			$the_coupon->set_code( $coupon_code );
			$the_coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_NOT_EXIST );
			return false;
		}

		// Check it can be used with cart.
		if ( ! $the_coupon->is_valid() ) {
			wc_add_notice( $the_coupon->get_error_message(), 'error' );
			return false;
		}

		// Check if applied.
		if ( $this->has_discount( $coupon_code ) ) {
			$the_coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_ALREADY_APPLIED );
			return false;
		}

		// If its individual use then remove other coupons.
		if ( $the_coupon->get_individual_use() ) {
			$coupons_to_keep = apply_filters( 'woocommerce_apply_individual_use_coupon', array(), $the_coupon, $this->applied_coupons );

			foreach ( $this->applied_coupons as $applied_coupon ) {
				$keep_key = array_search( $applied_coupon, $coupons_to_keep, true );
				if ( false === $keep_key ) {
					$this->remove_coupon( $applied_coupon );
				} else {
					unset( $coupons_to_keep[ $keep_key ] );
				}
			}

			if ( ! empty( $coupons_to_keep ) ) {
				$this->applied_coupons += $coupons_to_keep;
			}
		}

		// Check to see if an individual use coupon is set.
		if ( $this->applied_coupons ) {
			foreach ( $this->applied_coupons as $code ) {
				$coupon = new WC_Coupon( $code );

				if ( $coupon->get_individual_use() && false === apply_filters( 'woocommerce_apply_with_individual_use_coupon', false, $the_coupon, $coupon, $this->applied_coupons ) ) {

					// Reject new coupon.
					$coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_ALREADY_APPLIED_INDIV_USE_ONLY );

					return false;
				}
			}
		}

		$this->applied_coupons[] = $coupon_code;

		// Choose free shipping.
		if ( $the_coupon->get_free_shipping() ) {
			$packages = WC()->shipping->get_packages();
			$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

			foreach ( $packages as $i => $package ) {
				$chosen_shipping_methods[ $i ] = 'free_shipping';
			}

			WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
		}

		$the_coupon->add_coupon_message( WC_Coupon::WC_COUPON_SUCCESS );

		do_action( 'woocommerce_applied_coupon', $coupon_code );

		return true;
	}

	/**
	 * Get array of applied coupon objects and codes.
	 *
	 * @param null $deprecated No longer used.
	 * @return array of applied coupons
	 */
	public function get_coupons( $deprecated = null ) {
		$coupons = array();

		if ( 'order' === $deprecated ) {
			return $coupons;
		}

		foreach ( $this->get_applied_coupons() as $code ) {
			$coupon = new WC_Coupon( $code );
			$coupons[ $code ] = $coupon;
		}

		return $coupons;
	}

	/**
	 * Get the discount amount for a used coupon.
	 *
	 * @param  string $code coupon code.
	 * @param  bool   $ex_tax inc or ex tax.
	 * @return float discount amount
	 */
	public function get_coupon_discount_amount( $code, $ex_tax = true ) {
		$totals          = $this->get_coupon_discount_totals();
		$discount_amount = isset( $totals[ $code ] ) ? $totals[ $code ]: 0;

		if ( ! $ex_tax ) {
			$discount_amount += $this->get_coupon_discount_tax_amount( $code );
		}

		return wc_cart_round_discount( $discount_amount, wc_get_price_decimals() );
	}

	/**
	 * Get the discount tax amount for a used coupon (for tax inclusive prices).
	 *
	 * @param  string $code coupon code.
	 * @return float discount amount
	 */
	public function get_coupon_discount_tax_amount( $code ) {
		$totals = $this->get_coupon_discount_tax_totals();
		return wc_cart_round_discount( isset( $totals[ $code ] ) ? $totals[ $code ] : 0, wc_get_price_decimals() );
	}

	/**
	 * Remove coupons from the cart of a defined type. Type 1 is before tax, type 2 is after tax.
	 *
	 * @param null $deprecated No longer used.
	 */
	public function remove_coupons( $deprecated = null ) {
		$this->set_coupon_discount_totals( array() );
		$this->set_coupon_discount_tax_totals( array() );
		$this->set_applied_coupons( array() );
		$this->session->set_session();
	}

	/**
	 * Remove a single coupon by code.
	 *
	 * @param  string $coupon_code Code of the coupon to remove.
	 * @return bool
	 */
	public function remove_coupon( $coupon_code ) {
		if ( ! wc_coupons_enabled() ) {
			return false;
		}

		$coupon_code  = wc_format_coupon_code( $coupon_code );
		$position     = array_search( $coupon_code, $this->get_applied_coupons(), true );

		if ( false !== $position ) {
			unset( $this->applied_coupons[ $position ] );
		}

		WC()->session->set( 'refresh_totals', true );

		do_action( 'woocommerce_removed_coupon', $coupon_code );

		return true;
	}

	/**
	 * Trigger an action so 3rd parties can add custom fees.
	 *
	 * @since 2.0.0
	 */
	public function calculate_fees() {
		do_action( 'woocommerce_cart_calculate_fees', $this );
	}

	/**
	 * Return reference to fees API.
	 *
	 * @since  3.2.0
	 * @return WC_Cart_Fees
	 */
	public function fees_api() {
		return $this->fees_api;
	}

	/**
	 * Add additional fee to the cart.
	 *
	 * This method should be called on a callback attached to the
	 * woocommerce_cart_calculate_fees action during cart/checkout. Fees do not
	 * persist.
	 *
	 * @uses WC_Cart_Fees::add_fee
	 * @param string $name      Unique name for the fee. Multiple fees of the same name cannot be added.
	 * @param float  $amount    Fee amount (do not enter negative amounts).
	 * @param bool   $taxable   Is the fee taxable? (default: false).
	 * @param string $tax_class The tax class for the fee if taxable. A blank string is standard tax class. (default: '').
	 */
	public function add_fee( $name, $amount, $taxable = false, $tax_class = '' ) {
		$this->fees_api()->add_fee( array(
			'name'      => $name,
			'amount'    => (float) $amount,
			'taxable'   => $taxable,
			'tax_class' => $tax_class,
		) );
	}

	/**
	 * Return all added fees from the Fees API.
	 *
	 * @uses WC_Cart_Fees::get_fees
	 * @return array
	 */
	public function get_fees() {
		$fees = $this->fees_api()->get_fees();

		if ( property_exists( $this, 'fees' ) ) {
			$fees = $fees + (array) $this->fees;
		}
		return $fees;
	}

	/**
	 * Gets the total excluding taxes.
	 *
	 * @return string formatted price
	 */
	public function get_total_ex_tax() {
		return apply_filters( 'woocommerce_cart_total_ex_tax', wc_price( max( 0, $this->get_total( 'edit' ) - $this->get_cart_contents_tax() - $this->get_shipping_tax() ) ) );
	}

	/**
	 * Gets the cart contents total (after calculation).
	 *
	 * @return string formatted price
	 */
	public function get_cart_total() {
		return apply_filters( 'woocommerce_cart_contents_total', wc_price( wc_prices_include_tax() ? $this->get_cart_contents_total() + $this->get_cart_contents_tax() : $this->get_cart_contents_total() ) );
	}

	/**
	 * Gets the sub total (after calculation).
	 *
	 * @param bool $compound whether to include compound taxes.
	 * @return string formatted price
	 */
	public function get_cart_subtotal( $compound = false ) {
		/**
		 * If the cart has compound tax, we want to show the subtotal as cart + shipping + non-compound taxes (after discount).
		 */
		if ( $compound ) {
			$cart_subtotal = wc_price( $this->get_cart_contents_total() + $this->get_shipping_total() + $this->get_taxes_total( false, false ) );

		} elseif ( 'excl' === $this->tax_display_cart ) {
			$cart_subtotal = wc_price( $this->get_subtotal() );

			if ( $this->get_subtotal_tax() > 0 && wc_prices_include_tax() ) {
				$cart_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
			}
		} else {
			$cart_subtotal = wc_price( $this->get_subtotal() + $this->get_subtotal_tax() );

			if ( $this->get_subtotal_tax() > 0 && ! wc_prices_include_tax() ) {
				$cart_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
			}
		}

		return apply_filters( 'woocommerce_cart_subtotal', $cart_subtotal, $compound, $this );
	}

	/**
	 * Get the product row price per item.
	 *
	 * @param WC_Product $product Product object.
	 * @return string formatted price
	 */
	public function get_product_price( $product ) {
		if ( 'excl' === $this->tax_display_cart ) {
			$product_price = wc_get_price_excluding_tax( $product );
		} else {
			$product_price = wc_get_price_including_tax( $product );
		}
		return apply_filters( 'woocommerce_cart_product_price', wc_price( $product_price ), $product );
	}

	/**
	 * Get the product row subtotal.
	 *
	 * Gets the tax etc to avoid rounding issues.
	 *
	 * When on the checkout (review order), this will get the subtotal based on the customer's tax rate rather than the base rate.
	 *
	 * @param WC_Product $product Product object.
	 * @param int        $quantity Quantity being purchased.
	 * @return string formatted price
	 */
	public function get_product_subtotal( $product, $quantity ) {
		$price = $product->get_price();

		if ( $product->is_taxable() ) {

			if ( 'excl' === $this->tax_display_cart ) {

				$row_price        = wc_get_price_excluding_tax( $product, array( 'qty' => $quantity ) );
				$product_subtotal = wc_price( $row_price );

				if ( wc_prices_include_tax() && $this->get_subtotal_tax() > 0 ) {
					$product_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}
			} else {

				$row_price        = wc_get_price_including_tax( $product, array( 'qty' => $quantity ) );
				$product_subtotal = wc_price( $row_price );

				if ( ! wc_prices_include_tax() && $this->get_subtotal_tax() > 0 ) {
					$product_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
				}
			}
		} else {
			$row_price        = $price * $quantity;
			$product_subtotal = wc_price( $row_price );
		}

		return apply_filters( 'woocommerce_cart_product_subtotal', $product_subtotal, $product, $quantity, $this );
	}

	/**
	 * Gets the cart tax (after calculation).
	 *
	 * @return string formatted price
	 */
	public function get_cart_tax() {
		$cart_total_tax = wc_round_tax_total( $this->get_cart_contents_tax() + $this->get_shipping_tax() + $this->get_fee_tax() );

		return apply_filters( 'woocommerce_get_cart_tax', $cart_total_tax ? wc_price( $cart_total_tax ) : '' );
	}

	/**
	 * Get a tax amount.
	 *
	 * @param  string $tax_rate_id ID of the tax rate to get taxes for.
	 * @return float amount
	 */
	public function get_tax_amount( $tax_rate_id ) {
		$taxes = wc_array_merge_recursive_numeric( $this->get_cart_contents_taxes(), $this->get_fee_taxes() );
		return isset( $taxes[ $tax_rate_id ] ) ? $taxes[ $tax_rate_id ] : 0;
	}

	/**
	 * Get a tax amount.
	 *
	 * @param  string $tax_rate_id ID of the tax rate to get taxes for.
	 * @return float amount
	 */
	public function get_shipping_tax_amount( $tax_rate_id ) {
		$taxes = $this->get_shipping_taxes();
		return isset( $taxes[ $tax_rate_id ] ) ? $taxes[ $tax_rate_id ] : 0;
	}

	/**
	 * Get tax row amounts with or without compound taxes includes.
	 *
	 * @param  bool $compound True if getting compound taxes.
	 * @param  bool $display  True if getting total to display.
	 * @return float price
	 */
	public function get_taxes_total( $compound = true, $display = true ) {
		$total = 0;
		$taxes = $this->get_taxes();
		foreach ( $taxes as $key => $tax ) {
			if ( ! $compound && WC_Tax::is_compound( $key ) ) {
				continue;
			}
			$total += $tax;
		}
		if ( $display ) {
			$total = wc_round_tax_total( $total );
		}
		return apply_filters( 'woocommerce_cart_taxes_total', $total, $compound, $display, $this );
	}

	/**
	 * Gets the total discount amount.
	 *
	 * @return mixed formatted price or false if there are none
	 */
	public function get_total_discount() {
		return apply_filters( 'woocommerce_cart_total_discount', $this->get_discount_total() ? wc_price( $this->get_discount_total() ) : false, $this );
	}

	/**
	 * Reset cart totals to the defaults. Useful before running calculations.
	 *
	 * @access private
	 */
	private function reset_totals() {
		$this->totals = $this->default_totals;
		do_action( 'woocommerce_cart_reset', $this, false );
	}
}
