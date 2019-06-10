<?php
/**
 * Reports Orders REST API Test
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */

namespace WooCommerce\RestApi\UnitTests\Tests\Version4\Reports;

defined( 'ABSPATH' ) || exit;

use \WooCommerce\RestApi\UnitTests\AbstractReportsTest;
use \WP_REST_Request;
use \WooCommerce\RestApi\UnitTests\Helpers\OrderHelper;
use \WooCommerce\RestApi\UnitTests\Helpers\QueueHelper;
use \WooCommerce\RestApi\UnitTests\Helpers\CustomerHelper;

/**
 * Reports Orders REST API Test Class
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */
class Orders extends AbstractReportsTest {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v4/reports/orders';

	/**
	 * Test route registration.
	 *
	 * @since 3.5.0
	 */
	public function test_register_routes() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( $this->endpoint, $routes );
	}

	/**
	 * Test getting reports.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports() {
		// Populate all of the data.
		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_regular_price( 25 );
		$product->save();

		$order = OrderHelper::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 ); // $25 x 4.
		$order->save();

		QueueHelper::run_all_pending();

		$expected_customer_id = \WC_Admin_Reports_Customers_Data_Store::get_customer_id_by_user_id( 1 );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$reports  = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 1, count( $reports ) );

		$order_report = reset( $reports );

		$this->assertEquals( $order->get_id(), $order_report['order_id'] );
		$this->assertEquals( $order->get_order_number(), $order_report['order_number'] );
		$this->assertEquals( date( 'Y-m-d H:i:s', $order->get_date_created()->getTimestamp() ), $order_report['date_created'] );
		$this->assertEquals( $expected_customer_id, $order_report['customer_id'] );
		$this->assertEquals( 4, $order_report['num_items_sold'] );
		$this->assertEquals( 90.0, $order_report['net_total'] ); // 25 x 4 - 10 (shipping)
		$this->assertEquals( 'new', $order_report['customer_type'] );
		$this->assertArrayHasKey( '_links', $order_report );
		$this->assertArrayHasKey( 'order', $order_report['_links'] );
	}

	/**
	 * Test getting reports without valid permissions.
	 *
	 * @since 3.5.0
	 */
	public function test_get_reports_without_permission() {
		wp_set_current_user( 0 );
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test reports schema.
	 *
	 * @since 3.5.0
	 */
	public function test_reports_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 9, count( $properties ) );
		$this->assertArrayHasKey( 'order_id', $properties );
		$this->assertArrayHasKey( 'order_number', $properties );
		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'customer_id', $properties );
		$this->assertArrayHasKey( 'net_total', $properties );
		$this->assertArrayHasKey( 'num_items_sold', $properties );
		$this->assertArrayHasKey( 'customer_type', $properties );
		$this->assertArrayHasKey( 'extended_info', $properties );
	}
}
