<?php
/**
 * Order Data
 *
 * Functions for displaying the order items meta box.
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/Meta Boxes
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_Meta_Box_Order_Items {

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		global $wpdb, $thepostid, $theorder, $woocommerce;

		if ( ! is_object( $theorder ) )
			$theorder = new WC_Order( $thepostid );

		$order = $theorder;
		?>
		<div class="woocommerce_order_items_wrapper">
			<table cellpadding="0" cellspacing="0" class="woocommerce_order_items">
				<thead>
					<tr>
						<th><input type="checkbox" class="check-column" /></th>
						<th class="item" colspan="2"><?php _e( 'Item', 'woocommerce' ); ?></th>

						<?php do_action( 'woocommerce_admin_order_item_headers' ); ?>

						<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>
							<th class="tax_class"><?php _e( 'Tax Class', 'woocommerce' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Tax class for the line item', 'woocommerce' ); ?>." href="#">[?]</a></th>
						<?php endif; ?>

						<th class="quantity"><?php _e( 'Qty', 'woocommerce' ); ?></th>

						<th class="line_cost"><?php _e( 'Totals', 'woocommerce' ); ?>&nbsp;<a class="tips" data-tip="<?php _e( 'Line subtotals are before pre-tax discounts, totals are after.', 'woocommerce' ); ?>" href="#">[?]</a></th>

						<?php if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) : ?>
							<th class="line_tax"><?php _e( 'Tax', 'woocommerce' ); ?></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody id="order_items_list">

					<?php
						// List order items
						$order_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', array( 'line_item', 'fee' ) ) );

						foreach ( $order_items as $item_id => $item ) {

							switch ( $item['type'] ) {
								case 'line_item' :
									$_product 	= $order->get_product_from_item( $item );
									$item_meta 	= $order->get_item_meta( $item_id );

									include( 'views/html-order-item.php' );
								break;
								case 'fee' :
									include( 'views/html-order-fee.php' );
								break;
							}

							do_action( 'woocommerce_order_item_' . $item['type'] . '_html', $item_id, $item );
						}
					?>
				</tbody>
			</table>
		</div>

		<p class="bulk_actions">
			<select>
				<option value=""><?php _e( 'Actions', 'woocommerce' ); ?></option>
				<optgroup label="<?php _e( 'Edit', 'woocommerce' ); ?>">
					<option value="delete"><?php _e( 'Delete Lines', 'woocommerce' ); ?></option>
				</optgroup>
				<optgroup label="<?php _e( 'Stock Actions', 'woocommerce' ); ?>">
					<option value="reduce_stock"><?php _e( 'Reduce Line Stock', 'woocommerce' ); ?></option>
					<option value="increase_stock"><?php _e( 'Increase Line Stock', 'woocommerce' ); ?></option>
				</optgroup>
			</select>

			<button type="button" class="button do_bulk_action wc-reload" title="<?php _e( 'Apply', 'woocommerce' ); ?>"><span><?php _e( 'Apply', 'woocommerce' ); ?></span></button>
		</p>

		<p class="add_items">
			<select id="add_item_id" class="ajax_chosen_select_products_and_variations" multiple="multiple" data-placeholder="<?php _e( 'Search for a product&hellip;', 'woocommerce' ); ?>" style="width: 400px"></select>

			<button type="button" class="button add_order_item"><?php _e( 'Add item(s)', 'woocommerce' ); ?></button>
			<button type="button" class="button add_order_fee"><?php _e( 'Add fee', 'woocommerce' ); ?></button>
		</p>
		<div class="clear"></div>
		<?php
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		global $wpdb;

		// Order items + fees
		if ( isset( $_POST['order_item_id'] ) ) {

			$get_values = array( 'order_item_id', 'order_item_name', 'order_item_qty', 'line_subtotal', 'line_subtotal_tax', 'line_total', 'line_tax', 'order_item_tax_class' );

			foreach( $get_values as $value )
				$$value = isset( $_POST[ $value ] ) ? $_POST[ $value ] : array();

			foreach ( $order_item_id as $item_id ) {

				$item_id = absint( $item_id );

				if ( isset( $order_item_name[ $item_id ] ) )
					$wpdb->update(
						$wpdb->prefix . "woocommerce_order_items",
						array( 'order_item_name' => woocommerce_clean( $order_item_name[ $item_id ] ) ),
						array( 'order_item_id' => $item_id ),
						array( '%s' ),
						array( '%d' )
					);

				if ( isset( $order_item_qty[ $item_id ] ) )
			 		woocommerce_update_order_item_meta( $item_id, '_qty', apply_filters( 'woocommerce_stock_amount', $order_item_qty[ $item_id ] ) );

			 	if ( isset( $item_tax_class[ $item_id ] ) )
			 		woocommerce_update_order_item_meta( $item_id, '_tax_class', woocommerce_clean( $item_tax_class[ $item_id ] ) );

			 	if ( isset( $line_subtotal[ $item_id ] ) )
			 		woocommerce_update_order_item_meta( $item_id, '_line_subtotal', woocommerce_clean( $line_subtotal[ $item_id ] ) );

			 	if ( isset(  $line_subtotal_tax[ $item_id ] ) )
			 		woocommerce_update_order_item_meta( $item_id, '_line_subtotal_tax', woocommerce_clean( $line_subtotal_tax[ $item_id ] ) );

			 	if ( isset( $line_total[ $item_id ] ) )
			 		woocommerce_update_order_item_meta( $item_id, '_line_total', woocommerce_clean( $line_total[ $item_id ] ) );

			 	if ( isset( $line_tax[ $item_id ] ) )
			 		woocommerce_update_order_item_meta( $item_id, '_line_tax', woocommerce_clean( $line_tax[ $item_id ] ) );

			 	// Clear meta cache
			 	wp_cache_delete( $item_id, 'order_item_meta' );
			}
		}

		// Save meta
		$meta_keys 		= isset( $_POST['meta_key'] ) ? $_POST['meta_key'] : array();
		$meta_values 	= isset( $_POST['meta_value'] ) ? $_POST['meta_value'] : array();

		foreach ( $meta_keys as $id => $meta_key ) {
			$meta_value = ( empty( $meta_values[ $id ] ) && ! is_numeric( $meta_values[ $id ] ) ) ? '' : $meta_values[ $id ];
			$wpdb->update(
				$wpdb->prefix . "woocommerce_order_itemmeta",
				array(
					'meta_key' => $meta_key,
					'meta_value' => $meta_value
				),
				array( 'meta_id' => $id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}
	}
}