<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for parameter-based Product querying.
 * Args and usage: {wiki link here}
 *
 * @version  3.2.0
 * @since    3.2.0
 * @package  WooCommerce/Classes
 * @category Class
 */
class WC_Product_Query extends WC_Object_Query {

	/**
	 * Valid query vars for products.
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			array(
				'status'             => array( 'draft', 'pending', 'private', 'publish' ),
				'type'               => array_merge( array_keys( wc_get_product_types() ) ),
				'limit'              => get_option( 'posts_per_page' ),
				'include'            => array(),
				'date_created'       => '',
				'date_modified'      => '',
				'featured'           => '',
				'visibility'         => '',
				'sku'                => '',
				'price'              => '',
				'regular_price'      => '',
				'sale_price'         => '',
				'date_on_sale_from'  => '',
				'date_on_sale_to'    => '',
				'total_sales'        => '',
				'tax_status'         => '',
				'tax_class'          => '',
				'manage_stock'       => '',
				'stock_quantity'     => '',
				'stock_status'       => '',
				'backorders'         => '',
				'sold_individually'  => '',
				'weight'             => '',
				'length'             => '',
				'width'              => '',
				'height'             => '',
				'reviews_allowed'    => '',
				'virtual'            => '',
				'downloadable'       => '',
				'category'           => array(),
				'tag'                => array(),
				'shipping_class'     => array(),
				'download_limit'     => '',
				'download_expiry'    => '',
				'average_rating'     => '',
				'review_count'       => '',
			)
		);
	}

	/**
	 * Get products matching the current query vars.
	 * @return array of WC_Product objects
	 */
	public function get_products() {
		$args = apply_filters( 'woocommerce_product_query_args', $this->get_query_vars() );
		$results = WC_Data_Store::load( 'product' )->query( $args );
		return apply_filters( 'woocommerce_product_query', $results, $args );
	}
}
