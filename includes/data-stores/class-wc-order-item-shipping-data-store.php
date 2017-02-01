<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Shipping Data Store
 *
 * @version  2.7.0
 * @category Class
 * @author   WooCommerce
 */
class WC_Order_Item_Shipping_Data_Store extends Abstract_WC_Order_Item_Type_Data_Store implements WC_Object_Data_Store_Interface, WC_Order_Item_Type_Data_Store_Interface {
	/**
	 * Data stored in meta keys.
	 * @since 2.7.0
	 * @var array
	 */
	protected $internal_meta_keys = array( 'method_id', 'cost', 'total_tax', 'taxes' );

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function read( &$item ) {
		parent::read( $item );
		$id = $item->get_id();
		$item->set_props( array(
			'method_id' => get_metadata( 'order_item', $id, 'method_id', true ),
			'total'     => get_metadata( 'order_item', $id, 'cost', true ),
			'taxes'     => get_metadata( 'order_item', $id, 'taxes', true ),
		) );
		$item->set_object_read( true );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $id will be set.
	 *
	 * @since 2.7.0
	 * @param WC_Order_Item $item
	 */
	public function save_item_data( &$item ) {
		$id = $item->get_id();
		wc_update_order_item_meta( $id, 'method_id', $item->get_method_id( 'edit' ) );
		wc_update_order_item_meta( $id, 'cost', $item->get_total( 'edit' ) );
		wc_update_order_item_meta( $id, 'total_tax', $item->get_total_tax( 'edit' ) );
		wc_update_order_item_meta( $id, 'taxes', $item->get_taxes( 'edit' ) );
	}
}
