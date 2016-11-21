<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Item
 *
 * A class which represents an item within an order and handles CRUD.
 * Uses ArrayAccess to be BW compatible with WC_Orders::get_items().
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Order_Item extends WC_Data implements ArrayAccess {

	/**
	 * Order Data array. This is the core order data exposed in APIs since 2.7.0.
	 * @since 2.7.0
	 * @var array
	 */
	protected $data = array(
		'order_id' => 0,
		'name'     => '',
	);

	/**
	 * May store an order to prevent retriving it multiple times.
	 * @var object
	 */
	protected $order;

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 * @var string
	 */
	protected $cache_group = 'order_itemmeta';

	/**
	 * Meta type. This should match up with
	 * the types avaiable at https://codex.wordpress.org/Function_Reference/add_metadata.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 */
	protected $meta_type = 'order_item';

	/**
	 * Constructor.
	 * @param int|object|array $item ID to load from the DB, or WC_Order_Item Object
	 */
	public function __construct( $item = 0 ) {
		$this->data = array_merge( $this->data, $this->extra_data );
		parent::__construct( $item );

		if ( $item instanceof WC_Order_Item ) {
			$this->set_id( $item->get_id() );
		} elseif ( is_numeric( $item ) && $item > 0 ) {
			$this->set_id( $item );
		} else {
			$this->set_object_read( true );
		}

		$type = 'line_item' === $this->get_type() ? 'product' : $this->get_type();
		$this->data_store = WC_Data_Store::load( 'order-item-' . $type );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order ID this meta belongs to.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_order_id( $context = 'view' ) {
		return $this->get_prop( 'order_id', $context );
	}

	/**
	 * Get order item name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get order item type. Overridden by child classes.
	 *
	 * @return string
	 */
	public function get_type() {
		return;
	}

	/**
	 * Get quantity.
	 * @return int
	 */
	public function get_quantity() {
		return 1;
	}

	/**
	 * Get parent order object.
	 * @return int
	 */
	public function get_order() {
		if ( ! $this->order ) {
		 	$this->order = wc_get_order( $this->get_order_id() );
		}
		return $this->order;
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set order ID.
	 *
	 * @param  int $value
	 * @throws WC_Data_Exception
	 */
	public function set_order_id( $value ) {
		$this->set_prop( 'order_id', absint( $value ) );
	}

	/**
	 * Set order item name.
	 *
	 * @param  string $value
	 * @throws WC_Data_Exception
	 */
	public function set_name( $value ) {
		$this->set_prop( 'name', wc_clean( $value ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Other Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Type checking
	 * @param  string|array  $Type
	 * @return boolean
	 */
	public function is_type( $type ) {
		return is_array( $type ) ? in_array( $this->get_type(), $type ) : $type === $this->get_type();
	}

	/**
	 * Save properties specific to this order item.
	 * @return int Item ID
	 */
	public function save() {
		parent::save();

		if ( $this->data_store ) {
			$this->data_store->save_item_data( $this );
		}

		$this->apply_changes();

		return $this->get_id();
	}

	/*
	|--------------------------------------------------------------------------
	| Meta Data Handling
	|--------------------------------------------------------------------------
	*/

	/**
	 * Expands things like term slugs before return.
	 * @param string $hideprefix (default: _)
	 * @return array
	 */
	public function get_formatted_meta_data( $hideprefix = '_' ) {
		$formatted_meta = array();
		$meta_data      = $this->get_meta_data();

		foreach ( $meta_data as $meta ) {
			if ( "" === $meta->value || is_serialized( $meta->value ) || ( ! empty( $hideprefix ) && substr( $meta->key, 0, 1 ) === $hideprefix ) ) {
				continue;
			}

			$meta->key     = rawurldecode( $meta->key );
			$meta->value   = rawurldecode( $meta->value );
			$attribute_key = str_replace( 'attribute_', '', $meta->key );
			$display_key   = wc_attribute_label( $attribute_key, is_callable( array( $this, 'get_product' ) ) ? $this->get_product() : false );
			$display_value = $meta->value;

			if ( taxonomy_exists( $attribute_key ) ) {
				$term = get_term_by( 'slug', $meta->value, $attribute_key );
				if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
					$display_value = $term->name;
				}
			}

			$formatted_meta[ $meta->id ] = (object) array(
				'key'           => $meta->key,
				'value'         => $meta->value,
				'display_key'   => apply_filters( 'woocommerce_order_item_display_meta_key', $display_key ),
				'display_value' => apply_filters( 'woocommerce_order_item_display_meta_value', wpautop( make_clickable( $display_value ) ) ),
			);
		}

		return $formatted_meta;
	}

	/*
	|--------------------------------------------------------------------------
	| Array Access Methods
	|--------------------------------------------------------------------------
	|
	| For backwards compat with legacy arrays.
	|
	*/

	/**
	 * offsetSet for ArrayAccess
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		if ( 'item_meta_array' === $offset ) {
			foreach ( $value as $meta_id => $meta ) {
				$this->update_meta_data( $meta->key, $meta->value, $meta_id );
			}
			return;
		}

		if ( array_key_exists( $offset, $this->data ) ) {
			$this->data[ $offset ] = $value;
		}

		$this->update_meta_data( '_' . $offset, $value );
	}

	/**
	 * offsetUnset for ArrayAccess
	 * @param string $offset
	 */
	public function offsetUnset( $offset ) {
		if ( 'item_meta_array' === $offset || 'item_meta' === $offset ) {
			$this->meta_data = array();
			return;
		}

		if ( array_key_exists( $offset, $this->data ) ) {
			unset( $this->data[ $offset ] );
		}

		$this->delete_meta_data( '_' . $offset );
	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		if ( 'item_meta_array' === $offset || 'item_meta' === $offset || array_key_exists( $offset, $this->data ) ) {
			return true;
		}
		return array_key_exists( '_' . $offset, wp_list_pluck( $this->meta_data, 'value', 'key' ) );
	}

	/**
	 * offsetGet for ArrayAccess
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		if ( 'item_meta_array' === $offset ) {
			$return = array();

			foreach ( $this->meta_data as $meta ) {
				$return[ $meta->id ] = $meta;
			}

			return $return;
		}

		$meta_values = wp_list_pluck( $this->meta_data, 'value', 'key' );

		if ( 'item_meta' === $offset ) {
			return $meta_values;
		} elseif ( array_key_exists( $offset, $this->data ) ) {
			return $this->data[ $offset ];
		} elseif ( array_key_exists( '_' . $offset, $meta_values ) ) {
			// Item meta was expanded in previous versions, with prefixes removed. This maintains support.
			return $meta_values[ '_' . $offset ];
		}

		return null;
	}
}
