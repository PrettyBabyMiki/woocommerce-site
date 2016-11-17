<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Order Data Store: Stored in CPT.
 *
 * @version  2.7.0
 * @category Class
 * @author   WooThemes
 */
class WC_Order_Data_Store_CPT extends WC_Data_Store_CPT implements WC_Object_Data_Store, WC_Order_Data_Store_Interface {

	/**
	 * If we have already saved our extra data, don't do automatic / default handling.
	 */
	protected $extra_data_saved = false;

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new order in the database.
	 * @param WC_Order $order
	 */
	public function create( &$order ) {
		$order->set_version( WC_VERSION );
		$order->set_date_created( current_time( 'timestamp' ) );
		$order->set_currency( $order->get_currency() ? $order->get_currency() : get_woocommerce_currency() );

		$id = wp_insert_post( apply_filters( 'woocommerce_new_order_data', array(
			'post_date'     => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' ) ),
			'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' ) ) ),
			'post_type'     => $order->get_type( 'edit' ),
			'post_status'   => 'wc-' . ( $order->get_status( 'edit' ) ? $order->get_status( 'edit' ) : apply_filters( 'woocommerce_default_order_status', 'pending' ) ),
			'ping_status'   => 'closed',
			'post_author'   => 1,
			'post_title'    => $order->get_post_title( 'edit' ),
			'post_password' => uniqid( 'order_' ),
			'post_parent'   => $order->get_parent_id( 'edit' ),
		) ), true );

		if ( $id && ! is_wp_error( $id ) ) {
			$order->set_id( $id );
			$this->save_items( $order );
			$this->update_post_meta( $order );
			$order->save_meta_data();
			$order->apply_changes();
			$this->clear_caches( $order );
		}
	}

	/**
	 * Method to read an order from the database.
	 * @param WC_Order
	 */
	public function read( &$order ) {
		$order->set_defaults();

		if ( ! $order->get_id() || ! ( $post_object = get_post( $order->get_id() ) ) ) {
			throw new Exception( __( 'Invalid order.', 'woocommerce' ) );
		}

		$id = $order->get_id();
		$order->set_props( array(
			'parent_id'          => $post_object->post_parent,
			'date_created'       => $post_object->post_date,
			'date_modified'      => $post_object->post_modified,
			'status'             => $post_object->post_status,
		) );
		$this->read_order_data( $order );
		$order->read_meta_data();
		$order->set_object_read( true );
	}

	/**
	 * Method to update an order in the database.
	 * @param WC_Order $order
	 */
	public function update( &$order ) {
		$order->set_version( WC_VERSION );

		wp_update_post( array(
			'ID'            => $order->get_id(),
			'post_date'     => date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' ) ),
			'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $order->get_date_created( 'edit' ) ) ),
			'post_status'   => 'wc-' . ( $order->get_status( 'edit' ) ? $order->get_status( 'edit' ) : apply_filters( 'woocommerce_default_order_status', 'pending' ) ),
			'post_parent'   => $order->get_parent_id(),
		) );

		$this->save_items( $order );
		$this->update_post_meta( $order );
		$order->save_meta_data();
		$order->apply_changes();
		$this->clear_caches( $order );
	}

	/**
	 * Method to delete an order from the database.
	 * @param WC_Order
	 * @param array $args Array of args to pass to the delete method.
	 */
	public function delete( &$order, $args = array() ) {
		$id   = $order->get_id();
		$args = wp_parse_args( $args, array(
			'force_delete' => false,
		) );

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );
			$order->set_id( 0 );
		} else {
			wp_trash_post( $id );
			$order->set_status( 'trash' );
		}
		do_action( 'woocommerce_delete_' . $post_type, $id );
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Save all order items which are part of this order. @todo
	 */
	protected function save_items( $order ) {
		// remove items
		foreach ( $this->items_to_delete as $item ) {
			$item->delete();
		}

		$this->items_to_delete = array();

		// Add/save items
		foreach ( $this->items as $item_group => $items ) {
			if ( is_array( $items ) ) {
				foreach ( $items as $item_key => $item ) {
					$item->set_order_id( $this->get_id() );
					$item_id = $item->save();

					// If ID changed (new item saved to DB)...
					if ( $item_id !== $item_key ) {
						$this->items[ $item_group ][ $item_id ] = $item;
						unset( $this->items[ $item_group ][ $item_key ] );

						// Legacy action handler
						switch ( $item_group ) {
							case 'fee_lines' :
								if ( isset( $item->legacy_fee, $item->legacy_fee_key ) ) {
									wc_do_deprecated_action( 'woocommerce_add_order_fee_meta', array( $this->get_id(), $item_id, $item->legacy_fee, $item->legacy_fee_key ), '2.7', 'Use woocommerce_new_order_item action instead.' );
								}
							break;
							case 'shipping_lines' :
								if ( isset( $item->legacy_package_key ) ) {
									wc_do_deprecated_action( 'woocommerce_add_shipping_order_item', array( $item_id, $item->legacy_package_key ), '2.7', 'Use woocommerce_new_order_item action instead.' );
								}
							break;
							case 'line_items' :
								if ( isset( $item->legacy_values, $item->legacy_cart_item_key ) ) {
									wc_do_deprecated_action( 'woocommerce_add_order_item_meta', array( $item_id, $item->legacy_values, $item->legacy_cart_item_key ), '2.7', 'Use woocommerce_new_order_item action instead.' );
								}
							break;
						}
					}
				}
			}
		}
	}

	/**
	 * Read order data. Can be overridden by child classes to load other props.
	 *
	 * @param WC_Order
	 * @since 2.7.0
	 */
	protected function read_order_data( &$order ) {
		$id = $order->get_id();

		$order->set_props( array(
			'currency'           => get_post_meta( $id, '_order_currency', true ),
			'discount_total'     => get_post_meta( $id, '_cart_discount', true ),
			'discount_tax'       => get_post_meta( $id, '_cart_discount_tax', true ),
			'shipping_total'     => get_post_meta( $id, '_order_shipping', true ),
			'shipping_tax'       => get_post_meta( $id, '_order_shipping_tax', true ),
			'cart_tax'           => get_post_meta( $id, '_order_tax', true ),
			'total'              => get_post_meta( $id, '_order_total', true ),
			'version'            => get_post_meta( $id, '_order_version', true ),
			'prices_include_tax' => metadata_exists( 'post', $id, '_prices_include_tax' ) ? 'yes' === get_post_meta( $id, '_prices_include_tax', true ) : 'yes' === get_option( 'woocommerce_prices_include_tax' ),
		) );

		// Gets extra data associated with the order if needed.
		foreach ( $order->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;
			if ( is_callable( array( $order, $function ) ) ) {
				$order->{$function}( get_post_meta( $order->get_id(), '_' . $key, true ) );
			}
		}
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the WC_Order class.
	 *
	 * @param WC_Order
	 * @since 2.7.0
	 */
	protected function update_post_meta( &$order ) {
		$updated_props     = array();
		$changed_props     = array_keys( $order->get_changes() );
		$meta_key_to_props = array(
			'_order_currency'     => 'currency',
			'_cart_discount'      => 'discount_total',
			'_cart_discount_tax'  => 'discount_tax',
			'_order_shipping'     => 'shipping_total',
			'_order_shipping_tax' => 'shipping_tax',
			'_order_tax'          => 'cart_tax',
			'_order_total'        => 'total',
			'_order_version'      => 'version',
			'_prices_include_tax' => 'prices_include_tax',
		);
		foreach ( $meta_key_to_props as $meta_key => $prop ) {
			if ( ! in_array( $prop, $changed_props ) ) {
				continue;
			}
			$value = $order->{"get_$prop"}( 'edit' );

			if ( '' !== $value ) {
				$updated = update_post_meta( $order->get_id(), $meta_key, $value );
			} else {
				$updated = delete_post_meta( $order->get_id(), $meta_key );
			}

			if ( $updated ) {
				$updated_props[] = $prop;
			}
		}

		// Update extra data associated with the order if needed.
		if ( ! $this->extra_data_saved ) {
			foreach ( $order->get_extra_data_keys() as $key ) {
				$function = 'get_' . $key;
				if ( in_array( $key, $changed_props ) && is_callable( array( $order, $function ) ) ) {
					update_post_meta( $order->get_id(), '_' . $key, $order->{$function}( 'edit' ) );
				}
			}
		}
	}

	/**
	 * Clear any caches.
	 *
	 * @param WC_Order
	 * @since 2.7.0
	 */
	protected function clear_caches( &$order ) {
		clean_post_cache( $order->get_id() );
		wc_delete_shop_order_transients( $order->get_id() );
	}
}
