<?php
/**
 * Data REST API Test
 *
 * @package WooCommerce Admin\Tests\API
 */

namespace WooCommerce\RestApi\UnitTests\Tests\Version4;

defined( 'ABSPATH' ) || exit;

/**
 * WC Tests API Data
 */
class Data {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v4/data';

	/**
	 * Test that the list of data endpoints includes download-ips.
	 */
	public function test_get_items_contains_download_ips() {
		$request  = new \WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 4, count( $data ) );
		$this->assertEquals( 'download-ips', $data[3]['slug'] );
	}

	/**
	 * Test download-ips match searching.
	 */
	public function test_download_ips() {
		$prod_download = new \WC_Product_Download();
		$prod_download->set_file( plugin_dir_url( __FILE__ ) . '/assets/images/help.png' );
		$prod_download->set_id( 1 );

		$product = new \WC_Product_Simple();
		$product->set_name( 'Test Product' );
		$product->set_downloadable( 'yes' );
		$product->set_downloads( array( $prod_download ) );
		$product->set_regular_price( 25 );
		$product->save();

		$order = \WooCommerce\RestApi\UnitTests\Helpers\OrderHelper::create_order( 1, $product );
		$order->set_status( 'completed' );
		$order->set_total( 100 );
		$order->save();

		$download = new \WC_Customer_Download();
		$download->set_user_id( $this->user );
		$download->set_order_id( $order->get_id() );
		$download->set_product_id( $product->get_id() );
		$download->set_download_id( $prod_download->get_id() );
		$download->save();

		$object = new \WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( $this->user );
		$object->set_user_ip_address( '1.2.3.4' );
		$id = $object->save();

		$object = new \WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( $this->user );
		$object->set_user_ip_address( '54.2.1.3' );
		$id = $object->save();

		// Save a second log for the same IP -- only one result for this IP should be returned.
		$object = new \WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( $this->user );
		$object->set_user_ip_address( '54.2.1.3' );
		$id = $object->save();

		$object = new \WC_Customer_Download_Log();
		$object->set_permission_id( $download->get_id() );
		$object->set_user_id( $this->user );
		$object->set_user_ip_address( '54.5.1.7' );
		$id = $object->save();

		$request = new \WP_REST_Request( 'GET', $this->endpoint . '/download-ips' );
		$request->set_query_params(
			array(
				'match' => '54',
			)
		);

		$response  = $this->server->dispatch( $request );
		$addresses = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $addresses ) );

		$this->assertEquals( '54.2.1.3', $addresses[0]['user_ip_address'] );
		$this->assertEquals( '54.5.1.7', $addresses[1]['user_ip_address'] );
	}
}
