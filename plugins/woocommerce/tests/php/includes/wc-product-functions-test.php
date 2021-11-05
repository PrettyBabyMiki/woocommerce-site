<?php
/**
 * Unit tests for wc-product-functions.php.
 *
 * @package WooCommerce\Tests\Functions\Stock
 */

use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

/**
 * Class WC_Stock_Functions_Tests.
 */
class WC_Product_Functions_Tests extends \WC_Unit_Test_Case {

	/**
	 * @testdox If 'wc_get_price_excluding_tax' gets an order as argument, it passes the order customer to 'WC_Tax::get_rates'.
	 *
	 * @testWith [true, 1, true]
	 *           [true, 1, false]
	 *           [true, 0, true]
	 *           [true, 0, false]
	 *           [false, null, true]
	 *           [false, null, false]
	 *
	 * @param bool     $pass_order Whether an order is passed to 'wc_get_price_excluding_tax' or not.
	 * @param int|null $customer_id Id of the customer associated to the order.
	 * @param bool     $set_filter Whether the 'woocommerce_adjust_non_base_location_prices' filter should be set to return false.
	 */
	public function test_wc_get_price_excluding_tax_passes_order_customer_to_get_rates_if_order_is_available( $pass_order, $customer_id, $set_filter ) {
		$customer_passed_to_get_rates                  = false;
		$get_base_rates_invoked                        = false;
		$customer_id_passed_to_wc_customer_constructor = false;

		if ( $set_filter ) {
			add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
		}

		FunctionsMockerHack::add_function_mocks(
			array(
				'wc_prices_include_tax' => '__return_true',
			)
		);

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Tax' =>
				array(
					'get_rates'          => function( $tax_class, $customer ) use ( &$customer_passed_to_get_rates ) {
						$customer_passed_to_get_rates = $customer;
					},
					'get_base_tax_rates' => function( $tax_class ) use ( &$get_base_rates_invoked ) {
						$get_base_rates_invoked = true;
						return 0;
					},
					'calc_tax'           => function( $price, $rates, $price_includes_tax = false, $deprecated = false ) {
						return array( 0 );
					},
				),
			)
		);

		// phpcs:disable Squiz.Commenting

		$product = new class() extends WC_Product {
			public function get_price( $context = 'view' ) {
				return 0;
			}

			public function is_taxable() {
				return true;
			}

			public function get_tax_class( $context = 'view' ) {
				return '';
			}
		};

		$customer = new stdClass();
		$this->register_legacy_proxy_class_mocks(
			array(
				'WC_Customer' => function( $customer_id ) use ( &$customer_id_passed_to_wc_customer_constructor, $customer ) {
					$customer_id_passed_to_wc_customer_constructor = $customer_id;
					return $customer;
				},
			)
		);

		if ( $pass_order ) {
			$order = new class( $customer_id ) {
				private $customer_id;

				public function __construct( $customer_id ) {
					$this->customer_id = $customer_id;
				}

				public function get_customer_id() {
					return $this->customer_id;
				}
			};

			wc_get_price_excluding_tax( $product, array( 'order' => $order ) );

			if ( $customer_id && $set_filter ) {
				$this->assertEquals( $order->get_customer_id(), $customer_id_passed_to_wc_customer_constructor );
				$this->assertFalse( $get_base_rates_invoked );
				$this->assertSame( $customer, $customer_passed_to_get_rates );
			} else {
				$this->assertFalse( $customer_id_passed_to_wc_customer_constructor );
				$this->assertFalse( $customer_passed_to_get_rates );
				$this->assertTrue( $get_base_rates_invoked );
			}
		} else {
			wc_get_price_excluding_tax( $product );

			$this->assertFalse( $customer_id_passed_to_wc_customer_constructor );
			$this->assertFalse( $customer_passed_to_get_rates );
			$this->assertTrue( $get_base_rates_invoked );
		}

		// phpcs:enable Squiz.Commenting

		if ( $set_filter ) {
			remove_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );
		}
	}
}
