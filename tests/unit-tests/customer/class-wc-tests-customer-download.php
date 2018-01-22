<?php
/**
 * WC_Customer_Download tests file.
 *
 * @package WooCommerce\Tests\Customer
 */

/**
 * Class WC_Customer_Download.
 *
 * @since 3.3.0
 * @package WooCommerce\Tests\Customer
 */
class WC_Tests_Customer_Download extends WC_Unit_Test_Case {

	/**
	 * Download object used for testing.
	 *
	 * @var WC_Customer_Download
	 */
	private $download;

	/**
	 * ID of the customer created for the tests.
	 *
	 * @var int
	 */
	private $customer_id;

	/**
	 * Tests set up.
	 */
	public function setUp() {
		$customer = get_user_by( 'login', 'testuser' );

		if ( $customer ) {
			$this->customer_id = $customer->ID;
		} else {
			$this->customer_id = wc_create_new_customer( 'test@example.com', 'testuser', 'testpassword' );
		}

		$this->download = new WC_Customer_Download();
		$this->download->set_user_id( $this->customer_id );
		$this->download->set_user_email( 'test@example.com' );
		$this->download->set_order_id( 1 );
		$this->download->save();
	}

	/**
	 * Test WC_Customer_Download_Data_Store::delete()
	 */
	public function test_delete() {
		$data_store = WC_Data_Store::load( 'customer-download' );
		$data_store->delete( $this->download );
		$this->assertEquals( 0, $this->download->get_id() );
	}

	/**
	 * Test WC_Customer_Download_Data_Store::delete_by_id()
	 */
	public function test_delete_by_id() {
		$data_store = WC_Data_Store::load( 'customer-download' );
		$data_store->delete_by_id( $this->download->get_id() );
		$this->assertEquals( 0, $data_store->get_id() );
	}

	/**
	 * Test WC_Customer_Download_Data_Store::delete_by_download_id()
	 */
	public function test_delete_by_download_id() {
		$download_id = $this->download->get_download_id();
		$data_store  = WC_Data_Store::load( 'customer-download' );
		$downloads   = $data_store->get_downloads_for_customer( $this->customer_id );
		$this->assertInstanceOf( 'StdClass', $downloads[0] );
		$data_store->delete_by_download_id( $download_id );
		$downloads = $data_store->get_downloads_for_customer( $this->customer_id );
		$this->assertEquals( array(), $downloads );
	}

	/**
	 * Test WC_Customer_Download_Data_Store::get_downloads()
	 */
	public function test_get_downloads() {
		$download_2 = new WC_Customer_Download();
		$download_2->set_user_id( $this->customer_id );
		$download_2->set_user_email( 'test@example.com' );
		$download_2->set_order_id( 1 );
		$download_2->save();

		$data_store = WC_Data_Store::load( 'customer-download' );
		$downloads  = $data_store->get_downloads( array( 'user_email' => 'test@example.com' ) );
		$this->assertEquals( 2, count( $downloads ) );
		$this->assertTrue( $downloads[0] instanceof WC_Customer_Download );
		$this->assertTrue( $downloads[1] instanceof WC_Customer_Download );

		$downloads = $data_store->get_downloads( array( 'user_email' => 'test2@example.com' ) );
		$this->assertEquals( array(), $downloads );

		$expected_result = array( $this->download->get_id(), $download_2->get_id() );
		$downloads       = $data_store->get_downloads(
			array(
				'user_email' => 'test@example.com',
				'return'     => 'ids',
			)
		);
		$this->assertEquals( $expected_result, $downloads );
	}
}
