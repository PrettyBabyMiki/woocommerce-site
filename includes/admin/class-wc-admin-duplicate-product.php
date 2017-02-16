<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Duplicate product functionality
 *
 * @author      WooCommerce
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.7.0
 */

if ( ! class_exists( 'WC_Admin_Duplicate_Product', false ) ) :

/**
 * WC_Admin_Duplicate_Product Class.
 */
class WC_Admin_Duplicate_Product {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_action_duplicate_product', array( $this, 'duplicate_product_action' ) );
		add_filter( 'post_row_actions', array( $this, 'dupe_link' ), 10, 2 );
		add_action( 'post_submitbox_start', array( $this, 'dupe_button' ) );
	}

	/**
	 * Show the "Duplicate" link in admin products list.
	 * @param  array   $actions
	 * @param  WP_Post $post Post object
	 * @return array
	 */
	public function dupe_link( $actions, $post ) {
		if ( ! current_user_can( apply_filters( 'woocommerce_duplicate_product_capability', 'manage_woocommerce' ) ) ) {
			return $actions;
		}

		if ( 'product' !== $post->post_type ) {
			return $actions;
		}

		$actions['duplicate'] = '<a href="' . wp_nonce_url( admin_url( 'edit.php?post_type=product&action=duplicate_product&amp;post=' . $post->ID ), 'woocommerce-duplicate-product_' . $post->ID ) . '" aria-label="' . esc_attr__( 'Make a duplicate from this product', 'woocommerce' )
			. '" rel="permalink">' . __( 'Duplicate', 'woocommerce' ) . '</a>';

		return $actions;
	}

	/**
	 * Show the dupe product link in admin.
	 */
	public function dupe_button() {
		global $post;

		if ( ! current_user_can( apply_filters( 'woocommerce_duplicate_product_capability', 'manage_woocommerce' ) ) ) {
			return;
		}

		if ( ! is_object( $post ) ) {
			return;
		}

		if ( 'product' !== $post->post_type ) {
			return;
		}

		if ( isset( $_GET['post'] ) ) {
			$notify_url = wp_nonce_url( admin_url( "edit.php?post_type=product&action=duplicate_product&post=" . absint( $_GET['post'] ) ), 'woocommerce-duplicate-product_' . $_GET['post'] );
			?>
			<div id="duplicate-action"><a class="submitduplicate duplication" href="<?php echo esc_url( $notify_url ); ?>"><?php _e( 'Copy to a new draft', 'woocommerce' ); ?></a></div>
			<?php
		}
	}

	/**
	 * Duplicate a product action.
	 */
	public function duplicate_product_action() {
		if ( empty( $_REQUEST['post'] ) ) {
			wp_die( __( 'No product to duplicate has been supplied!', 'woocommerce' ) );
		}

		$product_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

		check_admin_referer( 'woocommerce-duplicate-product_' . $product_id );

		$product = wc_get_product( $product_id );

		if ( false === $product ) {
			/* translators: %s: product id */
			wp_die( sprintf( __( 'Product creation failed, could not find original product: %s', 'woocommerce' ), $product_id ) );
		}

		$duplicate = clone $product;
		$duplicate->set_id( 0 );
		$duplicate->save();

		$sku = $duplicate->get_sku();
		if ( '' !== $duplicate->get_sku() ) {
			wc_product_force_unique_sku( $duplicate->get_id() );
		}

		$exclude = apply_filters( 'woocommerce_duplicate_product_exclude_children', false );

		if ( ! $exclude && ( $product->is_type( 'variable' ) || $product->is_type( 'grouped' ) ) ) {
			foreach( $product->get_children() as $child_id ) {
				$child = wc_get_product( $child_id );
				$child_duplicate = clone $child;
				$child_duplicate->set_parent_id( $duplicate->get_id() );
				$child_duplicate->set_id( 0 );
				$child_duplicate->save();
				if ( '' !== $child_duplicate->get_sku() ) {
					wc_product_force_unique_sku( $child_duplicate->get_id() );
				}
			}
		}

		// Hook rename to match other woocommerce_product_* hooks, and to move away from depending on a response from the wp_posts table.
		// New hook returns new id and old id.
		do_action( 'woocommerce_product_duplicate', $duplicate, $product );
		wc_do_deprecated_action( 'woocommerce_duplicate_product', array( $duplicate->get_id(), $this->get_product_to_duplicate( $product_id ) ), '2.7', 'Use woocommerce_product_duplicate action instead.' );

		// Redirect to the edit screen for the new draft page
		wp_redirect( admin_url( 'post.php?action=edit&post=' . $duplicate->get_id() ) );
		exit;
	}

	/**
	 * Get a product from the database to duplicate.
	 *
	 * @deprecated 2.7.0
	 * @param mixed $id
	 * @return WP_Post|bool
	 * @see duplicate_product
	 */
	private function get_product_to_duplicate( $id ) {
		global $wpdb;

		$id = absint( $id );

		if ( ! $id ) {
			return false;
		}

		$post = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE ID=$id" );

		if ( isset( $post->post_type ) && 'revision' === $post->post_type ) {
			$id   = $post->post_parent;
			$post = $wpdb->get_results( "SELECT * FROM $wpdb->posts WHERE ID=$id" );
		}

		return $post[0];
	}
}

endif;

return new WC_Admin_Duplicate_Product();
