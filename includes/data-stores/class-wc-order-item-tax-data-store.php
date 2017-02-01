<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Item Tax Data Store
 *
 * @version  2.7.0
 * @category Class
 * @author   WooCommerce
 */
class WC_Order_Item_Tax_Data_Store extends Abstract_WC_Order_Item_Type_Data_Store implements WC_Object_Data_Store_Interface, WC_Order_Item_Type_Data_Store_Interface {
	/**
	 * Data stored in meta keys.
	 * @since 2.7.0
	 * @var array
	 */
	protected $internal_meta_keys = array( 'rate_id', 'label', 'compound', 'tax_amount', 'shipping_tax_amount' );

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
			'rate_id'            => get_metadata( 'order_item', $id, 'rate_id', true ),
			'label'              => get_metadata( 'order_item', $id, 'label', true ),
			'compound'           => get_metadata( 'order_item', $id, 'compound', true ),
			'tax_total'          => get_metadata( 'order_item', $id, 'tax_amount', true ),
			'shipping_tax_total' => get_metadata( 'order_item', $id, 'shipping_tax_amount', true ),
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
		update_metadata( 'order_item', $id, 'rate_id', $item->get_rate_id( 'edit' ) );
		update_metadata( 'order_item', $id, 'label', $item->get_label( 'edit' ) );
		update_metadata( 'order_item', $id, 'compound', $item->get_compound( 'edit' ) );
		update_metadata( 'order_item', $id, 'tax_amount', $item->get_tax_total( 'edit' ) );
		update_metadata( 'order_item', $id, 'shipping_tax_amount', $item->get_shipping_tax_total( 'edit' ) );
		$this->clear_cache( $item );
	}
}
