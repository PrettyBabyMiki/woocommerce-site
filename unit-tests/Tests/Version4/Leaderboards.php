<?php
/**
 * Leaderboards REST API Test
 *
 * @package WooCommerce Admin\Tests\API
 */

namespace WooCommerce\RestApi\UnitTests\Tests\Version4;

defined( 'ABSPATH' ) || exit;

use \WP_REST_Request;
use \WC_REST_Unit_Test_Case;

/**
 * WC Tests API Leaderboards
 */
class Leaderboards extends WC_REST_Unit_Test_Case {
	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v4/leaderboards';

	/**
	 * User variable.
	 *
	 * @var WP_User
	 */
	protected static $user;

	/**
	 * Setup once before running tests.
	 *
	 * @param object $factory Factory object.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::$user = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Setup our test server, endpoints, and user info.
	 */
	public function setUp() {
		if ( ! class_exists( '\WC_Admin_Reports_Sync' ) ) {
			$this->markTestSkipped( 'Skipping reports tests - WC_Admin_Reports_Sync class not found.' );
			return;
		}
		parent::setUp();
		wp_set_current_user( self::$user );
	}

	/**
	 * Test that leaderboards are returned by the endpoint.
	 */
	public function test_get_leaderboards() {
		$request  = new WP_REST_Request( 'GET', $this->endpoint );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'customers', $data[0]['id'] );
		$this->assertEquals( 'coupons', $data[1]['id'] );
		$this->assertEquals( 'categories', $data[2]['id'] );
		$this->assertEquals( 'products', $data[3]['id'] );
	}

	/**
	 * Test reports schema.
	 */
	public function test_schema() {
		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertCount( 4, $properties );
		$this->assert_item_schema( $properties );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint . '/allowed' );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertCount( 3, $properties );
		$this->assert_allowed_item_schema( $properties );
	}

	/**
	 * Asserts the item schema is correct.
	 *
	 * @param array $schema Item to check schema.
	 */
	public function assert_item_schema( $schema ) {
		$this->assertArrayHasKey( 'id', $schema );
		$this->assertArrayHasKey( 'label', $schema );
		$this->assertArrayHasKey( 'headers', $schema );
		$this->assertArrayHasKey( 'rows', $schema );

		$header_properties = $schema['headers']['items']['properties'];
		$this->assertCount( 1, $header_properties );
		$this->assertArrayHasKey( 'label', $header_properties );

		$row_properties = $schema['rows']['items']['properties'];
		$this->assertCount( 2, $row_properties );
		$this->assertArrayHasKey( 'display', $row_properties );
		$this->assertArrayHasKey( 'value', $row_properties );
	}

	/**
	 * Asserts the allowed item schema is correct.
	 *
	 * @param array $schema Item to check schema.
	 */
	public function assert_allowed_item_schema( $schema ) {
		$this->assertArrayHasKey( 'id', $schema );
		$this->assertArrayHasKey( 'label', $schema );
		$this->assertArrayHasKey( 'headers', $schema );

		$header_properties = $schema['headers']['items']['properties'];
		$this->assertCount( 1, $header_properties );
		$this->assertArrayHasKey( 'label', $header_properties );
	}

	/**
	 * Test that leaderboards response changes based on applied filters.
	 */
	public function test_filter_leaderboards() {
		add_filter(
			'woocommerce_leaderboards',
			function( $leaderboards, $per_page, $after, $before, $persisted_query ) {
				$leaderboards[] = array(
					'id'      => 'top_widgets',
					'label'   => 'Top Widgets',
					'headers' => array(
						array(
							'label' => 'Widget Link',
						),
					),
					'rows'    => array(
						array(
							'display' => wc_admin_url( 'test/path', $persisted_query ),
							'value'   => null,
						),
					),
				);
				return $leaderboards;
			},
			10,
			5
		);
		$request = new WP_REST_Request( 'GET', $this->endpoint );
		$request->set_query_params( array( 'persisted_query' => '{ "persisted_param": 1 }' ) );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$widgets_leaderboard = end( $data );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'top_widgets', $widgets_leaderboard['id'] );
		$this->assertEquals( admin_url( 'admin.php?page=wc-admin#test/path?persisted_param=1' ), $widgets_leaderboard['rows'][0]['display'] );

		$request  = new WP_REST_Request( 'GET', $this->endpoint . '/allowed' );
		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$widgets_leaderboard = end( $data );
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'top_widgets', $widgets_leaderboard['id'] );
	}
}
