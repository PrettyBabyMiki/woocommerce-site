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
 * @version     3.0.0
 * @since       3.0.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Order_Item extends WC_Data implements ArrayAccess {

	/**
	 * Order Data array. This is the core order data exposed in APIs since 3.0.0.
	 * @since 3.0.0
	 * @var array
	 */
	protected $data = array(
		'order_id' => 0,
		'name'     => '',
	);

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 * @var string
	 */
	protected $cache_group = 'order-items';

	/**
	 * Meta type. This should match up with
	 * the types available at https://codex.wordpress.org/Function_Reference/add_metadata.
	 * WP defines 'post', 'user', 'comment', and 'term'.
	 */
	protected $meta_type = 'order_item';

	/**
	 * This is the name of this object type.
	 * @var string
	 */
	protected $object_type = 'order_item';

	/**
	 * Constructor.
	 * @param int|object|array $item ID to load from the DB, or WC_Order_Item Object
	 */
	public function __construct( $item = 0 ) {
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

	/**
	 * Merge changes with data and clear.
	 * Overrides WC_Data::apply_changes.
	 * array_replace_recursive does not work well for order items because it merges taxes instead
	 * of replacing them.
	 *
	 * @since 3.2.0
	 */
	public function apply_changes() {
		if ( function_exists( 'array_replace' ) ) {
			$this->data = array_replace( $this->data, $this->changes );
		} else { // PHP 5.2 compatibility.
			foreach ( $this->changes as $key => $change ) {
				$this->data[ $key ] = $change;
			}
		}
		$this->changes = array();
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
	 * Get tax status.
	 * @return string
	 */
	public function get_tax_status() {
		return 'taxable';
	}

	/**
	 * Get tax class.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_tax_class() {
		return '';
	}

	/**
	 * Get parent order object.
	 * @return WC_Order
	 */
	public function get_order() {
		return wc_get_order( $this->get_order_id() );
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
	 * Type checking.
	 *
	 * @param  string|array  $type
	 * @return boolean
	 */
	public function is_type( $type ) {
		return is_array( $type ) ? in_array( $this->get_type(), $type ) : $type === $this->get_type();
	}

	/**
	 * Calculate item taxes.
	 *
	 * @since  3.2.0
	 * @param  array $calculate_tax_for Location data to get taxes for. Required.
	 * @param  bool $is_vat_exempt Indicates if the order should not have VAT applied to it.
	 * @return bool  True if taxes were calculated.
	 */
	public function calculate_taxes( $calculate_tax_for = array(), $is_vat_exempt = false ) {
		if ( ! isset( $calculate_tax_for['country'], $calculate_tax_for['state'], $calculate_tax_for['postcode'], $calculate_tax_for['city'] ) ) {
			return false;
		}
		if ( ! $is_vat_exempt && '0' !== $this->get_tax_class() && 'taxable' === $this->get_tax_status() && wc_tax_enabled() ) {
			$calculate_tax_for['tax_class'] = $this->get_tax_class();
			$tax_rates                      = WC_Tax::find_rates( $calculate_tax_for );
			$taxes                          = WC_Tax::calc_tax( $this->get_total(), $tax_rates, false );

			if ( method_exists( $this, 'get_subtotal' ) ) {
				$subtotal_taxes = WC_Tax::calc_tax( $this->get_subtotal(), $tax_rates, false );
				$this->set_taxes( array( 'total' => $taxes, 'subtotal' => $subtotal_taxes ) );
			} else {
				$this->set_taxes( array( 'total' => $taxes ) );
			}
		} else {
			$this->set_taxes( false );
		}
		return true;
	}

	/*
	|--------------------------------------------------------------------------
	| Meta Data Handling
	|--------------------------------------------------------------------------
	*/

	/**
	 * Expands things like term slugs before return.
	 *
	 * @param string $hideprefix  Meta data prefix, (default: _).
	 * @param bool   $include_all Include all meta data, this stop skip items with values already in the product name.
	 * @return array
	 */
	public function get_formatted_meta_data( $hideprefix = '_', $include_all = false ) {
		$formatted_meta    = array();
		$meta_data         = $this->get_meta_data();
		$hideprefix_length = ! empty( $hideprefix ) ? strlen( $hideprefix ) : 0;
		$product           = is_callable( array( $this, 'get_product' ) ) ? $this->get_product() : false;
		$order_item_name   = $this->get_name();

		foreach ( $meta_data as $meta ) {
			if ( empty( $meta->id ) || '' === $meta->value || ! is_scalar( $meta->value ) || ( $hideprefix_length && substr( $meta->key, 0, $hideprefix_length ) === $hideprefix ) ) {
				continue;
			}

			$meta->key     = rawurldecode( (string) $meta->key );
			$meta->value   = rawurldecode( (string) $meta->value );
			$attribute_key = str_replace( 'attribute_', '', $meta->key );
			$display_key   = wc_attribute_label( $attribute_key, $product );
			$display_value = $meta->value;

			if ( taxonomy_exists( $attribute_key ) ) {
				$term = get_term_by( 'slug', $meta->value, $attribute_key );
				if ( ! is_wp_error( $term ) && is_object( $term ) && $term->name ) {
					$display_value = $term->name;
				}
			}

			// Skip items with values already in the product details area of the product name.
			if ( ! $include_all && $product && $product->is_type( 'variation' ) && wc_is_attribute_in_product_name( $display_value, $order_item_name ) ) {
				continue;
			}

			$formatted_meta[ $meta->id ] = (object) array(
				'key'           => $meta->key,
				'value'         => $meta->value,
				'display_key'   => apply_filters( 'woocommerce_order_item_display_meta_key', $display_key, $meta, $this ),
				'display_value' => wpautop( make_clickable( apply_filters( 'woocommerce_order_item_display_meta_value', $display_value, $meta, $this ) ) ),
			);
		}

		return apply_filters( 'woocommerce_order_item_get_formatted_meta_data', $formatted_meta, $this );
	}

	/*
	|--------------------------------------------------------------------------
	| Array Access Methods
	|--------------------------------------------------------------------------
	|
	| For backwards compatibility with legacy arrays.
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
			$setter = "set_$offset";
			if ( is_callable( array( $this, $setter ) ) ) {
				$this->$setter( $value );
			}
			return;
		}

		$this->update_meta_data( $offset, $value );
	}

	/**
	 * offsetUnset for ArrayAccess
	 * @param string $offset
	 */
	public function offsetUnset( $offset ) {
		$this->maybe_read_meta_data();

		if ( 'item_meta_array' === $offset || 'item_meta' === $offset ) {
			$this->meta_data = array();
			return;
		}

		if ( array_key_exists( $offset, $this->data ) ) {
			unset( $this->data[ $offset ] );
		}

		if ( array_key_exists( $offset, $this->changes ) ) {
			unset( $this->changes[ $offset ] );
		}

		$this->delete_meta_data( $offset );
	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		$this->maybe_read_meta_data();
		if ( 'item_meta_array' === $offset || 'item_meta' === $offset || array_key_exists( $offset, $this->data ) ) {
			return true;
		}
		return array_key_exists( $offset, wp_list_pluck( $this->meta_data, 'value', 'key' ) ) || array_key_exists( '_' . $offset, wp_list_pluck( $this->meta_data, 'value', 'key' ) );
	}

	/**
	 * offsetGet for ArrayAccess
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		$this->maybe_read_meta_data();

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
		} elseif ( 'type' === $offset ) {
			return $this->get_type();
		} elseif ( array_key_exists( $offset, $this->data ) ) {
			$getter = "get_$offset";
			if ( is_callable( array( $this, $getter ) ) ) {
				return $this->$getter();
			}
		} elseif ( array_key_exists( '_' . $offset, $meta_values ) ) {
			// Item meta was expanded in previous versions, with prefixes removed. This maintains support.
			return $meta_values[ '_' . $offset ];
		} elseif ( array_key_exists( $offset, $meta_values ) ) {
			return $meta_values[ $offset ];
		}

		return null;
	}
}
