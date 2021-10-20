<?php
/**
 * Class WC_AJAX_Test file.
 *
 * @package WooCommerce\Tests\WC_AJAX.
 */

/**
 * Class WC_AJAX_Test file.
 */
class WC_AJAX_Test extends \WP_Ajax_UnitTestCase {

	/**
	 * Stock should not be reduced from AJAX when an item is added to an order.
	 */
	public function test_add_item_to_pending_payment_order() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1000 );
		$product->save();

		$order = WC_Helper_Order::create_order();

		$data = array(
			array(
				'id'  => $product->get_id(),
				'qty' => 10,
			),
		);
		// Call private method `maybe_add_order_item`.
		$maybe_add_order_item_func = function () use ( $order, $data ) {
			return static::maybe_add_order_item( $order->get_id(), '', $data );
		};
		$maybe_add_order_item_func->call( new WC_AJAX() );

		// Refresh from DB.
		$product = wc_get_product( $product->get_id() );

		// Stock should not have been reduced because order status is 'pending'.
		$this->assertEquals( 1000, $product->get_stock_quantity() );
		$line_items = $order->get_items();
		foreach ( $line_items as $line_item ) {
			if ( $line_item->get_product_id() === $product->get_id() ) {
				$this->assertEquals( false, $line_item->get_meta( '_reduced_stock', true ) );
			}
		}
	}

	/**
	 * Stock should be reduced from AJAX when an item is added to an order, when status is being changed
	 */
	public function test_add_item_to_processing_order() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->set_stock_quantity( 1000 );
		$product->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( 'pending' );
		$order->save();

		$data = array(
			array(
				'id'  => $product->get_id(),
				'qty' => 10,
			),
		);
		// Call private method `maybe_add_order_item`.
		$maybe_add_order_item_func = function () use ( $order, $data ) {
			return static::maybe_add_order_item( $order->get_id(), '', $data );
		};
		$maybe_add_order_item_func->call( new WC_AJAX() );
		$order->set_status( 'processing' );
		$order->save();

		// Refresh from DB.
		$product = wc_get_product( $product->get_id() );

		$this->assertEquals( 990, $product->get_stock_quantity() );
		$line_items = $order->get_items();
		foreach ( $line_items as $line_item ) {
			if ( $line_item->get_product_id() === $product->get_id() ) {
				$this->assertEquals( 10, $line_item->get_meta( '_reduced_stock', true ) );
			}
		}
	}

	/**
	 * Creating an API Key with too long of a description should report failure.
	 */
	public function test_create_api_key_long_description_failure() {
		$this->_setRole( 'administrator' );

		$description  = 'This_description_is_really_very_long_and_is_meant_to_exceed_the_database_column_length_of_200_characters_';
		$description .= $description;

		$_POST['security'] = wp_create_nonce( 'update-api-key' );
		$_POST['key_id']      = 0;
		$_POST['user']        = 1;
		$_POST['permissions'] = 'read';
		$_POST['description'] = $description;

		try {
			$this->_handleAjax( 'woocommerce_update_api_key' );
		} catch ( WPAjaxDieContinueException $e ) {
			// wp_die() doesn't actually occur, so we need to clean up WC_AJAX::update_api_key's output buffer.
			ob_end_clean();
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertFalse( $response['success'] );
		$this->assertEquals( $response['data']['message'], 'There was an error generating your API Key.' );
	}
}
