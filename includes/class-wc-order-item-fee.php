<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Line Item (fee).
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Order_Item_Fee extends WC_Order_Item {

	/**
	 * Data properties of this order item object.
	 * @since 2.7.0
	 * @var array
	 */
	protected $_data = array(
		'order_id'   => 0,
		'id'         => 0,
		'name'       => '',
		'tax_class'  => '',
		'tax_status' => 'taxable',
		'total'      => '',
		'total_tax'  => '',
		'taxes'      => array(
			'total' => array()
		)
	);

	/**
	 * offsetGet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		if ( 'line_total' === $offset ) {
			$offset = 'total';
		} elseif ( 'line_tax' === $offset ) {
			$offset = 'total_tax';
		} elseif ( 'line_tax_data' === $offset ) {
			$offset = 'taxes';
		}
		return parent::offsetGet( $offset );
	}

	/**
	 * offsetSet for ArrayAccess/Backwards compatibility.
	 * @deprecated Add deprecation notices in future release.
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		if ( 'line_total' === $offset ) {
			$offset = 'total';
		} elseif ( 'line_tax' === $offset ) {
			$offset = 'total_tax';
		} elseif ( 'line_tax_data' === $offset ) {
			$offset = 'taxes';
		}
		parent::offsetSet( $offset, $value );
	}

	/**
	 * offsetExists for ArrayAccess
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		if ( in_array( $offset, array( 'line_total', 'line_tax', 'line_tax_data' ) ) ) {
			return true;
		}
		return parent::offsetExists( $offset );
	}

	/**
	 * Read/populate data properties specific to this order item.
	 */
	public function read( $id ) {
		parent::read( $id );
		if ( $this->get_id() ) {
			$this->set_tax_class( get_metadata( 'order_item', $this->get_id(), '_tax_class', true ) );
			$this->set_tax_status( get_metadata( 'order_item', $this->get_id(), '_tax_status', true ) );
			$this->set_total( get_metadata( 'order_item', $this->get_id(), '_line_total', true ) );
			$this->set_total_tax( get_metadata( 'order_item', $this->get_id(), '_line_tax', true ) );
			$this->set_taxes( get_metadata( 'order_item', $this->get_id(), '_line_tax_data', true ) );
		}
	}

	/**
	 * Save properties specific to this order item.
	 * @return int Item ID
	 */
	public function save() {
		parent::save();
		if ( $this->get_id() ) {
			wc_update_order_item_meta( $this->get_id(), '_tax_class', $this->get_tax_class() );
			wc_update_order_item_meta( $this->get_id(), '_tax_status', $this->get_tax_status() );
			wc_update_order_item_meta( $this->get_id(), '_line_total', $this->get_total() );
			wc_update_order_item_meta( $this->get_id(), '_line_tax', $this->get_total_tax() );
			wc_update_order_item_meta( $this->get_id(), '_line_tax_data', $this->get_taxes() );
		}

		return $this->get_id();
	}

	/**
	 * Internal meta keys we don't want exposed as part of meta_data.
	 * @return array()
	 */
	protected function get_internal_meta_keys() {
		return array( '_tax_class', '_tax_status', '_line_subtotal', '_line_subtotal_tax', '_line_total', '_line_tax', '_line_tax_data' );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set tax class.
	 * @param string $value
	 * @return bool|WP_Error Returns sucess true or false/WP Error on failure.
	 */
	public function set_tax_class( $value ) {
		if ( $value && ! in_array( $value, WC_Tax::get_tax_classes() ) ) {
			return $this->error( 'Invalid tax class', $value );
		}
		return $this->set_prop( 'tax_class', $value );
	}

	/**
	 * Set tax_status.
	 * @param string $value
	 * @return bool|WP_Error Returns sucess true or false/WP Error on failure.
	 */
	public function set_tax_status( $value ) {
		if ( in_array( $value, array( 'taxable', 'none' ) ) ) {
			return $this->set_prop( 'tax_status', $value );
		} else {
			return $this->set_prop( 'tax_status', 'taxable' );
		}
	}

	/**
	 * Set total.
	 * @param string $value
	 * @return bool|WP_Error Returns sucess true or false/WP Error on failure.
	 */
	public function set_total( $value ) {
		return $this->set_prop( 'total', wc_format_decimal( $value ) );
	}

	/**
	 * Set total tax.
	 * @param string $value
	 * @return bool|WP_Error Returns sucess true or false/WP Error on failure.
	 */
	public function set_total_tax( $value ) {
		return $this->set_prop( 'total_tax', wc_format_decimal( $value ) );
	}

	/**
	 * Set taxes.
	 *
	 * This is an array of tax ID keys with total amount values.
	 * @param array $raw_tax_data
	 * @return bool|WP_Error Returns sucess true or false/WP Error on failure.
	 */
	public function set_taxes( $raw_tax_data ) {
		$raw_tax_data = maybe_unserialize( $raw_tax_data );
		$tax_data     = array(
			'total' => array(),
		);
		if ( ! empty( $raw_tax_data['total'] ) ) {
			$tax_data['total'] = array_map( 'wc_format_decimal', $raw_tax_data['total'] );
		}
		return $this->set_prop( 'taxes', $tax_data );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order item name.
	 * @return string
	 */
	public function get_name() {
		return $this->get_prop( 'name' ) ? $this->get_prop( 'name' ) : __( 'Fee', 'woocommerce' );
	}

	/**
	 * Get order item type.
	 * @return string
	 */
	public function get_type() {
		return 'fee';
	}

	/**
	 * Get tax class.
	 * @return string
	 */
	public function get_tax_class() {
		return $this->get_prop( 'tax_class' );
	}

	/**
	 * Get tax status.
	 * @return string
	 */
	public function get_tax_status() {
		return $this->get_prop( 'tax_status' );
	}

	/**
	 * Get total fee.
	 * @return string
	 */
	public function get_total() {
		return wc_format_decimal( $this->get_prop( 'total' ) );
	}

	/**
	 * Get total tax.
	 * @return string
	 */
	public function get_total_tax() {
		return wc_format_decimal( $this->get_prop( 'total_tax' ) );
	}

	/**
	 * Get fee taxes.
	 * @return array
	 */
	public function get_taxes() {
		return $this->get_prop( 'taxes' );
	}
}
