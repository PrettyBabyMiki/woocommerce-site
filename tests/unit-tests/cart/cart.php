<?php

/**
 * Class Cart.
 * @package WooCommerce\Tests\Cart
 */
class WC_Tests_Cart extends WC_Unit_Test_Case {

	/**
	 * Test some discount logic which has caused issues in the past.
	 * Tickets:
	 *  https://github.com/woocommerce/woocommerce/issues/10573
	 *  https://github.com/woocommerce/woocommerce/issues/10963
	 *
	 * Due to discounts being split amongst products in cart.
	 */
	public function test_cart_get_discounted_price() {
		global $wpdb;

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		# Test case 1 #10963

		// Create dummy coupon - fixed cart, 1 value
		$coupon  = WC_Helper_Coupon::create_coupon();

		// Add coupon
		WC()->cart->add_discount( $coupon->get_code() );

		// Create dummy product - price will be 10
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart x1, calc and test
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( '9.00', number_format( WC()->cart->total, 2, '.', '' ) );
		$this->assertEquals( '1.00', number_format( WC()->cart->discount_cart, 2, '.', '' ) );

		// Add product to cart x2, calc and test
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( '19.00', number_format( WC()->cart->total, 2, '.', '' ) );
		$this->assertEquals( '1.00', number_format( WC()->cart->discount_cart, 2, '.', '' ) );

		// Add product to cart x3, calc and test
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->calculate_totals();
		$this->assertEquals( '29.00', number_format( WC()->cart->total, 2, '.', '' ) );
		$this->assertEquals( '1.00', number_format( WC()->cart->discount_cart, 2, '.', '' ) );

		// Clean up the cart
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();

		# Test case 2 #10573
		update_post_meta( $product->get_id(), '_regular_price', '29.95' );
		update_post_meta( $product->get_id(), '_price', '29.95' );
		update_post_meta( $coupon->get_id(), 'discount_type', 'percent' );
		update_post_meta( $coupon->get_id(), 'coupon_amount', '10' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );
		$product = wc_get_product( $product->get_id() );

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );

		WC()->cart->calculate_totals();
		$cart_item = current( WC()->cart->get_cart() );
		$this->assertEquals( '24.51', number_format( $cart_item['line_total'], 2, '.', '' ) );

		// Cleanup
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		WC_Helper_Product::delete_product( $product->get_id() );

		# Test case 3 #11626
		update_post_meta( $coupon->get_id(), 'discount_type', 'percent' );
		update_post_meta( $coupon->get_id(), 'coupon_amount', '50' );
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '19.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		$product_ids   = array();
		$products_data = array(
			'5.17',
			'3.32',
			'1.25',
			'3.50',
			'5.01',
			'3.34',
			'5.99',
			'5.51',
		);
		foreach ( $products_data as $price ) {
			$loop_product  = WC_Helper_Product::create_simple_product();
			$product_ids[] = $loop_product->get_id();
			update_post_meta( $loop_product->get_id(), '_regular_price', $price );
			update_post_meta( $loop_product->get_id(), '_price', $price );
			WC()->cart->add_to_cart( $loop_product->get_id(), 1 );
		}

		WC()->cart->add_discount( $coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '16.55', WC()->cart->total );

		// Cleanup
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();

		foreach ( $product_ids as $product_id ) {
			WC_Helper_Product::delete_product( $product_id );
		}

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );

		// Delete coupon
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );
	}

	/**
	 * Test that calculation rounding is done correctly with and without taxes.
	 *
	 * @see https://github.com/woocommerce/woocommerce/issues/16305
	 * @since 3.2
	 */
	public function test_discount_cart_rounding() {
		global $wpdb;

		# Test with no taxes.
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();

		$product = new WC_Product_Simple();
		$product->set_regular_price( 51.86 );
		$product->save();

		$coupon = new WC_Coupon();
		$coupon->set_code( 'testpercent' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 40 );
		$coupon->save();

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );

		WC()->cart->calculate_totals();
		$cart_item = current( WC()->cart->get_cart() );
		$this->assertEquals( '31.12', number_format( $cart_item['line_total'], 2, '.', '' ) );

		// Clean up.
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();

		# Test with taxes.
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '8.2500',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->cart->add_discount( $coupon->get_code() );

		WC()->cart->calculate_totals();
		$cart_item = current( WC()->cart->get_cart() );
		$this->assertEquals( '33.69', number_format( $cart_item['line_total'] + $cart_item['line_tax'], 2, '.', '' ) );

		// Clean up.
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		WC_Helper_Product::delete_product( $product->get_id() );
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test cart calculations when out of base location and using inclusive taxes and discounts.
	 *
	 * @see GitHub issues #17517 and #17536.
	 * @since 3.3
	 */
	public function test_out_of_base_discounts_inclusive_tax() {
		global $wpdb;

		// Set up tax options.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );

		// 20% tax for GB.
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// 20% tax everywhere else.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product.
		$product = new WC_Product_Simple();
		$product->set_regular_price( '9.99' );
		$product->save();

		// Create coupons.
		$ten_coupon = new WC_Coupon();
		$ten_coupon->set_code( '10off' );
		$ten_coupon->set_discount_type( 'percent' );
		$ten_coupon->set_amount( 10 );
		$ten_coupon->save();

		$half_coupon = new WC_Coupon();
		$half_coupon->set_code( '50off' );
		$half_coupon->set_discount_type( 'percent' );
		$half_coupon->set_amount( 50 );
		$half_coupon->save();

		$full_coupon = new WC_Coupon();
		$full_coupon->set_code( '100off' );
		$full_coupon->set_discount_type( 'percent' );
		$full_coupon->set_amount( 100 );
		$full_coupon->save();

		add_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_gb_shipping' ) );
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test in store location with no coupon.
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '1.66', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '9.99', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Test in store location with 10% coupon.
		WC()->cart->add_discount( $ten_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.83', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '1.50', wc_format_decimal( WC()->cart->get_total_tax(), 2 ), WC()->cart->get_total_tax() );
		$this->assertEquals( '8.99', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test in store location with 50% coupon.
		WC()->cart->add_discount( $half_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '4.16', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.83', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '5.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test in store location with 100% coupon.
		WC()->cart->add_discount( $full_coupon->get_code() );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_discount_total(), 2 ), 'Discount total in base' );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		WC()->cart->empty_cart();
		remove_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_gb_shipping' ) );
		add_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_us_shipping' ) );
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test out of store location with no coupon.
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '1.66', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '9.99', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Test out of store location with 10% coupon.
		WC()->cart->add_discount( $ten_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.83', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '1.50', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '8.99', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test out of store location with 50% coupon.
		WC()->cart->add_discount( $half_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '4.16', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.83', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '5.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test out of store location with 100% coupon.
		WC()->cart->add_discount( $full_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '8.33', wc_format_decimal( WC()->cart->get_discount_total(), 2 ), 'Discount total out of base' );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Clean up.
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		remove_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_us_shipping' ) );
		WC_Helper_Product::delete_product( $product->get_id() );
		WC_Helper_Coupon::delete_coupon( $ten_coupon->get_id() );
		WC_Helper_Coupon::delete_coupon( $half_coupon->get_id() );
		WC_Helper_Coupon::delete_coupon( $full_coupon->get_id() );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test cart calculations when out of base location with no matching taxes and using inclusive taxes and discounts.
	 *
	 * @see GitHub issue #19390.
	 * @since 3.3
	 */
	public function test_out_of_base_discounts_inclusive_tax_no_oob_tax() {
		global $wpdb;

		// Set up tax options.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'GB' );
		update_option( 'woocommerce_default_customer_address', 'base' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );

		// 20% tax for GB.
		$tax_rate = array(
			'tax_rate_country'  => 'GB',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// 0% tax everywhere else.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '0.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product.
		$product = new WC_Product_Simple();
		$product->set_regular_price( '24.99' );
		$product->save();

		// Create coupon.
		$ten_coupon = new WC_Coupon();
		$ten_coupon->set_code( '10off' );
		$ten_coupon->set_discount_type( 'percent' );
		$ten_coupon->set_amount( 10 );
		$ten_coupon->save();

		$half_coupon = new WC_Coupon();
		$half_coupon->set_code( '50off' );
		$half_coupon->set_discount_type( 'percent' );
		$half_coupon->set_amount( 50 );
		$half_coupon->save();

		$full_coupon = new WC_Coupon();
		$full_coupon->set_code( '100off' );
		$full_coupon->set_discount_type( 'percent' );
		$full_coupon->set_amount( 100 );
		$full_coupon->save();

		add_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_us_shipping' ) );
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test out of store location with no coupon.
		WC()->cart->calculate_totals();
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Test out of store location with 10% coupon.
		WC()->cart->add_discount( $ten_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '2.08', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '18.75', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test out of store location with 50% coupon.
		WC()->cart->add_discount( $half_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '10.41', wc_format_decimal( WC()->cart->get_discount_total(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '10.42', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		WC()->cart->remove_coupons();

		// Test out of store location with 100% coupon.
		WC()->cart->add_discount( $full_coupon->get_code() );
		WC()->cart->calculate_totals();
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_subtotal(), 2 ) );
		$this->assertEquals( '20.83', wc_format_decimal( WC()->cart->get_discount_total(), 2 ), 'Discount total out of base' );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );
		$this->assertEquals( '0.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Clean up.
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		remove_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'force_customer_us_shipping' ) );
		WC_Helper_Product::delete_product( $product->get_id() );
		WC_Helper_Coupon::delete_coupon( $ten_coupon->get_id() );
		WC_Helper_Coupon::delete_coupon( $half_coupon->get_id() );
		WC_Helper_Coupon::delete_coupon( $full_coupon->get_id() );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}


	/**
	 * Helper that can be hooked to a filter to force the customer's shipping country to be GB.
	 *
	 * @since 3.3
	 * @param string $country
	 * @return string
	 */
	public function force_customer_gb_shipping( $country ) {
		return 'GB';
	}

	/**
	 * Helper that can be hooked to a filter to force the customer's shipping country to be US.
	 *
	 * @since 3.3
	 * @param string $country
	 * @return string
	 */
	public function force_customer_us_shipping( $country ) {
		return 'US';
	}

	/**
	 * Test a rounding issue on prices that are entered inclusive tax and shipping is used.
	 * See: #17970.
	 *
	 * @since 3.2.6
	 */
	public function test_inclusive_tax_rounding() {
		global $wpdb;

		// Store is set to enter product prices inclusive tax.
		update_option( 'woocommerce_prices_include_tax', 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// 19% tax.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '19.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create a flat rate method.
		$flat_rate_settings = array(
			'enabled'      => 'yes',
			'title'        => 'Flat rate',
			'availability' => 'all',
			'countries'    => '',
			'tax_status'   => 'taxable',
			'cost'         => '4.12',
		);
		update_option( 'woocommerce_flat_rate_settings', $flat_rate_settings );
		update_option( 'woocommerce_flat_rate', array() );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping->load_shipping_methods();

		// We need this to have the calculate_totals() method calculate totals.
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Create the product and add it to the cart.
		$product = new WC_Product_Simple();
		$product->set_regular_price( '149.14' );
		$product->save();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Set the flat_rate shipping method
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );

		WC()->cart->calculate_totals();
		$this->assertEquals( '154.04', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );
		$this->assertEquals( '24.59', wc_format_decimal( WC()->cart->get_total_tax(), 2 ) );

		// Clean up.
		WC()->cart->empty_cart();
		WC_Helper_Product::delete_product( $product->get_id() );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );

		// Delete the flat rate method
		WC()->session->set( 'chosen_shipping_methods', array() );
		delete_option( 'woocommerce_flat_rate_settings' );
		delete_option( 'woocommerce_flat_rate' );
		WC_Cache_Helper::get_transient_version( 'shipping', true );
		WC()->shipping->unregister_shipping_methods();
	}

	/**
	 * Test a rounding issue on prices that are entered exclusive tax.
	 * See: #17970.
	 *
	 * @since 3.2.6
	 */
	public function test_exclusive_tax_rounding() {
		global $wpdb;

		// todo remove this line when previous test stops failing.
		WC()->cart->empty_cart();

		// Store is set to enter product prices excluding tax.
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// 20% tax.
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '0',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Add 2 products whose retail prices (inc tax) are: £65, £50.
		// Their net prices are therefore: £54.1666667 and £41.6666667.
		$product1 = new WC_Product_Simple();
		$product1->set_regular_price( '54.1666667' );
		$product1->save();

		$product2 = new WC_Product_Simple();
		$product2->set_regular_price( '41.6666667' );
		$product2->save();

		WC()->cart->add_to_cart( $product1->get_id(), 1 );
		WC()->cart->add_to_cart( $product2->get_id(), 1 );

		WC()->cart->calculate_totals();
		$this->assertEquals( '115.00', wc_format_decimal( WC()->cart->get_total( 'edit' ), 2 ) );

		// Clean up.
		WC()->cart->empty_cart();
		WC_Helper_Product::delete_product( $product1->get_id() );
		WC_Helper_Product::delete_product( $product2->get_id() );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test a rounding issue on prices and totals that are entered exclusive tax.
	 * See: #17647.
	 *
	 * @since 3.2.6
	 */
	public function test_exclusive_tax_rounding_and_totals() {
		global $wpdb;

		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );

		$product_data = array(
			// price, quantity.
			array( 2.13, 1 ),
			array( 2.55, 0.5 ),
			array( 39, 1 ),
			array( 1.76, 1 ),
		);

		foreach ( $product_data as $data ) {
			$product = new WC_Product_Simple();
			$product->set_regular_price( $data[0] );
			$product->save();
			$products[] = array( $product, $data[1] );
		}

		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '5.5',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		WC_Tax::_insert_tax_rate( $tax_rate );

		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );

		foreach ( $products as $data ) {
			WC()->cart->add_to_cart( $data[0]->get_id(), $data[1] );
		}

		// We need this to have the calculate_totals() method calculate totals.
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		WC()->cart->calculate_totals();

		$cart_totals = WC()->cart->get_totals();

		$this->assertEquals( '2.44', wc_format_decimal( $cart_totals['total_tax'], 2 ) );
		$this->assertEquals( '2.44', wc_format_decimal( $cart_totals['cart_contents_tax'], 2 ) );
		$this->assertEquals( '44.17', wc_format_decimal( $cart_totals['cart_contents_total'], 2 ) );
		$this->assertEquals( '46.61', wc_format_decimal( $cart_totals['total'], 2 ) );

		// Clean up.
		WC()->cart->empty_cart();
		foreach ( $products as $data ) {
			WC_Helper_Product::delete_product( $data[0]->get_id() );
		}
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test get_remove_url.
	 *
	 * @since 2.3
	 */
	public function test_get_remove_url() {
		// Get the cart page id
		$cart_page_url = wc_get_page_permalink( 'cart' );

		// Test cart item key
		$cart_item_key = 'test';

		// Do the check
		$this->assertEquals( apply_filters( 'woocommerce_get_remove_url', $cart_page_url ? wp_nonce_url( add_query_arg( 'remove_item', $cart_item_key, $cart_page_url ), 'woocommerce-cart' ) : '' ), wc_get_cart_remove_url( $cart_item_key ) );
	}

	/**
	 * Test add to cart simple product.
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_simple() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		WC()->cart->empty_cart();

		// Add the product to the cart. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 1 ) );

		// Check if the item is in the cart
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Check if we can add a trashed product to the cart.
	 */
	public function test_add_to_cart_trashed() {
		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Trash product
		wp_trash_post( $product->get_id() );

		// Refetch product, to be sure
		$product = wc_get_product( $product->get_id() );

		// Add product to cart
		$this->assertFalse( WC()->cart->add_to_cart( $product->get_id(), 1 ) );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test add to cart variable product.
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_variable() {
		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();
		$variation  = array_shift( $variations );

		// Add the product to the cart. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 1, $variation['variation_id'], array( 'Size' => ucfirst( $variation['attributes']['attribute_pa_size'] ) ) ) );

		// Check if the item is in the cart
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();
		$product->delete( true );
	}

	/**
	 * Check if adding a product that is sold individually is corrected when adding multiple times.
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_sold_individually() {
		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Set sold_individually to yes
		$product->sold_individually = 'yes';
		update_post_meta( $product->get_id(), '_sold_individually', 'yes' );

		// Add the product twice to cart, should be corrected to 1. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->get_id(), 2 ) );

		// Check if the item is in the cart
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test the find_product_in_cart method.
	 *
	 * @since 2.3
	 */
	public function test_find_product_in_cart() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Generate cart id
		$cart_id = WC()->cart->generate_cart_id( $product->get_id() );

		// Get the product from the cart
		$this->assertNotEquals( '', WC()->cart->find_product_in_cart( $cart_id ) );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->get_id() );

	}

	/**
	 * Test the generate_cart_id method.
	 *
	 * @since 2.3
	 */
	public function test_generate_cart_id() {

		// Setup data
		$product_id     = 1;
		$variation_id   = 2;
		$variation      = array( 'Testing' => 'yup' );
		$cart_item_data = array(
			'string_val' => 'The string I was talking about',
			'array_val'  => array(
				'this',
				'is',
				'an',
				'array',
			),
		);

		// Manually generate ID
		$id_parts = array( $product_id );

		if ( $variation_id && 0 != $variation_id ) {
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

				if ( is_array( $value ) ) {
					$value = http_build_query( $value );
				}
				$cart_item_data_key .= trim( $key ) . trim( $value );

			}
			$id_parts[] = $cart_item_data_key;
		}

		$manual_cart_id = md5( implode( '_', $id_parts ) );

		// Assert
		$this->assertEquals( $manual_cart_id, WC()->cart->generate_cart_id( $product_id, $variation_id, array( 'Testing' => 'yup' ), $cart_item_data ) );

	}

	/**
	 * Test the set_quantity method.
	 *
	 * @since 2.3
	 */
	public function test_set_quantity() {
		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add 1 product to cart
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Get cart id
		$cart_id = WC()->cart->generate_cart_id( $product->get_id() );

		// Set quantity of product in cart to 2
		$this->assertTrue( WC()->cart->set_quantity( $cart_id, 2 ), $cart_id );

		// Check if there are 2 items in cart now
		$this->assertEquals( 2, WC()->cart->get_cart_contents_count() );

		// Set quantity of product in cart to 0
		$this->assertTrue( WC()->cart->set_quantity( $cart_id, 0 ) );

		// Check if there are 0 items in cart now
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test check_cart_item_validity method.
	 *
	 * @since 2.3
	 */
	public function test_check_cart_item_validity() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Check cart validity, should pass
		$this->assertTrue( WC()->cart->check_cart_item_validity() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->get_id() );

	}

	/**
	 * Test get_total.
	 *
	 * @since 2.3
	 */
	public function test_get_total() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add product to cart
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Check
		$this->assertEquals( apply_filters( 'woocommerce_cart_total', wc_price( WC()->cart->total ) ), WC()->cart->get_total() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test get_total_ex_tax.
	 *
	 * @since 2.3
	 */
	public function test_get_total_ex_tax() {
		global $wpdb;

		// Set calc taxes option.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		// We need this to have the calculate_totals() method calculate totals.
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add 10 fee.
		WC_Helper_Fee::add_cart_fee( 'taxed' );

		// Add product to cart (10).
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Check.
		$this->assertEquals( wc_price( 22 ), WC()->cart->get_total() );
		$this->assertEquals( wc_price( 20 ), WC()->cart->get_total_ex_tax() );
		$tax_totals = WC()->cart->get_tax_totals();

		// Clean up the cart.
		WC()->cart->empty_cart();

		// Clean up product.
		WC_Helper_Product::delete_product( $product->get_id() );

		// Restore option.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test needs_shipping_address method.
	 */
	public function test_needs_shipping_address() {
		$needs_shipping_address = false;

		if ( WC()->cart->needs_shipping() === true && ! wc_ship_to_billing_address_only() ) {
			$needs_shipping_address = true;
		}

		$this->assertEquals( apply_filters( 'woocommerce_cart_needs_shipping_address', $needs_shipping_address ), WC()->cart->needs_shipping_address() );
	}

	/**
	 * Test shipping total.
	 *
	 * @since 2.3
	 */
	public function test_shipping_total() {
		// Create product
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_price', '10' );
		update_post_meta( $product->get_id(), '_regular_price', '10' );

		// Create a flat rate method
		WC_Helper_Shipping::create_simple_flat_rate();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add product to cart
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Set the flat_rate shipping method
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->cart->calculate_totals();

		// Test if the shipping total amount is equal 20
		$this->assertEquals( 10, WC()->cart->shipping_total );

		// Test if the cart total amount is equal 20
		$this->assertEquals( 20, WC()->cart->total );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Delete the flat rate method
		WC()->session->set( 'chosen_shipping_methods', array() );
		WC_Helper_Shipping::delete_simple_flat_rate();

		// Delete product
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test cart fee.
	 *
	 * @since 2.3
	 */
	public function test_cart_fee() {
		// Create product.
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_price', '10' );
		update_post_meta( $product->get_id(), '_regular_price', '10' );

		// We need this to have the calculate_totals() method calculate totals.
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add fee.
		WC_Helper_Fee::add_cart_fee();

		// Add product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test if the cart total amount is equal 20.
		$this->assertEquals( 20, WC()->cart->total );

		// Clean up.
		wc_clear_notices();
		WC()->cart->empty_cart();
		WC_Helper_Fee::remove_cart_fee();
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test cart fee with taxes.
	 *
	 * @since 3.2
	 */
	public function test_cart_fee_taxes() {
		global $wpdb;

		// Set up taxes.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product.
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_price', '10' );
		update_post_meta( $product->get_id(), '_regular_price', '10' );

		// We need this to have the calculate_totals() method calculate totals.
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add fee.
		WC_Helper_Fee::add_cart_fee( 'taxed' );

		// Add product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test if the cart total amount is equal 22 ($10 item + $10 fee + 10% taxes).
		$this->assertEquals( 22, WC()->cart->total );

		// Clean up.
		wc_clear_notices();
		WC()->cart->empty_cart();
		WC_Helper_Fee::remove_cart_fee( 'taxed' );
		WC_Helper_Product::delete_product( $product->get_id() );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test negative cart fee.
	 *
	 * @since 3.2
	 */
	public function test_cart_negative_fee() {
		// Create product.
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_price', '15' );
		update_post_meta( $product->get_id(), '_regular_price', '15' );

		// We need this to have the calculate_totals() method calculate totals.
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add fee.
		WC_Helper_Fee::add_cart_fee( 'negative' );

		// Add product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test if the cart total amount is equal 5.
		$this->assertEquals( 5, WC()->cart->total );

		// Clean up.
		wc_clear_notices();
		WC()->cart->empty_cart();
		WC_Helper_Fee::remove_cart_fee( 'negative' );
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	/**
	 * Test negative cart fee with taxes.
	 *
	 * @since 3.2
	 */
	public function test_cart_negative_fee_taxes() {
		global $wpdb;

		// Set up taxes.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create product.
		$product = WC_Helper_Product::create_simple_product();
		update_post_meta( $product->get_id(), '_price', '15' );
		update_post_meta( $product->get_id(), '_regular_price', '15' );

		// We need this to have the calculate_totals() method calculate totals.
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add fee.
		WC_Helper_Fee::add_cart_fee( 'negative-taxed' );

		// Add product to cart.
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test if the cart total amount is equal 5.50 ($15 item - $10 negative fee + 10% tax).
		$this->assertEquals( 5.50, WC()->cart->total );

		// Clean up.
		wc_clear_notices();
		WC()->cart->empty_cart();
		WC_Helper_Fee::remove_cart_fee( 'negative-taxed' );
		WC_Helper_Product::delete_product( $product->get_id() );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test cart coupons.
	 */
	public function test_get_coupons() {
		// Create coupon
		$coupon = WC_Helper_Coupon::create_coupon();

		// Add coupon
		WC()->cart->add_discount( $coupon->get_code() );

		$this->assertEquals( count( WC()->cart->get_coupons() ), 1 );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Remove coupons
		WC()->cart->remove_coupons();

		// Delete coupon
		WC_Helper_Coupon::delete_coupon( $coupon->get_id() );

	}

	/**
	 * Test add_discount allows coupons by code but not by ID.
	 *
	 * @since 3.2
	 */
	public function test_add_discount_code_id() {

		$coupon = new WC_Coupon();
		$coupon->set_code( 'test' );
		$coupon->set_amount( 100 );
		$coupon->save();

		$success = WC()->cart->add_discount( $coupon->get_code() );
		$this->assertTrue( $success );

		$success = WC()->cart->add_discount( (string) $coupon->get_id() );
		$this->assertFalse( $success );
	}

	public function test_add_invidual_use_coupon() {
		$iu_coupon = WC_Helper_Coupon::create_coupon( 'code1' );
		$iu_coupon->set_individual_use( true );
		$iu_coupon->save();
		$coupon = WC_Helper_Coupon::create_coupon();

		WC()->cart->add_discount( $iu_coupon->get_code() );
		WC()->cart->add_discount( $coupon->get_code() );

		$coupons = WC()->cart->get_coupons();

		$this->assertEquals( count( $coupons ), 1 );
		$this->assertEquals( 'code1', reset( $coupons )->get_code() );

		// Clean up
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		WC_Helper_Coupon::delete_coupon( $coupon->get_code() );
		WC_Helper_Coupon::delete_coupon( $iu_coupon->get_code() );
	}

	public function test_add_individual_use_coupon_removal() {
		$coupon = WC_Helper_Coupon::create_coupon();
		$iu_coupon = WC_Helper_Coupon::create_coupon( 'code1' );
		$iu_coupon->set_individual_use( true );
		$iu_coupon->save();

		WC()->cart->add_discount( $coupon->get_code() );
		WC()->cart->add_discount( $iu_coupon->get_code() );

		$coupons = WC()->cart->get_coupons();

		$this->assertEquals( count( $coupons ), 1 );
		$this->assertEquals( 'code1', reset( $coupons )->get_code() );
		$this->assertEquals( 1, did_action( 'woocommerce_removed_coupon' ) );

		// Clean up
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		WC_Helper_Coupon::delete_coupon( $coupon->get_code() );
		WC_Helper_Coupon::delete_coupon( $iu_coupon->get_code() );
	}

	public function test_add_individual_use_coupon_double_individual() {
		$iu_coupon1 = WC_Helper_Coupon::create_coupon( 'code1' );
		$iu_coupon1->set_individual_use( true );
		$iu_coupon1->save();

		$iu_coupon2 = WC_Helper_Coupon::create_coupon( 'code2' );
		$iu_coupon2->set_individual_use( true );
		$iu_coupon2->save();

		WC()->cart->add_discount( $iu_coupon1->get_code() );
		WC()->cart->add_discount( $iu_coupon2->get_code() );

		$coupons = WC()->cart->get_coupons();

		$this->assertEquals( count( $coupons ), 1 );
		$this->assertEquals( 'code2', reset( $coupons )->get_code() );

		// Clean up
		WC()->cart->empty_cart();
		WC()->cart->remove_coupons();
		WC_Helper_Coupon::delete_coupon( $iu_coupon1->get_code() );
		WC_Helper_Coupon::delete_coupon( $iu_coupon2->get_code() );
	}

	public function test_clone_cart() {
		$cart              = wc()->cart;
		$new_cart          = clone $cart;
		$is_identical_cart = $cart === $new_cart;

		// Cloned carts should not be identical.
		$this->assertFalse( $is_identical_cart, 'Cloned cart not identical to original cart' );
	}

	public function test_cloned_cart_session() {
		// PHP 5.2 does not include support for ReflectionProperty::setAccessible().
		if ( version_compare( '5.3', PHP_VERSION, '>' ) ) {
			$this->markTestSkipped( 'Test requires PHP 5.3 and above to use ReflectionProperty::setAccessible()' );
		}

		$cart     = wc()->cart;
		$new_cart = clone $cart;

		// Allow accessing protected properties.
		$reflected_cart = new ReflectionClass( $cart );
		$cart_session   = $reflected_cart->getProperty( 'session' );
		$cart_session->setAccessible( true );
		$reflected_new_cart = new ReflectionClass( $new_cart );
		$new_cart_session   = $reflected_new_cart->getProperty( 'session' );
		$new_cart_session->setAccessible( true );

		// Ensure that cloned properties are not identical.
		$identical_sessions = $cart_session->getValue( $cart ) === $new_cart_session->getValue( $new_cart );
		$this->assertFalse( $identical_sessions, 'Cloned cart sessions should not be identical to original cart' );
	}

	public function test_cloned_cart_fees() {
		$cart     = wc()->cart;
		$new_cart = clone $cart;

		// Get the properties from each object.
		$cart_fees = $cart->fees_api();
		$new_cart_fees = $new_cart->fees_api();

		// Ensure that cloned properties are not identical.
		$identical_fees = $cart_fees === $new_cart_fees;
		$this->assertFalse( $identical_fees, 'Cloned cart fees should not be identical to original cart.' );
	}

	public function test_cart_object_istantiation() {
		$cart = new WC_Cart();
		$this->assertInstanceOf( 'WC_Cart', $cart );
	}

	public function test_get_cart_item_quantities() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertEquals( 1, array_sum( WC()->cart->get_cart_item_quantities() ) );
		// Clean up the cart.
		WC()->cart->empty_cart();
		// Clean up product.
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	public function test_get_cart_contents_weight() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertEquals( 1.1, WC()->cart->get_cart_contents_weight() );
		// Clean up the cart.
		WC()->cart->empty_cart();
		// Clean up product.
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	public function test_check_cart_items() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertEquals( true, WC()->cart->check_cart_items() );
		// Clean up the cart.
		WC()->cart->empty_cart();
		// Clean up product.
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	public function test_check_cart_item_stock() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertEquals( true, WC()->cart->check_cart_item_stock() );
		// Clean up the cart.
		WC()->cart->empty_cart();
		// Clean up product.
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	public function test_get_cross_sells() {
		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		$this->assertEquals( array(), WC()->cart->get_cross_sells() );
		// Clean up the cart.
		WC()->cart->empty_cart();
		// Clean up product.
		WC_Helper_Product::delete_product( $product->get_id() );
	}

	public function test_get_tax_totals() {
		global $wpdb;

		// Set calc taxes option.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$tax_rate = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '10.0000',
			'tax_rate_name'     => 'TAX',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);
		WC_Tax::_insert_tax_rate( $tax_rate );

		// Create dummy product.
		$product = WC_Helper_Product::create_simple_product();

		// We need this to have the calculate_totals() method calculate totals.
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add product to cart (10).
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Check.
		$tax_totals = WC()->cart->get_tax_totals();
		$this->assertArrayHasKey( 'TAX-1', $tax_totals );
		$this->assertEquals( 1, $tax_totals['TAX-1']->amount );
		$this->assertEquals( false, $tax_totals['TAX-1']->is_compound );
		$this->assertEquals( 'TAX', $tax_totals['TAX-1']->label );

		// Clean up the cart.
		WC()->cart->empty_cart();

		// Clean up product.
		WC_Helper_Product::delete_product( $product->get_id() );

		// Restore option.
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

	/**
	 * Test is_coupon_emails_allowed function on the cart, specifically test wildcard emails.
	 *
	 * @return void
	 */
	public function test_is_coupon_emails_allowed() {
		$this->assertEquals( true, WC()->cart->is_coupon_emails_allowed( array( 'customer@wc.local' ), array( '*.local' ) ) );
		$this->assertEquals( false, WC()->cart->is_coupon_emails_allowed( array( 'customer@wc.local' ), array( '*.test' ) ) );
		$this->assertEquals( true, WC()->cart->is_coupon_emails_allowed( array( 'customer@wc.local' ), array( 'customer@wc.local' ) ) );
		$this->assertEquals( false, WC()->cart->is_coupon_emails_allowed( array( 'customer@wc.local' ), array( 'customer2@wc.local' ) ) );
	}
}
