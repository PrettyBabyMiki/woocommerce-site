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
	 * Stores product data.
	 *
	 * @todo download_limit download_expiry download_type downloads
	 *
	 * @var array
	 */
	protected $data = array(
		'name'               => '',
		'slug'               => '',
		'date_created'       => '',
		'date_modified'      => '',
		'status'             => false,
		'featured'           => false,
		'catalog_visibility' => 'hidden',
		'description'        => '',
		'short_description'  => '',
		'sku'                => '',
		'price'              => '',
		'regular_price'      => '',
		'sale_price'         => '',
		'date_on_sale_from'  => '',
		'date_on_sale_to'    => '',
		'total_sales'        => '0',
		'tax_status'         => 'taxable',
		'tax_class'          => '',
		'manage_stock'       => false,
		'stock_quantity'     => null,
		'stock_status'       => '',
		'backorders'         => 'no',
		'sold_individually'  => false,
		'weight'             => '',
		'length'             => '',
		'width'              => '',
		'height'             => '',
		'upsell_ids'         => array(),
		'cross_sell_ids'     => array(),
		'parent_id'          => 0,
		'reviews_allowed'    => true,
		'purchase_note'      => '',
		'attributes'         => array(),
		'default_attributes' => array(),
		'menu_order'         => 0,
		'virtual'            => false,
		'downloadable'       => false,
		'category_ids'       => array(),
		'tag_ids'            => array(),
		'shipping_class_id'  => 0,
		'downloads'          => array(),
		'thumbnail_id'           => '',
		'gallery_attachment_ids' => array(),
		'download_limit'         => -1,
		'download_expiry'        => -1,
		'download_type'          => 'standard',
	);

	/**
	 * Data stored in meta keys, but not considered "meta".
	 *
	 * @since 2.7.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_visibility',
		'_sku',
		'_price',
		'_regular_price',
		'_sale_price',
		'_sale_price_dates_from',
		'_sale_price_dates_to',
		'total_sales',
		'_tax_status',
		'_tax_class',
		'_manage_stock',
		'_stock',
		'_stock_status',
		'_backorders',
		'_sold_individually',
		'_weight',
		'_length',
		'_width',
		'_height',
		'_upsell_ids',
		'_crosssell_ids',
		'_purchase_note',
		'_default_attributes',
		'_product_attributes',
		'_virtual',
		'_downloadable',
		'_featured',
		'_downloadable_files',
	);

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
		parent::__construct( $product );
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
	 * Get internal type.
	 * @since 2.7.0
	 * @return string
	 */
	public function get_type() {
		return 'simple';
	}

	/**
	 * Product permalink.
	 * @return string
	 */
	public function get_permalink() {
		return get_permalink( $this->get_id() );
	}

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
	 * @since 2.7.0
	 * @return string
	 */
	public function get_slug() {
		return $this->data['slug'];
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
	 * @return boolean
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
	 * Returns the product's active price.
	 *
	 * @return string price
	 */
	public function get_price() {
		return apply_filters( 'woocommerce_get_price', $this->data['price'], $this );
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
	 * @return boolean
	 */
	public function get_manage_stock() {
		return $this->data['manage_stock'];
	}

	/**
	 * Returns number of items available for sale.
	 *
	 * @return int|null
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
	 * @return string yes no or notify
	 */
	public function get_backorders() {
		return $this->data['backorders'];
	}

	/**
	 * Return if should be sold individually.
	 *
	 * @since 2.7.0
	 * @return boolean
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
		$weight = apply_filters( 'woocommerce_product_weight', $this->data['weight'], $this ); // @todo standardize these filter names and move BW compat to deprecated class file.

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
	 * Get upsel IDs.
	 *
	 * @since 2.7.0
	 * @return array
	 */
	public function get_upsell_ids() {
		return $this->data['upsell_ids'];
	}

	/**
	 * Get cross sell IDs.
	 *
	 * @since 2.7.0
	 * @return array
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
	 * Returns product attributes.
	 *
	 * @return array
	 */
	public function get_attributes() {
		return apply_filters( 'woocommerce_get_product_attributes', $this->data['attributes'] );
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

	/**
	 * Get category ids.
	 *
	 * @since 2.7.0
	 * @return array
	 */
	public function get_category_ids() {
		return $this->data['category_ids'];
	}

	/**
	 * Get tag ids.
	 *
	 * @since 2.7.0
	 * @return array
	 */
	public function get_tag_ids() {
		return $this->data['tag_ids'];
	}

	/**
	 * Get virtual.
	 *
	 * @since 2.7.0
	 * @return bool
	 */
	public function get_virtual() {
		return $this->data['virtual'];
	}

	/**
	 * Get downloadable.
	 *
	 * @since 2.7.0
	 * @return bool
	 */
	public function get_downloadable() {
		return $this->data['downloadable'];
	}

	/**
	 * Returns the gallery attachment ids.
	 *
	 * @return array
	 */
	public function get_gallery_attachment_ids() {
		return apply_filters( 'woocommerce_product_gallery_attachment_ids', array_filter( array_filter( $this->data['gallery_attachment_ids'] ), 'wp_attachment_is_image' ), $this );
	}

	/**
	 * Get download limit.
	 *
	 * @since 2.7.0
	 * @return int
	 */
	public function get_download_limit() {
		return $this->data['download_limit'];
	}

	/**
	 * Get shipping class ID.
	 *
	 * @since 2.7.0
	 * @return int
	 */
	public function get_shipping_class_id() {
		return $this->data['shipping_class_id'];
	}

	/**
	 * Get downloads.
	 *
	 * @since 2.7.0
	 * @return array
	 */
	public function get_downloads() {
		return $this->data['downloads'];
	}

	/**
	 * Get download expiry.
	 *
	 * @since 2.7.0
	 * @return int
	 */
	public function get_download_expiry() {
		return $this->data['download_expiry'];
	}

	/**
	 * Get download type.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_download_type() {
		return $this->data['download_type'];
	}

	/**
	 * Get thumbnail ID.
	 *
	 * @since 2.7.0
	 * @return string
	 */
	public function get_thumbnail_id() {
		return $this->data['thumbnail_id'];
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
	 * @param bool|string
	 */
	public function set_featured( $featured ) {
		$this->data['featured'] = wc_string_to_bool( $featured );
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
		$sku = (string) $sku;
		if ( ! empty( $sku ) && ! wc_product_has_unique_sku( $this->get_id(), $sku ) ) {
			$this->error( 'product_invalid_sku', __( 'Invalid or duplicated SKU.', 'woocommerce' ) );
		}
		$this->data['sku'] = $sku;
	}

	/**
	 * Set the product's active price.
	 *
	 * @param string $price Price.
	 */
	public function set_price( $price ) {
		$this->data['price'] = wc_format_decimal( $price );
	}

	/**
	 * Set the product's regular price.
	 *
	 * @since 2.7.0
	 * @param string $price Regular price.
	 */
	public function set_regular_price( $price ) {
		$this->data['regular_price'] = wc_format_decimal( $price );
	}

	/**
	 * Set the product's sale price.
	 *
	 * @since 2.7.0
	 * @param string $price sale price.
	 */
	public function set_sale_price( $price ) {
		$this->data['sale_price'] = wc_format_decimal( $price );
	}

	/**
	 * Set date on sale from.
	 *
	 * @since 2.7.0
	 * @param string $timestamp Sale from date.
	 */
	public function set_date_on_sale_from( $timestamp ) {
		$this->data['date_on_sale_from'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
	}

	/**
	 * Set date on sale to.
	 *
	 * @since 2.7.0
	 * @param string $timestamp Sale to date.
	 */
	public function set_date_on_sale_to( $timestamp ) {
		return $this->data['date_on_sale_to'] = is_numeric( $timestamp ) ? $timestamp : strtotime( $timestamp );
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

		// Set default if empty.
		if ( empty( $status ) ) {
			$status = 'taxable';
		}

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
	 * @param bool
	 */
	public function set_manage_stock( $manage_stock ) {
		$this->data['manage_stock'] = wc_string_to_bool( $manage_stock );
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
			do_action( 'woocommerce_product_set_stock_status', $this->get_id(), $status );
		}

		$this->data['stock_status'] = $status;
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
	 * @param bool
	 */
	public function set_sold_individually( $sold_individually ) {
		$this->data['sold_individually'] = wc_string_to_bool( $sold_individually );
	}

	/**
	 * Set the product's weight.
	 *
	 * @since 2.7.0
	 * @param float $weigth Total weigth.
	 */
	public function set_weight( $weight ) {
		$this->data['weight'] = '' === $weight ? '' : wc_format_decimal( $weight );
	}

	/**
	 * Set the product length.
	 *
	 * @since 2.7.0
	 * @param float $weigth Total weigth.
	 */
	public function set_length( $length ) {
		$this->data['length'] = '' === $length ? '' : wc_format_decimal( $length );
	}

	/**
	 * Set the product width.
	 *
	 * @since 2.7.0
	 * @param float $width Total width.
	 */
	public function set_width( $width ) {
		$this->data['width'] = '' === $width ? '' : wc_format_decimal( $width );
	}

	/**
	 * Set the product height.
	 *
	 * @since 2.7.0
	 * @param float $height Total height.
	 */
	public function set_height( $height ) {
		$this->data['height'] = '' === $height ? '' : wc_format_decimal( $height );
	}

	/**
	 * Set upsell IDs.
	 *
	 * @since 2.7.0
	 * @param string $upsell_ids IDs from the up-sell products.
	 */
	public function set_upsell_ids( $upsell_ids ) {
		$this->data['upsell_ids'] = array_filter( (array) $upsell_ids );
	}

	/**
	 * Set crosssell IDs.
	 *
	 * @since 2.7.0
	 * @param string $cross_sell_ids IDs from the cross-sell products.
	 */
	public function set_cross_sell_ids( $cross_sell_ids ) {
		$this->data['cross_sell_ids'] = array_filter( (array) $cross_sell_ids );
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
		$this->data['reviews_allowed'] = wc_string_to_bool( $reviews_allowed );
	}

	/**
	 * Set purchase note.
	 *
	 * @since 2.7.0
	 * @param string $purchase_note Purchase note.
	 */
	public function set_purchase_note( $purchase_note ) {
		$this->data['purchase_note'] = $purchase_note;
	}

	/**
	 * Set product attributes.
	 *
	 * Attributes are made up of:
	 * 		id - 0 for product level attributes. ID for global attributes.
	 * 		name - Attribute name.
	 * 		options - attribute value or array of term ids/names.
	 * 		position - integer sort order.
	 * 		visible - If visible on frontend.
	 * 		variation - If used for variations.
	 * 	Indexed by unqiue key to allow clearing old ones after a set.
	 *
	 * @since 2.7.0
	 * @param array $raw_attributes Array of WC_Product_Attribute objects.
	 */
	public function set_attributes( $raw_attributes ) {
		$attributes = array_fill_keys( array_keys( $this->data['attributes'] ), null );

		foreach ( $raw_attributes as $attribute ) {
			if ( is_a( $attribute, 'WC_Product_Attribute' ) ) {
				$attributes[ sanitize_title( $attribute->get_name() ) ] = $attribute;
			}
		}

		uasort( $attributes, 'wc_product_attribute_uasort_comparison' );
		$this->data['attributes'] = $attributes;
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

	/**
	 * Set the product categories.
	 *
	 * @since 2.7.0
	 * @param array $term_ids List of terms IDs.
	 */
	public function set_category_ids( $term_ids ) {
		$this->data['category_ids'] = $this->sanitize_term_ids( $term_ids, 'product_cat' );
	}

	/**
	 * Set the product tags.
	 *
	 * @since 2.7.0
	 * @param array $term_ids List of terms IDs.
	 */
	public function set_tag_ids( $term_ids ) {
		$this->data['tag_ids'] = $this->sanitize_term_ids( $term_ids, 'product_tag' );
	}

	/**
	 * Set if the product is virtual.
	 *
	 * @since 2.7.0
	 * @param bool|string
	 */
	public function set_virtual( $virtual ) {
		$this->data['virtual'] = wc_string_to_bool( $virtual );
	}

	/**
	 * Set if the product is downloadable.
	 *
	 * @since 2.7.0
	 * @param bool|string
	 */
	public function set_downloadable( $downloadable ) {
		$this->data['downloadable'] = wc_string_to_bool( $downloadable );
	}

	/**
	 * Set shipping class ID.
	 *
	 * @since 2.7.0
	 * @param int
	 */
	public function set_shipping_class_id( $id ) {
		$this->data['shipping_class_id'] = absint( $id );
	}

	/**
	 * Set downloads.
	 *
	 * @since 2.7.0
	 * @param $raw_downloads array of arrays with download data (name/file)
	 */
	public function set_downloads( $raw_downloads ) {
		$downloads          = array();
		$errors             = array();
		$allowed_file_types = apply_filters( 'woocommerce_downloadable_file_allowed_mime_types', get_allowed_mime_types() );

		foreach ( $raw_downloads as $raw_download ) {
			$file_name = wc_clean( $raw_download['name'] );

			// Find type and file URL
			if ( 0 === strpos( $raw_download['file'], 'http' ) ) {
				$file_is  = 'absolute';
				$file_url = esc_url_raw( $raw_download['file'] );
			} elseif ( '[' === substr( $raw_download['file'], 0, 1 ) && ']' === substr( $raw_download['file'], -1 ) ) {
				$file_is  = 'shortcode';
				$file_url = wc_clean( $raw_download['file'] );
			} else {
				$file_is = 'relative';
				$file_url = wc_clean( $raw_download['file'] );
			}

			$file_name = wc_clean( $raw_download['name'] );

			// Validate the file extension
			if ( in_array( $file_is, array( 'absolute', 'relative' ) ) ) {
				$file_type  = wp_check_filetype( strtok( $file_url, '?' ), $allowed_file_types );
				$parsed_url = parse_url( $file_url, PHP_URL_PATH );
				$extension  = pathinfo( $parsed_url, PATHINFO_EXTENSION );
				if ( ! empty( $extension ) && ! in_array( $file_type['type'], $allowed_file_types ) ) {
					$errors[] = sprintf( __( 'The downloadable file %1$s cannot be used as it does not have an allowed file type. Allowed types include: %2$s', 'woocommerce' ), '<code>' . basename( $file_url ) . '</code>', '<code>' . implode( ', ', array_keys( $allowed_file_types ) ) . '</code>' );
					continue;
				}
			}

			// Validate the file exists
			if ( 'relative' === $file_is ) {
				$_file_url = $file_url;
				if ( '..' === substr( $file_url, 0, 2 ) || '/' !== substr( $file_url, 0, 1 ) ) {
					$_file_url = realpath( ABSPATH . $file_url );
				}

				if ( ! apply_filters( 'woocommerce_downloadable_file_exists', file_exists( $_file_url ), $file_url ) ) {
					$errors[] = sprintf( __( 'The downloadable file %s cannot be used as it does not exist on the server.', 'woocommerce' ), '<code>' . $file_url . '</code>' );
					continue;
				}
			}

			$downloads[ md5( $file_url ) ] = array(
				'name' => $file_name,
				'file' => $file_url,
			);
		}

		$this->data['downloads'] = $downloads;

		if ( $errors ) {
			$this->error( 'product_invalid_download', $errors[0] );
		}
	}

	/**
	 * Set gallery attachment ids.
	 *
	 * @since 2.7.0
	 * @param array $gallery_ids
	 */
	public function set_gallery_attachment_ids( $gallery_ids ) {
		$this->data['gallery_attachment_ids'] = $gallery_ids;
	}

	/**
	 * Set download limit.
	 *
	 * @since 2.7.0
	 * @param int $download_limit
	 */
	public function set_download_limit( $download_limit ) {
		$this->data['download_limit'] = -1 === (int) $download_limit || '' === $download_limit ? -1 : absint( $download_limit );
	}

	/**
	 * Set download expiry.
	 *
	 * @since 2.7.0
	 * @param int $download_expiry
	 */
	public function set_download_expiry( $download_expiry ) {
		$this->data['download_expiry'] = -1 === (int) $download_expiry || '' === $download_expiry ? -1 : absint( $download_expiry );
	}

	/**
	 * Set download type.
	 *
	 * @since 2.7.0
	 * @param string $download_type
	 */
	public function set_download_type( $download_type ) {
		$this->data['download_type'] = $download_type;
	}

	/**
	 * Set thumbnail ID.
	 *
	 * @since 2.7.0
	 * @param int $thumbnail_id
	 */
	public function set_thumbnail_id( $thumbnail_id = '' ) {
		$this->data['thumbnail_id'] = $thumbnail_id;
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
	 * Get and store terms from a taxonomy.
	 *
	 * @since  2.7.0
	 * @param  string $taxonomy Taxonomy name e.g. product_cat
	 * @return array of terms
	 */
	protected function get_term_ids( $taxonomy ) {
		return wp_get_post_terms( $this->get_id(), $taxonomy, array( 'fields' => 'ids' ) );
	}

	/**
	 * Get term ids from either a list of names, ids, or terms.
	 *
	 * @since 2.7.0
	 * @param array $terms
	 * @param string $taxonomy
	 */
	protected function sanitize_term_ids( $terms, $taxonomy ) {
		$term_ids = array();
		foreach ( $terms as $term ) {
			if ( is_object( $term ) ) {
				$term_ids[] = $term->term_id;
			} elseif ( is_integer( $term ) ) {
				$term_ids[] = absint( $term );
			} else {
				$term_object = get_term_by( 'name', $term, $taxonomy );

				if ( $term_object && ! is_wp_error( $term_object ) ) {
					$term_ids[] = $term_object->term_id;
				}
			}
		}
		return $term_ids;
	}

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
			'catalog_visibility' => get_post_meta( $id, '_visibility', true ),
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
			'manage_stock'       => get_post_meta( $id, '_manage_stock', true ),
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
			'reviews_allowed'    => 'open' === $post_object->comment_status,
			'purchase_note'      => get_post_meta( $id, '_purchase_note', true ),
			'default_attributes' => get_post_meta( $id, '_default_attributes', true ),
			'menu_order'         => $post_object->menu_order,
			'category_ids'       => $this->get_term_ids( 'product_cat' ),
			'tag_ids'            => $this->get_term_ids( 'product_tag' ),
			'shipping_class_id'  => current( $this->get_term_ids( 'product_shipping_class' ) ),
			'virtual'            => get_post_meta( $id, '_virtual', true ),
			'downloadable'       => get_post_meta( $id, '_downloadable', true ),
			'downloads'          => array_filter( (array) get_post_meta( $id, '_downloadable_files', true ) ),
			'gallery_attachment_ids' => array_filter( explode( ',', get_post_meta( $id, '_product_image_gallery', true ) ) ),
			'download_limit'         =>  get_post_meta( $id, '_download_limit', true ),
			'download_expiry'        => get_post_meta( $id, '_download_expiry', true ),
			'download_type'          => get_post_meta( $id, '_download_type', true ),
			'thumbnail_id'           => get_post_thumbnail_id( $id ),
		) );
		if ( $this->is_on_sale() ) {
			$this->set_price( $this->get_sale_price() );
		} else {
			$this->set_price( $this->get_regular_price() );
		}
		$this->read_meta_data();
		$this->read_attributes();
	}

	/**
	 * Read attributes from post meta.
	 *
	 * @since 2.7.0
	 */
	protected function read_attributes() {
		$meta_values = maybe_unserialize( get_post_meta( $this->get_id(), '_product_attributes', true ) );

		if ( $meta_values ) {
			$attributes = array();
			foreach ( $meta_values as $meta_value ) {
				if ( ! empty( $meta_value['is_taxonomy'] ) ) {
					if ( ! taxonomy_exists( $meta_value['name'] ) ) {
						continue;
					}
					$options = wp_get_post_terms( $this->get_id(), $meta_value['name'], array( 'fields' => 'ids' ) );
				} else {
					$options = wc_get_text_attributes( $meta_value['value'] );
				}
				$attribute = new WC_Product_Attribute();
				$attribute->set_id( wc_attribute_taxonomy_id_by_name( $meta_value['name'] ) );
				$attribute->set_name( $meta_value['name'] );
				$attribute->set_options( $options );
				$attribute->set_position( $meta_value['position'] );
				$attribute->set_visible( $meta_value['is_visible'] );
				$attribute->set_variation( $meta_value['is_variation'] );
				$attributes[] = $attribute;
			}
			$this->set_attributes( $attributes );
		}
	}

	/**
	 * Create a new product.
	 *
	 * @since 2.7.0
	 */
	public function create() {
		$this->set_date_created( current_time( 'timestamp' ) );

		$id = wp_insert_post( apply_filters( 'woocommerce_new_product_data', array(
			'post_type'      => 'product',
			'post_status'    => $this->get_status() ? $this->get_status() : 'publish',
			'post_author'    => get_current_user_id(),
			'post_title'     => $this->get_name() ? $this->get_name() : __( 'Product', 'woocommerce' ),
			'post_content'   => $this->get_description(),
			'post_excerpt'   => $this->get_short_description(),
			'post_parent'    => $this->get_parent_id(),
			'comment_status' => $this->get_reviews_allowed() ? 'open' : 'closed',
			'ping_status'    => 'closed',
			'menu_order'     => $this->get_menu_order(),
			'post_date'      => date( 'Y-m-d H:i:s', $this->get_date_created() ),
			'post_date_gmt'  => get_gmt_from_date( date( 'Y-m-d H:i:s', $this->get_date_created() ) ),
		) ), true );

		if ( $id && ! is_wp_error( $id ) ) {
			$this->set_id( $id );
			wp_set_object_terms( $id, $this->get_type(), 'product_type' );
			$this->update_post_meta();
			$this->update_terms();
			$this->update_attributes();
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
		$post_data = array(
			'ID'             => $this->get_id(),
			'post_content'   => $this->get_description(),
			'post_excerpt'   => $this->get_short_description(),
			'post_title'     => $this->get_name(),
			'post_parent'    => $this->get_parent_id(),
			'comment_status' => $this->get_reviews_allowed() ? 'open' : 'closed',
			'post_status'    => $this->get_status() ? $this->get_status() : 'publish',
			'menu_order'     => $this->get_menu_order(),
		);
		wp_update_post( $post_data );
		$this->update_post_meta();
		$this->update_terms();
		$this->update_attributes();
		$this->save_meta_data();
		do_action( 'woocommerce_update_product', $this->get_id() );
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
		// Make sure we store the product type.
		$type_term = get_term_by( 'name', $this->get_type(), 'product_type' );
		wp_set_object_terms( $this->get_id(), absint( $type_term->term_id ), 'product_type' );

		// Version is set to current WC version to track data changes.
		update_post_meta( $this->get_id(), '_product_version', WC_VERSION );
		wc_delete_product_transients( $this->get_id() );
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
	 */
	protected function update_post_meta() {
		$id = $this->get_id();
		update_post_meta( $id, '_visibility', $this->get_catalog_visibility() );
		update_post_meta( $id, '_sku', $this->get_sku() );
		update_post_meta( $id, '_regular_price', $this->get_regular_price() );
		update_post_meta( $id, '_sale_price', $this->get_sale_price() );
		update_post_meta( $id, '_sale_price_dates_from', $this->get_date_on_sale_from() );
		update_post_meta( $id, '_sale_price_dates_to', $this->get_date_on_sale_to() );
		update_post_meta( $id, 'total_sales', $this->get_total_sales() );
		update_post_meta( $id, '_tax_status', $this->get_tax_status() );
		update_post_meta( $id, '_tax_class', $this->get_tax_class() );
		update_post_meta( $id, '_manage_stock', $this->get_manage_stock() );
		update_post_meta( $id, '_stock', $this->get_stock_quantity() );
		update_post_meta( $id, '_stock_status', $this->get_stock_status() );
		update_post_meta( $id, '_backorders', $this->get_backorders() );
		update_post_meta( $id, '_sold_individually', $this->get_sold_individually() );
		update_post_meta( $id, '_weight', $this->get_weight() );
		update_post_meta( $id, '_length', $this->get_length() );
		update_post_meta( $id, '_width', $this->get_width() );
		update_post_meta( $id, '_height', $this->get_height() );
		update_post_meta( $id, '_upsell_ids', $this->get_upsell_ids() );
		update_post_meta( $id, '_crosssell_ids', $this->get_cross_sell_ids() );
		update_post_meta( $id, '_purchase_note', $this->get_purchase_note() );
		update_post_meta( $id, '_default_attributes', $this->get_default_attributes() );
		update_post_meta( $id, '_virtual', $this->get_virtual() ? 'yes' : 'no' );
		update_post_meta( $id, '_downloadable', $this->get_downloadable() ? 'yes' : 'no' );
		update_post_meta( $id, '_product_image_gallery', implode( ',', $this->get_gallery_attachment_ids() ) );
		update_post_meta( $id, '_download_limit', $this->get_download_limit() );
		update_post_meta( $id, '_download_expiry', $this->get_download_expiry() );
		update_post_meta( $id, '_download_type', $this->get_download_type() );

		if ( update_post_meta( $id, '_featured', $this->get_featured() ) ) {
			delete_transient( 'wc_featured_products' );
		}

		if ( ! empty( $this->get_thumbnail_id() ) ) {
			set_post_thumbnail( $id, $this->get_thumbnail_id() );
		} else {
			delete_post_meta( $id, '_thumbnail_id' );
		}

		if ( $this->is_on_sale() ) {
			update_post_meta( $id, '_price', $this->get_sale_price() );
		} else {
			update_post_meta( $id, '_price', $this->get_regular_price() );
		}

		if ( update_post_meta( $id, '_downloadable_files', $this->get_downloads() ) ) {
			// grant permission to any newly added files on any existing orders for this product prior to saving @todo hook for variations?
			do_action( 'woocommerce_process_product_file_download_paths', $id, 0, $this->get_downloads() );
		}
	}

	/**
	 * For all stored terms in all taxonomies, save them to the DB.
	 *
	 * @since 2.7.0
	 */
	protected function update_terms() {
		wp_set_post_terms( $this->get_id(), $this->data['category_ids'], 'product_cat', false );
		wp_set_post_terms( $this->get_id(), $this->data['tag_ids'], 'product_tag', false );
		wp_set_post_terms( $this->get_id(), array( $this->data['shipping_class_id'] ), 'product_shipping_class', false );
	}

	/**
	 * Update attributes which are a mix of terms and meta data.
	 *
	 * @since 2.7.0
	 */
	protected function update_attributes() {
		$attributes  = $this->get_attributes();
		$meta_values = array();

		if ( $attributes ) {
			foreach ( $attributes as $attribute_key => $attribute ) {
				$value = '';

				if ( is_null( $attribute ) ) {
					if ( taxonomy_exists( $attribute_key ) ) {
						// Handle attributes that have been unset.
						wp_set_object_terms( $this->get_id(), array(), $attribute_key );
					}
					continue;

				} elseif ( $attribute->is_taxonomy() ) {
					wp_set_object_terms( $this->get_id(), wp_list_pluck( $attribute->get_terms(), 'term_id' ), $attribute->get_name() );

				} else {
					$value = wc_implode_text_attributes( $attribute->get_options() );
				}

				// Store in format WC uses in meta.
				$meta_values[ $attribute_key ] = array(
					'name'         => $attribute->get_name(),
					'value'        => $value,
					'position'     => $attribute->get_position(),
					'is_visible'   => $attribute->get_visible() ? 1 : 0,
					'is_variation' => $attribute->get_variation() ? 1 : 0,
					'is_taxonomy'  => $attribute->is_taxonomy() ? 1 : 0,
				);
			}
		}
		update_post_meta( $this->get_id(), '_product_attributes', $meta_values );
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
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
	 * Returns whether or not the product post exists.
	 *
	 * @return bool
	 */
	public function exists() {
		return false !== $this->get_status();
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
		return ( $this->get_type() === $type || ( is_array( $type ) && in_array( $this->get_type(), $type ) ) );
	}

	/**
	 * Checks if a product is downloadable.
	 *
	 * @return bool
	 */
	public function is_downloadable() {
		return apply_filters( 'woocommerce_is_downloadable', true === $this->data['downloadable'] , $this );
	}

	/**
	 * Checks if a product is virtual (has no shipping).
	 *
	 * @return bool
	 */
	public function is_virtual() {
		return apply_filters( 'woocommerce_is_virtual', true === $this->data['virtual'], $this );
	}

	/**
	 * Returns whether or not the product is featured.
	 *
	 * @return bool
	 */
	public function is_featured() {
		return true === $this->get_featured();
	}

	/**
	 * Check if a product is sold individually (no quantities).
	 *
	 * @return bool
	 */
	public function is_sold_individually() {
		return apply_filters( 'woocommerce_is_sold_individually', true === $this->get_sold_individually(), $this );
	}

	/**
	 * Returns whether or not the product is visible in the catalog.
	 *
	 * @return bool
	 */
	public function is_visible() {
		$visible = 'visible' === $this->get_catalog_visibility() || ( is_search() && 'search' === $this->get_catalog_visibility() ) || ( ! is_search() && 'catalog' === $this->get_catalog_visibility() );

		if ( 'publish' !== $this->get_status() && ! current_user_can( 'edit_post', $this->get_id() ) ) {
			$visible = false;
		}

		if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $this->is_in_stock() ) {
			$visible = false;
		}

		return apply_filters( 'woocommerce_product_is_visible', $visible, $this->get_id() );
	}

	/**
	 * Returns false if the product cannot be bought.
	 *
	 * @return bool
	 */
	public function is_purchasable() {
		return apply_filters( 'woocommerce_is_purchasable', $this->exists() && ( 'publish' === $this->get_status() || current_user_can( 'edit_post', $this->get_id() ) ) && '' !== $this->get_price(), $this );
	}

	/**
	 * Returns whether or not the product is on sale.
	 *
	 * @return bool
	 */
	public function is_on_sale() {
		if ( '' !== $this->get_sale_price() && $this->get_regular_price() > $this->get_sale_price() ) {
			$onsale = true;

			if ( '' !== $this->get_date_on_sale_from() && $this->get_date_on_sale_from() > strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				$onsale = false;
			}

			if ( '' !== $this->get_date_on_sale_to() && $this->get_date_on_sale_to() < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
				$onsale = false;
			}
		} else {
			$onsale = false;
		}
		return apply_filters( 'woocommerce_product_is_on_sale', $onsale, $this );
	}

	/**
	 * Returns whether or not the product has dimensions set.
	 *
	 * @return bool
	 */
	public function has_dimensions() {
		return $this->get_length() || $this->get_height() || $this->get_width();
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
	 * Returns whether or not the product is in stock.
	 *
	 * @return bool
	 */
	public function is_in_stock() {
		return apply_filters( 'woocommerce_product_is_in_stock', 'instock' === $this->get_stock_status(), $this );
	}

	/**
	 * Checks if a product needs shipping.
	 *
	 * @return bool
	 */
	public function needs_shipping() {
		return apply_filters( 'woocommerce_product_needs_shipping', ! $this->is_virtual(), $this );
	}

	/**
	 * Returns whether or not the product is taxable.
	 *
	 * @return bool
	 */
	public function is_taxable() {
		return apply_filters( 'woocommerce_product_is_taxable', $this->get_tax_status() === 'taxable' && wc_tax_enabled(), $this );
	}

	/**
	 * Returns whether or not the product shipping is taxable.
	 *
	 * @return bool
	 */
	public function is_shipping_taxable() {
		return $this->get_tax_status() === 'taxable' || $this->get_tax_status() === 'shipping';
	}

	/**
	 * Returns whether or not the product is stock managed.
	 *
	 * @return bool
	 */
	public function managing_stock() {
		return $this->get_manage_stock() && 'yes' === get_option( 'woocommerce_manage_stock' );
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
		return apply_filters( 'woocommerce_product_backorders_require_notification', ( $this->managing_stock() && 'notify' === $this->get_backorders() ), $this );
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
	 * Returns whether or not we are showing dimensions on the product page.
	 *
	 * @return bool
	 */
	public function enable_dimensions_display() {
		return apply_filters( 'wc_product_enable_dimensions_display', ! $this->get_virtual() ) && ( $this->has_dimensions() || $this->has_weight() );
	}

	/**
	 * Returns whether or not the product has any visible attributes.
	 *
	 * @return boolean
	 */
	public function has_attributes() {
		foreach ( $this->get_attributes() as $attribute ) {
			if ( $attribute->get_visible() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns whether or not the product has any child product.
	 *
	 * @return bool
	 */
	public function has_child() {
		return 0 < count( $this->get_children() );
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Returns the children IDs if applicable. Overridden by child classes.
	 *
	 * @return array of IDs
	 */
	public function get_children() {
		return array();
	}

	/**
	 * Returns the price in html format.
	 * @todo Should this be moved out of the classes?
	 * @return string
	 */
	public function get_price_html( $deprecated = '' ) {
		if ( '' === $this->get_price() ) {
			return apply_filters( 'woocommerce_empty_price_html', '', $this );
		}

		if ( $this->is_on_sale() ) {
			$price = wc_format_price_range( wc_get_price_to_display( $this, array( 'price' => $this->get_regular_price() ) ), wc_get_price_to_display( $this ) ) . wc_get_price_suffix( $this );
		} else {
			$price = wc_price( wc_get_price_to_display( $this ) ) . wc_get_price_suffix( $this );
		}

		return apply_filters( 'woocommerce_get_price_html', $price, $this );
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
		return sprintf( '%s &ndash; %s', $identifier, $this->get_name() );
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
	 * Returns the product shipping class SLUG.
	 *
	 * @return string
	 */
	public function get_shipping_class() {
		if ( $class_id = $this->get_shipping_class_id() ) {
			$term = get_term_by( 'id', $class_id, 'product_shipping_class' );

			if ( $term && ! is_wp_error( $term ) ) {
				return $term->slug;
			}
		}
		return '';
	}

	/**
	 * Returns a single product attribute as a string.
	 * @param  string $attribute to get.
	 * @return string
	 */
	public function get_attribute( $attribute ) {
		$attributes = $this->get_attributes();
		$attribute  = sanitize_title( $attribute );

		if ( isset( $attributes[ $attribute ] ) ) {
			$attribute_object = $attributes[ $attribute ];
		} elseif ( isset( $attributes[ 'pa_' . $attribute ] ) ) {
			$attribute_object = $attributes[ 'pa_' . $attribute ];
		} else {
			return '';
		}
		return $attribute_object->is_taxonomy() ? implode( ', ', wc_get_product_terms( $this->get_id(), $attribute_object->get_name(), array( 'fields' => 'names' ) ) ) : wc_implode_text_attributes( $attribute_object->get_options() );
	}

	/*
	|--------------------------------------------------------------------------
	| @todo stock functions
	|--------------------------------------------------------------------------
	*/

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
	 * Set stock level of the product. @todo '' stock if not managing it.
	 *
	 * Uses queries rather than update_post_meta so we can do this in one query (to avoid stock issues).
	 * We cannot rely on the original loaded value in case another order was made since then.
	 *
	 * @param int $amount (default: null)
	 * @param string $mode can be set, add, or subtract
	 * @return int new stock level
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

	/*
	|--------------------------------------------------------------------------
	| @todo download functions
	|--------------------------------------------------------------------------
	*/

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

	/*
	|--------------------------------------------------------------------------
	| @todo misc
	|--------------------------------------------------------------------------
	*/

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

			$rating_html  = '<div class="star-rating" title="' . sprintf( __( 'Rated %s out of 5', 'woocommerce' ), $rating ) . '">';

			$rating_html .= '<span style="width:' . ( ( $rating / 5 ) * 100 ) . '%"><strong class="rating">' . $rating . '</strong> ' . __( 'out of 5', 'woocommerce' ) . '</span>';

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
}
