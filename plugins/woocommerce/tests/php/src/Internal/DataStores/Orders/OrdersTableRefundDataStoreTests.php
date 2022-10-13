<?php
/**
 * File containing the class WP_Test_WC_Order_Refund.
 */

use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableRefundDataStore;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

/**
 * Class OrdersTableRefundDataStoreTests.
 */
class OrdersTableRefundDataStoreTests extends WC_Unit_Test_Case {

	/**
	 * @var PostsToOrdersMigrationController
	 */
	private $migrator;

	/**
	 * @var OrdersTableRefundDataStore
	 */
	private $sut;

	/**
	 * @var OrdersTableDataStore;
	 */
	private $order_data_store;

	/**
	 * @var WC_Order_Refund_Data_Store_CPT
	 */
	private $cpt_data_store;

	/**
	 * Initializes system under test.
	 */
	public function setUp(): void {
		parent::setUp();
		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		OrderHelper::delete_order_custom_tables(); // We need this since non-temporary tables won't drop automatically.
		OrderHelper::create_order_custom_table_if_not_exist();
		$this->sut              = wc_get_container()->get( OrdersTableRefundDataStore::class );
		$this->order_data_store = wc_get_container()->get( OrdersTableDataStore::class );
		$this->migrator         = wc_get_container()->get( PostsToOrdersMigrationController::class );
		$this->cpt_data_store   = new WC_Order_Refund_Data_Store_CPT();
	}

	/**
	 * Test that we are able to create refund.
	 */
	public function test_create_refund() {
		$order  = OrderHelper::create_complex_data_store_order( $this->order_data_store );
		$refund = new WC_Order_Refund();
		OrderHelper::switch_data_store( $refund, $this->sut );
		$refund->set_amount( 10 );
		$refund->set_refunded_by( get_current_user_id() );
		$refund->set_reason( 'Test' );
		$refund->set_parent_id( $order->get_id() );
		$this->sut->create( $refund );

		$this->assertNotEquals( 0, $refund->get_id() );
		// Read from DB.
		$refreshed_refund = new WC_Order_Refund();
		OrderHelper::switch_data_store( $refreshed_refund, $this->sut );
		$refreshed_refund->set_id( $refund->get_id() );
		$this->sut->read( $refreshed_refund );
		$this->assertEquals( $refund->get_id(), $refreshed_refund->get_id() );
		$this->assertEquals( 10, $refreshed_refund->get_amount() );
		$this->assertEquals( 'Test', $refreshed_refund->get_reason() );
	}

}
