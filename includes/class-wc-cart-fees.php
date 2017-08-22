<?php
/**
 * Cart fees API.
 *
 * Developers can add fees to the cart via WC()->cart->fees_api which will reference this class.
 *
 * Fees can be added/removed at any time, however, before cart total calculations fees are purged
 * so we suggest using the action woocommerce_cart_calculate_fees or woocommerce_before_calculate_totals.
 *
 * @author  Automattic
 * @package WooCommerce/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Cart_Fees class.
 *
 * @since 3.2.0
 */
final class WC_Cart_Fees {

	/**
	 * An array of fee objects.
	 *
	 * @var object[]
	 */
	private $fees = array();

	/**
	 * Reference to cart object.
	 *
	 * @since 3.2.0
	 * @var array
	 */
	private $cart;

	/**
	 * New fees are made out of these props.
	 *
	 * @var array
	 */
	private $default_fee_props = array(
		'id'        => '',
		'name'      => '',
		'tax_class' => '',
		'taxable'   => false,
		'amount'    => 0,
	);

	/**
	 * Constructor. Reference to the cart.
	 *
	 * @since 3.2.0
	 * @param object $cart Cart object.
	 */
	public function __construct( &$cart = null ) {
		$this->cart = $cart;
		add_action( 'woocommerce_cart_emptied', array( $this, 'remove_all_fees' ) );
		add_action( 'woocommerce_cart_reset', array( $this, 'remove_all_fees' ) );
	}

	/**
	 * Add a fee. Fee IDs must be unique.
	 *
	 * @since 3.2.0
	 * @param array $args Array of fee properties.
	 * @return object Either a fee object if added, or a WP_Error if it failed.
	 */
	public function add_fee( $args = array() ) {
		$fee_props            = (object) wp_parse_args( $args, $this->default_fee_props );
		$fee_props->name      = $fee_props->name ? $fee_props->name : __( 'Fee', 'woocommerce' );
		$fee_props->tax_class = in_array( $fee_props->tax_class, WC_Tax::get_tax_classes(), true ) ? $fee_props->tax_class: '';
		$fee_props->taxable   = wc_string_to_bool( $fee_props->taxable );
		$fee_props->amount    = wc_format_decimal( $fee_props->amount );

		if ( empty( $fee_props->id ) ) {
			$fee_props->id = $this->generate_id( $fee_props );
		}

		if ( array_key_exists( $fee_props->id, $this->fees ) ) {
			return new WP_Error( 'fee_exists', __( 'Fee has already been added.', 'woocommerce' ) );
		}

		return $this->fees[ $fee_props->id ] = $fee_props;
	}

	/**
	 * Get fees.
	 *
	 * @return array
	 */
	public function get_fees() {
		uasort( $this->fees, array( $this, 'sort_fees_callback' ) );

		return $this->fees;
	}

	/**
	 * Set fees.
	 *
	 * @param object[] $raw_fees Array of fees.
	 */
	public function set_fees( $raw_fees = array() ) {
		$this->fees = array();

		foreach ( $raw_fees as $raw_fee ) {
			$this->add_fee( $raw_fee );
		}
	}

	/**
	 * Remove all fees.
	 *
	 * @since 3.2.0
	 */
	public function remove_all_fees() {
		$this->set_fees();
	}

	/**
	 * Sort fees by amount.
	 *
	 * @param WC_Coupon $a Coupon object.
	 * @param WC_Coupon $b Coupon object.
	 * @return int
	 */
	protected function sort_fees_callback( $a, $b ) {
		return ( $a->amount > $b->amount ) ? -1 : 1;
	}

	/**
	 * Generate a unique ID for the fee being added.
	 *
	 * @param string $fee Fee object.
	 * @return string fee key.
	 */
	private function generate_id( $fee ) {
		return sanitize_title( $fee->name );
	}
}
