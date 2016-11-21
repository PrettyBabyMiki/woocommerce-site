<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Product Data Store
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Item_Product_Data_Store extends WC_Order_Item_Data_Store implements WC_Object_Data_Store {

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function read( &$item ) {
		parent::read( $item );
		$item->set_props( array(
			'product_id'   => get_metadata( 'order_item', $item->get_id(), '_product_id', true ),
			'variation_id' => get_metadata( 'order_item', $item->get_id(), '_variation_id', true ),
			'quantity'     => get_metadata( 'order_item', $item->get_id(), '_qty', true ),
			'tax_class'    => get_metadata( 'order_item', $item->get_id(), '_tax_class', true ),
			'subtotal'     => get_metadata( 'order_item', $item->get_id(), '_line_subtotal', true ),
			'total'        => get_metadata( 'order_item', $item->get_id(), '_line_total', true ),
			'taxes'        => get_metadata( 'order_item', $item->get_id(), '_line_tax_data', true ),
		) );
	}

	/**
	 * Save properties specific to this order item.
	 *
	 * @return int Item ID
	 */
	public function save_item_data( &$item ) {
		wc_update_order_item_meta( $item->get_id(), '_product_id', $item->get_product_id() );
		wc_update_order_item_meta( $item->get_id(), '_variation_id', $item->get_variation_id() );
		wc_update_order_item_meta( $item->get_id(), '_qty', $item->get_quantity() );
		wc_update_order_item_meta( $item->get_id(), '_tax_class', $item->get_tax_class() );
		wc_update_order_item_meta( $item->get_id(), '_line_subtotal', $item->get_subtotal() );
		wc_update_order_item_meta( $item->get_id(), '_line_subtotal_tax', $item->get_subtotal_tax() );
		wc_update_order_item_meta( $item->get_id(), '_line_total', $item->get_total() );
		wc_update_order_item_meta( $item->get_id(), '_line_tax', $item->get_total_tax() );
		wc_update_order_item_meta( $item->get_id(), '_line_tax_data', $item->get_taxes() );
	}

	public function get_download_ids( $item, $order ) {
		global $wpdb;
		return $wpdb->get_col(
			$wpdb->prepare(
				"SELECT download_id FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions WHERE user_email = %s AND order_key = %s AND product_id = %d ORDER BY permission_id",
				$order->get_billing_email(),
				$order->get_order_key(),
				$item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id()
			)
		);
	}

}
