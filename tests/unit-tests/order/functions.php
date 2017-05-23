<?php

/**
 * Class Functions.
 * @package WooCommerce\Tests\Order
 * @since 2.3
 */
class WC_Tests_Order_Functions extends WC_Unit_Test_Case {

	/**
	 * Test wc_get_order_statuses().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_order_statuses() {

		$order_statuses = apply_filters( 'wc_order_statuses', array(
			'wc-pending'    => _x( 'Pending payment', 'Order status', 'woocommerce' ),
			'wc-processing' => _x( 'Processing', 'Order status', 'woocommerce' ),
			'wc-on-hold'    => _x( 'On hold', 'Order status', 'woocommerce' ),
			'wc-completed'  => _x( 'Completed', 'Order status', 'woocommerce' ),
			'wc-cancelled'  => _x( 'Cancelled', 'Order status', 'woocommerce' ),
			'wc-refunded'   => _x( 'Refunded', 'Order status', 'woocommerce' ),
			'wc-failed'     => _x( 'Failed', 'Order status', 'woocommerce' ),
		) );

		$this->assertEquals( $order_statuses, wc_get_order_statuses() );
	}

	/**
	 * Test wc_is_order_status().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_is_order_status() {
		$this->assertTrue( wc_is_order_status( 'wc-pending' ) );
		$this->assertFalse( wc_is_order_status( 'wc-another-status' ) );
	}

	/**
	 * Test wc_get_order_status_name().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_get_order_status_name() {

		$this->assertEquals( _x( 'Pending payment', 'Order status', 'woocommerce' ), wc_get_order_status_name( 'wc-pending' ) );
		$this->assertEquals( _x( 'Pending payment', 'Order status', 'woocommerce' ), wc_get_order_status_name( 'pending' ) );
	}

	/**
	 * Test wc_processing_order_count().
	 * @since 2.4
	 */
	public function test_wc_processing_order_count() {
		$this->assertEquals( 0, wc_processing_order_count() );
	}

	/**
	 * Test wc_orders_count().
	 * @since 2.4
	 */
	public function test_wc_orders_count() {
		foreach ( wc_get_order_statuses() as $status ) {
			$this->assertEquals( 0, wc_orders_count( $status ) );
		}

		// Invalid status returns 0
		$this->assertEquals( 0, wc_orders_count( 'unkown-status' ) );
	}

	/**
	 * Test wc_ship_to_billing_address_only().
	 *
	 * @since 2.3.0
	 */
	public function test_wc_ship_to_billing_address_only() {

		$default = get_option( 'woocommerce_ship_to_destination' );

		update_option( 'woocommerce_ship_to_destination', 'shipping' );
		$this->assertFalse( wc_ship_to_billing_address_only() );

		update_option( 'woocommerce_ship_to_destination', 'billing_only' );
		$this->assertTrue( wc_ship_to_billing_address_only() );

		update_option( 'woocommerce_ship_to_destination', $default );
	}

	/**
	 * Test wc_get_order().
	 *
	 * @since 2.4.0
	 * @group test
	 */
	public function test_wc_get_order() {

		$order = WC_Helper_Order::create_order();

		// Assert that $order is a WC_Order object
		$this->assertInstanceOf( 'WC_Order', $order );

		// Assert that wc_get_order() accepts a WC_Order object
		$this->assertInstanceOf( 'WC_Order', wc_get_order( $order ) );

		// Assert that wc_get_order() accepts a order post id.
		$this->assertInstanceOf( 'WC_Order', wc_get_order( $order->get_id() ) );

		// Assert that a non-shop_order post returns false
		$post = $this->factory->post->create_and_get( array( 'post_type' => 'post' ) );
		$this->assertFalse( wc_get_order( $post->ID ) );

		// Assert the return when $the_order args is false
		$this->assertFalse( wc_get_order( false ) );

		// Assert the return when $the_order args is a random (incorrect) id.
		$this->assertFalse( wc_get_order( 123456 ) );
	}

	/**
	 * Test getting an orders payment tokens
	 *
	 * @since 2.6
	 */
	public function test_wc_order_get_payment_tokens() {
		$order = WC_Helper_Order::create_order();
		$this->assertEmpty( $order->get_payment_tokens() );

		$token = WC_Helper_Payment_Token::create_cc_token();
		update_post_meta( $order->get_id(), '_payment_tokens', array( $token->get_id() ) );

		$this->assertCount( 1, $order->get_payment_tokens() );
	}


	/**
	 * Test adding a payment token to an order
	 *
	 * @since 2.6
	 */
	public function test_wc_order_add_payment_token() {
		$order = WC_Helper_Order::create_order();
		$this->assertEmpty( $order->get_payment_tokens() );

		$token = WC_Helper_Payment_Token::create_cc_token();
		$order->add_payment_token( $token );

		$this->assertCount( 1, $order->get_payment_tokens() );
	}

	/**
	 * Test the before and after date parameters for wc_get_orders.
	 *
	 * @since 3.0
	 */
	public function test_wc_get_orders_customer_params() {
		$order1  = WC_Helper_Order::create_order( 0 );
		$order2  = WC_Helper_Order::create_order( 0 );
		$order3  = WC_Helper_Order::create_order( 1 );
		$order4  = WC_Helper_Order::create_order( 1 );
		$order_1 = $order1->get_id();
		$order_2 = $order2->get_id();
		$order_3 = $order3->get_id();
		$order_4 = $order4->get_id();

		$orders   = wc_get_orders( array( 'customer' => 0, 'return' => 'ids' ) );
		$expected = array( $order_1, $order_2 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders( array( 'customer' => 1, 'return' => 'ids' ) );
		$expected = array( $order_3, $order_4 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders( array( 'customer' => '', 'return' => 'ids' ) );
		$expected = array( $order_1, $order_2, $order_3, $order_4 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$order1->delete();
		$order2->delete();
		$order3->delete();
		$order4->delete();
	}

	/**
	 * Test the before and after date parameters for wc_get_orders.
	 *
	 * @since 3.0
	 */
	public function test_wc_get_orders_date_params() {
		$order1 = WC_Helper_Order::create_order();
		$order1->set_date_created( '2015-01-01 05:20:30' );
		$order1->save();
		$order_1 = $order1->get_id();
		$order2   = WC_Helper_Order::create_order();
		$order2->set_date_created( '2017-01-01' );
		$order2->save();
		$order_2 = $order2->get_id();
		$order3   = WC_Helper_Order::create_order();
		$order3->set_date_created( '2017-01-01' );
		$order3->save();
		$order_3 = $order3->get_id();

		$orders   = wc_get_orders( array( 'date_before' => '2017-01-15', 'return' => 'ids' ) );
		$expected = array( $order_1, $order_2, $order_3 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders( array( 'date_before' => '2017-01-01', 'return' => 'ids' ) );
		$expected = array( $order_1 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders( array( 'date_before' => '2016-12-31', 'return' => 'ids' ) );
		$expected = array( $order_1 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders( array( 'date_after' => '2015-01-01 00:00:00', 'return' => 'ids' ) );
		$expected = array( $order_1, $order_2, $order_3 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders( array( 'date_after' => '2016-01-01', 'return' => 'ids' ) );
		$expected = array( $order_2, $order_3 );
		sort( $expected );
		sort( $orders );
		$this->assertEquals( $expected, $orders );

		$order1->delete();
		$order2->delete();
		$order3->delete();
	}

	public function test_wc_get_order_status_param() {
		$order1 = WC_Helper_Order::create_order();
		$order1->set_status( 'pending' );
		$order1->save();
		$order2 = WC_Helper_Order::create_order();
		$order2->set_status( 'completed' );
		$order2->save();

		$orders   = wc_get_orders( array( 'status' => 'wc-pending', 'return' => 'ids' ) );
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders   = wc_get_orders( array( 'status' => 'wc-completed', 'return' => 'ids' ) );
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	public function test_wc_get_order_type_param() {
		$order = WC_Helper_Order::create_order();
		$order->save();
		$refund = new WC_Order_Refund();
		$refund->save();

		$orders = wc_get_orders( array( 'type' => 'shop_order_refund', 'return' => 'ids' ) );
		$expected = array( $refund->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders = wc_get_orders( array( 'type' => 'shop_order', 'return' => 'ids' ) );
		$expected = array( $order->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	public function test_wc_get_order_version_param() {
		$order  = WC_Helper_Order::create_order();
		$order->save();

		$orders = wc_get_orders( array( 'version' => WC_VERSION, 'return' => 'ids' ) );
		$expected = array( $order->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	public function test_wc_get_order_created_via_param() {
		$order1  = WC_Helper_Order::create_order();
		$order1->set_created_via( 'rest-api' );
		$order1->save();
		$order2  = WC_Helper_Order::create_order();
		$order2->set_created_via( 'checkout' );
		$order2->save();

		$orders = wc_get_orders( array( 'created_via' => 'rest-api', 'return' => 'ids' ) );
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders = wc_get_orders( array( 'created_via' => 'checkout', 'return' => 'ids' ) );
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	public function test_wc_get_order_parent_param() {
		$parent  = WC_Helper_Order::create_order();
		$parent->save();

		$order1  = WC_Helper_Order::create_order();
		$order1->set_parent_id( $parent->get_id() );
		$order1->save();
		$order2  = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders( array( 'parent' => $parent->get_id(), 'return' => 'ids' ) );
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	public function test_wc_get_order_parent_exclude_param() {
		$parent  = WC_Helper_Order::create_order();
		$parent->save();

		$order1  = WC_Helper_Order::create_order();
		$order1->set_parent_id( $parent->get_id() );
		$order1->save();
		$order2  = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders( array( 'parent_exclude' => array( $parent->get_id() ), 'return' => 'ids' ) );
		sort( $orders );
		$expected = array( $parent->get_id(), $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	public function test_wc_get_order_exclude_param() {
		$order1  = WC_Helper_Order::create_order();
		$order1->save();
		$order2  = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders( array( 'exclude' => array( $order1->get_id() ), 'return' => 'ids' ) );
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	public function test_wc_get_order_limit_param() {
		$order1  = WC_Helper_Order::create_order();
		$order1->save();
		$order2  = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders( array( 'limit' => 1 ) );
		$this->assertEquals( 1, count( $orders ) );
	}

	public function test_wc_get_order_paged_param() {
		$order1  = WC_Helper_Order::create_order();
		$order1->save();
		$order2  = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders( array( 'paged' => 1, 'limit' => 1, 'return' => 'ids' ) );
		$expected = array( $order1->get_id() );
		$this->assertEquals( $expected, $orders );

		$orders = wc_get_orders( array( 'paged' => 2, 'limit' => 1, 'return' => 'ids' ) );
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	public function test_wc_get_order_offset_param() {
		$order1  = WC_Helper_Order::create_order();
		$order1->save();
		$order2  = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders( array( 'offset' => 1, 'return' => 'ids' ) );
		$expected = array( $order2->get_id() );
		$this->assertEquals( $expected, $orders );
	}

	public function test_wc_get_order_paginate_param() {
		$order1  = WC_Helper_Order::create_order();
		$order1->save();
		$order2  = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders( array( 'paginate' => true ) );
		$this->assertEquals( 2, $orders->total );
		$this->assertEquals( 2, count( $orders->orders ) );
		$this->assertEquals( 1, $orders->max_num_pages );
	}

	public function test_wc_get_order_order_param() {
		$order1  = WC_Helper_Order::create_order();
		$order1->save();
		$order2  = WC_Helper_Order::create_order();
		$order2->save();

		$orders = wc_get_orders( array( 'orderby' => 'id', 'return' => 'ids' ) );
		$expected = array( $order2->get_id(), $order1->get_id() );

		$orders = wc_get_orders( array( 'orderby' => 'id', 'order' => 'ASC', 'return' => 'ids' ) );
		$expected = array( $order1->get_id(), $order2->get_id() );
	}

	public function test_wc_get_order_currency_param() {
		$order1  = WC_Helper_Order::create_order();
		$order1->set_currency( 'BRL' );
		$order1->save();
		$order2  = WC_Helper_Order::create_order();
		$order2->set_currency( 'USD' );
		$order2->save();

		$orders = wc_get_orders( array( 'currency' => 'BRL', 'return' => 'ids' ) );
		$expected = array( $order1->get_id() );

		$orders = wc_get_orders( array( 'currency' => 'USD', 'return' => 'ids' ) );
		$expected = array( $order2->get_id() );
	}
}
