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

include_once( 'legacy/class-wc-legacy-cart.php' );

/**
 * WC_Cart class.
 */
class WC_Cart extends WC_Legacy_Cart {

	/**
	 * This stores the chosen shipping methods for the cart item packages.
	 *
	 * @var array
	 */
	protected $shipping_methods;

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
	 * Contains an array of coupon code discounts after they have been applied.
	 *
	 * @var array
	 */
	public $coupon_discount_amounts = array();

	/**
	 * Contains an array of coupon code discount taxes. Used for tax incl pricing.
	 *
	 * @var array
	 */
	public $coupon_discount_tax_amounts = array();

	/**
	 * The total cost of the cart items.
	 *
	 * @var float
	 */
	public $cart_contents_total;

	/**
	 * Cart grand total.
	 *
	 * @var float
	 */
	public $total;

	/**
	 * Cart subtotal.
	 *
	 * @var float
	 */
	public $subtotal;

	/**
	 * Cart subtotal without tax.
	 *
	 * @var float
	 */
	public $subtotal_ex_tax;

	/**
	 * Total cart tax.
	 *
	 * @var float
	 */
	public $tax_total;

	/**
	 * An array of taxes/tax rates for the cart.
	 *
	 * @var array
	 */
	public $taxes;

	/**
	 * An array of taxes/tax rates for the shipping.
	 *
	 * @var array
	 */
	public $shipping_taxes;

	/**
	 * Discount amount before tax.
	 *
	 * @var float
	 */
	public $discount_cart;

	/**
	 * Discounted tax amount. Used predominantly for displaying tax inclusive prices correctly.
	 *
	 * @var float
	 */
	public $discount_cart_tax;

	/**
	 * Total for additional fees.
	 *
	 * @var float
	 */
	public $fee_total;

	/**
	 * Shipping cost.
	 *
	 * @var float
	 */
	public $shipping_total;

	/**
	 * Shipping tax.
	 *
	 * @var float
	 */
	public $shipping_tax_total;

	/**
	 * Array of data the cart calculates and stores in the session with defaults
	 *
	 * @var array cart_session_data.
	 */
	public $cart_session_data = array(
		'cart_contents_total'         => 0,
		'total'                       => 0,
		'subtotal'                    => 0,
		'subtotal_ex_tax'             => 0,
		'tax_total'                   => 0,
		'taxes'                       => array(),
		'shipping_taxes'              => array(),
		'discount_cart'               => 0,
		'discount_cart_tax'           => 0,
		'shipping_total'              => 0,
		'shipping_tax_total'          => 0,
		'coupon_discount_amounts'     => array(),
		'coupon_discount_tax_amounts' => array(),
		'fee_total'                   => 0,
		'fees'                        => array(),
	);

	/**
	 * An array of fees.
	 *
	 * @var array
	 */
	public $fees = array();

	/**
	 * Constructor for the cart class. Loads options and hooks in the init method.
	 */
	public function __construct() {
		add_action( 'wp_loaded', array( $this, 'init' ) ); // Get cart after WP and plugins are loaded.
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 ); // Set cookies.
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 ); // Set cookies before shutdown and ob flushing.
		add_action( 'woocommerce_add_to_cart', array( $this, 'calculate_totals' ), 20, 0 );
		add_action( 'woocommerce_applied_coupon', array( $this, 'calculate_totals' ), 20, 0 );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param mixed $key Key to get.
	 * @return mixed
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'prices_include_tax' :
				return wc_prices_include_tax();
			break;
			case 'round_at_subtotal' :
				return 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' );
			break;
			case 'tax_display_cart' :
				return get_option( 'woocommerce_tax_display_cart' );
			break;
			case 'dp' :
				return wc_get_price_decimals();
			break;
			case 'display_totals_ex_tax' :
			case 'display_cart_ex_tax' :
				return 'excl' === $this->tax_display_cart;
			break;
			case 'cart_contents_weight' :
				return $this->get_cart_contents_weight();
			break;
			case 'cart_contents_count' :
				return $this->get_cart_contents_count();
			break;
			case 'tax' :
				wc_deprecated_argument( 'WC_Cart->tax', '2.3', 'Use WC_Tax:: directly' );
				$this->tax = new WC_Tax();
				return $this->tax;
			case 'discount_total':
				wc_deprecated_argument( 'WC_Cart->discount_total', '2.3', 'After tax coupons are no longer supported. For more information see: https://woocommerce.wordpress.com/2014/12/upcoming-coupon-changes-in-woocommerce-2-3/' );
				return 0;
			case 'coupons' :
				return $this->get_coupons();
		}
	}

	/**
	 * Loads the cart data from the PHP session during WordPress init and hooks in other methods.
	 */
	public function init() {
		$this->get_cart_from_session();

		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_items' ), 1 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_coupons' ), 1 );
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'check_customer_coupons' ), 1 );
	}

	/**
	 * Will set cart cookies if needed, once, during WP hook.
	 */
	public function maybe_set_cart_cookies() {
		if ( ! headers_sent() && did_action( 'wp_loaded' ) ) {
			if ( ! $this->is_empty() ) {
				$this->set_cart_cookies( true );
			} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) {
				$this->set_cart_cookies( false );
			}
		}
	}

	/**
	 * Set cart hash cookie and items in cart.
	 *
	 * @access private
	 * @param bool $set Should cookies be set (true) or unset.
	 */
	private function set_cart_cookies( $set = true ) {
		if ( $set ) {
			wc_setcookie( 'woocommerce_items_in_cart', 1 );
			wc_setcookie( 'woocommerce_cart_hash', md5( wp_json_encode( $this->get_cart_for_session() ) ) );
		} elseif ( isset( $_COOKIE['woocommerce_items_in_cart'] ) ) {
			wc_setcookie( 'woocommerce_items_in_cart', 0, time() - HOUR_IN_SECONDS );
			wc_setcookie( 'woocommerce_cart_hash', '', time() - HOUR_IN_SECONDS );
		}
		do_action( 'woocommerce_set_cart_cookies', $set );
	}

	/**
	 * Get the cart data from the PHP session and store it in class variables.
	 */
	public function get_cart_from_session() {
		foreach ( $this->cart_session_data as $key => $default ) {
			$this->$key = WC()->session->get( $key, $default );
		}

		$update_cart_session         = false;
		$this->removed_cart_contents = array_filter( WC()->session->get( 'removed_cart_contents', array() ) );
		$this->applied_coupons       = array_filter( WC()->session->get( 'applied_coupons', array() ) );

		/**
		 * Load the cart object. This defaults to the persistent cart if null.
		 */
		$cart = WC()->session->get( 'cart', null );

		if ( is_null( $cart ) && ( $saved_cart = get_user_meta( get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id(), true ) ) ) {
			$cart                = $saved_cart['cart'];
			$update_cart_session = true;
		} elseif ( is_null( $cart ) ) {
			$cart = array();
		}

		if ( is_array( $cart ) ) {
			// Prime meta cache to reduce future queries.
			update_meta_cache( 'post', wp_list_pluck( $cart, 'product_id' ) );
			update_object_term_cache( wp_list_pluck( $cart, 'product_id' ), 'product' );

			foreach ( $cart as $key => $values ) {
				$product = wc_get_product( $values['variation_id'] ? $values['variation_id'] : $values['product_id'] );

				if ( ! empty( $product ) && $product->exists() && $values['quantity'] > 0 ) {

					if ( ! $product->is_purchasable() ) {
						$update_cart_session = true; // Flag to indicate the stored cart should be updated.
						/* translators: %s: product name */
						wc_add_notice( sprintf( __( '%s has been removed from your cart because it can no longer be purchased. Please contact us if you need assistance.', 'woocommerce' ), $product->get_name() ), 'error' );
						do_action( 'woocommerce_remove_cart_item_from_session', $key, $values );

					} else {

						// Put session data into array. Run through filter so other plugins can load their own session data.
						$session_data = array_merge( $values, array( 'data' => $product ) );
						$this->cart_contents[ $key ] = apply_filters( 'woocommerce_get_cart_item_from_session', $session_data, $values, $key );
					}
				}
			}
		}

		do_action( 'woocommerce_cart_loaded_from_session', $this );

		if ( $update_cart_session ) {
			WC()->session->cart = $this->get_cart_for_session();
		}

		// Queue re-calc if subtotal is not set.
		if ( ( ! $this->subtotal && ! $this->is_empty() ) || $update_cart_session ) {
			$this->calculate_totals();
		}
	}

	/**
	 * Sets the php session data for the cart and coupons.
	 */
	public function set_session() {
		$cart_session = $this->get_cart_for_session();

		WC()->session->set( 'cart', $cart_session );
		WC()->session->set( 'applied_coupons', $this->applied_coupons );
		WC()->session->set( 'coupon_discount_amounts', $this->coupon_discount_amounts );
		WC()->session->set( 'coupon_discount_tax_amounts', $this->coupon_discount_tax_amounts );
		WC()->session->set( 'removed_cart_contents', $this->removed_cart_contents );

		foreach ( $this->cart_session_data as $key => $default ) {
			WC()->session->set( $key, $this->$key );
		}

		if ( get_current_user_id() ) {
			$this->persistent_cart_update();
		}

		do_action( 'woocommerce_cart_updated' );
	}

	/**
	 * Empties the cart and optionally the persistent cart too.
	 *
	 * @param bool $clear_persistent_cart Should the persistant cart be cleared too. Defaults to true.
	 */
	public function empty_cart( $clear_persistent_cart = true ) {
		$this->cart_contents = array();
		$this->shipping_methods = null;
		$this->reset( true );

		unset( WC()->session->order_awaiting_payment, WC()->session->applied_coupons, WC()->session->coupon_discount_amounts, WC()->session->coupon_discount_tax_amounts, WC()->session->cart );

		if ( $clear_persistent_cart && get_current_user_id() ) {
			$this->persistent_cart_destroy();
		}

		do_action( 'woocommerce_cart_emptied' );
	}

	/**
	 * Save the persistent cart when the cart is updated.
	 */
	public function persistent_cart_update() {
		update_user_meta( get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id(), array(
			'cart' => WC()->session->get( 'cart' ),
		) );
	}

	/**
	 * Delete the persistent cart permanently.
	 */
	public function persistent_cart_destroy() {
		delete_user_meta( get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id() );
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
	 * Checks if the cart is empty.
	 *
	 * @return bool
	 */
	public function is_empty() {
		return 0 === count( $this->get_cart() );
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
		foreach ( $this->applied_coupons as $code ) {
			$coupon = new WC_Coupon( $code );

			if ( ! $coupon->is_valid() ) {
				$coupon->add_coupon_message( WC_Coupon::E_WC_COUPON_INVALID_REMOVED );
				$this->remove_coupon( $code );
			}
		}
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
	 * Returns the contents of the cart in an array.
	 *
	 * @return array contents of the cart
	 */
	public function get_cart() {
		if ( ! did_action( 'wp_loaded' ) ) {
			wc_doing_it_wrong( __FUNCTION__, __( 'Get cart should not be called before the wp_loaded action.', 'woocommerce' ), '2.3' );
		}
		if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
			$this->get_cart_from_session();
		}
		return array_filter( (array) $this->cart_contents );
	}

	/**
	 * Returns the contents of the cart in an array without the 'data' element.
	 *
	 * @return array contents of the cart
	 */
	public function get_cart_for_session() {
		$cart_session = array();

		if ( $this->get_cart() ) {
			foreach ( $this->get_cart() as $key => $values ) {
				$cart_session[ $key ] = $values;
				unset( $cart_session[ $key ]['data'] ); // Unset product object.
			}
		}

		return $cart_session;
	}

	/**
	 * Returns a specific item in the cart.
	 *
	 * @param string $item_key Cart item key.
	 * @return array Item data
	 */
	public function get_cart_item( $item_key ) {
		if ( isset( $this->cart_contents[ $item_key ] ) ) {
			return $this->cart_contents[ $item_key ];
		}

		return array();
	}

	/**
	 * Returns the cart and shipping taxes, merged.
	 *
	 * @return array merged taxes
	 */
	public function get_taxes() {
		$taxes = array();

		foreach ( array_keys( $this->taxes + $this->shipping_taxes ) as $key ) {
			$taxes[ $key ] = ( isset( $this->shipping_taxes[ $key ] ) ? $this->shipping_taxes[ $key ] : 0 ) + ( isset( $this->taxes[ $key ] ) ? $this->taxes[ $key ] : 0 );
		}

		return apply_filters( 'woocommerce_cart_get_taxes', $taxes, $this );
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

			if ( $code || apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) === $key ) {
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
		if ( 'incl' === $this->tax_display_cart ) {
			return wc_format_decimal( $this->subtotal );
		} elseif ( 'excl' === $this->tax_display_cart ) {
			return wc_format_decimal( $this->subtotal_ex_tax );
		}
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
	 * @throws Exception To prevent adding to cart.
	 * @param int   $product_id contains the id of the product to add to the cart.
	 * @param int   $quantity contains the quantity of the item to add.
	 * @param int   $variation_id ID of the variation being added to the cart.
	 * @param array $variation attribute values.
	 * @param array $cart_item_data extra cart item data we want to pass into the item.
	 * @return string|bool $cart_item_key
	 */
	public function add_to_cart( $product_id = 0, $quantity = 1, $variation_id = 0, $variation = array(), $cart_item_data = array() ) {
		// Wrap in try catch so plugins can throw an exception to prevent adding to cart.
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

			if ( did_action( 'wp' ) ) {
				$this->set_cart_cookies( ! $this->is_empty() );
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

			$this->calculate_totals();

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
			$this->cart_contents[ $cart_item_key ] = $this->removed_cart_contents[ $cart_item_key ];
			$this->cart_contents[ $cart_item_key ]['data'] = wc_get_product( $this->cart_contents[ $cart_item_key ]['variation_id'] ? $this->cart_contents[ $cart_item_key ]['variation_id'] : $this->cart_contents[ $cart_item_key ]['product_id'] );

			do_action( 'woocommerce_restore_cart_item', $cart_item_key, $this );

			unset( $this->removed_cart_contents[ $cart_item_key ] );

			do_action( 'woocommerce_cart_item_restored', $cart_item_key, $this );

			$this->calculate_totals();

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
			do_action( 'woocommerce_before_cart_item_quantity_zero', $cart_item_key );
			unset( $this->cart_contents[ $cart_item_key ] );
		} else {
			$old_quantity = $this->cart_contents[ $cart_item_key ]['quantity'];
			$this->cart_contents[ $cart_item_key ]['quantity'] = $quantity;
			do_action( 'woocommerce_after_cart_item_quantity_update', $cart_item_key, $quantity, $old_quantity );
		}

		if ( $refresh_totals ) {
			$this->calculate_totals();
		}

		return true;
	}

	/**
	 * Reset cart totals to the defaults. Useful before running calculations.
	 *
	 * @param bool $unset_session If true, the session data will be forced unset.
	 * @access private
	 */
	private function reset( $unset_session = false ) {
		foreach ( $this->cart_session_data as $key => $default ) {
			$this->$key = $default;
			if ( $unset_session ) {
				unset( WC()->session->$key );
			}
		}
		do_action( 'woocommerce_cart_reset', $this, $unset_session );
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
		$this->reset();

		do_action( 'woocommerce_before_calculate_totals', $this );

		if ( $this->is_empty() ) {
			$this->set_session();
			return;
		}

		new WC_Cart_Totals( $this );

		do_action( 'woocommerce_after_calculate_totals', $this );

		$this->set_session();
	}

	/**
	 * Remove taxes.
	 */
	public function remove_taxes() {
		$this->shipping_tax_total = $this->tax_total = 0;
		$this->subtotal           = $this->subtotal_ex_tax;

		foreach ( $this->cart_contents as $cart_item_key => $item ) {
			$this->cart_contents[ $cart_item_key ]['line_subtotal_tax'] = $this->cart_contents[ $cart_item_key ]['line_tax'] = 0;
			$this->cart_contents[ $cart_item_key ]['line_tax_data']     = array( 'total' => array(), 'subtotal' => array() );
		}

		// If true, zero rate is applied so '0' tax is displayed on the frontend rather than nothing.
		if ( apply_filters( 'woocommerce_cart_remove_taxes_apply_zero_rate', true ) ) {
			$this->taxes = $this->shipping_taxes = array( apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) => 0 );
		} else {
			$this->taxes = $this->shipping_taxes = array();
		}
	}

	/**
	 * Looks at the totals to see if payment is actually required.
	 *
	 * @return bool
	 */
	public function needs_payment() {
		return apply_filters( 'woocommerce_cart_needs_payment', $this->total > 0, $this );
	}

	/*
	 * Shipping related functions.
	 */

	/**
	 * Uses the shipping class to calculate shipping then gets the totals when its finished.
	 */
	public function calculate_shipping() {
		$this->shipping_methods = $this->needs_shipping() ? $this->get_chosen_shipping_methods( WC()->shipping->calculate_shipping( $this->get_shipping_packages() ) ) : array();

		// Set legacy totals for backwards compatibility with versions prior to 3.2.
		$this->shipping_total = WC()->shipping->shipping_total = array_sum( wp_list_pluck( $this->shipping_methods, 'cost' ) );
		$this->shipping_taxes = WC()->shipping->shipping_taxes = wp_list_pluck( $this->shipping_methods, 'taxes' );

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
					'applied_coupons' => $this->applied_coupons,
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

		if ( ! empty( $this->cart_contents ) ) {
			foreach ( $this->cart_contents as $cart_item_key => $values ) {
				if ( $values['data']->needs_shipping() ) {
					$needs_shipping = true;
					break;
				}
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
		return apply_filters( 'woocommerce_cart_needs_shipping_address', $this->needs_shipping() === true && ! wc_ship_to_billing_address_only() );
	}

	/**
	 * Sees if the customer has entered enough data to calc the shipping yet.
	 *
	 * @return bool
	 */
	public function show_shipping() {
		if ( ! wc_shipping_enabled() || ! is_array( $this->cart_contents ) ) {
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
		if ( isset( $this->shipping_total ) ) {
			if ( $this->shipping_total > 0 ) {

				if ( 'excl' === $this->tax_display_cart ) {
					$return = wc_price( $this->shipping_total );

					if ( $this->shipping_tax_total > 0 && $this->prices_include_tax ) {
						$return .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}

					return $return;
				} else {
					$return = wc_price( $this->shipping_total + $this->shipping_tax_total );

					if ( $this->shipping_tax_total > 0 && ! $this->prices_include_tax ) {
						$return .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}

					return $return;
				}
			} else {
				return __( 'Free!', 'woocommerce' );
			}
		}
		return '';
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
		if ( ! empty( $this->applied_coupons ) ) {
			foreach ( $this->applied_coupons as $code ) {
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
								'key'      => '_billing_email',
								'value'    => $check_emails,
								'compare'  => 'IN',
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
	public function add_discount( $coupon_code ) {
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
	 * Gets the array of applied coupon codes.
	 *
	 * @return array of applied coupons
	 */
	public function get_applied_coupons() {
		return $this->applied_coupons;
	}

	/**
	 * Get the discount amount for a used coupon.
	 *
	 * @param  string $code coupon code.
	 * @param  bool   $ex_tax inc or ex tax.
	 * @return float discount amount
	 */
	public function get_coupon_discount_amount( $code, $ex_tax = true ) {
		$discount_amount = isset( $this->coupon_discount_amounts[ $code ] ) ? $this->coupon_discount_amounts[ $code ] : 0;

		if ( ! $ex_tax ) {
			$discount_amount += $this->get_coupon_discount_tax_amount( $code );
		}

		return wc_cart_round_discount( $discount_amount, $this->dp );
	}

	/**
	 * Get the discount tax amount for a used coupon (for tax inclusive prices).
	 *
	 * @param  string $code coupon code.
	 * @return float discount amount
	 */
	public function get_coupon_discount_tax_amount( $code ) {
		return wc_cart_round_discount( isset( $this->coupon_discount_tax_amounts[ $code ] ) ? $this->coupon_discount_tax_amounts[ $code ] : 0, $this->dp );
	}

	/**
	 * Remove coupons from the cart of a defined type. Type 1 is before tax, type 2 is after tax.
	 *
	 * @param null $deprecated No longer used.
	 */
	public function remove_coupons( $deprecated = null ) {
		$this->applied_coupons = $this->coupon_discount_amounts = $this->coupon_discount_tax_amounts = $this->coupon_applied_count = array();
		WC()->session->set( 'applied_coupons', array() );
		WC()->session->set( 'coupon_discount_amounts', array() );
		WC()->session->set( 'coupon_discount_tax_amounts', array() );
	}

	/**
	 * Remove a single coupon by code.
	 *
	 * @param  string $coupon_code Code of the coupon to remove.
	 * @return bool
	 */
	public function remove_coupon( $coupon_code ) {
		// Coupons are globally disabled.
		if ( ! wc_coupons_enabled() ) {
			return false;
		}

		$coupon_code  = wc_format_coupon_code( $coupon_code );
		$position     = array_search( $coupon_code, $this->applied_coupons, true );

		if ( false !== $position ) {
			unset( $this->applied_coupons[ $position ] );
		}

		WC()->session->set( 'applied_coupons', $this->applied_coupons );
		WC()->session->set( 'refresh_totals', true );

		do_action( 'woocommerce_removed_coupon', $coupon_code );

		return true;
	}

	/**
	 * Add additional fee to the cart.
	 *
	 * Fee is an amount of money charged for a particular piece of work
	 * or for a particular right or service, and not supposed to be negative.
	 *
	 * This method should be called on a callback attached to the
	 * woocommerce_cart_calculate_fees action during cart/checkout. Fees do not
	 * persist.
	 *
	 * @param string $name      Unique name for the fee. Multiple fees of the same name cannot be added.
	 * @param float  $amount    Fee amount (do not enter negative amounts).
	 * @param bool   $taxable   Is the fee taxable? (default: false).
	 * @param string $tax_class The tax class for the fee if taxable. A blank string is standard tax class. (default: '').
	 */
	public function add_fee( $name, $amount, $taxable = false, $tax_class = '' ) {
		$new_fee_id = sanitize_title( $name );

		// Only add each fee once.
		foreach ( $this->fees as $fee ) {
			if ( $fee->id === $new_fee_id ) {
				return;
			}
		}

		$new_fee            = new stdClass();
		$new_fee->id        = $new_fee_id;
		$new_fee->name      = esc_attr( $name );
		$new_fee->amount    = (float) esc_attr( $amount );
		$new_fee->tax_class = $tax_class;
		$new_fee->taxable   = $taxable ? true : false;
		$new_fee->tax       = 0;
		$new_fee->tax_data  = array();
		$this->fees[]       = $new_fee;
	}

	/**
	 * Get fees.
	 *
	 * @return array
	 */
	public function get_fees() {
		return array_filter( (array) $this->fees );
	}

	/**
	 * Calculate fees.
	 */
	public function calculate_fees() {
		// Reset fees before calculation.
		$this->fee_total = 0;
		$this->fees      = array();

		// Fire an action where developers can add their fees.
		do_action( 'woocommerce_cart_calculate_fees', $this );

		// If fees were added, total them and calculate tax.
		if ( ! empty( $this->fees ) ) {
			foreach ( $this->fees as $fee_key => $fee ) {
				$this->fee_total += $fee->amount;

				if ( $fee->taxable ) {
					$tax_rates = WC_Tax::get_rates( $fee->tax_class );
					$fee_taxes = WC_Tax::calc_tax( $fee->amount, $tax_rates, false );

					if ( ! empty( $fee_taxes ) ) {
						$this->fees[ $fee_key ]->tax = array_sum( $fee_taxes );
						$this->fees[ $fee_key ]->tax_data = $fee_taxes;

						// Tax rows - merge the totals we just got.
						foreach ( array_keys( $this->taxes + $fee_taxes ) as $key ) {
							$this->taxes[ $key ] = ( isset( $fee_taxes[ $key ] ) ? $fee_taxes[ $key ] : 0 ) + ( isset( $this->taxes[ $key ] ) ? $this->taxes[ $key ] : 0 );
						}
					}
				}
			}
		}
	}

	/**
	 * Gets the order total (after calculation).
	 *
	 * @return string formatted price
	 */
	public function get_total() {
		return apply_filters( 'woocommerce_cart_total', wc_price( $this->total ) );
	}

	/**
	 * Gets the total excluding taxes.
	 *
	 * @return string formatted price
	 */
	public function get_total_ex_tax() {
		$total = $this->total - $this->tax_total - $this->shipping_tax_total;
		if ( $total < 0 ) {
			$total = 0;
		}
		return apply_filters( 'woocommerce_cart_total_ex_tax', wc_price( $total ) );
	}

	/**
	 * Gets the cart contents total (after calculation).
	 *
	 * @return string formatted price
	 */
	public function get_cart_total() {
		if ( ! $this->prices_include_tax ) {
			$cart_contents_total = wc_price( $this->cart_contents_total );
		} else {
			$cart_contents_total = wc_price( $this->cart_contents_total + $this->tax_total );
		}

		return apply_filters( 'woocommerce_cart_contents_total', $cart_contents_total );
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
			$cart_subtotal = wc_price( $this->cart_contents_total + $this->shipping_total + $this->get_taxes_total( false, false ) );

		} elseif ( 'excl' === $this->tax_display_cart ) {
			$cart_subtotal = wc_price( $this->subtotal_ex_tax );

			if ( $this->tax_total > 0 && $this->prices_include_tax ) {
				$cart_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
			}
		} else {
			$cart_subtotal = wc_price( $this->subtotal );

			if ( $this->tax_total > 0 && ! $this->prices_include_tax ) {
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

				if ( $this->prices_include_tax && $this->tax_total > 0 ) {
					$product_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
				}
			} else {

				$row_price        = wc_get_price_including_tax( $product, array( 'qty' => $quantity ) );
				$product_subtotal = wc_price( $row_price );

				if ( ! $this->prices_include_tax && $this->tax_total > 0 ) {
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
		$cart_total_tax = wc_round_tax_total( $this->tax_total + $this->shipping_tax_total );

		return apply_filters( 'woocommerce_get_cart_tax', $cart_total_tax ? wc_price( $cart_total_tax ) : '' );
	}

	/**
	 * Get a tax amount.
	 *
	 * @param  string $tax_rate_id ID of the tax rate to get taxes for.
	 * @return float amount
	 */
	public function get_tax_amount( $tax_rate_id ) {
		return isset( $this->taxes[ $tax_rate_id ] ) ? $this->taxes[ $tax_rate_id ] : 0;
	}

	/**
	 * Get a tax amount.
	 *
	 * @param  string $tax_rate_id ID of the tax rate to get taxes for.
	 * @return float amount
	 */
	public function get_shipping_tax_amount( $tax_rate_id ) {
		return isset( $this->shipping_taxes[ $tax_rate_id ] ) ? $this->shipping_taxes[ $tax_rate_id ] : 0;
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
		foreach ( $this->taxes as $key => $tax ) {
			if ( ! $compound && WC_Tax::is_compound( $key ) ) {
				continue;
			}
			$total += $tax;
		}
		foreach ( $this->shipping_taxes as $key => $tax ) {
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
	 * Get the total of all cart discounts.
	 *
	 * @return float
	 */
	public function get_cart_discount_total() {
		return wc_cart_round_discount( $this->discount_cart, $this->dp );
	}

	/**
	 * Get the total of all cart tax discounts (used for discounts on tax inclusive prices).
	 *
	 * @return float
	 */
	public function get_cart_discount_tax_total() {
		return wc_cart_round_discount( $this->discount_cart_tax, $this->dp );
	}

	/**
	 * Gets the total discount amount - both kinds.
	 *
	 * @return mixed formatted price or false if there are none
	 */
	public function get_total_discount() {
		return apply_filters( 'woocommerce_cart_total_discount', $this->get_cart_discount_total() ? wc_price( $this->get_cart_discount_total() ) : false, $this );
	}
}
