<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Discount Data Store
 *
 * @version  3.2.0
 * @category Class
 * @author   WooCommerce
 */
class WC_Order_Item_Discount_Data_Store extends Abstract_WC_Order_Item_Type_Data_Store implements WC_Object_Data_Store_Interface, WC_Order_Item_Type_Data_Store_Interface {

	/**
	 * Data stored in meta keys.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array( 'discount_type', 'amount', '_line_total', '_line_tax', '_line_tax_data' );

	/**
	 * Read/populate data properties specific to this order item.
	 *
	 * @since 3.0.0
	 * @param WC_Order_Item_Discount $item
	 */
	public function read( &$item ) {
		parent::read( $item );
		$id = $item->get_id();
		$item->set_props( array(
			'discount_type' => get_metadata( 'order_item', $id, 'discount_type', true ),
			'total'         => get_metadata( 'order_item', $id, '_line_total', true ),
			'taxes'         => get_metadata( 'order_item', $id, '_line_tax_data', true ),
			'amount'        => get_metadata( 'order_item', $id, 'amount', true ),
		) );
		$item->set_object_read( true );
	}

	/**
	 * Saves an item's data to the database / item meta.
	 * Ran after both create and update, so $item->get_id() will be set.
	 *
	 * @since 3.0.0
	 * @param WC_Order_Item_Discount $item
	 */
	public function save_item_data( &$item ) {
		$id          = $item->get_id();
		$save_values = array(
			'discount_type'  => $item->get_discount_type( 'edit' ),
			'_line_total'    => $item->get_total( 'edit' ),
			'_line_tax'      => $item->get_total_tax( 'edit' ),
			'_line_tax_data' => $item->get_taxes( 'edit' ),
			'amount'         => $item->get_amount( 'edit' ),
		);
		foreach ( $save_values as $key => $value ) {
			update_metadata( 'order_item', $id, $key, $value );
		}
	}
}
