<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Post Data.
 *
 * Standardises certain post data on save.
 *
 * @class 		WC_Post_Data
 * @version		2.2.0
 * @package		WooCommerce/Classes/Data
 * @category	Class
 * @author 		WooThemes
 */
class WC_Post_Data {

	/**
	 * Editing term.
	 *
	 * @var object
	 */
	private static $editing_term = null;

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'post_type_link', array( __CLASS__, 'variation_post_link' ), 10, 2 );
		add_action( 'woocommerce_deferred_product_sync', array( __CLASS__, 'deferred_product_sync' ), 10, 1 );
		add_action( 'set_object_terms', array( __CLASS__, 'set_object_terms' ), 10, 6 );

		add_action( 'transition_post_status', array( __CLASS__, 'transition_post_status' ), 10, 3 );
		add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'delete_product_query_transients' ) );
		add_action( 'woocommerce_product_set_visibility', array( __CLASS__, 'delete_product_query_transients' ) );

		add_action( 'edit_term', array( __CLASS__, 'edit_term' ), 10, 3 );
		add_action( 'edited_term', array( __CLASS__, 'edited_term' ), 10, 3 );
		add_filter( 'update_order_item_metadata', array( __CLASS__, 'update_order_item_metadata' ), 10, 5 );
		add_filter( 'update_post_metadata', array( __CLASS__, 'update_post_metadata' ), 10, 5 );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'wp_insert_post_data' ) );

		// Status transitions
		add_action( 'delete_post', array( __CLASS__, 'delete_post' ) );
		add_action( 'wp_trash_post', array( __CLASS__, 'trash_post' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'untrash_post' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'delete_order_items' ) );
		add_action( 'before_delete_post', array( __CLASS__, 'delete_order_downloadable_permissions' ) );

		// Download permissions
		add_action( 'woocommerce_process_product_file_download_paths', array( __CLASS__, 'process_product_file_download_paths' ), 10, 3 );
	}

	/**
	 * Link to parent products when getting permalink for variation.
	 *
	 * @return string
	 */
	public static function variation_post_link( $permalink, $post ) {
		if ( 'product_variation' === $post->post_type ) {
			$variation = wc_get_product( $post->ID );
			return $variation->get_permalink();
		}
		return $permalink;
	}

	/**
	 * Sync a product.
	 * @param  int $product_id
	 */
	public static function deferred_product_sync( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( is_callable( array( $product, 'sync' ) ) ) {
			$product->sync( $product );
		}
	}

	/**
	 * Delete transients when terms are set.
	 */
	public static function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		foreach ( array_merge( $tt_ids, $old_tt_ids ) as $id ) {
			delete_transient( 'wc_ln_count_' . md5( sanitize_key( $taxonomy ) . sanitize_key( $id ) ) );
		}
	}

	/**
	 * When a post status changes.
	 */
	public static function transition_post_status( $new_status, $old_status, $post ) {
		if ( ( 'publish' === $new_status || 'publish' === $old_status ) && in_array( $post->post_type, array( 'product', 'product_variation' ) ) ) {
			self::delete_product_query_transients();
		}
	}

	/**
	 * Delete product view transients when needed e.g. when post status changes, or visibility/stock status is modified.
	 */
	public static function delete_product_query_transients() {
		// Increments the transient version to invalidate cache
		WC_Cache_Helper::get_transient_version( 'product_query', true );

		// If not using an external caching system, we can clear the transients out manually and avoid filling our DB
		if ( ! wp_using_ext_object_cache() ) {
			global $wpdb;

			$wpdb->query( "
				DELETE FROM `$wpdb->options`
				WHERE `option_name` LIKE ('\_transient\_wc\_uf\_pid\_%')
				OR `option_name` LIKE ('\_transient\_timeout\_wc\_uf\_pid\_%')
				OR `option_name` LIKE ('\_transient\_wc\_products\_will\_display\_%')
				OR `option_name` LIKE ('\_transient\_timeout\_wc\_products\_will\_display\_%')
			" );
		}
	}

	/**
	 * When editing a term, check for product attributes.
	 * @param  id $term_id
	 * @param  id $tt_id
	 * @param  string $taxonomy
	 */
	public static function edit_term( $term_id, $tt_id, $taxonomy ) {
		if ( strpos( $taxonomy, 'pa_' ) === 0 ) {
			self::$editing_term = get_term_by( 'id', $term_id, $taxonomy );
		} else {
			self::$editing_term = null;
		}
	}

	/**
	 * When a term is edited, check for product attributes and update variations.
	 * @param  id $term_id
	 * @param  id $tt_id
	 * @param  string $taxonomy
	 */
	public static function edited_term( $term_id, $tt_id, $taxonomy ) {
		if ( ! is_null( self::$editing_term ) && strpos( $taxonomy, 'pa_' ) === 0 ) {
			$edited_term = get_term_by( 'id', $term_id, $taxonomy );

			if ( $edited_term->slug !== self::$editing_term->slug ) {
				global $wpdb;

				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s AND meta_value = %s;", $edited_term->slug, 'attribute_' . sanitize_title( $taxonomy ), self::$editing_term->slug ) );
			}
		} else {
			self::$editing_term = null;
		}
	}

	/**
	 * Ensure floats are correctly converted to strings based on PHP locale.
	 *
	 * @param  null $check
	 * @param  int $object_id
	 * @param  string $meta_key
	 * @param  mixed $meta_value
	 * @param  mixed $prev_value
	 * @return null|bool
	 */
	public static function update_order_item_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( ! empty( $meta_value ) && is_float( $meta_value ) ) {

			// Convert float to string
			$meta_value = wc_float_to_string( $meta_value );

			// Update meta value with new string
			update_metadata( 'order_item', $object_id, $meta_key, $meta_value, $prev_value );

			// Return
			return true;
		}
		return $check;
	}

	/**
	 * Ensure floats are correctly converted to strings based on PHP locale.
	 *
	 * @param  null $check
	 * @param  int $object_id
	 * @param  string $meta_key
	 * @param  mixed $meta_value
	 * @param  mixed $prev_value
	 * @return null|bool
	 */
	public static function update_post_metadata( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
		if ( ! empty( $meta_value ) && is_float( $meta_value ) && in_array( get_post_type( $object_id ), array_merge( wc_get_order_types(), array( 'shop_coupon', 'product', 'product_variation' ) ) ) ) {

			// Convert float to string
			$meta_value = wc_float_to_string( $meta_value );

			// Update meta value with new string
			update_metadata( 'post', $object_id, $meta_key, $meta_value, $prev_value );

			// Return
			return true;
		}
		return $check;
	}

	/**
	 * When setting stock level, ensure the stock status is kept in sync.
	 * @param  int $meta_id
	 * @param  int $object_id
	 * @param  string $meta_key
	 * @param  mixed $meta_value
	 * @deprecated
	 */
	public static function sync_product_stock_status( $meta_id, $object_id, $meta_key, $meta_value ) {}

	/**
	 * Forces the order posts to have a title in a certain format (containing the date).
	 * Forces certain product data based on the product's type, e.g. grouped products cannot have a parent.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function wp_insert_post_data( $data ) {
		if ( 'shop_order' === $data['post_type'] && isset( $data['post_date'] ) ) {
			$order_title = 'Order';
			if ( $data['post_date'] ) {
				$order_title .= ' &ndash; ' . date_i18n( 'F j, Y @ h:i A', strtotime( $data['post_date'] ) );
			}
			$data['post_title'] = $order_title;
		} elseif ( 'product' === $data['post_type'] && isset( $_POST['product-type'] ) ) {
			$product_type = stripslashes( $_POST['product-type'] );
			switch ( $product_type ) {
				case 'grouped' :
				case 'variable' :
					$data['post_parent'] = 0;
				break;
			}
		}

		return $data;
	}

	/**
	 * Removes variations etc belonging to a deleted post, and clears transients.
	 *
	 * @param mixed $id ID of post being deleted
	 */
	public static function delete_post( $id ) {
		global $woocommerce, $wpdb;

		if ( ! current_user_can( 'delete_posts' ) ) {
			return;
		}

		if ( $id > 0 ) {

			$post_type = get_post_type( $id );

			switch ( $post_type ) {
				case 'product' :

					$child_product_variations = get_children( 'post_parent=' . $id . '&post_type=product_variation' );

					if ( ! empty( $child_product_variations ) ) {
						foreach ( $child_product_variations as $child ) {
							wp_delete_post( $child->ID, true );
						}
					}

					$child_products = get_children( 'post_parent=' . $id . '&post_type=product' );

					if ( ! empty( $child_products ) ) {
						foreach ( $child_products as $child ) {
							$child_post                = array();
							$child_post['ID']          = $child->ID;
							$child_post['post_parent'] = 0;
							wp_update_post( $child_post );
						}
					}

					if ( $parent_id = wp_get_post_parent_id( $id ) ) {
						wc_delete_product_transients( $parent_id );
					}

				break;
				case 'product_variation' :
					wc_delete_product_transients( wp_get_post_parent_id( $id ) );
				break;
				case 'shop_order' :
					$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order_refund' AND post_parent = %d", $id ) );

					if ( ! is_null( $refunds ) ) {
						foreach ( $refunds as $refund ) {
							wp_delete_post( $refund->ID, true );
						}
					}
				break;
			}
		}
	}

	/**
	 * woocommerce_trash_post function.
	 *
	 * @param mixed $id
	 */
	public static function trash_post( $id ) {
		global $wpdb;

		if ( $id > 0 ) {

			$post_type = get_post_type( $id );

			if ( in_array( $post_type, wc_get_order_types( 'order-count' ) ) ) {
				$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order_refund' AND post_parent = %d", $id ) );

				foreach ( $refunds as $refund ) {
					$wpdb->update( $wpdb->posts, array( 'post_status' => 'trash' ), array( 'ID' => $refund->ID ) );
				}

				delete_transient( 'woocommerce_processing_order_count' );
				wc_delete_shop_order_transients( $id );
			}
		}
	}

	/**
	 * woocommerce_untrash_post function.
	 *
	 * @param mixed $id
	 */
	public static function untrash_post( $id ) {
		global $wpdb;

		if ( $id > 0 ) {

			$post_type = get_post_type( $id );

			if ( in_array( $post_type, wc_get_order_types( 'order-count' ) ) ) {

				$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = 'shop_order_refund' AND post_parent = %d", $id ) );

				foreach ( $refunds as $refund ) {
					$wpdb->update( $wpdb->posts, array( 'post_status' => 'wc-completed' ), array( 'ID' => $refund->ID ) );
				}

				delete_transient( 'woocommerce_processing_order_count' );
				wc_delete_shop_order_transients( $id );
			} elseif ( 'product' === $post_type ) {
				// Check if SKU is valid before untrash the product.
				$sku = get_post_meta( $id, '_sku', true );

				if ( ! empty( $sku ) ) {
					if ( ! wc_product_has_unique_sku( $id, $sku ) ) {
						update_post_meta( $id, '_sku', '' );
					}
				}
			}
		}
	}

	/**
	 * Remove item meta on permanent deletion.
	 */
	public static function delete_order_items( $postid ) {
		global $wpdb;

		if ( in_array( get_post_type( $postid ), wc_get_order_types() ) ) {
			do_action( 'woocommerce_delete_order_items', $postid );

			$wpdb->query( "
				DELETE {$wpdb->prefix}woocommerce_order_items, {$wpdb->prefix}woocommerce_order_itemmeta
				FROM {$wpdb->prefix}woocommerce_order_items
				JOIN {$wpdb->prefix}woocommerce_order_itemmeta ON {$wpdb->prefix}woocommerce_order_items.order_item_id = {$wpdb->prefix}woocommerce_order_itemmeta.order_item_id
				WHERE {$wpdb->prefix}woocommerce_order_items.order_id = '{$postid}';
				" );

			do_action( 'woocommerce_deleted_order_items', $postid );
		}
	}

	/**
	 * Remove downloadable permissions on permanent order deletion.
	 */
	public static function delete_order_downloadable_permissions( $postid ) {
		global $wpdb;

		if ( in_array( get_post_type( $postid ), wc_get_order_types() ) ) {
			do_action( 'woocommerce_delete_order_downloadable_permissions', $postid );

			$data_store = WC_Data_Store::load( 'customer-download' );
			$data_store->delete_by_order_id( $postid );

			do_action( 'woocommerce_deleted_order_downloadable_permissions', $postid );
		}
	}

	/**
	 * Update changed downloads.
	 *
	 * @param int $product_id product identifier
	 * @param int $variation_id optional product variation identifier
	 * @param array $downloads newly set files
	 */
	public static function process_product_file_download_paths( $product_id, $variation_id, $downloads ) {
		if ( $variation_id ) {
			$product_id = $variation_id;
		}
		$product    = wc_get_product( $product_id );
		$data_store = WC_Data_Store::load( 'customer-download' );

		if ( $downloads ) {
			foreach ( $downloads as $download ) {
				$new_hash = md5( $download->get_file() );

				if ( $download->get_previous_hash() && $download->get_previous_hash() !== $new_hash ) {
					// Update permissions.
					$data_store->update_download_id( $product_id, $download->get_previous_hash(), $new_hash );
				}
			}
		}
	}
}

WC_Post_Data::init();
