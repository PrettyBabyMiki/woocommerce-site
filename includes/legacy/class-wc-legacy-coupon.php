<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy Coupon.
 *
 * Legacy and deprecated functions are here to keep the WC_Legacy_Coupon class clean.
 * This class will be removed in future versions.
 *
 * @class       WC_Legacy_Coupon
 * @version     2.7.0
 * @package     WooCommerce/Classes
 * @category    Class
 * @author      WooThemes
 */
abstract class WC_Legacy_Coupon extends WC_Data {

	/**
	 * Magic __isset method for backwards compatibility. Legacy properties which could be accessed directly in the past.
	 * @param  string $key
	 * @return bool
	 */
	public function __isset( $key ) {
		$legacy_keys = array(
			'id',
			'exists',
			'coupon_custom_fields',
			'type',
			'discount_type',
			'amount',
			'code',
			'individual_use',
			'product_ids',
			'exclude_product_ids',
			'usage_limit',
			'usage_limit_per_user',
			'limit_usage_to_x_items',
			'usage_count',
			'expiry_date',
			'product_categories',
			'exclude_product_categories',
			'minimum_amount',
			'maximum_amount',
			'customer_email',
		);
		if ( in_array( $key, $legacy_keys ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Magic __get method for backwards compatibility. Maps legacy vars to new getters.
	 * @param  string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		_doing_it_wrong( $key, 'Coupon properties should not be accessed directly.', '2.7' );

		switch ( $key ) {
			case 'id' :
				$value = $this->get_id();
			break;
			case 'exists' :
				$value = ( $this->get_id() > 0 ) ? true : false;
			break;
			case 'coupon_custom_fields' :
				$legacy_custom_fields = array();
				$custom_fields = $this->get_id() ? $this->get_meta_data() : array();
				if ( ! empty( $custom_fields ) ) {
					foreach ( $custom_fields as  $cf_value ) {
						// legacy only supports 1 key
						$legacy_custom_fields[ $cf_value->key ][0] = $cf_value->value;
					}
				}
				$value = $legacy_custom_fields;
			break;
			case 'type' :
			case 'discount_type' :
				$value = $this->get_discount_type();
			break;
			case 'amount' :
				$value = $this->get_amount();
			break;
			case 'code' :
				$value = $this->get_code();
			break;
			case 'individual_use' :
				$value = ( true === $this->get_individual_use() ) ? 'yes' : 'no';
			break;
			case 'product_ids' :
				$value = $this->get_product_ids();
			break;
			case 'exclude_product_ids' :
				$value = $this->get_excluded_product_ids();
			break;
			case 'usage_limit' :
				$value = $this->get_usage_limit();
			break;
			case 'usage_limit_per_user' :
				$value = $this->get_usage_limit_per_user();
			break;
			case 'limit_usage_to_x_items' :
				$value = $this->get_limit_usage_to_x_items();
			break;
			case 'usage_count' :
				$value = $this->get_usage_count();
			break;
			case 'expiry_date' :
				$value = $this->get_expiry_date();
			break;
			case 'product_categories' :
				$value = $this->get_product_categories();
			break;
			case 'exclude_product_categories' :
				$value = $this->get_excluded_product_categories();
			break;
			case 'minimum_amount' :
				$value = $this->get_minimum_amount();
			break;
			case 'maximum_amount' :
				$value = $this->get_maximum_amount();
			break;
			case 'customer_email' :
				$value = $this->get_email_restrictions();
			break;
			default :
				$value = '';
			break;
		}

		return $value;
	}

	/**
	 * Format loaded data as array.
	 * @param  string|array $array
	 * @return array
	 */
	public function format_array( $array ) {
		_deprecated_function( 'format_array', '2.7', '' );
		if ( ! is_array( $array ) ) {
			if ( is_serialized( $array ) ) {
				$array = maybe_unserialize( $array );
			} else {
				$array = explode( ',', $array );
			}
		}
		return array_filter( array_map( 'trim', array_map( 'strtolower', $array ) ) );
	}


	/**
	 * Check if coupon needs applying before tax.
	 *
	 * @return bool
	 */
	public function apply_before_tax() {
		_deprecated_function( 'apply_before_tax', '2.7', '' );
		return true;
	}

	/**
	 * Check if a coupon enables free shipping.
	 *
	 * @return bool
	 */
	public function enable_free_shipping() {
		_deprecated_function( 'enable_free_shipping', '2.7', 'get_free_shipping' );
		return $this->get_free_shipping();
	}

	/**
	 * Check if a coupon excludes sale items.
	 *
	 * @return bool
	 */
	public function exclude_sale_items() {
		_deprecated_function( 'exclude_sale_items', '2.7', 'get_exclude_sale_items' );
		return $this->get_exclude_sale_items();
	}

}
