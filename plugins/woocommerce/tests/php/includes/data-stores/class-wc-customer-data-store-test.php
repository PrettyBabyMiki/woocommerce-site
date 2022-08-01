<?php

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Class WC_Customer_Data_Store_CPT_Test.
 */
class WC_Customer_Data_Store_CPT_Test extends WC_Unit_Test_Case {

	/**
	 * Runs before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		OrderHelper::create_order_custom_table_if_not_exist();
	}

	/**
	 * Test that metadata cannot overwrite customer's column data.
	 *
	 * @link https://github.com/woocommerce/woocommerce/issues/28100
	 */
	public function test_meta_data_cannot_overwrite_column_data() {
		$customer    = WC_Helper_Customer::create_customer();
		$customer_id = $customer->get_id();
		$username    = $customer->get_username();
		$customer->add_meta_data( 'id', '99999' );
		$customer->add_meta_data( 'username', 'abcde' );
		$customer->save();

		$customer_datastore = new WC_Customer_Data_Store();
		$customer_datastore->read( $customer );
		$this->assertEquals( $customer_id, $customer->get_id() );
		$this->assertEquals( $username, $customer->get_username() );
	}

	/**
	 * Handler for the wc_order_statuses filter, returns just 'pending" as the valid order statuses list.
	 *
	 * @return string[]
	 */
	public function get_pending_only_as_order_statuses() {
		return array( 'wc-pending' => 'pending' );
	}

	/**
	 * @testdox 'get_last_order' works when the posts table is used for storing orders.
	 */
	public function test_get_last_customer_order_not_using_cot() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );
		WC_Helper_Order::create_order( $customer_1->get_id() );
		$last_valid_order_of_1 = WC_Helper_Order::create_order( $customer_1->get_id() );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => 'completed' ) );
		WC_Helper_Order::create_order( $customer_2->get_id() );
		WC_Helper_Order::create_order( $customer_2->get_id() );

		$sut = new WC_Customer_Data_Store();
		add_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10, 0 );
		$actual_order = $sut->get_last_order( $customer_1 );
		remove_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10 );

		$this->assertEquals( $last_valid_order_of_1->get_id(), $actual_order->get_id() );
	}

	/**
	 * @testdox 'get_last_order' works when the custom orders table is used for storing orders.
	 */
	public function test_get_last_customer_order_using_cot() {
		global $wpdb;

		$customer_1       = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2       = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );
		$last_valid_order = WC_Helper_Order::create_order( $customer_1->get_id() );

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );

		$sql =
			'INSERT INTO ' . OrdersTableDataStore::get_orders_table_name() . "
			( id, customer_id, status )
			VALUES
			( 1, %d, 'wc-completed' ), ( %d, %d, 'wc-completed' ), ( 3, %d, 'wc-invalid-status' ),
			( 4, %d, 'wc-completed' ), ( 5, %d, 'wc-completed' )";

		$customer_1_id = $customer_1->get_id();
		$customer_2_id = $customer_2->get_id();
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( $sql, $customer_1_id, $last_valid_order->get_id(), $customer_1_id, $customer_1_id, $customer_2_id, $customer_2_id );
		$wpdb->query( $query );
		//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$sut          = new WC_Customer_Data_Store();
		$actual_order = $sut->get_last_order( $customer_1 );

		$this->assertEquals( $last_valid_order->get_id(), $actual_order->get_id() );
	}

	/**
	 * @testdox 'get_order_count' works when the posts table is used for storing orders.
	 */
	public function test_order_count_not_using_cot() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );
		WC_Helper_Order::create_order( $customer_1->get_id() );
		WC_Helper_Order::create_order( $customer_1->get_id() );
		WC_Helper_Order::create_order( $customer_1->get_id() );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => 'completed' ) );
		WC_Helper_Order::create_order( $customer_2->get_id() );
		WC_Helper_Order::create_order( $customer_2->get_id() );

		$sut = new WC_Customer_Data_Store();
		add_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10, 0 );
		$actual_count = $sut->get_order_count( $customer_1 );
		remove_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10 );

		$this->assertEquals( 3, $actual_count );
	}

	/**
	 * @testdox 'get_order_count' works when the custom orders table is used for storing orders.
	 */
	public function test_get_order_count_using_cot() {
		global $wpdb;

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );

		$sql =
			'INSERT INTO ' . OrdersTableDataStore::get_orders_table_name() . "
			( id, customer_id, status )
			VALUES
			( 1, %d, 'wc-completed' ), ( 2, %d, 'wc-completed' ), ( 3, %d, 'wc-completed' ), ( 4, %d, 'wc-invalid-status' ),
			( 5, %d, 'wc-completed' ), ( 6, %d, 'wc-completed' )";

		$customer_1_id = $customer_1->get_id();
		$customer_2_id = $customer_2->get_id();
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( $sql, $customer_1_id, $customer_1_id, $customer_1_id, $customer_1_id, $customer_2_id, $customer_2_id );
		$wpdb->query( $query );
		//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$sut          = new WC_Customer_Data_Store();
		$actual_count = $sut->get_order_count( $customer_1 );

		$this->assertEquals( 3, $actual_count );
	}

	/**
	 * @testdox 'get_total_spent' works when the posts table is used for storing orders.
	 */
	public function test_get_total_spent_not_using_cot() {
		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'no' );

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => 'completed' ) );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => 'completed' ) );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => 'completed' ) );
		WC_Helper_Order::create_order( $customer_1->get_id(), null, array( 'status' => 'pending' ) );
		WC_Helper_Order::create_order( $customer_2->get_id() );
		WC_Helper_Order::create_order( $customer_2->get_id() );

		$sut = new WC_Customer_Data_Store();
		add_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10, 0 );
		$actual_amount = $sut->get_total_spent( $customer_1 );
		remove_filter( 'wc_order_statuses', array( $this, 'get_pending_only_as_order_statuses' ), 10 );

		// Each order created by WC_Helper_Order::create_order has a total amount of 50.
		$this->assertEquals( '150.00', $actual_amount );
	}

	/**
	 * @testdox 'get_total_spent' works when the custom orders table is used for storing orders.
	 */
	public function test_get_total_spent_using_cot() {
		global $wpdb;

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, 'yes' );

		$customer_1 = WC_Helper_Customer::create_customer( 'test1', 'pass1', 'test1@example.com' );
		$customer_2 = WC_Helper_Customer::create_customer( 'test2', 'pass2', 'test2@example.com' );

		$sql =
			'INSERT INTO ' . OrdersTableDataStore::get_orders_table_name() . "
			( id, customer_id, status, total_amount )
			VALUES
			( 1, %d, 'wc-completed', 10 ), ( 2, %d, 'wc-completed', 20 ), ( 3, %d, 'wc-completed', 30 ), ( 4, %d, 'wc-invalid-status', 40 ),
			( 5, %d, 'wc-completed', 200 ), ( 6, %d, 'wc-completed', 300 )";

		$customer_1_id = $customer_1->get_id();
		$customer_2_id = $customer_2->get_id();
		//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$query = $wpdb->prepare( $sql, $customer_1_id, $customer_1_id, $customer_1_id, $customer_1_id, $customer_2_id, $customer_2_id );
		$wpdb->query( $query );
		//phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		$sut          = new WC_Customer_Data_Store();
		$actual_spent = $sut->get_total_spent( $customer_1 );

		$this->assertEquals( '60.00', $actual_spent );
	}
}
