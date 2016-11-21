<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order Line Item (tax).
 *
 * @version     2.7.0
 * @since       2.7.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Order_Item_Tax extends WC_Order_Item {

	/**
	 * Order Data array. This is the core order data exposed in APIs since 2.7.0.
	 * @since 2.7.0
	 * @var array
	 */
	protected $extra_data = array(
		'rate_code'          => '',
		'rate_id'            => 0,
		'label'              => '',
		'compound'           => false,
		'tax_total'          => 0,
		'shipping_tax_total' => 0,
	);

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set order item name.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_name( $value ) {
		$this->set_rate_code( $value );
	}

	/**
	 * Set item name.
	 *
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_rate_code( $value ) {
		$this->set_prop( 'rate_code', wc_clean( $value ) );
	}

	/**
	 * Set item name.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_label( $value ) {
		$this->set_prop( 'label', wc_clean( $value ) );
	}

	/**
	 * Set tax rate id.
	 * @param int $value
	 * @throws WC_Data_Exception
	 */
	public function set_rate_id( $value ) {
		$this->set_prop( 'rate_id', absint( $value ) );
	}

	/**
	 * Set tax total.
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_tax_total( $value ) {
		$this->set_prop( 'tax_total', wc_format_decimal( $value ) );
	}

	/**
	 * Set shipping_tax_total
	 * @param string $value
	 * @throws WC_Data_Exception
	 */
	public function set_shipping_tax_total( $value ) {
		$this->set_prop( 'shipping_tax_total', wc_format_decimal( $value ) );
	}

	/**
	 * Set compound
	 * @param bool $value
	 * @throws WC_Data_Exception
	 */
	public function set_compound( $value ) {
		$this->set_prop( 'compound', (bool) $value );
	}

	/**
	 * Set properties based on passed in tax rate by ID.
	 * @param int $tax_rate_id
	 * @throws WC_Data_Exception
	 */
	public function set_rate( $tax_rate_id ) {
		$this->set_rate_id( $tax_rate_id );
		$this->set_rate_code( WC_Tax::get_rate_code( $tax_rate_id ) );
		$this->set_label( WC_Tax::get_rate_code( $tax_rate_id ) );
		$this->set_compound( WC_Tax::get_rate_code( $tax_rate_id ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get order item type.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return 'tax';
	}

	/**
	 * Get rate code/name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_rate_code( $context );
	}

	/**
	 * Get rate code/name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_rate_code( $context = 'view' ) {
		return $this->get_prop( 'rate_code', $context );
	}

	/**
	 * Get label.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_label( $context = 'view' ) {
		$label = $this->get_prop( 'label', $context );
		return $label ? $label : __( 'Tax', 'woocommerce' );
	}

	/**
	 * Get tax rate ID.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_rate_id( $context = 'view' ) {
		return $this->get_prop( 'rate_id', $context );
	}

	/**
	 * Get tax_total
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_tax_total( $context = 'view' ) {
		return $this->get_prop( 'tax_total', $context );
	}

	/**
	 * Get shipping_tax_total
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_shipping_tax_total( $context = 'view' ) {
		return $this->get_prop( 'shipping_tax_total', $context );
	}

	/**
	 * Get compound.
	 *
	 * @param  string $context
	 * @return bool
	 */
	public function get_compound( $context = 'view' ) {
		return $this->get_prop( 'compound', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Other
	|--------------------------------------------------------------------------
	*/

	/**
	 * Internal meta keys we don't want exposed as part of meta_data.
	 * @return array()
	 */
	protected function get_internal_meta_keys() {
		return array( 'rate_id', 'label', 'compound', 'tax_amount', 'shipping_tax_amount' );
	}

	/**
	 * Is this a compound tax rate?
	 * @return boolean
	 */
	public function is_compound() {
		return $this->get_compound();
	}
}
