<?php
/**
 * Admin notes REST API Test
 *
 * @package WooCommerce\Tests\API
 * @since 3.5.0
 */
class WC_Tests_API_Admin_Notes extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc/v3/admin/notes';

	/**
	 * Setup test admin notes data. Called before every test.
	 *
	 * @since 3.5.0
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		WC_Helper_Admin_Notes::reset_notes_dbs();
		WC_Helper_Admin_Notes::add_notes_for_tests();
	}

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
	 * Test getting a single note.
	 *
	 * @since 3.5.0
	 */
	public function test_get_note() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/1' ) );
		$note     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

	}

	/**
	 * Test getting a single note without permission. It should fail.
	 *
	 * @since 3.5.0
	 */
	public function test_get_note_without_permission() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint . '/1' ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting lots of notes.
	 *
	 * @since 3.5.0
	 */
	public function test_get_notes() {
		wp_set_current_user( $this->user );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$notes    = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 2, count( $notes ) );
	}

	/**
	 * Test getting lots of notes without permission. It should fail.
	 *
	 * @since 3.5.0
	 */
	public function test_get_notes_without_permission() {
		$response = $this->server->dispatch( new WP_REST_Request( 'GET', $this->endpoint ) );
		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test getting the notes schema.
	 *
	 * @since 3.5.0
	 */
	public function test_get_notes_schema() {
		wp_set_current_user( $this->user );

		$request    = new WP_REST_Request( 'OPTIONS', $this->endpoint );
		$response   = $this->server->dispatch( $request );
		$data       = $response->get_data();
		$properties = $data['schema']['properties'];

		$this->assertEquals( 15, count( $properties ) );

		$this->assertArrayHasKey( 'id', $properties );
		$this->assertArrayHasKey( 'name', $properties );
		$this->assertArrayHasKey( 'type', $properties );
		$this->assertArrayHasKey( 'locale', $properties );
		$this->assertArrayHasKey( 'title', $properties );

		$this->assertArrayHasKey( 'content', $properties );
		$this->assertArrayHasKey( 'icon', $properties );
		$this->assertArrayHasKey( 'content_data', $properties );
		$this->assertArrayHasKey( 'status', $properties );
		$this->assertArrayHasKey( 'source', $properties );

		$this->assertArrayHasKey( 'date_created', $properties );
		$this->assertArrayHasKey( 'date_created_gmt', $properties );
		$this->assertArrayHasKey( 'date_reminder', $properties );
		$this->assertArrayHasKey( 'date_reminder_gmt', $properties );
		$this->assertArrayHasKey( 'actions', $properties );
	}
}
