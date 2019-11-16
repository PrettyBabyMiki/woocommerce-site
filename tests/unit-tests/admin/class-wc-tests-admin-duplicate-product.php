<?php
/**
 * Unit tests for the admin product duplication class.
 *
 * @package WooCommerce\Tests\Admin
 */

/**
 * WC_Tests_Admin_Duplicate_Product tests.
 *
 * @package WooCommerce\Tests\Admin
 * @since 3.9.0
 */
class WC_Tests_Admin_Duplicate_Product extends WC_Unit_Test_Case {
	/**
	 * Test duplication of a simple product.
	 */
	public function test_simple_product_duplication() {
		$product = WC_Helper_Product::create_simple_product();

		$duplicate = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product );

		$this->assertNotEquals( $product->get_id(), $duplicate->get_id() );
		$this->assertEquals( $product->get_name() . ' (Copy)', $duplicate->get_name() );
		$this->assertEquals( 'draft', $duplicate->get_status() );
		$this->assertDuplicateWasReset( $duplicate );
	}

	/**
	 * Test duplication of a variable product.
	 */
	public function test_variable_product_duplication() {
		$product = WC_Helper_Product::create_variation_product();

		$duplicate = ( new WC_Admin_Duplicate_Product() )->product_duplicate( $product );

		$this->assertNotEquals( $product->get_id(), $duplicate->get_id() );
		$this->assertEquals( $product->get_name() . ' (Copy)', $duplicate->get_name() );
		$this->assertEquals( 'draft', $duplicate->get_status() );
		$this->assertDuplicateWasReset( $duplicate );

		$product_children   = $product->get_children();
		$duplicate_children = $duplicate->get_children();
		$child_count        = count( $product_children );
		$this->assertEquals( $child_count, count( $duplicate_children ) );

		for ( $i = 0; $i < $child_count; $i++ ) {
			$product_child   = wc_get_product( $product_children[ $i ] );
			$duplicate_child = wc_get_product( $duplicate_children[ $i ] );

			$this->assertNotEquals( $product_child->get_id(), $duplicate_child->get_id() );
			$this->assertEquals( $product_child->get_name() . ' (Copy)', $duplicate_child->get_name() );
			$this->assertEquals( 'publish', $duplicate_child->get_status() );
			$this->assertDuplicateWasReset( $duplicate_child );
		}
	}

	/**
	 * Asserts that the product was correctly reset after duplication.
	 *
	 * @param WC_Product $duplicate The duplicate product to evaluate.
	 */
	private function assertDuplicateWasReset( $duplicate ) {
		$this->assertEquals( 0, $duplicate->get_total_sales() );
		$this->assertEquals( array(), $duplicate->get_rating_counts() );
		$this->assertEquals( 0, $duplicate->get_average_rating() );
		$this->assertEquals( 0, $duplicate->get_rating_count() );
	}
}
