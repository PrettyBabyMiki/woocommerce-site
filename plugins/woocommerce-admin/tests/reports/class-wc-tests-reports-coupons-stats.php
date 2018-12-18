<?php
/**
 * Reports coupons stats tests.
 *
 * @package WooCommerce\Tests\Coupons-stats
 */

/**
 * Class WC_Tests_Reports_Coupons_Stats
 */
class WC_Tests_Reports_Coupons_Stats extends WC_Unit_Test_Case {

	/**
	 * Test the for the basic cases.
	 */
	public function test_populate_and_query() {
		WC_Helper_Reports::reset_stats_dbs();

		// Simple product.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		// Coupons.
		$coupon_1_amount = 3; // by default in create_coupon.
		$coupon_1        = WC_Helper_Coupon::create_coupon( 'coupon_1' );
		$coupon_1->set_amount( $coupon_1_amount );
		$coupon_1->save();

		$coupon_2_amount = 7;
		$coupon_2        = WC_Helper_Coupon::create_coupon( 'coupon_2' );
		$coupon_2->set_amount( $coupon_2_amount );
		$coupon_2->save();

		// Order without coupon.
		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		// Order with 1 coupon.
		$order_1c = WC_Helper_Order::create_order( 1, $product );
		$order_1c->set_status( 'completed' );
		$order_1c->apply_coupon( $coupon_1 );
		$order_1c->calculate_totals();
		$order_1c->save();

		// Order with 2 coupons.
		$order_2c = WC_Helper_Order::create_order( 1, $product );
		$order_2c->set_status( 'completed' );
		$order_2c->apply_coupon( $coupon_1 );
		$order_2c->apply_coupon( $coupon_2 );
		$order_2c->calculate_totals();
		$order_2c->save();

		$data_store = new WC_Admin_Reports_Coupons_Stats_Data_Store();
		$start_time = date( 'Y-m-d 00:00:00', $order->get_date_created()->getOffsetTimestamp() );
		$end_time   = date( 'Y-m-d 23:59:59', $order->get_date_created()->getOffsetTimestamp() );
		$args       = array(
			'after'    => $start_time,
			'before'   => $end_time,
			'interval' => 'day',
		);

		// Test retrieving the stats through the data store.
		$start_datetime = new DateTime( $start_time );
		$end_datetime   = new DateTime( $end_time );
		$data           = $data_store->get_data( $args );
		$expected_data  = (object) array(
			'total'     => 1,
			'pages'     => 1,
			'page_no'   => 1,
			'totals'    => (object) array(
				'gross_discount' => 2 * $coupon_1_amount + $coupon_2_amount,
				'coupons_count'  => 2,
				'orders_count'   => 2,
			),
			'intervals' => array(
				array(
					'interval'       => $start_datetime->format( 'Y-m-d' ),
					'date_start'     => $start_datetime->format( 'Y-m-d H:i:s' ),
					'date_start_gmt' => $start_datetime->format( 'Y-m-d H:i:s' ),
					'date_end'       => $end_datetime->format( 'Y-m-d H:i:s' ),
					'date_end_gmt'   => $end_datetime->format( 'Y-m-d H:i:s' ),
					'subtotals'      => (object) array(
						'gross_discount' => 2 * $coupon_1_amount + $coupon_2_amount,
						'coupons_count'  => 2,
						'orders_count'   => 2,
					),
				),
			),
		);
		$this->assertEquals( $expected_data, $data );

		// Test retrieving the stats through the query class.
		$query = new WC_Admin_Reports_Coupons_Stats_Query( $args );
		$this->assertEquals( $expected_data, $query->get_data() );
	}
}
