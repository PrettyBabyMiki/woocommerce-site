<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Fee Data Store
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Item_Fee_Data_Store extends WC_Order_Item_Data_Store implements WC_Object_Data_Store {

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function read( &$item ) {
		parent::read( $item );
		$item->set_props( array(
			'tax_class'  => get_metadata( 'order_item', $item->get_id(), '_tax_class', true ),
			'tax_status' => get_metadata( 'order_item', $item->get_id(), '_tax_status', true ),
			'total'      => get_metadata( 'order_item', $item->get_id(), '_line_total', true ),
			'taxes'      => get_metadata( 'order_item', $item->get_id(), '_line_tax_data', true ),
		) );
	}

	public function save_item_data( &$item ) {
		wc_update_order_item_meta( $item->get_id(), '_tax_class', $item->get_tax_class() );
		wc_update_order_item_meta( $item->get_id(), '_tax_status', $item->get_tax_status() );
		wc_update_order_item_meta( $item->get_id(), '_line_total', $item->get_total() );
		wc_update_order_item_meta( $item->get_id(), '_line_tax', $item->get_total_tax() );
		wc_update_order_item_meta( $item->get_id(), '_line_tax_data', $item->get_taxes() );
	}

}
