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

		$product = new WC_Product( $product_id );
		$update_properties = array(
			'product_id'            => $product_id,
			// 'product_type'          => TODO where does this come from?? there is no product type, only category ids.
			'category_ids'          => $product->get_category_ids(),
			'manage_stock'          => 0 == $product->get_manage_stock() ? 'N' : 'Y',
			// 'stock_quantity_update' => 'Y'	TODO this may require hooking in to product edit at a lower level, which doesn't seem possible for an extension.
			// You can only get the current product, not the product before the update...
		);

		WC_Tracks::record_event( 'product_update', $update_properties );
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
