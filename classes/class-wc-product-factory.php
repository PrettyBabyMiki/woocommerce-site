<?php

/**
 * Product Factory Class
 *
 * The WooCommerce product factory creating the right product object
 *
 * @class 		WC_Product_Factory
 * @version		2.0.0
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		WooThemes
 */
class WC_Product_Factory {

	/**
	 * get_product function.
	 *
	 * @access public
	 * @param bool $the_product (default: false)
	 * @param array $args (default: array())
	 * @return WC_Product_Simple
	 */
	public function get_product( $the_product = false, $args = array() ) {
		global $post;

		if ( false === $the_product ) {
			$the_product = $post;
		} elseif ( is_numeric( $the_product ) ) {
			$the_product = get_post( $the_product );
		}

		if ( ! $the_product )
			return false;

		$product_id = absint( $the_product->ID );
		$post_type  = $the_product->post_type;

		if ( in_array( $post_type, array( 'product', 'product_variation' ) ) ) {
			if ( isset( $args['product_type'] ) ) {
				$product_type = $args['product_type'];
			} elseif ( 'product_variation' == $post_type ) {
				$product_type = 'variation';
			} else {
				$terms        = get_the_terms( $product_id, 'product_type' );
				$product_type = ! empty( $terms ) && isset( current( $terms )->slug ) ? current( $terms )->slug : 'simple';
			}

			$classname = 'WC_Product_' . preg_replace( '/(?<=_)(.)/e', "strtoupper( '$1' )", ucfirst( $product_type ) );
		} else {
			$classname = false;
			$product_type = false;
		}

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'woocommerce_product_class', $classname, $product_type, $post_type, $product_id );

		if ( ! class_exists( $classname ) )
			$classname = 'WC_Product_Simple';

		return new $classname( $the_product, $args );
	}
}