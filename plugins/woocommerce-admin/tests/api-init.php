<?php
/**
 * REST API Init Class Test
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

use Automattic\WooCommerce\Admin\ReportsSync;
use \Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore as OrdersStatsDataStore;

/**
 * Class WC_Tests_API_Init
 */
class WC_Tests_API_Init extends WC_REST_Unit_Test_Case {
	/**
	 * Set up.
	 */
	public function setUp() {
		parent::setUp();
		$this->queue = new WC_Admin_Test_Action_Queue();
		ReportsSync::set_queue( $this->queue );
	}

	/**
	 * Tear down.
	 */
	public function tearDown() {
		parent::tearDown();
		ReportsSync::set_queue( null );
		$this->queue->actions = array();
	}

	/**
	 * Cause a failure when updating order stats for the test order, without deleting it.
	 *
	 * @param string $query Query.
	 * @return string
	 */
	public function filter_order_query( $query ) {
		global $wpdb;

		if (
			0 === strpos( $query, 'REPLACE INTO' ) &&
			false !== strpos( $query, OrdersStatsDataStore::get_db_table_name() )
		) {
			remove_filter( 'query', array( $this, 'filter_order_query' ) );
			return "DESCRIBE $wpdb->posts"; // Execute any random query.
		}

		return $query;
	}

	/**
	 * Test that a retry job is scheduled for a failed sync.
	 *
	 * @return void
	 */
	public function test_order_sync_retries_on_failure() {
		// Create a test Order.
		$product = new WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = WC_Helper_Order::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		// Clear the existing action queue (the above save adds an action).
		$this->queue->actions = array();

		// Force a failure by sabotaging the query run after retreiving order coupons.
		add_filter( 'query', array( $this, 'filter_order_query' ) );

		// Initiate sync.
		ReportsSync::orders_lookup_import_order( $order->get_id() );

		// Verify that a retry job was scheduled.
		$this->assertCount( 1, $this->queue->actions );
		$this->assertArraySubset(
			array(
				'hook' => ReportsSync::SINGLE_ORDER_IMPORT_ACTION,
				'args' => array( $order->get_id() ),
			),
			$this->queue->actions[0]
		);
	}
}
