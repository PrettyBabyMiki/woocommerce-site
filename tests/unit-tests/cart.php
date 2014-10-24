<?php

class WC_Tests_Cart extends WC_Unit_Test_Case {

	/**
	 * Helper method to get the checkout URL
	 *
	 * @since 2.3
	 * @return string
	 */
	private function get_checkout_url() {

		// Get the checkout URL
		$checkout_page_id = wc_get_page_id( 'checkout' );

		$checkout_url = '';

		// Check if there is a checkout page
		if ( $checkout_page_id ) {

			// Get the permalink
			$checkout_url = get_permalink( $checkout_page_id );

			// Force SLL if needed
			if ( is_ssl() || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
				$checkout_url = str_replace( 'http:', 'https:', $checkout_url );
			}

			// Allow filtering of checkout URL
			$checkout_url = apply_filters( 'woocommerce_get_checkout_url', $checkout_url );
		}

		return $checkout_url;
	}

	/**
	 * Test get_checkout_url over HTTP
	 *
	 * @since 2.3
	 */
	public function test_get_checkout_url_regular() {

		// Get the original setting
		$o_setting = get_option( 'woocommerce_force_ssl_checkout' );

		// Force SLL checkout
		update_option( 'woocommerce_force_ssl_checkout', 'no' );

		$this->assertEquals( $this->get_checkout_url(), WC()->cart->get_checkout_url() );

		// Restore option
		update_option( 'woocommerce_force_ssl_checkout', $o_setting );

	}

	/**
	 * Test get_checkout_url over HTTP
	 *
	 * @since 2.3
	 */
	public function test_get_checkout_url_ssl() {

		// Get the original setting
		$o_setting = get_option( 'woocommerce_force_ssl_checkout' );

		// Force SLL checkout
		update_option( 'woocommerce_force_ssl_checkout', 'yes' );

		$this->assertEquals( $this->get_checkout_url(), WC()->cart->get_checkout_url() );

		// Restore option
		update_option( 'woocommerce_force_ssl_checkout', $o_setting );

	}

	/**
	 * Test get_cart_url method
	 *
	 * @since 2.3
	 */
	public function test_get_cart_url() {
		$cart_page_id = wc_get_page_id( 'cart' );
		$this->assertEquals( apply_filters( 'woocommerce_get_cart_url', $cart_page_id ? get_permalink( $cart_page_id ) : '' ), WC()->cart->get_cart_url() );
	}

	/**
	 * Test get_remove_url
	 *
	 * @since 2.3
	 */
	public function test_get_remove_url() {
		// Get the cart page id
		$cart_page_id = wc_get_page_id( 'cart' );

		// Test cart item key
		$cart_item_key = 'test';

		// Do the check
		$this->assertEquals( apply_filters( 'woocommerce_get_remove_url', $cart_page_id ? wp_nonce_url( add_query_arg( 'remove_item', $cart_item_key, get_permalink( $cart_page_id ) ), 'woocommerce-cart' ) : '' ), WC()->cart->get_remove_url( $cart_item_key ) );
	}

	/**
	 * Test add to cart simple product
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_simple() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add the product to the cart. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->id, 1 ) );

		// Check if the item is in the cart
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test add to cart variable product
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_variable() {
		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();
		$variation  = array_shift( $variations );

		// Add the product to the cart. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->id, 1, $variation['variation_id'], array( 'Size' => ucfirst( $variation['attributes']['attribute_pa_size'] ) ) ) );

		// Check if the item is in the cart
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// @todo clean up the variable product
	}

	/**
	 * Check if adding a product that is sold individually is corrected when adding multiple times
	 *
	 * @since 2.3
	 */
	public function test_add_to_cart_sold_individually() {
		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Set sold_individually to yes
		$product->sold_individually = 'yes';
		update_post_meta( $product->id, '_sold_individually', 'yes' );

		// Add the product twice to cart, should be corrected to 1. Methods returns boolean on failure, string on success.
		$this->assertNotFalse( WC()->cart->add_to_cart( $product->id, 2 ) );

		// Check if the item is in the cart
		$this->assertEquals( 1, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test the find_product_in_cart method
	 *
	 * @since 2.3
	 */
	public function test_find_product_in_cart() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Generate cart id
		$cart_id = WC()->cart->generate_cart_id( $product->id );

		// Get the product from the cart
		$this->assertNotEquals( '', WC()->cart->find_product_in_cart( $cart_id ) );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );

	}

	/**
	 * Test the generate_cart_id method
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
				'array'
			)
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
	 * Test the set_quantity method
	 *
	 * @since 2.3
	 */
	public function test_set_quantity() {
		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add 1 product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Get cart id
		$cart_id = WC()->cart->generate_cart_id( $product->id );

		// Set quantity of product in cart to 2
		$this->assertTrue( WC()->cart->set_quantity( $cart_id, 2 ) );

		// Check if there are 2 items in cart now
		$this->assertEquals( 2, WC()->cart->get_cart_contents_count() );

		// Set quantity of product in cart to 0
		$this->assertTrue( WC()->cart->set_quantity( $cart_id, 0 ) );

		// Check if there are 0 items in cart now
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test check_cart_item_validity method
	 *
	 * @since 2.3
	 */
	public function test_check_cart_item_validity() {

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Check cart validity, should pass
		$this->assertTrue( WC()->cart->check_cart_item_validity() );

		// Trash product
		wp_trash_post( $product->id );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Re-add item to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Check cart validity, should fail
		$this->assertIsWPError( WC()->cart->check_cart_item_validity() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );

	}

	/**
	 * Test get_total
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
		WC()->cart->add_to_cart( $product->id, 1 );

		// Check
		$this->assertEquals( apply_filters( 'woocommerce_cart_total', wc_price( WC()->cart->total ) ), WC()->cart->get_total() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );
	}

	/**
	 * Test get_total_ex_tax
	 *
	 * @since 2.3
	 */
	public function test_get_total_ex_tax() {

		// Set calc taxes option
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Create dummy product
		$product = WC_Helper_Product::create_simple_product();

		// We need this to have the calculate_totals() method calculate totals
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}

		// Add product to cart
		WC()->cart->add_to_cart( $product->id, 1 );

		// Calc total
		$total = WC()->cart->total - WC()->cart->tax_total - WC()->cart->shipping_tax_total;
		if ( $total < 0 ) {
			$total = 0;
		}

		// Check
		$this->assertEquals( apply_filters( 'woocommerce_cart_total_ex_tax', wc_price( $total ) ), WC()->cart->get_total_ex_tax() );

		// Clean up the cart
		WC()->cart->empty_cart();

		// Clean up product
		WC_Helper_Product::delete_product( $product->id );

		// Restore option
		update_option( 'woocommerce_calc_taxes', 'no' );
	}

}