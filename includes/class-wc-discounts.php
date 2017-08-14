<?php
/**
 * Discount calculation
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 * @version 3.2.0
 * @since   3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Discounts class.
 */
class WC_Discounts {

	/**
	 * An array of items to discount.
	 *
	 * @var array
	 */
	protected $items = array();

	/**
	 * Stores fee total from cart/order. Manual discounts can discount this.
	 *
	 * @var int
	 */
	protected $fee_total = 0;

	/**
	 * Stores shipping total from cart/order. Manual discounts can discount this.
	 *
	 * @var int
	 */
	protected $shipping_total = 0;

	/**
	 * An array of discounts which have been applied to items.
	 *
	 * @var array[] Code => Item Key => Value
	 */
	protected $discounts = array();

	/**
	 * An array of applied WC_Discount objects.
	 *
	 * @var array
	 */
	protected $manual_discounts = array();

	/**
	 * Constructor.
	 *
	 * @param array $object Cart or order object.
	 */
	public function __construct( $object = array() ) {
		if ( is_a( $object, 'WC_Cart' ) ) {
			$this->set_items_from_cart( $object );
		} elseif ( is_a( $object, 'WC_Order' ) ) {
			$this->set_items_from_order( $object );
		}
	}

	/**
	 * Normalise cart items which will be discounted.
	 *
	 * @since 3.2.0
	 * @param array $cart Cart object.
	 */
	public function set_items_from_cart( $cart ) {
		$this->items = $this->discounts = $this->manual_discounts = array();

		if ( ! is_a( $cart, 'WC_Cart' ) ) {
			return;
		}

		foreach ( $cart->get_cart() as $key => $cart_item ) {
			$item                = new stdClass();
			$item->key           = $key;
			$item->object        = $cart_item;
			$item->product       = $cart_item['data'];
			$item->quantity      = $cart_item['quantity'];
			$item->price         = wc_add_number_precision_deep( $item->product->get_price() ) * $item->quantity;
			$this->items[ $key ] = $item;
		}

		uasort( $this->items, array( $this, 'sort_by_price' ) );
	}

	/**
	 * Normalise order items which will be discounted.
	 *
	 * @since 3.2.0
	 * @param array $order Cart object.
	 */
	public function set_items_from_order( $order ) {
		$this->items     = $this->discounts      = $this->manual_discounts = array();
		$this->fee_total = $this->shipping_total = 0;

		if ( ! is_a( $order, 'WC_Order' ) ) {
			return;
		}

		foreach ( $order->get_items() as $order_item ) {
			$item                = new stdClass();
			$item->key           = $order_item->get_id();
			$item->object        = $order_item;
			$item->product       = $order_item->get_product();
			$item->quantity      = $order_item->get_quantity();
			$item->price         = wc_add_number_precision_deep( $order_item->get_total() );
			$this->items[ $order_item->get_id() ] = $item;
		}

		uasort( $this->items, array( $this, 'sort_by_price' ) );

		foreach ( $order->get_fees() as $item ) {
			$this->fee_total += wc_add_number_precision( $item->get_total() );
		}

		$this->shipping_total = wc_add_number_precision( $order->get_shipping_total() );
	}

	/**
	 * Get items.
	 *
	 * @since  3.2.0
	 * @return object[]
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Get discount by key with or without precision.
	 *
	 * @since  3.2.0
	 * @param  string $key name of discount row to return.
	 * @param  bool   $in_cents Should the totals be returned in cents, or without precision.
	 * @return array
	 */
	public function get_discount( $key, $in_cents = false ) {
		$item_discount_totals = $this->get_discounts_by_item( $in_cents );
		return isset( $item_discount_totals[ $key ] ) ? ( $in_cents ? $item_discount_totals[ $key ] : wc_remove_number_precision( $item_discount_totals[ $key ] ) ) : 0;
	}

	/**
	 * Get all discount totals.
	 *
	 * @since  3.2.0
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return array
	 */
	public function get_discounts( $in_cents = false ) {
		$discounts = $this->discounts;

		foreach ( $this->get_manual_discounts() as $manual_discount_key => $manual_discount ) {
			$discounts[ $manual_discount_key ] = $manual_discount->get_discount_total();
		}

		return $in_cents ? $discounts : wc_remove_number_precision_deep( $discounts );
	}

	/**
	 * Get all discount totals per item.
	 *
	 * @since  3.2.0
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return array
	 */
	public function get_discounts_by_item( $in_cents = false ) {
		$discounts            = $this->discounts;
		$item_discount_totals = (array) array_shift( $discounts );

		foreach ( $discounts as $item_discounts ) {
			foreach ( $item_discounts as $item_key => $item_discount ) {
				$item_discount_totals[ $item_key ] += $item_discount;
			}
		}

		return $in_cents ? $item_discount_totals : wc_remove_number_precision_deep( $item_discount_totals );
	}

	/**
	 * Get all discount totals per coupon.
	 *
	 * @since  3.2.0
	 * @param  bool $in_cents Should the totals be returned in cents, or without precision.
	 * @return array
	 */
	public function get_discounts_by_coupon( $in_cents = false ) {
		$coupon_discount_totals = array_map( 'array_sum', $this->discounts );

		return $in_cents ? $coupon_discount_totals : wc_remove_number_precision_deep( $coupon_discount_totals );
	}

	/**
	 * Get an array of manual discounts which have been applied.
	 *
	 * @since  3.2.0
	 * @return WC_Discount[]
	 */
	public function get_manual_discounts() {
		return $this->manual_discounts;
	}

	/**
	 * Get discounted price of an item without precision.
	 *
	 * @since  3.2.0
	 * @param  object $item Get data for this item.
	 * @return float
	 */
	public function get_discounted_price( $item ) {
		return wc_remove_number_precision_deep( $this->get_discounted_price_in_cents( $item ) );
	}

	/**
	 * Get discounted price of an item to precision (in cents).
	 *
	 * @since  3.2.0
	 * @param  object $item Get data for this item.
	 * @return int
	 */
	public function get_discounted_price_in_cents( $item ) {
		return absint( $item->price - $this->get_discount( $item->key, true ) );
	}

	/**
	 * Get total remaining after discounts.
	 *
	 * @since  3.2.0
	 * @return int
	 */
	protected function get_total_after_discounts() {
		$total_to_discount = 0;

		// Sum line item costs.
		foreach ( $this->items as $item ) {
			$total_to_discount += $this->get_discounted_price_in_cents( $item );
		}

		// Manual discounts can also discount shipping and fees.
		$total_to_discount += $this->shipping_total + $this->fee_total;

		// Remove existing discount amounts.
		foreach ( $this->manual_discounts as $key => $value ) {
			$total_to_discount = $total_to_discount - $value->get_discount_total();
		}

		return $total_to_discount;
	}

	/**
	 * Generate a unique ID for a discount.
	 *
	 * @param  WC_Discount $discount Discount object.
	 * @return string
	 */
	protected function generate_discount_id( $discount ) {
		$discount_id    = '';
		$index          = 1;
		while ( ! $discount_id ) {
			$discount_id = 'discount-' . $discount->get_amount() . ( 'percent' === $discount->get_discount_type() ? '%' : '' );

			if ( 1 < $index ) {
				$discount_id .= '-' . $index;
			}

			if ( isset( $this->manual_discounts[ $discount_id ] ) ) {
				$index ++;
				$discount_id = '';
			}
		}
		return $discount_id;
	}

	/**
	 * Apply a discount to all items.
	 *
	 * @param  string|object $raw_discount Accepts a string (fixed or percent discounts), or WC_Coupon object.
	 * @param  string        $discount_id Optional ID for the discount. Generated from discount or coupon code if not defined.
	 * @return bool|WP_Error True if applied or WP_Error instance in failure.
	 */
	public function apply_discount( $raw_discount, $discount_id = null ) {
		if ( is_a( $raw_discount, 'WC_Coupon' ) ) {
			return $this->apply_coupon( $raw_discount );
		}

		$discount = new WC_Discount;

		if ( strstr( $raw_discount, '%' ) ) {
			$discount->set_discount_type( 'percent' );
			$discount->set_amount( trim( $raw_discount, '%' ) );
		} elseif ( is_numeric( $raw_discount ) && 0 < absint( $raw_discount ) ) {
			$discount->set_discount_type( 'fixed' );
			$discount->set_amount( wc_add_number_precision( absint( $raw_discount ) ) );
		}

		if ( ! $discount->get_amount() ) {
			return new WP_Error( 'invalid_discount', __( 'Invalid discount', 'woocommerce' ) );
		}

		$total_to_discount = $this->get_total_after_discounts();

		if ( 'percent' === $discount->get_discount_type() ) {
			$discount->set_discount_total( $discount->get_amount() * ( $total_to_discount / 100 ) );
		} else {
			$discount->set_discount_total( min( $discount->get_amount(), $total_to_discount ) );
		}

		$discount_id = $discount_id ? $discount_id : $this->generate_discount_id( $discount );

		$this->manual_discounts[ $discount_id ] = $discount;

		return true;
	}

	/**
	 * Apply a discount to all items using a coupon.
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon Coupon object being applied to the items.
	 * @return bool|WP_Error True if applied or WP_Error instance in failure.
	 */
	public function apply_coupon( $coupon ) {
		if ( ! is_a( $coupon, 'WC_Coupon' ) ) {
			return new WP_Error( 'invalid_coupon', __( 'Invalid coupon', 'woocommerce' ) );
		}

		$is_coupon_valid = $this->is_coupon_valid( $coupon );

		if ( is_wp_error( $is_coupon_valid ) ) {
			return $is_coupon_valid;
		}

		if ( ! isset( $this->discounts[ $coupon->get_code() ] ) ) {
			$this->discounts[ $coupon->get_code() ] = array_fill_keys( array_keys( $this->items ), 0 );
		}

		$items_to_apply = $this->get_items_to_apply_coupon( $coupon );
		$coupon_type    = $coupon->get_discount_type();

		// Core discounts are handled here as of 3.2.
		switch ( $coupon->get_discount_type() ) {
			case 'percent' :
				$this->apply_coupon_percent( $coupon, $items_to_apply );
				break;
			case 'fixed_product' :
				$this->apply_coupon_fixed_product( $coupon, $items_to_apply );
				break;
			case 'fixed_cart' :
				$this->apply_coupon_fixed_cart( $coupon, $items_to_apply );
				break;
			default :
				foreach ( $items_to_apply as $item ) {
					$discounted_price  = $this->get_discounted_price_in_cents( $item );
					$price_to_discount = wc_remove_number_precision( ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? $item->price : $discounted_price );
					$discount          = min( $discounted_price, wc_add_number_precision( $coupon->get_discount_amount( $price_to_discount ), $item->object ) );

					// Store code and discount amount per item.
					$this->discounts[ $coupon->get_code() ][ $item->key ] += $discount;
				}
				break;
		}

		return true;
	}

	/**
	 * Sort by price.
	 *
	 * @since  3.2.0
	 * @param  array $a First element.
	 * @param  array $b Second element.
	 * @return int
	 */
	protected function sort_by_price( $a, $b ) {
		$price_1 = $a->price * $a->quantity;
		$price_2 = $b->price * $b->quantity;
		if ( $price_1 === $price_2 ) {
			return 0;
		}
		return ( $price_1 < $price_2 ) ? 1 : -1;
	}

	/**
	 * Filter out all products which have been fully discounted to 0.
	 * Used as array_filter callback.
	 *
	 * @since  3.2.0
	 * @param  object $item Get data for this item.
	 * @return bool
	 */
	protected function filter_products_with_price( $item ) {
		return $this->get_discounted_price_in_cents( $item ) > 0;
	}

	/**
	 * Get items which the coupon should be applied to.
	 *
	 * @since  3.2.0
	 * @param  object $coupon Coupon object.
	 * @return array
	 */
	protected function get_items_to_apply_coupon( $coupon ) {
		$items_to_apply  = array();
		$limit_usage_qty = 0;
		$applied_count   = 0;

		if ( null !== $coupon->get_limit_usage_to_x_items() ) {
			$limit_usage_qty = $coupon->get_limit_usage_to_x_items();
		}

		foreach ( $this->items as $item ) {
			if ( 0 === $this->get_discounted_price_in_cents( $item ) ) {
				continue;
			}
			if ( ! $coupon->is_valid_for_product( $item->product, $item->object ) && ! $coupon->is_valid_for_cart() ) {
				continue;
			}
			if ( $limit_usage_qty && $applied_count > $limit_usage_qty ) {
				break;
			}
			if ( $limit_usage_qty && $item->quantity > ( $limit_usage_qty - $applied_count ) ) {
				$limit_to_qty   = absint( $limit_usage_qty - $applied_count );
				$item->price    = ( $item->price / $item->quantity ) * $limit_to_qty;
				$item->quantity = $limit_to_qty; // Lower the qty so the discount is applied less.
			}
			if ( 0 >= $item->quantity ) {
				continue;
			}
			$items_to_apply[] = $item;
			$applied_count   += $item->quantity;
		}
		return $items_to_apply;
	}

	/**
	 * Apply percent discount to items and return an array of discounts granted.
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon Coupon object. Passed through filters.
	 * @param  array     $items_to_apply Array of items to apply the coupon to.
	 * @return int Total discounted.
	 */
	protected function apply_coupon_percent( $coupon, $items_to_apply ) {
		$total_discount = 0;
		$cart_total     = 0;

		foreach ( $items_to_apply as $item ) {
			// Find out how much price is available to discount for the item.
			$discounted_price  = $this->get_discounted_price_in_cents( $item );

			// Get the price we actually want to discount, based on settings.
			$price_to_discount = ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? $item->price: $discounted_price;

			// Total up.
			$cart_total += $price_to_discount;

			// Run coupon calculations.
			$discount       = floor( $price_to_discount * ( $coupon->get_amount() / 100 ) );
			$discount       = min( $discounted_price, apply_filters( 'woocommerce_coupon_get_discount_amount', $discount, $price_to_discount, $item->object, false, $coupon ) );
			$total_discount += $discount;

			// Store code and discount amount per item.
			$this->discounts[ $coupon->get_code() ][ $item->key ] += $discount;
		}

		// Work out how much discount would have been given to the cart has a whole and compare to what was discounted on all line items.
		$cart_total_discount = wc_cart_round_discount( $cart_total * ( $coupon->get_amount() / 100 ), 0 );

		if ( $total_discount < $cart_total_discount ) {
			$total_discount += $this->apply_coupon_remainder( $coupon, $items_to_apply, $cart_total_discount - $total_discount );
		}

		return $total_discount;
	}

	/**
	 * Apply fixed product discount to items.
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon Coupon object. Passed through filters.
	 * @param  array     $items_to_apply Array of items to apply the coupon to.
	 * @param  int       $amount Fixed discount amount to apply in cents. Leave blank to pull from coupon.
	 * @return int Total discounted.
	 */
	protected function apply_coupon_fixed_product( $coupon, $items_to_apply, $amount = null ) {
		$total_discount = 0;
		$amount         = $amount ? $amount: wc_add_number_precision( $coupon->get_amount() );

		foreach ( $items_to_apply as $item ) {
			// Find out how much price is available to discount for the item.
			$discounted_price  = $this->get_discounted_price_in_cents( $item );

			// Get the price we actually want to discount, based on settings.
			$price_to_discount = ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? $item->price: $discounted_price;

			// Run coupon calculations.
			$discount       = $amount * $item->quantity;
			$discount       = min( $discounted_price, apply_filters( 'woocommerce_coupon_get_discount_amount', $discount, $price_to_discount, $item->object, false, $coupon ) );
			$total_discount += $discount;

			// Store code and discount amount per item.
			$this->discounts[ $coupon->get_code() ][ $item->key ] += $discount;
		}
		return $total_discount;
	}

	/**
	 * Apply fixed cart discount to items.
	 *
	 * @since  3.2.0
	 * @param  WC_Coupon $coupon Coupon object. Passed through filters.
	 * @param  array     $items_to_apply Array of items to apply the coupon to.
	 * @param  int       $amount Fixed discount amount to apply in cents. Leave blank to pull from coupon.
	 * @return int Total discounted.
	 */
	protected function apply_coupon_fixed_cart( $coupon, $items_to_apply, $amount = null ) {
		$total_discount = 0;
		$amount         = $amount ? $amount : wc_add_number_precision( $coupon->get_amount() );
		$items_to_apply = array_filter( $items_to_apply, array( $this, 'filter_products_with_price' ) );

		if ( ! $item_count = array_sum( wp_list_pluck( $items_to_apply, 'quantity' ) ) ) {
			return $total_discount;
		}

		$per_item_discount = absint( $amount / $item_count ); // round it down to the nearest cent.

		if ( $per_item_discount > 0 ) {
			$total_discount = $this->apply_coupon_fixed_product( $coupon, $items_to_apply, $per_item_discount );

			/**
			 * If there is still discount remaining, repeat the process.
			 */
			if ( $total_discount > 0 && $total_discount < $amount ) {
				$total_discount += $this->apply_coupon_fixed_cart( $coupon, $items_to_apply, $amount - $total_discount );
			}
		} elseif ( $amount > 0 ) {
			$total_discount += $this->apply_coupon_remainder( $coupon, $items_to_apply, $amount );
		}
		return $total_discount;
	}

	/**
	 * Deal with remaining fractional discounts by splitting it over items
	 * until the amount is expired, discounting 1 cent at a time.
	 *
	 * @since 3.2.0
	 * @param  WC_Coupon $coupon Coupon object if appliable. Passed through filters.
	 * @param  array     $items_to_apply Array of items to apply the coupon to.
	 * @param  int       $amount Fixed discount amount to apply.
	 * @return int Total discounted.
	 */
	protected function apply_coupon_remainder( $coupon, $items_to_apply, $amount ) {
		$total_discount = 0;

		foreach ( $items_to_apply as $item ) {
			for ( $i = 0; $i < $item->quantity; $i ++ ) {
				// Find out how much price is available to discount for the item.
				$discounted_price  = $this->get_discounted_price_in_cents( $item );

				// Get the price we actually want to discount, based on settings.
				$price_to_discount = ( 'yes' === get_option( 'woocommerce_calc_discounts_sequentially', 'no' ) ) ? $item->price: $discounted_price;

				// Run coupon calculations.
				$discount = min( $discounted_price, 1 );

				// Store totals.
				$total_discount += $discount;

				// Store code and discount amount per item.
				$this->discounts[ $coupon->get_code() ][ $item->key ] += $discount;

				if ( $total_discount >= $amount ) {
					break 2;
				}
			}
			if ( $total_discount >= $amount ) {
				break;
			}
		}
		return $total_discount;
	}

	/*
	 |--------------------------------------------------------------------------
	 | Validation & Error Handling
	 |--------------------------------------------------------------------------
	 */

	/**
	 * Ensure coupon exists or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_exists( $coupon ) {
		if ( ! $coupon->get_id() ) {
			/* translators: %s: coupon code */
			throw new Exception( sprintf( __( 'Coupon "%s" does not exist!', 'woocommerce' ), $coupon->get_code() ), 105 );
		}

		return true;
	}

	/**
	 * Ensure coupon usage limit is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_usage_limit( $coupon ) {
		if ( $coupon->get_usage_limit() > 0 && $coupon->get_usage_count() >= $coupon->get_usage_limit() ) {
			throw new Exception( __( 'Coupon usage limit has been reached.', 'woocommerce' ), 106 );
		}

		return true;
	}

	/**
	 * Ensure coupon user usage limit is valid or throw exception.
	 *
	 * Per user usage limit - check here if user is logged in (against user IDs).
	 * Checked again for emails later on in WC_Cart::check_customer_coupons().
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon  Coupon data.
	 * @param  int       $user_id User ID.
	 * @return bool
	 */
	protected function validate_coupon_user_usage_limit( $coupon, $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( $coupon && $user_id && $coupon->get_usage_limit_per_user() > 0 && $coupon->get_id() && $coupon->get_data_store() ) {
			$date_store  = $coupon->get_data_store();
			$usage_count = $date_store->get_usage_by_user_id( $coupon, $user_id );
			if ( $usage_count >= $coupon->get_usage_limit_per_user() ) {
				throw new Exception( __( 'Coupon usage limit has been reached.', 'woocommerce' ), 106 );
			}
		}

		return true;
	}

	/**
	 * Ensure coupon date is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_expiry_date( $coupon ) {
		if ( $coupon->get_date_expires() && current_time( 'timestamp', true ) > $coupon->get_date_expires()->getTimestamp() ) {
			throw new Exception( __( 'This coupon has expired.', 'woocommerce' ), 107 );
		}

		return true;
	}

	/**
	 * Ensure coupon amount is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon   Coupon data.
	 * @param  float     $subtotal Items subtotal.
	 * @return bool
	 */
	protected function validate_coupon_minimum_amount( $coupon, $subtotal = 0 ) {
		if ( $coupon->get_minimum_amount() > 0 && apply_filters( 'woocommerce_coupon_validate_minimum_amount', $coupon->get_minimum_amount() > $subtotal, $coupon, $subtotal ) ) {
			/* translators: %s: coupon minimum amount */
			throw new Exception( sprintf( __( 'The minimum spend for this coupon is %s.', 'woocommerce' ), wc_price( $coupon->get_minimum_amount() ) ), 108 );
		}

		return true;
	}

	/**
	 * Ensure coupon amount is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon   Coupon data.
	 * @param  float     $subtotal Items subtotal.
	 * @return bool
	 */
	protected function validate_coupon_maximum_amount( $coupon, $subtotal = 0 ) {
		if ( $coupon->get_maximum_amount() > 0 && apply_filters( 'woocommerce_coupon_validate_maximum_amount', $coupon->get_maximum_amount() < $subtotal, $coupon ) ) {
			/* translators: %s: coupon maximum amount */
			throw new Exception( sprintf( __( 'The maximum spend for this coupon is %s.', 'woocommerce' ), wc_price( $coupon->get_maximum_amount() ) ), 112 );
		}

		return true;
	}

	/**
	 * Ensure coupon is valid for products in the list is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_product_ids( $coupon ) {
		if ( count( $coupon->get_product_ids() ) > 0 ) {
			$valid = false;

			foreach ( $this->items as $item ) {
				if ( $item->product && in_array( $item->product->get_id(), $coupon->get_product_ids(), true ) || in_array( $item->product->get_parent_id(), $coupon->get_product_ids(), true ) ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				throw new Exception( __( 'Sorry, this coupon is not applicable to selected products.', 'woocommerce' ), 109 );
			}
		}

		return true;
	}

	/**
	 * Ensure coupon is valid for product categories in the list is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_product_categories( $coupon ) {
		if ( count( $coupon->get_product_categories() ) > 0 ) {
			$valid = false;

			foreach ( $this->items as $item ) {
				if ( $coupon->get_exclude_sale_items() && $item->product && $item->product->is_on_sale() ) {
					continue;
				}

				$product_cats = wc_get_product_cat_ids( $item->product->get_id() );

				// If we find an item with a cat in our allowed cat list, the coupon is valid.
				if ( count( array_intersect( $product_cats, $coupon->get_product_categories() ) ) > 0 ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				throw new Exception( __( 'Sorry, this coupon is not applicable to selected products.', 'woocommerce' ), 109 );
			}
		}

		return true;
	}

	/**
	 * Ensure coupon is valid for sale items in the list is valid or throw exception.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_sale_items( $coupon ) {
		if ( $coupon->get_exclude_sale_items() ) {
			$valid = false;

			foreach ( $this->items as $item ) {
				if ( $item->product && ! $item->product->is_on_sale() ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				throw new Exception( __( 'Sorry, this coupon is not valid for sale items.', 'woocommerce' ), 110 );
			}
		}

		return true;
	}

	/**
	 * All exclusion rules must pass at the same time for a product coupon to be valid.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_excluded_items( $coupon ) {
		if ( ! empty( $this->items ) && $coupon->is_type( wc_get_product_coupon_types() ) ) {
			$valid = false;

			foreach ( $this->items as $item ) {
				if ( $item->product && $coupon->is_valid_for_product( $item->product, $item->object ) ) {
					$valid = true;
					break;
				}
			}

			if ( ! $valid ) {
				throw new Exception( __( 'Sorry, this coupon is not applicable to selected products.', 'woocommerce' ), 109 );
			}
		}

		return true;
	}

	/**
	 * Cart discounts cannot be added if non-eligible product is found.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_eligible_items( $coupon ) {
		if ( ! $coupon->is_type( wc_get_product_coupon_types() ) ) {
			$this->validate_coupon_excluded_product_ids( $coupon );
			$this->validate_coupon_excluded_product_categories( $coupon );
		}

		return true;
	}

	/**
	 * Exclude products.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_excluded_product_ids( $coupon ) {
		// Exclude Products.
		if ( count( $coupon->get_excluded_product_ids() ) > 0 ) {
			$products = array();

			foreach ( $this->items as $item ) {
				if ( $item->product && in_array( $item->product->get_id(), $coupon->get_excluded_product_ids(), true ) || in_array( $item->product->get_parent_id(), $coupon->get_excluded_product_ids(), true ) ) {
					$products[] = $item->product->get_name();
				}
			}

			if ( ! empty( $products ) ) {
				/* translators: %s: products list */
				throw new Exception( sprintf( __( 'Sorry, this coupon is not applicable to the products: %s.', 'woocommerce' ), implode( ', ', $products ) ), 113 );
			}
		}

		return true;
	}

	/**
	 * Exclude categories from product list.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool
	 */
	protected function validate_coupon_excluded_product_categories( $coupon ) {
		if ( count( $coupon->get_excluded_product_categories() ) > 0 ) {
			$categories = array();

			foreach ( $this->items as $item ) {
				if ( $coupon->get_exclude_sale_items() && $item->product && $item->product->is_on_sale() ) {
					continue;
				}

				$product_cats = wc_get_product_cat_ids( $item->product->get_id() );
				$cat_id_list  = array_intersect( $product_cats, $coupon->get_excluded_product_categories() );
				if ( count( $cat_id_list ) > 0 ) {
					foreach ( $cat_id_list as $cat_id ) {
						$cat          = get_term( $cat_id, 'product_cat' );
						$categories[] = $cat->name;
					}
				}
			}

			if ( ! empty( $categories ) ) {
				/* translators: %s: categories list */
				throw new Exception( sprintf( __( 'Sorry, this coupon is not applicable to the categories: %s.', 'woocommerce' ), implode( ', ', array_unique( $categories ) ) ), 114 );
			}
		}

		return true;
	}

	/**
	 * Check if a coupon is valid.
	 *
	 * Error Codes:
	 * - 100: Invalid filtered.
	 * - 101: Invalid removed.
	 * - 102: Not yours removed.
	 * - 103: Already applied.
	 * - 104: Individual use only.
	 * - 105: Not exists.
	 * - 106: Usage limit reached.
	 * - 107: Expired.
	 * - 108: Minimum spend limit not met.
	 * - 109: Not applicable.
	 * - 110: Not valid for sale items.
	 * - 111: Missing coupon code.
	 * - 112: Maximum spend limit met.
	 * - 113: Excluded products.
	 * - 114: Excluded categories.
	 *
	 * @since  3.2.0
	 * @throws Exception Error message.
	 * @param  WC_Coupon $coupon Coupon data.
	 * @return bool|WP_Error
	 */
	public function is_coupon_valid( $coupon ) {
		try {
			$this->validate_coupon_exists( $coupon );
			$this->validate_coupon_usage_limit( $coupon );
			$this->validate_coupon_user_usage_limit( $coupon );
			$this->validate_coupon_expiry_date( $coupon );
			$this->validate_coupon_minimum_amount( $coupon );
			$this->validate_coupon_maximum_amount( $coupon );
			$this->validate_coupon_product_ids( $coupon );
			$this->validate_coupon_product_categories( $coupon );
			$this->validate_coupon_sale_items( $coupon );
			$this->validate_coupon_excluded_items( $coupon );
			$this->validate_coupon_eligible_items( $coupon );

			if ( ! apply_filters( 'woocommerce_coupon_is_valid', true, $coupon, $this ) ) {
				throw new Exception( __( 'Coupon is not valid.', 'woocommerce' ), 100 );
			}
		} catch ( Exception $e ) {
			/**
			 * Filter the coupon error message.
			 *
			 * @param string    $error_message Error message.
			 * @param int       $error_code    Error code.
			 * @param WC_Coupon $coupon        Coupon data.
			 */
			$message = apply_filters( 'woocommerce_coupon_error', $e->getMessage(), $e->getCode(), $coupon );

			return new WP_Error( 'invalid_coupon', $message, array(
				'status' => 400,
			) );
		}
		return true;
	}
}
