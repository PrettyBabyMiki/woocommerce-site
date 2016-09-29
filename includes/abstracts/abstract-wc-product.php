<?php
/**
 * Abstract Product Class
 *
 * The WooCommerce product class handles individual product data.
 *
 * @version  2.7.0
 * @package  WooCommerce/Abstracts
 * @category Abstract Class
 * @author   WooThemes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( 'abstract-wc-legacy-product.php' );

/**
 * Product Class.
 */
class WC_Product extends WC_Abstract_Legacy_Product {

	/**
	 * Stores customer data.
	 *
	 * @var array
	 */
	protected $data = array(
		'name'               => '',
		'slug'               => '',
		'permalink'          => '',
		'date_created'       => '',
		'date_modified'      => '',
		'type'               => '',
		'status'             => '',
		'featured'           => 'no',
		'catalog_visibility' => 'hidden',
		'description'        => '',
		'short_description'  => '',
		'sku'                => '',
		'regular_price'      => '',
		'sale_price'         => '',
		'date_on_sale_from'  => '',
		'date_on_sale_to'    => '',
		'total_sales'        => '',
		'tax_status'         => 'taxable',
		'tax_class'          => '',
		'manage_stock'       => 'no',
		'stock_quantity'     => null,
		'stock_status'       => '',
		'backorders'         => 'no',
		'sold_individually'  => 'no',
		'weight'             => '',
		'length'             => '',
		'width'              => '',
		'height'             => '',
		'upsell_ids'         => '',
		'cross_sell_ids'     => '',
		'parent_id'          => 0,
		'reviews_allowed'    => true,
		'purchase_note'      => '',
		'attributes'         => array(),
		'default_attributes' => array(),
		'menu_order'         => 0,
	);

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 2.7.0
	 * @var array
	 */
	protected $internal_meta_keys = array();

	/**
	 * Supported features such as 'ajax_add_to_cart'.
	 *
	 * @var array
	 */
	protected $supports = array();

	/**
	 * Get the product if ID is passed, otherwise the product is new and empty.
	 * This class should NOT be instantiated, but the wc_get_product() function
	 * should be used. It is possible, but the wc_get_product() is preferred.
	 *
	 * @param int|WC_Product|object $product Product to init.
	 */
	public function __construct( $product = 0 ) {
		if ( is_numeric( $product ) && $product > 0 ) {
			$this->read( $product );
		} elseif ( $product instanceof self ) {
			$this->read( absint( $product->get_id() ) );
		} elseif ( ! empty( $product->ID ) ) {
			$this->read( absint( $product->ID ) );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the product object.
	*/

	/**
	 * Get product name.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_name() {
		return apply_filters( 'woocommerce_product_get_name', $this->data['name'], $this );
	}

	/**
	 * Get product slug.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_slug() {
		return $this->data['slug'];
	}

	/**
	 * Product permalink.
	 *
	 * @return string
	 */
	public function get_permalink() {
		return $this->data['permalink'];
	}

	/**
	 * Get product created date.
	 *
	 * @since 2.7.0
	 * @return string Timestamp.
	 */
	public function get_date_created() {
		return $this->data['date_created'];
	}

	/**
	 * Get product modified date.
	 *
	 * @since 2.7.0
	 * @return string Timestamp.
	 */
	public function get_date_modified() {
		return $this->data['date_modified'];
	}

	/**
	 * Return the product type.
	 *
	 * @return string
	 */
	public function get_type() {
		return $this->data['type'];
	}

	/**
	 * Get product status.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_status() {
		return $this->data['status'];
	}

	/**
	 * If the product is featured.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_featured() {
		return $this->data['featured'];
	}

	/**
	 * Get catalog visibility.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_catalog_visibility() {
		return $this->data['catalog_visibility'];
	}

	/**
	 * Get product description.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_description() {
		return $this->data['description'];
	}

	/**
	 * Get product short description.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_short_description() {
		return $this->data['short_description'];
	}

	/**
	 * Get SKU (Stock-keeping unit) - product unique ID.
	 *
	 * @return string
	 */
	public function get_sku() {
		return apply_filters( 'woocommerce_get_sku', $this->data['sku'], $this );
	}

	/**
	 * Returns the product's regular price.
	 *
	 * @return string price
	 */
	public function get_regular_price() {
		return apply_filters( 'woocommerce_get_regular_price', $this->data['regular_price'], $this );
	}

	/**
	 * Returns the product's sale price.
	 *
	 * @return string price
	 */
	public function get_sale_price() {
		return apply_filters( 'woocommerce_get_sale_price', $this->data['sale_price'], $this );
	}

	/**
	 * Get date on sale from.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_date_on_sale_from() {
		return $this->data['date_on_sale_from'];
	}

	/**
	 * Get date on sale to.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_date_on_sale_to() {
		return $this->data['date_on_sale_to'];
	}

	/**
	 * Get number total of sales.
	 *
	 * @since 2.7.0
	 * @return int
	 */
	public function get_total_sales() {
		return $this->data['total_sales'];
	}

	/**
	 * Returns the tax status.
	 *
	 * @return string
	 */
	public function get_tax_status() {
		return $this->data['tax_status'];
	}

	/**
	 * Returns the tax class.
	 *
	 * @return string
	 */
	public function get_tax_class() {
		return apply_filters( 'woocommerce_product_tax_class', $this->data['tax_class'], $this );
	}

	/**
	 * Return if product manage stock.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_manage_stock() {
		return $this->data['manage_stock'];
	}

	/**
	 * Returns number of items available for sale.
	 *
	 * @return int
	 */
	public function get_stock_quantity() {
		return apply_filters( 'woocommerce_get_stock_quantity', $this->get_manage_stock() ? wc_stock_amount( $this->data['stock_quantity'] ) : null, $this );
	}

	/**
	 * Return the stock status.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_stock_status() {
		return $this->data['stock_status'];
	}

	/**
	 * Get backorders.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_backorders() {
		return $this->data['backorders'];
	}

	/**
	 * Return if should be sold individually.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_sold_individually() {
		return $this->data['sold_individually'];
	}

	/**
	 * Returns the product's weight.
	 *
	 * @return string
	 */
	public function get_weight() {
		// Legacy filter.
		$weight = apply_filters( 'woocommerce_product_weight', $this->data['weight'], $this );

		// New filter.
		return apply_filters( 'woocommerce_product_get_weight', $weight, $this );
	}

	/**
	 * Returns the product length.
	 *
	 * @return string
	 */
	public function get_length() {
		// Legacy filter.
		$length = apply_filters( 'woocommerce_product_length', $this->data['length'], $this );

		// New filter since 2.7.
		return apply_filters( 'woocommerce_product_get_length', $length, $this );
	}

	/**
	 * Returns the product width.
	 *
	 * @return string
	 */
	public function get_width() {
		// Legacy filter.
		$width = apply_filters( 'woocommerce_product_width', $this->data['width'], $this );

		// New filter since 2.7.
		return apply_filters( 'woocommerce_product_get_width', $width, $this );
	}

	/**
	 * Returns the product height.
	 *
	 * @return string
	 */
	public function get_height() {
		// Legacy filter.
		$height = apply_filters( 'woocommerce_product_height', $this->data['height'], $this );

		// New filter since 2.7.
		return apply_filters( 'woocommerce_product_get_height', $height, $this );
	}

	/**
	 * Get Upseels IDs.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_upsell_ids() {
		return $this->data['upsell_ids'];
	}

	/**
	 * Get Upseels IDs.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_cross_sell_ids() {
		return $this->data['cross_sell_ids'];
	}

	/**
	 * Get parent ID.
	 *
	 * @since 2.7.0
	 * @return int
	 */
	public function get_parent_id() {
		return $this->data['parent_id'];
	}

	/**
	 * Return if reviews is allowed.
	 *
	 * @since 2.7.0
	 * @return bool
	 */
	public function get_reviews_allowed() {
		return $this->data['reviews_allowed'];
	}

	/**
	 * Get purchase note.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_purchase_note() {
		return $this->data['purchase_note'];
	}

	/**
	 * Returns the product categories.
	 *
	 * @param string $sep (default: ', ').
	 * @param string $before (default: '').
	 * @param string $after (default: '').
	 * @return string
	 */
	public function get_categories( $sep = ', ', $before = '', $after = '' ) {
		return get_the_term_list( $this->get_id(), 'product_cat', $before, $sep, $after );
	}

	/**
	 * Returns the product tags.
	 *
	 * @param string $sep (default: ', ').
	 * @param string $before (default: '').
	 * @param string $after (default: '').
	 * @return array
	 */
	public function get_tags( $sep = ', ', $before = '', $after = '' ) {
		return get_the_term_list( $this->get_id(), 'product_tag', $before, $sep, $after );
	}

	/**
	 * Returns product attributes.
	 *
	 * @return array
	 */
	public function get_attributes() {
		$attributes = array_filter( (array) maybe_unserialize( $this->data['product_attributes'] ) );
		$taxonomies = wp_list_pluck( wc_get_attribute_taxonomies(), 'attribute_name' );

		// Check for any attributes which have been removed globally
		foreach ( $attributes as $key => $attribute ) {
			if ( $attribute['is_taxonomy'] ) {
				if ( ! in_array( substr( $attribute['name'], 3 ), $taxonomies ) ) {
					unset( $attributes[ $key ] );
				}
			}
		}

		return apply_filters( 'woocommerce_get_product_attributes', $attributes );
	}

	/**
	 * Get default attributes.
	 *
	 * @since 2.7.0
	 * @return array
	 */
	public function get_default_attributes() {
		return $this->data['default_attributes'];
	}

	/**
	 * Get menu order.
	 *
	 * @since 2.7.0
	 * @return int
	 */
	public function get_menu_order() {
		return $this->data['menu_order'];
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting product data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/

	/**
	 * Set product name.
	 *
	 * @since 2.7.0
	 * @param string $name Product name.
	 */
	public function set_name( $name ) {
		$this->data['name'] = $name;
	}

	/**
	 * Set product slug.
	 *
	 * @since 2.7.0
	 * @param string $slug Product slug.
	 */
	public function set_slug( $slug ) {
		$this->data['slug'] = $slug;
	}

	/**
	 * Set product permalink.
	 *
	 * @since 2.7.0
	 * @param string $permalink Product permalink.
	 */
	public function set_permalink( $permalink ) {
		$this->data['permalink'] = $permalink;
	}

	/**
	 * Set product created date.
	 *
	 * @since 2.7.0
	 * @param string $timestamp Timestamp.
	 */
	public function set_date_created( $timestamp ) {
		$this->data['date_created'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set product modified date.
	 *
	 * @since 2.7.0
	 * @param string $timestamp Timestamp.
	 */
	public function set_date_modified( $timestamp ) {
		$this->data['date_modified'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set the product type.
	 *
	 * @return string
	 */
	public function set_type( $type ) {
		$this->data['type'] = $type;
	}

	/**
	 * Set product status.
	 *
	 * @since 2.7.0
	 * @param string $status Product status.
	 */
	public function set_status( $status ) {
		$this->data['status'] = $status;
	}

	/**
	 * Set if the product is featured.
	 *
	 * @since 2.7.0
	 * @param string $featured Options: 'yes' or 'no'.
	 */
	public function set_featured( $featured ) {
		$this->data['featured'] = $featured;
	}

	/**
	 * Set catalog visibility.
	 *
	 * @since 2.7.0
	 * @throws WC_Data_Exception
	 * @param string $visibility Options: 'hidden', 'visible', 'search' and 'catalog'.
	 */
	public function set_catalog_visibility( $visibility ) {
		$options = array_keys( wc_get_product_visibility_options() );
		if ( ! in_array( $visibility, $options, true ) ) {
			$this->error( 'product_invalid_catalog_visibility', __( 'Invalid catalog visibility option.', 'woocommerce' ) );
		}

		$this->data['catalog_visibility'] = $visibility;
	}

	/**
	 * Set product description.
	 *
	 * @since 2.7.0
	 * @param string $description Product description.
	 */
	public function set_description( $description ) {
		$this->data['description'] = $description;
	}

	/**
	 * Set product short description.
	 *
	 * @since 2.7.0
	 * @param string $short_description Product short description.
	 */
	public function set_short_description( $short_description ) {
		$this->data['short_description'] = $short_description;
	}

	/**
	 * Set SKU.
	 *
	 * @since 2.7.0
	 * @throws WC_Data_Exception
	 * @param string $sku Product SKU.
	 */
	public function set_sku( $sku ) {
		if ( ! wc_product_has_unique_sku( $this->get_id(), $sku ) ) {
			$this->error( 'product_invalid_sku', __( 'Invalid or duplicated SKU.', 'woocommerce' ) );
		}

		$this->data['sku'] = $sku;
	}

	/**
	 * Set the product's regular price.
	 *
	 * @since 2.7.0
	 * @param string $price Regular price.
	 */
	public function set_regular_price( $price ) {
		$this->data['regular_price'] = $price;
	}

	/**
	 * Set the product's sale price.
	 *
	 * @since 2.7.0
	 * @param string $price sale price.
	 */
	public function set_sale_price( $price ) {
		$this->data['sale_price'] = $price;
	}

	/**
	 * Set date on sale from.
	 *
	 * @since 2.7.0
	 * @param string $data Sale from date.
	 */
	public function set_date_on_sale_from( $date ) {
		$this->data['date_on_sale_from'] = $date;
	}

	/**
	 * Set date on sale to.
	 *
	 * @since 2.7.0
	 * @param string $data Sale to date.
	 */
	public function set_date_on_sale_to( $date ) {
		return $this->data['date_on_sale_to'] = $date;
	}

	/**
	 * Set number total of sales.
	 *
	 * @since 2.7.0
	 * @param int $total Total of sales.
	 */
	public function set_total_sales( $total ) {
		$this->data['total_sales'] = absint( $total );
	}

	/**
	 * Set the tax status.
	 *
	 * @since 2.7.0
	 * @throws WC_Data_Exception
	 * @param string $status Tax status.
	 */
	public function set_tax_status( $status ) {
		$options = array(
			'taxable',
			'shipping',
			'none',
		);

		if ( ! in_array( $status, $options, true ) ) {
			$this->error( 'product_invalid_tax_status', __( 'Invalid product tax status.', 'woocommerce' ) );
		}

		$this->data['tax_status'] = $status;
	}

	/**
	 * Set the tax class.
	 *
	 * @since 2.7.0
	 * @param string $class Tax class.
	 */
	public function set_tax_class( $class ) {
		$this->data['tax_class'] = wc_clean( $class );
	}

	/**
	 * Set if product manage stock.
	 *
	 * @since 2.7.0
	 * @param string $manage_stock Options: 'yes' or 'no'.
	 */
	public function set_manage_stock( $manage_stock ) {
		$this->data['manage_stock'] = $manage_stock;
	}

	/**
	 * Set number of items available for sale.
	 *
	 * @since 2.7.0
	 * @param float|null $quantity Stock quantity.
	 */
	public function set_stock_quantity( $quantity ) {
		$this->data['stock_quantity'] = $quantity;
	}

	/**
	 * Set stock status.
	 *
	 * @param string $status New status.
	 */
	public function set_stock_status( $status ) {
		$status = 'outofstock' === $status ? 'outofstock' : 'instock';

		// Sanity check.
		if ( $this->managing_stock() ) {
			if ( ! $this->backorders_allowed() && $this->get_stock_quantity() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
				$status = 'outofstock';
			}
		}

		if ( update_post_meta( $this->get_id(), '_stock_status', $status ) ) {
			$this->data['stock_status'] = $status;
			do_action( 'woocommerce_product_set_stock_status', $this->get_id(), $status );
		}
	}

	/**
	 * Set backorders.
	 *
	 * @since 2.7.0
	 * @param string $backorders Options: 'yes', 'no' or 'notify'.
	 */
	public function set_backorders( $backorders ) {
		$this->data['backorders'] = $backorders;
	}

	/**
	 * Set if should be sold individually.
	 *
	 * @since 2.7.0
	 * @param string $sold_individually Options: 'yes' or 'no'.
	 */
	public function set_sold_individually( $sold_individually ) {
		$this->data['sold_individually'] = $sold_individually;
	}

	/**
	 * Set the product's weight.
	 *
	 * @since 2.7.0
	 * @param float $weigth Total weigth.
	 */
	public function set_weight( $weight ) {
		$this->data['weight'] = $weight;
	}

	/**
	 * Set the product length.
	 *
	 * @since 2.7.0
	 * @param float $weigth Total weigth.
	 */
	public function set_length( $length ) {
		$this->data['length'] = $length;
	}

	/**
	 * Set the product width.
	 *
	 * @since 2.7.0
	 * @param float $width Total width.
	 */
	public function set_width( $width ) {
		$this->data['width'] = $width;
	}

	/**
	 * Set the product height.
	 *
	 * @since 2.7.0
	 * @param float $height Total height.
	 */
	public function set_height( $height ) {
		$this->data['height'] = $height;
	}

	/**
	 * Set Upseels IDs.
	 *
	 * @since 2.7.0
	 * @param string $upsell_ids IDs from the up-sell products.
	 */
	public function set_upsell_ids( $upsell_ids ) {
		$this->data['upsell_ids'] = $upsell_ids;
	}

	/**
	 * Set Upseels IDs.
	 *
	 * @since 2.7.0
	 * @param string $cross_sell_ids IDs from the cross-sell products.
	 */
	public function set_cross_sell_ids( $cross_sell_ids ) {
		$this->data['cross_sell_ids'] = $cross_sell_ids;
	}

	/**
	 * Set parent ID.
	 *
	 * @since 2.7.0
	 * @param int $parent_id Product parent ID.
	 */
	public function set_parent_id( $parent_id ) {
		$this->data['parent_id'] = absint( $parent_id );
	}

	/**
	 * Set if reviews is allowed.
	 *
	 * @since 2.7.0
	 * @param bool $reviews_allowed Reviews allowed or not.
	 */
	public function set_reviews_allowed( $reviews_allowed ) {
		$this->data['reviews_allowed'] = (bool) $reviews_allowed;
	}

	/**
	 * Set purchase note.
	 *
	 * @since 2.7.0
	 * @param string $purchase_note Purchase note.
	 */
	public function set_purchase_note() {
		$this->data['purchase_note'] = $purchase_note;
	}

	/**
	 * Set the product categories.
	 *
	 * @since 2.7.0
	 * @param array $terms_id List of terms IDs.
	 */
	public function set_categories( $terms_id ) {
		$this->save_taxonomy_terms( $terms_id, 'cat' );
	}

	/**
	 * Set the product tags.
	 *
	 * @since 2.7.0
	 * @param array $terms_id List of terms IDs.
	 */
	public function set_tags( $terms_id ) {
		$this->save_taxonomy_terms( $terms_id, 'tag' );
	}

	/**
	 * Returns product attributes.
	 *
	 * @since 2.7.0
	 * @param array $attributes List of product attributes.
	 */
	public function set_attributes( $attributes ) {
		$this->data['product_attributes'] = $attributes;
	}

	/**
	 * Set default attributes.
	 *
	 * @since 2.7.0
	 * @param array $default_attributes List of default attributes.
	 */
	public function set_default_attributes( $default_attributes ) {
		$this->data['default_attributes'] = $default_attributes;
	}

	/**
	 * Set menu order.
	 *
	 * @since 2.7.0
	 * @param int $menu_order Menu order.
	 */
	public function set_menu_order( $menu_order ) {
		$this->data['menu_order'] = intval( $menu_order );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete products from the database.
	|
	| A save method is included for convenience (chooses update or create based
	| on if the order exists yet).
	*/

	/**
	 * Reads a product from the database and sets its data to the class.
	 *
	 * @since 2.7.0
	 * @param int $id Product ID.
	 */
	public function read( $id ) {
		$this->set_defaults();

		if ( ! $id || ! ( $post_object = get_post( $id ) ) ) {
			return;
		}

		$this->set_id( $id );
		$this->set_props( array(
			'name'               => get_the_title( $post_object ),
			'slug'               => $post_object->post_name,
			'permalink'          => get_permalink( $post_object ),
			'date_created'       => $post_object->post_date,
			'date_modified'      => $post_object->post_modified,
			'type'               => '',
			'status'             => $post_object->post_status,
			'featured'           => get_post_meta( $id, '_featured', true ),
			'catalog_visibility' => 'hidden',
			'description'        => $post_object->post_content,
			'short_description'  => $post_object->post_excerpt,
			'sku'                => get_post_meta( $id, '_sku', true ),
			'regular_price'      => get_post_meta( $id, '_regular_price', true ),
			'sale_price'         => get_post_meta( $id, '_sale_price', true ),
			'date_on_sale_from'  => get_post_meta( $id, '_sale_price_dates_from', true ),
			'date_on_sale_to'    => get_post_meta( $id, '_sale_price_dates_to', true ),
			'total_sales'        => get_post_meta( $id, 'total_sales', true ),
			'tax_status'         => get_post_meta( $id, '_tax_status', true ),
			'tax_class'          => get_post_meta( $id, '_tax_class', true ),
			'manage_stock'       => 'no',
			'stock_quantity'     => get_post_meta( $id, '_stock', true ),
			'stock_status'       => get_post_meta( $id, '_stock_status', true ),
			'backorders'         => get_post_meta( $id, '_backorders', true ),
			'sold_individually'  => get_post_meta( $id, '_sold_individually', true ),
			'weight'             => get_post_meta( $id, '_weight', true ),
			'length'             => get_post_meta( $id, '_length', true ),
			'width'              => get_post_meta( $id, '_width', true ),
			'height'             => get_post_meta( $id, '_height', true ),
			'upsell_ids'         => get_post_meta( $id, '_upsell_ids', true ),
			'cross_sell_ids'     => get_post_meta( $id, '_crosssell_ids', true ),
			'parent_id'          => $post_object->post_parent,
			'reviews_allowed'    => $post_object->comment_status,
			'purchase_note'      => get_post_meta( $id, '_purchase_note', true ),
			'attributes'         => get_post_meta( $id, '_attributes', true ),
			'default_attributes' => get_post_meta( $id, '_default_attributes', true ),
			'menu_order'         => $post_object->menu_order,
		) );
		$this->read_meta_data();

		do_action( 'woocommerce_product_loaded', $this );
	}

	/**
	 * Create a new product.
	 *
	 * @since 2.7.0
	 */
	public function create() {
		$this->set_date_created( current_time( 'timestamp' ) );

		$id = wp_insert_post( apply_filters( 'woocommerce_new_product_data', array(
			'post_type'     => 'product',
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id(),
			'post_title'    => $this->get_code(),
			'post_content'  => '',
			'post_excerpt'  => $this->get_description(),
			'post_date'     => date( 'Y-m-d H:i:s', $this->get_date_created() ),
			'post_date_gmt' => get_gmt_from_date( date( 'Y-m-d H:i:s', $this->get_date_created() ) ),
		) ), true );

		if ( $id ) {
			$this->set_id( $id );
			$this->update_post_meta( $id );
			$this->save_meta_data();
			do_action( 'woocommerce_new_product', $id );
		}
	}

	/**
	 * Updates an existing product.
	 *
	 * @since 2.7.0
	 */
	public function update() {

	}

	/**
	 * Save data (either create or update depending on if we are working on an existing product).
	 *
	 * @since 2.7.0
	 */
	public function save() {
		if ( $this->get_id() ) {
			$this->update();
		} else {
			$this->create();
		}
	}

	/**
	 * Delete product from the database.
	 *
	 * @since 2.7.0
	 */
	public function delete() {
		wp_delete_post( $this->get_id() );
		do_action( 'woocommerce_delete_product', $this->get_id() );
		$this->set_id( 0 );
	}

	/**
	 * Helper method that updates all the post meta for a product based on it's settings in the WC_Product class.
	 *
	 * @since 2.7.0
	 * @param int $id Object ID.
	 */
	private function update_post_meta( $id ) {
		// update_post_meta( $id, 'discount_type', $this->get_discount_type() );
	}

	/*
	|--------------------------------------------------------------------------
	| Other Actions
	|--------------------------------------------------------------------------
	*/

	/**
	 * Check if a product supports a given feature.
	 *
	 * Product classes should override this to declare support (or lack of support) for a feature.
	 *
	 * @param string $feature string The name of a feature to test support for.
	 * @return bool True if the product supports the feature, false otherwise.
	 * @since 2.5.0
	 */
	public function supports( $feature ) {
		return apply_filters( 'woocommerce_product_supports', in_array( $feature, $this->supports ) ? true : false, $feature, $this );
	}

	/**
	 * Returns the gallery attachment ids.
	 *
	 * @return array
	 */
	public function get_gallery_attachment_ids() {
		return apply_filters( 'woocommerce_product_gallery_attachment_ids', array_filter( array_filter( (array) explode( ',', $this->product_image_gallery ) ), 'wp_attachment_is_image' ), $this );
	}

	/**
	 * Get total stock - This is the stock of parent and children combined.
	 *
	 * @return int
	 */
	public function get_total_stock() {
		if ( empty( $this->total_stock ) ) {
			if ( sizeof( $this->get_children() ) > 0 ) {
				$this->total_stock = max( 0, $this->get_stock_quantity() );

				foreach ( $this->get_children() as $child_id ) {
					if ( 'yes' === get_post_meta( $child_id, '_manage_stock', true ) ) {
						$stock = get_post_meta( $child_id, '_stock', true );
						$this->total_stock += max( 0, wc_stock_amount( $stock ) );
					}
				}
			} else {
				$this->total_stock = $this->get_stock_quantity();
			}
		}
		return wc_stock_amount( $this->total_stock );
	}

	/**
	 * Check if the stock status needs changing.
	 */
	public function check_stock_status() {
		if ( ! $this->backorders_allowed() && $this->get_total_stock() <= get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			if ( 'outofstock' !== $this->stock_status ) {
				$this->set_stock_status( 'outofstock' );
			}
		} elseif ( $this->backorders_allowed() || $this->get_total_stock() > get_option( 'woocommerce_notify_no_stock_amount' ) ) {
			if ( 'instock' !== $this->stock_status ) {
				$this->set_stock_status( 'instock' );
			}
		}
	}

	/**
	 * Set stock level of the product.
	 *
	 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues).
	 * We cannot rely on the original loaded value in case another order was made since then.
	 *
	 * @param  int     $amount  (default: null)
	 * @param  string  $mode    can be set, add, or subtract
	 * @return int              new stock level
	 */
	public function set_stock( $amount = null, $mode = 'set' ) {
		global $wpdb;

		if ( ! is_null( $amount ) && $this->managing_stock() ) {

			// Ensure key exists
			add_post_meta( $this->get_id(), '_stock', 0, true );

			// Update stock in DB directly
			switch ( $mode ) {
				case 'add' :
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value + %f WHERE post_id = %d AND meta_key='_stock'", $amount, $this->get_id() ) );
				break;
				case 'subtract' :
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = meta_value - %f WHERE post_id = %d AND meta_key='_stock'", $amount, $this->get_id() ) );
				break;
				default :
					$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %f WHERE post_id = %d AND meta_key='_stock'", $amount, $this->get_id() ) );
				break;
			}

			// Clear caches
			wp_cache_delete( $this->get_id(), 'post_meta' );
			delete_transient( 'wc_low_stock_count' );
			delete_transient( 'wc_outofstock_count' );
			unset( $this->stock );

			// Stock status
			$this->check_stock_status();

			// Trigger action
			do_action( 'woocommerce_product_set_stock', $this );

		// If not managing stock and clearing the stock meta, trigger action to indicate that stock has changed (infinite stock)
		} elseif ( '' === $amount && '' !== get_post_meta( $this->id, '_stock', true ) ) {

			update_post_meta( $this->id, '_stock', '' );

			// Trigger action
			do_action( 'woocommerce_product_set_stock', $this );
		}

		return $this->get_stock_quantity();
	}

	/**
	 * Reduce stock level of the product.
	 *
	 * @param int $amount Amount to reduce by. Default: 1
	 * @return int new stock level
	 */
	public function reduce_stock( $amount = 1 ) {
		return $this->set_stock( $amount, 'subtract' );
	}

	/**
	 * Increase stock level of the product.
	 *
	 * @param int $amount Amount to increase by. Default 1.
	 * @return int new stock level
	 */
	public function increase_stock( $amount = 1 ) {
		return $this->set_stock( $amount, 'add' );
	}

	/**
	 * Checks the product type.
	 *
	 * Backwards compat with downloadable/virtual.
	 *
	 * @param string $type Array or string of types
	 * @return bool
	 */
	public function is_type( $type ) {
		return ( $this->product_type == $type || ( is_array( $type ) && in_array( $this->product_type, $type ) ) ) ? true : false;
	}

	/**
	 * Checks if a product is downloadable.
	 *
	 * @return bool
	 */
	public function is_downloadable() {
		return ( 'yes' === $this->downloadable );
	}

	/**
	 * Check if downloadable product has a file attached.
	 *
	 * @since 1.6.2
	 *
	 * @param string $download_id file identifier
	 * @return bool Whether downloadable product has a file attached.
	 */
	public function has_file( $download_id = '' ) {
		return ( $this->is_downloadable() && $this->get_file( $download_id ) ) ? true : false;
	}

	/**
	 * Gets an array of downloadable files for this product.
	 *
	 * @since 2.1.0
	 *
	 * @return array
	 */
	public function get_files() {

		$downloadable_files = array_filter( isset( $this->downloadable_files ) ? (array) maybe_unserialize( $this->downloadable_files ) : array() );

		if ( ! empty( $downloadable_files ) ) {

			foreach ( $downloadable_files as $key => $file ) {

				if ( ! is_array( $file ) ) {
					$downloadable_files[ $key ] = array(
						'file' => $file,
						'name' => '',
					);
				}

				// Set default name
				if ( empty( $file['name'] ) ) {
					$downloadable_files[ $key ]['name'] = wc_get_filename_from_url( $file['file'] );
				}

				// Filter URL
				$downloadable_files[ $key ]['file'] = apply_filters( 'woocommerce_file_download_path', $downloadable_files[ $key ]['file'], $this, $key );
			}
		}

		return apply_filters( 'woocommerce_product_files', $downloadable_files, $this );
	}

	/**
	 * Get a file by $download_id.
	 *
	 * @param string $download_id file identifier
	 * @return array|false if not found
	 */
	public function get_file( $download_id = '' ) {

		$files = $this->get_files();

		if ( '' === $download_id ) {
			$file = sizeof( $files ) ? current( $files ) : false;
		} elseif ( isset( $files[ $download_id ] ) ) {
			$file = $files[ $download_id ];
		} else {
			$file = false;
		}

		// allow overriding based on the particular file being requested
		return apply_filters( 'woocommerce_product_file', $file, $this, $download_id );
	}

	/**
	 * Get file download path identified by $download_id.
	 *
	 * @param string $download_id file identifier
	 * @return string
	 */
	public function get_file_download_path( $download_id ) {
		$files = $this->get_files();

		if ( isset( $files[ $download_id ] ) ) {
			$file_path = $files[ $download_id ]['file'];
		} else {
			$file_path = '';
		}

		// allow overriding based on the particular file being requested
		return apply_filters( 'woocommerce_product_file_download_path', $file_path, $this, $download_id );
	}

	/**
	 * Checks if a product is virtual (has no shipping).
	 *
	 * @return bool
	 */
	public function is_virtual() {
		return apply_filters( 'woocommerce_is_virtual', ( 'yes' === $this->virtual ), $this );
	}

	/**
	 * Checks if a product needs shipping.
	 *
	 * @return bool
	 */
	public function needs_shipping() {
		return apply_filters( 'woocommerce_product_needs_shipping', $this->is_virtual() ? false : true, $this );
	}

	/**
	 * Check if a product is sold individually (no quantities).
	 *
	 * @return bool
	 */
	public function is_sold_individually() {

		$return = false;

		if ( 'yes' == $this->sold_individually ) {
			$return = true;
		}

		return apply_filters( 'woocommerce_is_sold_individually', $return, $this );
	}

	/**
	 * Returns the child product.
	 *
	 * @param mixed $child_id
	 * @return WC_Product|WC_Product|WC_Product_variation
	 */
	public function get_child( $child_id ) {
		return wc_get_product( $child_id );
	}

	/**
	 * Returns the children.
	 *
	 * @return array
	 */
	public function get_children() {
		return array();
	}

	/**
	 * Returns whether or not the product has any child product.
	 *
	 * @return bool
	 */
	public function has_child() {
		return false;
	}

	/**
	 * Returns whether or not the product post exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return empty( $this->post ) ? false : true;
	}

	/**
	 * Returns whether or not the product is taxable.
	 *
	 * @return bool
	 */
	public function is_taxable() {
		$taxable = $this->get_tax_status() === 'taxable' && wc_tax_enabled() ? true : false;
		return apply_filters( 'woocommerce_product_is_taxable', $taxable, $this );
	}

	/**
	 * Returns whether or not the product shipping is taxable.
	 *
	 * @return bool
	 */
	public function is_shipping_taxable() {
		return $this->get_tax_status() === 'taxable' || $this->get_tax_status() === 'shipping' ? true : false;
	}

	/**
	 * Get the add to url used mainly in loops.
	 *
	 * @return string
	 */
	public function add_to_cart_url() {
		return apply_filters( 'woocommerce_product_add_to_cart_url', get_permalink( $this->get_id() ), $this );
	}

	/**
	 * Get the add to cart button text for the single page.
	 *
	 * @return string
	 */
	public function single_add_to_cart_text() {
		return apply_filters( 'woocommerce_product_single_add_to_cart_text', __( 'Add to cart', 'woocommerce' ), $this );
	}

	/**
	 * Get the add to cart button text.
	 *
	 * @return string
	 */
	public function add_to_cart_text() {
		return apply_filters( 'woocommerce_product_add_to_cart_text', __( 'Read more', 'woocommerce' ), $this );
	}

	/**
	 * Returns whether or not the product is stock managed.
	 *
	 * @return bool
	 */
	public function managing_stock() {
		$managing_stock = 'no' === $this->get_manage_stock() || 'yes' !== get_option( 'woocommerce_manage_stock' );

		return ! $managing_stock;
	}

	/**
	 * Returns whether or not the product is in stock.
	 *
	 * @return bool
	 */
	public function is_in_stock() {
		return apply_filters( 'woocommerce_product_is_in_stock', ( 'instock' === $this->stock_status ), $this );
	}

	/**
	 * Returns whether or not the product can be backordered.
	 *
	 * @return bool
	 */
	public function backorders_allowed() {
		return apply_filters( 'woocommerce_product_backorders_allowed', ( 'yes' === $this->get_backorders() || 'notify' === $this->get_backorders() ), $this->get_id(), $this );
	}

	/**
	 * Returns whether or not the product needs to notify the customer on backorder.
	 *
	 * @return bool
	 */
	public function backorders_require_notification() {
		return apply_filters( 'woocommerce_product_backorders_require_notification', ( $this->managing_stock() && 'notify' === $this->backorders ), $this );
	}

	/**
	 * Check if a product is on backorder.
	 *
	 * @param int $qty_in_cart (default: 0)
	 * @return bool
	 */
	public function is_on_backorder( $qty_in_cart = 0 ) {
		return $this->managing_stock() && $this->backorders_allowed() && ( $this->get_total_stock() - $qty_in_cart ) < 0 ? true : false;
	}

	/**
	 * Returns whether or not the product has enough stock for the order.
	 *
	 * @param mixed $quantity
	 * @return bool
	 */
	public function has_enough_stock( $quantity ) {
		return ! $this->managing_stock() || $this->backorders_allowed() || $this->get_stock_quantity() >= $quantity ? true : false;
	}

	/**
	 * Returns the availability of the product.
	 *
	 * If stock management is enabled at global and product level, a stock message
	 * will be shown. e.g. In stock, In stock x10, Out of stock.
	 *
	 * If stock management is disabled at global or product level, out of stock
	 * will be shown when needed, but in stock will be hidden from view.
	 *
	 * This can all be changed through use of the woocommerce_get_availability filter.
	 *
	 * @return string
	 */
	public function get_availability() {
		return apply_filters( 'woocommerce_get_availability', array(
			'availability' => $this->get_availability_text(),
			'class'        => $this->get_availability_class(),
		), $this );
	}

	/**
	 * Get availability text based on stock status.
	 *
	 * @return string
	 */
	protected function get_availability_text() {
		if ( ! $this->is_in_stock() ) {
			$availability = __( 'Out of stock', 'woocommerce' );
		} elseif ( $this->managing_stock() && $this->is_on_backorder( 1 ) ) {
			$availability = $this->backorders_require_notification() ? __( 'Available on backorder', 'woocommerce' ) : __( 'In stock', 'woocommerce' );
		} elseif ( $this->managing_stock() ) {
			switch ( get_option( 'woocommerce_stock_format' ) ) {
				case 'no_amount' :
					$availability = __( 'In stock', 'woocommerce' );
				break;
				case 'low_amount' :
					if ( $this->get_total_stock() <= get_option( 'woocommerce_notify_low_stock_amount' ) ) {
						/* translators: %s: total items in stock */
						$availability = sprintf( __( 'Only %s left in stock', 'woocommerce' ), $this->get_total_stock() );

						if ( $this->backorders_allowed() && $this->backorders_require_notification() ) {
							$availability .= ' ' . __( '(also available on backorder)', 'woocommerce' );
						}
					} else {
						$availability = __( 'In stock', 'woocommerce' );
					}
				break;
				default :
					/* translators: %s: total items in stock */
					$availability = sprintf( __( '%s in stock', 'woocommerce' ), $this->get_total_stock() );

					if ( $this->backorders_allowed() && $this->backorders_require_notification() ) {
						$availability .= ' ' . __( '(also available on backorder)', 'woocommerce' );
					}
				break;
			}
		} else {
			$availability = '';
		}
		return apply_filters( 'woocommerce_get_availability_text', $availability, $this );
	}

	/**
	 * Get availability classname based on stock status.
	 *
	 * @return string
	 */
	protected function get_availability_class() {
		if ( ! $this->is_in_stock() ) {
			$class = 'out-of-stock';
		} elseif ( $this->managing_stock() && $this->is_on_backorder( 1 ) && $this->backorders_require_notification() ) {
			$class = 'available-on-backorder';
		} else {
			$class = 'in-stock';
		}

		return apply_filters( 'woocommerce_get_availability_class', $class, $this );
	}

	/**
	 * Returns whether or not the product is featured.
	 *
	 * @return bool
	 */
	public function is_featured() {
		return 'yes' === $this->get_featured();
	}

	/**
	 * Returns whether or not the product is visible in the catalog.
	 *
	 * @return bool
	 */
	public function is_visible() {
		if ( ! $this->post ) {
			$visible = false;

		// Published/private.
		} elseif ( 'publish' !== $this->post->post_status && ! current_user_can( 'edit_post', $this->get_id() ) ) {
			$visible = false;

		// Out of stock visibility.
		} elseif ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $this->is_in_stock() ) {
			$visible = false;

		// visibility setting.
		} elseif ( 'hidden' === $this->visibility ) {
			$visible = false;
		} elseif ( 'visible' === $this->visibility ) {
			$visible = true;

		// Visibility in loop.
		} elseif ( is_search() ) {
			$visible = 'search' === $this->visibility;
		} else {
			$visible = 'catalog' === $this->visibility;
		}

		return apply_filters( 'woocommerce_product_is_visible', $visible, $this->get_id() );
	}

	/**
	 * Returns whether or not the product is on sale.
	 *
	 * @return bool
	 */
	public function is_on_sale() {
		return apply_filters( 'woocommerce_product_is_on_sale', ( $this->get_sale_price() !== $this->get_regular_price() && $this->get_sale_price() === $this->get_price() ), $this );
	}

	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @return bool
	 */
	public function is_purchasable() {

		$purchasable = true;

		// Products must exist of course.
		if ( ! $this->exists() ) {
			$purchasable = false;

		// Other products types need a price to be set.
		} elseif ( $this->get_price() === '' ) {
			$purchasable = false;

		// Check the product is published.
		} elseif ( 'publish' !== $this->post->post_status && ! current_user_can( 'edit_post', $this->get_id() ) ) {
			$purchasable = false;
		}

		return apply_filters( 'woocommerce_is_purchasable', $purchasable, $this );
	}

	/**
	 * Set a products price dynamically.
	 *
	 * @param float $price Price to set.
	 */
	public function set_price( $price ) {
		$this->price = $price;
	}

	/**
	 * Adjust a products price dynamically.
	 *
	 * @param mixed $price
	 */
	public function adjust_price( $price ) {
		$this->price = $this->price + $price;
	}

	/**
	 * Returns the product's active price.
	 *
	 * @return string price
	 */
	public function get_price() {
		return apply_filters( 'woocommerce_get_price', $this->price, $this );
	}

	/**
	 * Returns the price (including tax). Uses customer tax rates. Can work for a specific $qty for more accurate taxes.
	 *
	 * @param  int $qty
	 * @param  string $price to calculate, left blank to just use get_price()
	 * @return string
	 */
	public function get_price_including_tax( $qty = 1, $price = '' ) {

		if ( '' === $price ) {
			$price = $this->get_price();
		}

		if ( $this->is_taxable() ) {

			if ( get_option( 'woocommerce_prices_include_tax' ) === 'no' ) {

				$tax_rates  = WC_Tax::get_rates( $this->get_tax_class() );
				$taxes      = WC_Tax::calc_tax( $price * $qty, $tax_rates, false );
				$tax_amount = WC_Tax::get_tax_total( $taxes );
				$price      = round( $price * $qty + $tax_amount, wc_get_price_decimals() );

			} else {

				$tax_rates      = WC_Tax::get_rates( $this->get_tax_class() );
				$base_tax_rates = WC_Tax::get_base_tax_rates( $this->tax_class );

				if ( ! empty( WC()->customer ) && WC()->customer->get_is_vat_exempt() ) {

					$base_taxes         = WC_Tax::calc_tax( $price * $qty, $base_tax_rates, true );
					$base_tax_amount    = array_sum( $base_taxes );
					$price              = round( $price * $qty - $base_tax_amount, wc_get_price_decimals() );

				/**
				 * The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations.
				 * e.g. If a product costs 10 including tax, all users will pay 10 regardless of location and taxes.
				 * This feature is experimental @since 2.4.7 and may change in the future. Use at your risk.
				 */
				} elseif ( $tax_rates !== $base_tax_rates && apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {

					$base_taxes         = WC_Tax::calc_tax( $price * $qty, $base_tax_rates, true );
					$modded_taxes       = WC_Tax::calc_tax( ( $price * $qty ) - array_sum( $base_taxes ), $tax_rates, false );
					$price              = round( ( $price * $qty ) - array_sum( $base_taxes ) + array_sum( $modded_taxes ), wc_get_price_decimals() );

				} else {

					$price = $price * $qty;

				}
			}
		} else {
			$price = $price * $qty;
		}

		return apply_filters( 'woocommerce_get_price_including_tax', $price, $qty, $this );
	}

	/**
	 * Returns the price (excluding tax) - ignores tax_class filters since the price may *include* tax and thus needs subtracting.
	 * Uses store base tax rates. Can work for a specific $qty for more accurate taxes.
	 *
	 * @param  int $qty
	 * @param  string $price to calculate, left blank to just use get_price()
	 * @return string
	 */
	public function get_price_excluding_tax( $qty = 1, $price = '' ) {

		if ( '' === $price ) {
			$price = $this->get_price();
		}

		if ( $this->is_taxable() && 'yes' === get_option( 'woocommerce_prices_include_tax' ) ) {
			$tax_rates  = WC_Tax::get_base_tax_rates( $this->tax_class );
			$taxes      = WC_Tax::calc_tax( $price * $qty, $tax_rates, true );
			$price      = WC_Tax::round( $price * $qty - array_sum( $taxes ) );
		} else {
			$price = $price * $qty;
		}

		return apply_filters( 'woocommerce_get_price_excluding_tax', $price, $qty, $this );
	}

	/**
	 * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_shop' setting.
	 *
	 * @param  string  $price to calculate, left blank to just use get_price()
	 * @param  integer $qty   passed on to get_price_including_tax() or get_price_excluding_tax()
	 * @return string
	 */
	public function get_display_price( $price = '', $qty = 1 ) {
		if ( '' === $price ) {
			$price = $this->get_price();
		}

		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$display_price    = ( 'incl' === $tax_display_mode ) ? $this->get_price_including_tax( $qty, $price ) : $this->get_price_excluding_tax( $qty, $price );

		return apply_filters( 'woocommerce_get_display_price', $display_price, $price, $qty );
	}

	/**
	 * Get the suffix to display after prices > 0.
	 *
	 * @param  string  $price to calculate, left blank to just use get_price()
	 * @param  integer $qty   passed on to get_price_including_tax() or get_price_excluding_tax()
	 * @return string
	 */
	public function get_price_suffix( $price = '', $qty = 1 ) {

		if ( '' === $price ) {
			$price = $this->get_price();
		}

		$price_display_suffix  = get_option( 'woocommerce_price_display_suffix' );
		$woocommerce_calc_taxes = get_option( 'woocommerce_calc_taxes', 'no' );

		if ( $price_display_suffix && 'yes' === $woocommerce_calc_taxes ) {

			$price_display_suffix = ' <small class="woocommerce-price-suffix">' . $price_display_suffix . '</small>';

			$find = array(
				'{price_including_tax}',
				'{price_excluding_tax}',
			);

			$replace = array(
				wc_price( $this->get_price_including_tax( $qty, $price ) ),
				wc_price( $this->get_price_excluding_tax( $qty, $price ) ),
			);

			$price_display_suffix = str_replace( $find, $replace, $price_display_suffix );
		} else {
			$price_display_suffix = '';
		}

		return apply_filters( 'woocommerce_get_price_suffix', $price_display_suffix, $this );
	}

	/**
	 * Returns the price in html format.
	 *
	 * @param string $price (default: '')
	 * @return string
	 */
	public function get_price_html( $price = '' ) {

		$display_price         = $this->get_display_price();
		$display_regular_price = $this->get_display_price( $this->get_regular_price() );

		if ( $this->get_price() > 0 ) {

			if ( $this->is_on_sale() && $this->get_regular_price() ) {

				$price .= $this->get_price_html_from_to( $display_regular_price, $display_price ) . $this->get_price_suffix();

				$price = apply_filters( 'woocommerce_sale_price_html', $price, $this );

			} else {

				$price .= wc_price( $display_price ) . $this->get_price_suffix();

				$price = apply_filters( 'woocommerce_price_html', $price, $this );

			}
		} elseif ( $this->get_price() === '' ) {

			$price = apply_filters( 'woocommerce_empty_price_html', '', $this );

		} elseif ( $this->get_price() == 0 ) {

			if ( $this->is_on_sale() && $this->get_regular_price() ) {

				$price .= $this->get_price_html_from_to( $display_regular_price, __( 'Free!', 'woocommerce' ) );

				$price = apply_filters( 'woocommerce_free_sale_price_html', $price, $this );

			} else {

				$price = '<span class="amount">' . __( 'Free!', 'woocommerce' ) . '</span>';

				$price = apply_filters( 'woocommerce_free_price_html', $price, $this );

			}
		}

		return apply_filters( 'woocommerce_get_price_html', $price, $this );
	}

	/**
	 * Functions for getting parts of a price, in html, used by get_price_html.
	 *
	 * @return string
	 */
	public function get_price_html_from_text() {
		$from = '<span class="from">' . _x( 'From:', 'From minimum price', 'woocommerce' ) . ' </span>';

		return apply_filters( 'woocommerce_get_price_html_from_text', $from, $this );
	}

	/**
	 * Functions for getting parts of a price, in html, used by get_price_html.
	 *
	 * @param  string $from String or float to wrap with 'from' text
	 * @param  mixed $to String or float to wrap with 'to' text
	 * @return string
	 */
	public function get_price_html_from_to( $from, $to ) {
		$price = '<del>' . ( ( is_numeric( $from ) ) ? wc_price( $from ) : $from ) . '</del> <ins>' . ( ( is_numeric( $to ) ) ? wc_price( $to ) : $to ) . '</ins>';

		return apply_filters( 'woocommerce_get_price_html_from_to', $price, $from, $to, $this );
	}

	/**
	 * Get the average rating of product. This is calculated once and stored in postmeta.
	 * @return string
	 */
	public function get_average_rating() {
		// No meta data? Do the calculation
		if ( ! metadata_exists( 'post', $this->get_id(), '_wc_average_rating' ) ) {
			$this->sync_average_rating( $this->get_id() );
		}

		return (string) floatval( get_post_meta( $this->get_id(), '_wc_average_rating', true ) );
	}

	/**
	 * Get the total amount (COUNT) of ratings.
	 * @param  int $value Optional. Rating value to get the count for. By default returns the count of all rating values.
	 * @return int
	 */
	public function get_rating_count( $value = null ) {
		// No meta data? Do the calculation
		if ( ! metadata_exists( 'post', $this->get_id(), '_wc_rating_count' ) ) {
			$this->sync_rating_count( $this->get_id() );
		}

		$counts = get_post_meta( $this->get_id(), '_wc_rating_count', true );

		if ( is_null( $value ) ) {
			return array_sum( $counts );
		} else {
			return isset( $counts[ $value ] ) ? $counts[ $value ] : 0;
		}
	}

	/**
	 * Sync product rating. Can be called statically.
	 * @param  int $post_id
	 */
	public static function sync_average_rating( $post_id ) {
		if ( ! metadata_exists( 'post', $post_id, '_wc_rating_count' ) ) {
			self::sync_rating_count( $post_id );
		}

		$count = array_sum( (array) get_post_meta( $post_id, '_wc_rating_count', true ) );

		if ( $count ) {
			global $wpdb;

			$ratings = $wpdb->get_var( $wpdb->prepare("
				SELECT SUM(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = %d
				AND comment_approved = '1'
				AND meta_value > 0
			", $post_id ) );
			$average = number_format( $ratings / $count, 2, '.', '' );
		} else {
			$average = 0;
		}
		update_post_meta( $post_id, '_wc_average_rating', $average );
	}

	/**
	 * Sync product rating count. Can be called statically.
	 * @param  int $post_id
	 */
	public static function sync_rating_count( $post_id ) {
		global $wpdb;

		$counts     = array();
		$raw_counts = $wpdb->get_results( $wpdb->prepare( "
			SELECT meta_value, COUNT( * ) as meta_value_count FROM $wpdb->commentmeta
			LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
			WHERE meta_key = 'rating'
			AND comment_post_ID = %d
			AND comment_approved = '1'
			AND meta_value > 0
			GROUP BY meta_value
		", $post_id ) );

		foreach ( $raw_counts as $count ) {
			$counts[ $count->meta_value ] = $count->meta_value_count;
		}

		update_post_meta( $post_id, '_wc_rating_count', $counts );
	}

	/**
	 * Returns the product rating in html format.
	 *
	 * @param string $rating (default: '')
	 *
	 * @return string
	 */
	public function get_rating_html( $rating = null ) {
		$rating_html = '';

		if ( ! is_numeric( $rating ) ) {
			$rating = $this->get_average_rating();
		}

		if ( $rating > 0 ) {

			$rating_html  = '<div class="star-rating">';

			/* translators: %s: rating */
			$rating_html .= '<span style="width:' . ( ( $rating / 5 ) * 100 ) . '%">' . sprintf( __( '%s out of 5', 'woocommerce' ), '<strong class="rating">' . $rating . '</strong>' ) . '</span>';

			$rating_html .= '</div>';
		}

		return apply_filters( 'woocommerce_product_get_rating_html', $rating_html, $rating );
	}

	/**
	 * Get the total amount (COUNT) of reviews.
	 *
	 * @since 2.3.2
	 * @return int The total numver of product reviews
	 */
	public function get_review_count() {
		global $wpdb;

		// No meta date? Do the calculation
		if ( ! metadata_exists( 'post', $this->get_id(), '_wc_review_count' ) ) {
			$count = $wpdb->get_var( $wpdb->prepare("
				SELECT COUNT(*) FROM $wpdb->comments
				WHERE comment_parent = 0
				AND comment_post_ID = %d
				AND comment_approved = '1'
			", $this->get_id() ) );

			update_post_meta( $this->get_id(), '_wc_review_count', $count );
		} else {
			$count = get_post_meta( $this->get_id(), '_wc_review_count', true );
		}

		return apply_filters( 'woocommerce_product_review_count', $count, $this );
	}

	/**
	 * Returns the upsell product ids.
	 *
	 * @return array
	 */
	public function get_upsells() {
		return apply_filters( 'woocommerce_product_upsell_ids', (array) maybe_unserialize( $this->upsell_ids ), $this );
	}

	/**
	 * Returns the cross sell product ids.
	 *
	 * @return array
	 */
	public function get_cross_sells() {
		return apply_filters( 'woocommerce_product_crosssell_ids', (array) maybe_unserialize( $this->crosssell_ids ), $this );
	}

	/**
	 * Returns the product shipping class.
	 *
	 * @return string
	 */
	public function get_shipping_class() {

		if ( ! $this->shipping_class ) {

			$classes = get_the_terms( $this->get_id(), 'product_shipping_class' );

			if ( $classes && ! is_wp_error( $classes ) ) {
				$this->shipping_class = current( $classes )->slug;
			} else {
				$this->shipping_class = '';
			}
		}

		return $this->shipping_class;
	}

	/**
	 * Returns the product shipping class ID.
	 *
	 * @return int
	 */
	public function get_shipping_class_id() {

		if ( ! $this->shipping_class_id ) {

			$classes = get_the_terms( $this->get_id(), 'product_shipping_class' );

			if ( $classes && ! is_wp_error( $classes ) ) {
				$this->shipping_class_id = current( $classes )->term_id;
			} else {
				$this->shipping_class_id = 0;
			}
		}

		return absint( $this->shipping_class_id );
	}

	/**
	 * Get and return related products.
	 *
	 * Notes:
	 * 	- Results are cached in a transient for faster queries.
	 *  - To make results appear random, we query and extra 10 products and shuffle them.
	 *  - To ensure we always have enough results, it will check $limit before returning the cached result, if not recalc.
	 *  - This used to rely on transient version to invalidate cache, but to avoid multiple transients we now just expire daily.
	 *  	This means if a related product is edited and no longer related, it won't be removed for 24 hours. Acceptable trade-off for performance.
	 *  - Saving a product will flush caches for that product.
	 *
	 * @param int $limit (default: 5) Should be an integer greater than 0.
	 * @return array Array of post IDs
	 */
	public function get_related( $limit = 5 ) {
		global $wpdb;

		$transient_name = 'wc_related_' . $this->get_id();
		$related_posts  = get_transient( $transient_name );
		$limit          = $limit > 0 ? $limit : 5;

		// We want to query related posts if they are not cached, or we don't have enough
		if ( false === $related_posts || sizeof( $related_posts ) < $limit ) {
			// Related products are found from category and tag
			$tags_array = $this->get_related_terms( 'product_tag' );
			$cats_array = $this->get_related_terms( 'product_cat' );

			// Don't bother if none are set
			if ( 1 === sizeof( $cats_array ) && 1 === sizeof( $tags_array ) ) {
				$related_posts = array();
			} else {
				// Sanitize
				$exclude_ids = array_map( 'absint', array_merge( array( 0, $this->get_id() ), $this->get_upsells() ) );

				// Generate query - but query an extra 10 results to give the appearance of random results
				$query = $this->build_related_query( $cats_array, $tags_array, $exclude_ids, $limit + 10 );

				// Get the posts
				$related_posts = $wpdb->get_col( implode( ' ', $query ) );
			}

			set_transient( $transient_name, $related_posts, DAY_IN_SECONDS );
		}

		// Randomise the results
		shuffle( $related_posts );

		// Limit the returned results
		return array_slice( $related_posts, 0, $limit );
	}

	/**
	 * Returns a single product attribute.
	 *
	 * @param mixed $attr
	 * @return string
	 */
	public function get_attribute( $attr ) {

		$attributes = $this->get_attributes();

		$attr = sanitize_title( $attr );

		if ( isset( $attributes[ $attr ] ) || isset( $attributes[ 'pa_' . $attr ] ) ) {

			$attribute = isset( $attributes[ $attr ] ) ? $attributes[ $attr ] : $attributes[ 'pa_' . $attr ];

			if ( isset( $attribute['is_taxonomy'] ) && $attribute['is_taxonomy'] ) {

				return implode( ', ', wc_get_product_terms( $this->get_id(), $attribute['name'], array( 'fields' => 'names' ) ) );

			} else {

				return $attribute['value'];
			}
		}

		return '';
	}

	/**
	 * Returns whether or not the product has any attributes set.
	 *
	 * @return boolean
	 */
	public function has_attributes() {

		if ( sizeof( $this->get_attributes() ) > 0 ) {

			foreach ( $this->get_attributes() as $attribute ) {

				if ( isset( $attribute['is_visible'] ) && $attribute['is_visible'] ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Returns whether or not we are showing dimensions on the product page.
	 *
	 * @return bool
	 */
	public function enable_dimensions_display() {
		return apply_filters( 'wc_product_enable_dimensions_display', true ) && ( $this->has_dimensions() || $this->has_weight() );
	}

	/**
	 * Returns whether or not the product has dimensions set.
	 *
	 * @return bool
	 */
	public function has_dimensions() {
		return $this->get_dimensions() ? true : false;
	}

	/**
	 * Does a child have dimensions set?
	 *
	 * @since 2.7.0
	 * @return bool
	 */
	public function child_has_dimensions() {
		return false;
	}

	/**
	 * Returns whether or not the product has weight set.
	 *
	 * @return bool
	 */
	public function has_weight() {
		return $this->get_weight() ? true : false;
	}

	/**
	 * Does a child have a weight set?
	 * @since 2.7.0
	 * @return boolean
	 */
	public function child_has_weight() {
		return false;
	}

	/**
	 * Returns formatted dimensions.
	 * @return string
	 */
	public function get_dimensions() {
		$dimensions = implode( ' x ', array_filter( array(
			wc_format_localized_decimal( $this->get_length() ),
			wc_format_localized_decimal( $this->get_width() ),
			wc_format_localized_decimal( $this->get_height() ),
		) ) );

		if ( ! empty( $dimensions ) ) {
			$dimensions .= ' ' . get_option( 'woocommerce_dimension_unit' );
		}

		return apply_filters( 'woocommerce_product_dimensions', $dimensions, $this );
	}

	/**
	 * Lists a table of attributes for the product page.
	 */
	public function list_attributes() {
		wc_get_template( 'single-product/product-attributes.php', array(
			'product'    => $this,
		) );
	}

	/**
	 * Gets the main product image ID.
	 *
	 * @return int
	 */
	public function get_image_id() {

		if ( has_post_thumbnail( $this->get_id() ) ) {
			$image_id = get_post_thumbnail_id( $this->get_id() );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->get_id() ) ) && has_post_thumbnail( $parent_id ) ) {
			$image_id = get_post_thumbnail_id( $parent_id );
		} else {
			$image_id = 0;
		}

		return $image_id;
	}

	/**
	 * Returns the main product image.
	 *
	 * @param string $size (default: 'shop_thumbnail')
	 * @param array $attr
	 * @param bool True to return $placeholder if no image is found, or false to return an empty string.
	 * @return string
	 */
	public function get_image( $size = 'shop_thumbnail', $attr = array(), $placeholder = true ) {
		if ( has_post_thumbnail( $this->get_id() ) ) {
			$image = get_the_post_thumbnail( $this->get_id(), $size, $attr );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $this->get_id() ) ) && has_post_thumbnail( $parent_id ) ) {
			$image = get_the_post_thumbnail( $parent_id, $size, $attr );
		} elseif ( $placeholder ) {
			$image = wc_placeholder_img( $size );
		} else {
			$image = '';
		}
		return str_replace( array( 'https://', 'http://' ), '//', $image );
	}

	/**
	 * Get product name with SKU or ID. Used within admin.
	 *
	 * @return string Formatted product name
	 */
	public function get_formatted_name() {
		if ( $this->get_sku() ) {
			$identifier = $this->get_sku();
		} else {
			$identifier = '#' . $this->get_id();
		}

		return sprintf( '%s &ndash; %s', $identifier, $this->get_title() );
	}

	/**
	 * Retrieves related product terms.
	 *
	 * @param string $term
	 * @return array
	 */
	protected function get_related_terms( $term ) {
		$terms_array = array( 0 );

		$terms = apply_filters( 'woocommerce_get_related_' . $term . '_terms', wp_get_post_terms( $this->get_id(), $term ), $this->get_id() );
		foreach ( $terms as $term ) {
			$terms_array[] = $term->term_id;
		}

		return array_map( 'absint', $terms_array );
	}

	/**
	 * Builds the related posts query.
	 *
	 * @param array $cats_array
	 * @param array $tags_array
	 * @param array $exclude_ids
	 * @param int   $limit
	 * @return string
	 */
	protected function build_related_query( $cats_array, $tags_array, $exclude_ids, $limit ) {
		global $wpdb;

		$limit = absint( $limit );

		$query           = array();
		$query['fields'] = "SELECT DISTINCT ID FROM {$wpdb->posts} p";
		$query['join']   = " INNER JOIN {$wpdb->postmeta} pm ON ( pm.post_id = p.ID AND pm.meta_key='_visibility' )";
		$query['join']  .= " INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)";
		$query['join']  .= " INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";
		$query['join']  .= " INNER JOIN {$wpdb->terms} t ON (t.term_id = tt.term_id)";

		if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' ) {
			$query['join'] .= " INNER JOIN {$wpdb->postmeta} pm2 ON ( pm2.post_id = p.ID AND pm2.meta_key='_stock_status' )";
		}

		$query['where']  = " WHERE 1=1";
		$query['where'] .= " AND p.post_status = 'publish'";
		$query['where'] .= " AND p.post_type = 'product'";
		$query['where'] .= " AND p.ID NOT IN ( " . implode( ',', $exclude_ids ) . " )";
		$query['where'] .= " AND pm.meta_value IN ( 'visible', 'catalog' )";

		if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' ) {
			$query['where'] .= " AND pm2.meta_value = 'instock'";
		}

		$relate_by_category = apply_filters( 'woocommerce_product_related_posts_relate_by_category', true, $this->get_id() );
		$relate_by_tag      = apply_filters( 'woocommerce_product_related_posts_relate_by_tag', true, $this->get_id() );

		if ( $relate_by_category || $relate_by_tag ) {
			$query['where'] .= ' AND (';

			if ( $relate_by_category ) {
				$query['where'] .= " ( tt.taxonomy = 'product_cat' AND t.term_id IN ( " . implode( ',', $cats_array ) . " ) ) ";
				if ( $relate_by_tag ) {
					$query['where'] .= ' OR ';
				}
			}

			if ( $relate_by_tag ) {
				$query['where'] .= " ( tt.taxonomy = 'product_tag' AND t.term_id IN ( " . implode( ',', $tags_array ) . " ) ) ";
			}

			$query['where'] .= ')';
		}

		$query['limits'] = " LIMIT {$limit} ";
		$query           = apply_filters( 'woocommerce_product_related_posts_query', $query, $this->get_id() );

		return $query;
	}

	/**
	 * Save taxonomy terms.
	 *
	 * @since 2.7.0
	 * @param array $terms_id Terms ID.
	 * @param string $taxonomy Taxonomy.
	 * @return array|WP_Error
	 */
	protected function save_taxonomy_terms( $terms_id, $taxonomy = 'cat' ) {
		$terms_id = array_unique( array_map( 'intval', $terms_id ) );

		return wp_set_object_terms( $this->get_id(), $terms_id, 'product_' . $taxonomy );
	}
}
