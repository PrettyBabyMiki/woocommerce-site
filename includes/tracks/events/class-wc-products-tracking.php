<?php
/**
 * WooCommerce Import Tracking
 *
 * @package WooCommerce\Tracks
 */

use Automattic\Jetpack\Constants;

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of WooCommerce Products.
 */
class WC_Products_Tracking {
	/**
	 * Init tracking.
	 */
	public function init() {
		add_action( 'edit_post', array( $this, 'track_product_updated' ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'track_product_published' ), 10, 3 );
		add_action( 'created_product_cat', array( $this, 'track_product_category_created' ) );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'track_product_stock_level_set' ), 10, 1 );
		add_action( 'woocommerce_product_set_stock', array( $this, 'track_product_stock_level_set' ), 10, 1 );
	}

	/**
	 * Send some Tracks events when a product is updated.
	 *
	 * @param int    $product_id Product id.
	 * @param object $post WordPress post.
	 */
	public function track_product_updated( $product_id, $post ) {
		if ( 'product' !== $post->post_type ) {
			return;
		}

		$properties = array(
			'product_id' => $product_id,
		);

		WC_Tracks::record_event( 'product_edit', $properties );

		$product_factory = new WC_Product_Factory();
		$product = $product_factory->get_product( $product_id );
		$update_properties = array(
			'product_id'            => $product_id,
			'product_type'          => $product->get_type(),
			'is_virtual'            => $product->get_virtual(),
			'is_downloadable'       => $product->get_downloadable(),
			'manage_stock'          => 0 == $product->get_manage_stock() ? 'N' : 'Y',
		);

		WC_Tracks::record_event( 'product_update', $update_properties );
	}

	/**
	 * Send a Tracks event when a product's stock level is adjusted.
	 *
	 * @param WC_Product $product Product.
	 */
	public function track_product_stock_level_set( $product ) {
		$properties = array(
			'product_id' => $product->get_id(),
		);

		WC_Tracks::record_event( 'product_stock_level_set', $properties );
	}

	/**
	 * Send a Tracks event when a product is published.
	 *
	 * @param string $new_status New post_status.
	 * @param string $old_status Previous post_status.
	 * @param object $post WordPress post.
	 */
	public function track_product_published( $new_status, $old_status, $post ) {
		if (
			'product' !== $post->post_type ||
			'publish' !== $new_status ||
			'publish' === $old_status
		) {
			return;
		}

		$properties = array(
			'product_id' => $post->ID,
		);

		WC_Tracks::record_event( 'product_add_publish', $properties );
	}

	/**
	 * Send a Tracks event when a product category is created.
	 *
	 * @param int $category_id Category ID.
	 */
	public function track_product_category_created( $category_id ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		// Only track category creation from the edit product screen or the
		// category management screen (which both occur via AJAX).
		if (
			! Constants::is_defined( 'DOING_AJAX' ) ||
			empty( $_POST['action'] ) ||
			(
				// Product Categories screen.
				'add-tag' !== $_POST['action'] &&
				// Edit Product screen.
				'add-product_cat' !== $_POST['action']
			)
		) {
			return;
		}

		$category   = get_term( $category_id, 'product_cat' );
		$properties = array(
			'category_id' => $category_id,
			'parent_id'   => $category->parent,
			'page'        => ( 'add-tag' === $_POST['action'] ) ? 'categories' : 'product',
		);
		// phpcs:enable

		WC_Tracks::record_event( 'product_category_add', $properties );
	}
}
