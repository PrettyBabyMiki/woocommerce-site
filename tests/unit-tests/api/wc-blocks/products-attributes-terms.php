<?php
/**
 * @package WooCommerce\Tests\API
 */

/**
 * Product Controller "products attributes terms" REST API Test
 *
 * @since 3.6.0
 */
class WC_Tests_API_Products_Attributes_Terms_Controller extends WC_REST_Unit_Test_Case {

	/**
	 * Endpoints.
	 *
	 * @var string
	 */
	protected $endpoint = '/wc-blocks/v1';

	/**
	 * Setup test products data. Called before every test.
	 *
	 * @since 3.6.0
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
        );
        
        $this->editor = $this->factory->user->create(
			array(
				'role' => 'editor',
			)
		);

		// Create 2 product attributes with terms.
		$this->attr_color = WC_Helper_Product::create_attribute( 'color', array( 'red', 'yellow', 'blue' ) );
		$this->attr_size  = WC_Helper_Product::create_attribute( 'size', array( 'small', 'medium', 'large', 'xlarge' ) );
	}

	/**
	 * Test getting attribute terms.
	 *
	 * @since 3.6.0
	 */
	public function test_get_terms() {
        wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products/attributes/' . $this->attr_color['attribute_id'] . '/terms' );

		$response       = $this->server->dispatch( $request );
		$response_terms = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
        $this->assertEquals( 3, count( $response_terms ) );
        $term = $response_terms[0];
        $this->assertArrayHasKey( 'attr_name', $term );
        $this->assertArrayHasKey( 'attr_slug', $term );
    }
    
    /**
	 * Test getting invalid attribute terms.
	 *
	 * @since 3.6.0
	 */
	public function test_get_invalid_attribute_terms() {
        wp_set_current_user( $this->user );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products/attributes/99999/terms' );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 404, $response->get_status() );
    }
    
    /**
	 * Test un-authorized getting attribute terms.
	 *
	 * @since 3.6.0
	 */
	public function test_get_unauthed_attribute_terms() {
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products/attributes/'. $this->attr_size['attribute_id'] . '/terms' );

		$response = $this->server->dispatch( $request );
		$this->assertEquals( 401, $response->get_status() );
	}

    /**
	 * Test getting attribute terms as editor.
	 *
	 * @since 3.6.0
	 */
	public function test_get_attribute_terms_editor() {
        wp_set_current_user( $this->editor );
		$request = new WP_REST_Request( 'GET', $this->endpoint . '/products/attributes/'. $this->attr_size['attribute_id'] . '/terms' );

        $response       = $this->server->dispatch( $request );
        $response_terms = $response->get_data();
		$this->assertEquals( 200, $response->get_status() );
        $this->assertEquals( 4, count( $response_terms ) );
	}
}
