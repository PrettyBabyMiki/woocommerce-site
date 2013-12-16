<?php
/**
 * WooCommerce Order Functions
 *
 * Functions for order specific things.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Finds an Order ID based on an order key.
 *
 * @access public
 * @param string $order_key An order key has generated by
 * @return int The ID of an order, or 0 if the order could not be found
 */
function wc_get_order_id_by_order_key( $order_key ) {
	global $wpdb;

	// Faster than get_posts()
	$order_id = $wpdb->get_var( "SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '_order_key' AND meta_value = '{$order_key}'" );

	return $order_id;
}

/**
 * Grant downloadable product access to the file identified by $download_id
 *
 * @access public
 * @param string $download_id file identifier
 * @param int $product_id product identifier
 * @param WC_Order $order the order
 * @return int|bool insert id or false on failure
 */
function wc_downloadable_file_permission( $download_id, $product_id, $order ) {
	global $wpdb;

	if ( $order->status == 'processing' && get_option( 'woocommerce_downloads_grant_access_after_payment' ) == 'no' )
		return false;

	$user_email = sanitize_email( $order->billing_email );
	$limit      = trim( get_post_meta( $product_id, '_download_limit', true ) );
	$expiry     = trim( get_post_meta( $product_id, '_download_expiry', true ) );

	$limit      = empty( $limit ) ? '' : absint( $limit );

	// Default value is NULL in the table schema
	$expiry     = empty( $expiry ) ? null : absint( $expiry );

	if ( $expiry )
		$expiry = date_i18n( "Y-m-d", strtotime( 'NOW + ' . $expiry . ' DAY' ) );

	$data = apply_filters( 'woocommerce_downloadable_file_permission_data', array(
		'download_id'			=> $download_id,
		'product_id' 			=> $product_id,
		'user_id' 				=> absint( $order->user_id ),
		'user_email' 			=> $user_email,
		'order_id' 				=> $order->id,
		'order_key' 			=> $order->order_key,
		'downloads_remaining' 	=> $limit,
		'access_granted'		=> current_time( 'mysql' ),
		'download_count'		=> 0
	));

	$format = apply_filters( 'woocommerce_downloadable_file_permission_format', array(
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%s',
		'%d'
	), $data);

	if ( ! is_null( $expiry ) ) {
			$data['access_expires'] = $expiry;
			$format[] = '%s';
	}

	// Downloadable product - give access to the customer
	$result = $wpdb->insert( $wpdb->prefix . 'woocommerce_downloadable_product_permissions',
		$data,
		$format
	);

	do_action( 'woocommerce_grant_product_download_access', $data );

	return $result ? $wpdb->insert_id : false;
}

add_action('woocommerce_order_status_completed', 'wc_downloadable_product_permissions');
add_action('woocommerce_order_status_processing', 'wc_downloadable_product_permissions');


/**
 * Order Status completed - GIVE DOWNLOADABLE PRODUCT ACCESS TO CUSTOMER
 *
 * @access public
 * @param int $order_id
 * @return void
 */
function wc_downloadable_product_permissions( $order_id ) {
	if ( get_post_meta( $order_id, '_download_permissions_granted', true ) == 1 )
		return; // Only do this once

	$order = new WC_Order( $order_id );

	if ( sizeof( $order->get_items() ) > 0 ) {
		foreach ( $order->get_items() as $item ) {
			$_product = $order->get_product_from_item( $item );

			if ( $_product && $_product->exists() && $_product->is_downloadable() ) {
				$downloads = $_product->get_files();

				foreach ( array_keys( $downloads ) as $download_id ) {
					wc_downloadable_file_permission( $download_id, $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'], $order );
				}
			}
		}
	}

	update_post_meta( $order_id, '_download_permissions_granted', 1 );

	do_action( 'woocommerce_grant_product_download_permissions', $order_id );

}

/**
 * Add a item to an order (for example a line item).
 *
 * @access public
 * @param int $order_id
 * @param array $data
 * @return mixed
 */
function wc_add_order_item( $order_id, $item ) {
	global $wpdb;

	$order_id = absint( $order_id );

	if ( ! $order_id )
		return false;

	$defaults = array(
		'order_item_name' 		=> '',
		'order_item_type' 		=> 'line_item',
	);

	$item = wp_parse_args( $item, $defaults );

	$wpdb->insert(
		$wpdb->prefix . "woocommerce_order_items",
		array(
			'order_item_name' 		=> $item['order_item_name'],
			'order_item_type' 		=> $item['order_item_type'],
			'order_id'				=> $order_id
		),
		array(
			'%s', '%s', '%d'
		)
	);

	$item_id = absint( $wpdb->insert_id );

	do_action( 'woocommerce_new_order_item', $item_id, $item, $order_id );

	return $item_id;
}

/**
 * Delete an item from the order it belongs to based on item id
 *
 * @access public
 * @param int $item_id
 * @return bool
 */
function wc_delete_order_item( $item_id ) {
	global $wpdb;

	$item_id = absint( $item_id );

	if ( ! $item_id )
		return false;

	do_action( 'woocommerce_before_delete_order_item', $item_id );

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id = %d", $item_id ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = %d", $item_id ) );

	do_action( 'woocommerce_delete_order_item', $item_id );

	return true;
}

/**
 * WooCommerce Order Item Meta API - Update term meta
 *
 * @access public
 * @param mixed $item_id
 * @param mixed $meta_key
 * @param mixed $meta_value
 * @param string $prev_value (default: '')
 * @return bool
 */
function wc_update_order_item_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'order_item', $item_id, $meta_key, $meta_value, $prev_value );
}

/**
 * WooCommerce Order Item Meta API - Add term meta
 *
 * @access public
 * @param mixed $item_id
 * @param mixed $meta_key
 * @param mixed $meta_value
 * @param bool $unique (default: false)
 * @return bool
 */
function wc_add_order_item_meta( $item_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'order_item', $item_id, $meta_key, $meta_value, $unique );
}

/**
 * WooCommerce Order Item Meta API - Delete term meta
 *
 * @access public
 * @param mixed $item_id
 * @param mixed $meta_key
 * @param string $meta_value (default: '')
 * @param bool $delete_all (default: false)
 * @return bool
 */
function wc_delete_order_item_meta( $item_id, $meta_key, $meta_value = '', $delete_all = false ) {
	return delete_metadata( 'order_item', $item_id, $meta_key, $meta_value, $delete_all );
}

/**
 * WooCommerce Order Item Meta API - Get term meta
 *
 * @access public
 * @param mixed $item_id
 * @param mixed $key
 * @param bool $single (default: true)
 * @return mixed
 */
function wc_get_order_item_meta( $item_id, $key, $single = true ) {
	return get_metadata( 'order_item', $item_id, $key, $single );
}

/**
 * Cancel all unpaid orders after held duration to prevent stock lock for those products
 *
 * @access public
 * @return void
 */
function wc_cancel_unpaid_orders() {
	global $wpdb;

	$held_duration = get_option( 'woocommerce_hold_stock_minutes' );

	if ( $held_duration < 1 || get_option( 'woocommerce_manage_stock' ) != 'yes' )
		return;

	$date = date( "Y-m-d H:i:s", strtotime( '-' . absint( $held_duration ) . ' MINUTES', current_time( 'timestamp' ) ) );

	$unpaid_orders = $wpdb->get_col( $wpdb->prepare( "
		SELECT posts.ID
		FROM {$wpdb->posts} AS posts
		LEFT JOIN {$wpdb->term_relationships} AS rel ON posts.ID=rel.object_ID
		LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
		LEFT JOIN {$wpdb->terms} AS term USING( term_id )

		WHERE 	posts.post_type   = 'shop_order'
		AND 	posts.post_status = 'publish'
		AND 	tax.taxonomy      = 'shop_order_status'
		AND		term.slug	      = 'pending'
		AND 	posts.post_modified < %s
	", $date ) );

	if ( $unpaid_orders ) {
		foreach ( $unpaid_orders as $unpaid_order ) {
			$order = new WC_Order( $unpaid_order );

			if ( apply_filters( 'woocommerce_cancel_unpaid_order', true, $order ) )
				$order->update_status( 'cancelled', __( 'Unpaid order cancelled - time limit reached.', 'woocommerce' ) );
		}
	}

	wp_clear_scheduled_hook( 'woocommerce_cancel_unpaid_orders' );
	wp_schedule_single_event( time() + ( absint( $held_duration ) * 60 ), 'wc_cancel_unpaid_orders' );
}
add_action( 'woocommerce_cancel_unpaid_orders', 'wc_cancel_unpaid_orders' );


/**
 * Return the count of processing orders.
 *
 * @access public
 * @return int
 */
function wc_processing_order_count() {
	if ( false === ( $order_count = get_transient( 'woocommerce_processing_order_count' ) ) ) {
		$order_statuses = get_terms( 'shop_order_status' );
			$order_count = false;
			foreach ( $order_statuses as $status ) {
					if( $status->slug === 'processing' ) {
							$order_count += $status->count;
							break;
					}
			}
			$order_count = apply_filters( 'woocommerce_admin_menu_count', intval( $order_count ) );
		set_transient( 'woocommerce_processing_order_count', $order_count );
	}

	return $order_count;
}

