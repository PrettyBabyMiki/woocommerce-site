<?php
/**
 * Unit tests for the WC_Cart_Test class.
 *
 * @package WooCommerce\Tests\Cart.
 */

/**
 * Class WC_Cart_Test
 */
class WC_Cart_Test extends \WC_Unit_Test_Case {

	/**
	 * tearDown.
	 */
	public function tearDown() {
		parent::tearDown();

		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		WC()->session->set( 'wc_notices', null );
	}

	/**
	 * @testdox should throw a notice to the cart if an "any" attribute is empty.
	 */
	public function test_add_variation_to_the_cart_with_empty_attributes() {
		WC()->cart->empty_cart();
		WC()->session->set( 'wc_notices', null );

		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();

		// Get a variation with small pa_size and any pa_colour and pa_number.
		$variation = $variations[0];

		// Add variation using parent id.
		WC()->cart->add_to_cart(
			$variation['variation_id'],
			1,
			0,
			array(
				'attribute_pa_colour' => '',
				'attribute_pa_number' => '',
			)
		);
		$notices = WC()->session->get( 'wc_notices', array() );

		// Check that the second add to cart call increases the quantity of the existing cart-item.
		$this->assertCount( 0, WC()->cart->get_cart_contents() );
		$this->assertEquals( 0, WC()->cart->get_cart_contents_count() );

		// Check that the notices contain an error message about invalid colour and number.
		$this->assertArrayHasKey( 'error', $notices );
		$this->assertCount( 1, $notices['error'] );
		$this->assertEquals( 'colour and number are required fields', $notices['error'][0]['notice'] );

		// Reset cart.
		WC()->cart->empty_cart();
		WC()->customer->set_is_vat_exempt( false );
		$product->delete( true );
	}

	/**
	 * Test show shipping.
	 */
	public function test_show_shipping() {
		// Test with an empty cart.
		$this->assertFalse( WC()->cart->show_shipping() );

		// Add a product to the cart.
		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Test with "woocommerce_ship_to_countries" disabled.
		$default_ship_to_countries = get_option( 'woocommerce_ship_to_countries', '' );
		update_option( 'woocommerce_ship_to_countries', 'disabled' );
		$this->assertFalse( WC()->cart->show_shipping() );

		// Test with default "woocommerce_ship_to_countries" and "woocommerce_shipping_cost_requires_address".
		update_option( 'woocommerce_ship_to_countries', $default_ship_to_countries );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Test with "woocommerce_shipping_cost_requires_address" enabled.
		$default_shipping_cost_requires_address = get_option( 'woocommerce_shipping_cost_requires_address', 'no' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );
		$this->assertFalse( WC()->cart->show_shipping() );

		// Set address for shipping calculation required for "woocommerce_shipping_cost_requires_address".
		WC()->cart->get_customer()->set_shipping_country( 'US' );
		WC()->cart->get_customer()->set_shipping_state( 'NY' );
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Reset.
		update_option( 'woocommerce_shipping_cost_requires_address', $default_shipping_cost_requires_address );
		$product->delete( true );
		WC()->cart->get_customer()->set_shipping_country( 'GB' );
		WC()->cart->get_customer()->set_shipping_state( '' );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
	}

	/**
	 * Test show_shipping for countries with various state/postcode requirement.
	 */
	public function test_show_shipping_for_countries_different_shipping_requirements() {
		$default_shipping_cost_requires_address = get_option( 'woocommerce_shipping_cost_requires_address', 'no' );
		update_option( 'woocommerce_shipping_cost_requires_address', 'yes' );

		WC()->cart->empty_cart();
		$this->assertFalse( WC()->cart->show_shipping() );

		$product = WC_Helper_Product::create_simple_product();
		WC()->cart->add_to_cart( $product->get_id(), 1 );

		// Country that does not require state.
		WC()->cart->get_customer()->set_shipping_country( 'LB' );
		WC()->cart->get_customer()->set_shipping_state( '' );
		WC()->cart->get_customer()->set_shipping_postcode( '12345' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Country that does not require postcode.
		WC()->cart->get_customer()->set_shipping_country( 'NG' );
		WC()->cart->get_customer()->set_shipping_state( 'AB' );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
		$this->assertTrue( WC()->cart->show_shipping() );

		// Reset.
		update_option( 'woocommerce_shipping_cost_requires_address', $default_shipping_cost_requires_address );
		$product->delete( true );
		WC()->cart->get_customer()->set_shipping_country( 'GB' );
		WC()->cart->get_customer()->set_shipping_state( '' );
		WC()->cart->get_customer()->set_shipping_postcode( '' );
	}
}
