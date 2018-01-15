<?php
/**
 * WC Order Item Shipping Data Store
 *
 * @version 3.0.0
 * @package data-stores
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Order_Item_Shipping_Data_Store class.
 */
class WC_Order_Item_Shipping_Data_Store extends Abstract_WC_Order_Item_Type_Data_Store implements WC_Object_Data_Store_Interface, WC_Order_Item_Type_Data_Store_Interface {

	/**
	 * Data stored in meta keys.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array( 'method_id', 'instance_id', 'cost', 'total_tax', 'taxes' );

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 3.0.0
	 * @param WC_Order_Item_Shipping $item Item to read to.
	 */
	public function read( &$item ) {
		parent::read( $item );
		$id = $item->get_id();
		$item->set_props( array(
			'method_id'   => get_metadata( 'order_item', $id, 'method_id', true ),
			'instance_id' => get_metadata( 'order_item', $id, 'instance_id', true ),
			'total'       => get_metadata( 'order_item', $id, 'cost', true ),
			'taxes'       => get_metadata( 'order_item', $id, 'taxes', true ),
		) );

		// BW compat.
		if ( '' === $item->get_instance_id() && strstr( $item->get_method_id(), ':' ) ) {
			$legacy_method_id = explode( ':', $item->get_method_id() );
			$item->set_method_id( $legacy_method_id[0] );
			$item->set_instance_id( $legacy_method_id[1] );
		}

		$item->set_object_read( true );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $id will be set.
	 *
	 * @since 3.0.0
	 * @param WC_Order_Item_Shipping $item Item to save.
	 */
	public function save_item_data( &$item ) {
		$id          = $item->get_id();
		$save_values = array(
			'method_id'   => $item->get_method_id( 'edit' ),
			'instance_id' => $item->get_instance_id( 'edit' ),
			'cost'        => $item->get_total( 'edit' ),
			'total_tax'   => $item->get_total_tax( 'edit' ),
			'taxes'       => $item->get_taxes( 'edit' ),
		);
		foreach ( $save_values as $key => $value ) {
			update_metadata( 'order_item', $id, $key, $value );
		}
	}
}
