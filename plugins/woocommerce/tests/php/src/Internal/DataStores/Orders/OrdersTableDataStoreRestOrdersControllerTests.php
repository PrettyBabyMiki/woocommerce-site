<?php
use Automattic\WooCommerce\Database\Migrations\CustomOrderTable\PostsToOrdersMigrationController;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;

if ( ! class_exists( 'WC_REST_Orders_Controller_Tests' ) ) {
	require_once dirname( __FILE__, 5 ) . '/includes/rest-api/Controllers/Version3/class-wc-rest-orders-controller-tests.php';
}

/**
 * Class OrdersTableDataStoreRestOrdersControllerTests.
 *
 * Test for REST support in the OrdersTableDataStore class.
 */
class OrdersTableDataStoreRestOrdersControllerTests extends \WC_REST_Orders_Controller_Tests {

	/**
	 * Initializes system under test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );
		OrderHelper::delete_order_custom_tables();
		OrderHelper::create_order_custom_table_if_not_exist();

		$this->toggle_cot( true );
	}

	/**
	 * Destroys system under test.
	 */
	public function tearDown(): void {
		$this->toggle_cot( false );

		// Add back removed filter.
		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		parent::tearDown();
	}

	/**
	 * Specifically tests that CPT and COT return the same REST response for an order.
	 */
	public function test_orders_cpt() {
		wp_set_current_user( $this->user );

		$this->toggle_cot( false );
		$post_order_id = OrderHelper::create_complex_wp_post_order();
		( wc_get_container()->get( PostsToOrdersMigrationController::class ) )->migrate_orders( array( $post_order_id ) );

		$request = new \WP_REST_Request( 'GET', '/wc/v3/orders/' . $post_order_id );

		$response_cpt = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response_cpt->get_status() );
		$response_cpt_data = $response_cpt->get_data();

		// Re-enable COT.
		$this->toggle_cot( true );

		$response_cot = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response_cot->get_status() );
		$response_cot_data = $response_cot->get_data();

		$this->assertEmpty( array_diff_key( $response_cpt_data, $response_cot_data ) );
		$this->assertEmpty( array_diff_key( $response_cot_data, $response_cpt_data ) );
		$this->assertEquals( count( $response_cpt_data['meta_data'] ), count( $response_cot_data['meta_data'] ) );
		$this->assertTrue( $response_cpt_data == $response_cot_data ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
	}

	/**
	 * Enables or disables the custom orders table across WP temporarily.
	 *
	 * @param boolean $enabled TRUE to enable COT or FALSE to disable.
	 * @return void
	 */
	private function toggle_cot( bool $enabled ): void {
		$features_controller = wc_get_container()->get( Featurescontroller::class );
		$features_controller->change_feature_enable( 'custom_order_tables', true );

		update_option( CustomOrdersTableController::CUSTOM_ORDERS_TABLE_USAGE_ENABLED_OPTION, wc_bool_to_string( $enabled ) );

		// Confirm things are really correct.
		$wc_data_store = WC_Data_Store::load( 'order' );
		assert( is_a( $wc_data_store->get_current_class_name(), OrdersTableDataStore::class, true ) === $enabled );
	}

}
