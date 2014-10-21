<?php

class WC_Tests_Cart extends WC_Unit_Test_Case {

	/**
	 * Helper method to get the checkout URL
	 *
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

	public function test_get_cart_url() {
	}


}