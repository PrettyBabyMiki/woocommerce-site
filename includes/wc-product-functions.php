<?php
/**
 * WooCommerce Product Functions
 *
 * Functions for product specific things.
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Main function for returning products, uses the WC_Product_Factory class.
 *
 * @since 2.2.0
 *
 * @param mixed $the_product Post object or post ID of the product.
 * @param array $args (default: array()) Contains all arguments to be used to get this product.
 * @return WC_Product
 */
function wc_get_product( $the_product = false, $args = array() ) {
	if ( ! did_action( 'woocommerce_init' ) ) {
		_doing_it_wrong( __FUNCTION__, __( 'wc_get_product should not be called before the woocommerce_init action.', 'woocommerce' ), '2.5' );
		return false;
	}
	return WC()->product_factory->get_product( $the_product, $args );
}

/**
 * Update a product's stock amount.
 *
 * @param  int $product_id
 * @param  int $new_stock_level
 */
function wc_update_product_stock( $product_id, $new_stock_level ) {
	$product = wc_get_product( $product_id );

	if ( ! metadata_exists( 'post', $product_id, '_stock' ) || $product->get_stock_quantity() !== $new_stock_level ) {
		$product->set_stock( $new_stock_level );
	}
}

/**
 * Update a product's stock status.
 *
 * @param  int $product_id
 * @param  int $status
 */
function wc_update_product_stock_status( $product_id, $status ) {
	$product = wc_get_product( $product_id );
	if ( $product ) {
		$product->set_stock_status( $status );
	}
}

/**
 * Returns whether or not SKUS are enabled.
 * @return bool
 */
function wc_product_sku_enabled() {
	return apply_filters( 'wc_product_sku_enabled', true );
}

/**
 * Returns whether or not product weights are enabled.
 * @return bool
 */
function wc_product_weight_enabled() {
	return apply_filters( 'wc_product_weight_enabled', true );
}

/**
 * Returns whether or not product dimensions (HxWxD) are enabled.
 * @return bool
 */
function wc_product_dimensions_enabled() {
	return apply_filters( 'wc_product_dimensions_enabled', true );
}

/**
 * Clear all transients cache for product data.
 *
 * @param int $post_id (default: 0)
 */
function wc_delete_product_transients( $post_id = 0 ) {
	// Core transients
	$transients_to_clear = array(
		'wc_products_onsale',
		'wc_featured_products',
		'wc_outofstock_count',
		'wc_low_stock_count',
	);

	// Transient names that include an ID
	$post_transient_names = array(
		'wc_product_children_',
		'wc_product_total_stock_',
		'wc_var_prices_',
		'wc_related_',
	);

	if ( $post_id > 0 ) {
		foreach ( $post_transient_names as $transient ) {
			$transients_to_clear[] = $transient . $post_id;
		}

		// Does this product have a parent?
		if ( $parent_id = wp_get_post_parent_id( $post_id ) ) {
			wc_delete_product_transients( $parent_id );
		}
	}

	// Delete transients
	foreach ( $transients_to_clear as $transient ) {
		delete_transient( $transient );
	}

	// Increments the transient version to invalidate cache
	WC_Cache_Helper::get_transient_version( 'product', true );

	do_action( 'woocommerce_delete_product_transients', $post_id );
}

/**
 * Function that returns an array containing the IDs of the products that are on sale.
 *
 * @since 2.0
 * @access public
 * @return array
 */
function wc_get_product_ids_on_sale() {
	global $wpdb;

	// Load from cache
	$product_ids_on_sale = get_transient( 'wc_products_onsale' );

	// Valid cache found
	if ( false !== $product_ids_on_sale ) {
		return $product_ids_on_sale;
	}

	$on_sale_posts = $wpdb->get_results( "
		SELECT post.ID, post.post_parent FROM `$wpdb->posts` AS post
		LEFT JOIN `$wpdb->postmeta` AS meta ON post.ID = meta.post_id
		LEFT JOIN `$wpdb->postmeta` AS meta2 ON post.ID = meta2.post_id
		WHERE post.post_type IN ( 'product', 'product_variation' )
			AND post.post_status = 'publish'
			AND meta.meta_key = '_sale_price'
			AND meta2.meta_key = '_price'
			AND CAST( meta.meta_value AS DECIMAL ) >= 0
			AND CAST( meta.meta_value AS CHAR ) != ''
			AND CAST( meta.meta_value AS DECIMAL ) = CAST( meta2.meta_value AS DECIMAL )
		GROUP BY post.ID;
	" );

	$product_ids_on_sale = array_unique( array_map( 'absint', array_merge( wp_list_pluck( $on_sale_posts, 'ID' ), array_diff( wp_list_pluck( $on_sale_posts, 'post_parent' ), array( 0 ) ) ) ) );

	set_transient( 'wc_products_onsale', $product_ids_on_sale, DAY_IN_SECONDS * 30 );

	return $product_ids_on_sale;
}

/**
 * Function that returns an array containing the IDs of the featured products.
 *
 * @since 2.1
 * @access public
 * @return array
 */
function wc_get_featured_product_ids() {

	// Load from cache
	$featured_product_ids = get_transient( 'wc_featured_products' );

	// Valid cache found
	if ( false !== $featured_product_ids )
		return $featured_product_ids;

	$featured = get_posts( array(
		'post_type'      => array( 'product', 'product_variation' ),
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'meta_query'     => array(
			array(
				'key' 		=> '_visibility',
				'value' 	=> array( 'catalog', 'visible' ),
				'compare' 	=> 'IN',
			),
			array(
				'key' 	=> '_featured',
				'value' => 'yes',
			),
		),
		'fields' => 'id=>parent',
	) );

	$product_ids          = array_keys( $featured );
	$parent_ids           = array_values( array_filter( $featured ) );
	$featured_product_ids = array_unique( array_merge( $product_ids, $parent_ids ) );

	set_transient( 'wc_featured_products', $featured_product_ids, DAY_IN_SECONDS * 30 );

	return $featured_product_ids;
}

/**
 * Filter to allow product_cat in the permalinks for products.
 *
 * @param  string  $permalink The existing permalink URL.
 * @param  WP_Post $post
 * @return string
 */
function wc_product_post_type_link( $permalink, $post ) {
	// Abort if post is not a product.
	if ( 'product' !== $post->post_type ) {
		return $permalink;
	}

	// Abort early if the placeholder rewrite tag isn't in the generated URL.
	if ( false === strpos( $permalink, '%' ) ) {
		return $permalink;
	}

	// Get the custom taxonomy terms in use by this post.
	$terms = get_the_terms( $post->ID, 'product_cat' );

	if ( ! empty( $terms ) ) {
		usort( $terms, '_usort_terms_by_ID' ); // order by ID

		$category_object = apply_filters( 'wc_product_post_type_link_product_cat', $terms[0], $terms, $post );
		$category_object = get_term( $category_object, 'product_cat' );
		$product_cat     = $category_object->slug;

		if ( $category_object->parent ) {
			$ancestors = get_ancestors( $category_object->term_id, 'product_cat' );
			foreach ( $ancestors as $ancestor ) {
				$ancestor_object = get_term( $ancestor, 'product_cat' );
				$product_cat     = $ancestor_object->slug . '/' . $product_cat;
			}
		}
	} else {
		// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
		$product_cat = _x( 'uncategorized', 'slug', 'woocommerce' );
	}

	$find = array(
		'%year%',
		'%monthnum%',
		'%day%',
		'%hour%',
		'%minute%',
		'%second%',
		'%post_id%',
		'%category%',
		'%product_cat%',
	);

	$replace = array(
		date_i18n( 'Y', strtotime( $post->post_date ) ),
		date_i18n( 'm', strtotime( $post->post_date ) ),
		date_i18n( 'd', strtotime( $post->post_date ) ),
		date_i18n( 'H', strtotime( $post->post_date ) ),
		date_i18n( 'i', strtotime( $post->post_date ) ),
		date_i18n( 's', strtotime( $post->post_date ) ),
		$post->ID,
		$product_cat,
		$product_cat,
	);

	$permalink = str_replace( $find, $replace, $permalink );

	return $permalink;
}
add_filter( 'post_type_link', 'wc_product_post_type_link', 10, 2 );


/**
 * Get the placeholder image URL for products etc.
 *
 * @access public
 * @return string
 */
function wc_placeholder_img_src() {
	return apply_filters( 'woocommerce_placeholder_img_src', WC()->plugin_url() . '/assets/images/placeholder.png' );
}

/**
 * Get the placeholder image.
 *
 * @access public
 * @return string
 */
function wc_placeholder_img( $size = 'shop_thumbnail' ) {
	$dimensions = wc_get_image_size( $size );

	return apply_filters( 'woocommerce_placeholder_img', '<img src="' . wc_placeholder_img_src() . '" alt="' . esc_attr__( 'Placeholder', 'woocommerce' ) . '" width="' . esc_attr( $dimensions['width'] ) . '" class="woocommerce-placeholder wp-post-image" height="' . esc_attr( $dimensions['height'] ) . '" />', $size, $dimensions );
}

/**
 * Variation Formatting.
 *
 * Gets a formatted version of variation data or item meta.
 *
 * @access public
 * @param string $variation
 * @param bool $flat (default: false)
 * @return string
 */
function wc_get_formatted_variation( $variation, $flat = false ) {
	$return = '';
	if ( is_array( $variation ) ) {

		if ( ! $flat ) {
			$return = '<dl class="variation">';
		}

		$variation_list = array();

		foreach ( $variation as $name => $value ) {
			if ( ! $value ) {
				continue;
			}

			// If this is a term slug, get the term's nice name
			if ( taxonomy_exists( esc_attr( str_replace( 'attribute_', '', $name ) ) ) ) {
				$term = get_term_by( 'slug', $value, esc_attr( str_replace( 'attribute_', '', $name ) ) );
				if ( ! is_wp_error( $term ) && ! empty( $term->name ) ) {
					$value = $term->name;
				}
			} else {
				$value = ucwords( str_replace( '-', ' ', $value ) );
			}

			if ( $flat ) {
				$variation_list[] = wc_attribute_label( str_replace( 'attribute_', '', $name ) ) . ': ' . rawurldecode( $value );
			} else {
				$variation_list[] = '<dt>' . wc_attribute_label( str_replace( 'attribute_', '', $name ) ) . ':</dt><dd>' . rawurldecode( $value ) . '</dd>';
			}
		}

		if ( $flat ) {
			$return .= implode( ', ', $variation_list );
		} else {
			$return .= implode( '', $variation_list );
		}

		if ( ! $flat ) {
			$return .= '</dl>';
		}
	}
	return $return;
}

/**
 * Function which handles the start and end of scheduled sales via cron.
 *
 * @access public
 */
function wc_scheduled_sales() {
	global $wpdb;

	// Sales which are due to start
	$product_ids = $wpdb->get_col( $wpdb->prepare( "
		SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
		LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id
		LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id
		WHERE postmeta.meta_key = '_sale_price_dates_from'
		AND postmeta_2.meta_key = '_price'
		AND postmeta_3.meta_key = '_sale_price'
		AND postmeta.meta_value > 0
		AND postmeta.meta_value < %s
		AND postmeta_2.meta_value != postmeta_3.meta_value
	", current_time( 'timestamp' ) ) );

	if ( $product_ids ) {
		foreach ( $product_ids as $product_id ) {
			$sale_price = get_post_meta( $product_id, '_sale_price', true );

			if ( $sale_price ) {
				update_post_meta( $product_id, '_price', $sale_price );
			} else {
				// No sale price!
				update_post_meta( $product_id, '_sale_price_dates_from', '' );
				update_post_meta( $product_id, '_sale_price_dates_to', '' );
			}

			$parent = wp_get_post_parent_id( $product_id );

			// Sync parent
			if ( $parent ) {
				// Clear prices transient for variable products.
				delete_transient( 'wc_var_prices_' . $parent );

				// Grouped products need syncing via a function
				$this_product = wc_get_product( $product_id );

				if ( $this_product->is_type( 'simple' ) ) {
					$this_product->grouped_product_sync();
				}
			}
		}

		delete_transient( 'wc_products_onsale' );
	}

	// Sales which are due to end
	$product_ids = $wpdb->get_col( $wpdb->prepare( "
		SELECT postmeta.post_id FROM {$wpdb->postmeta} as postmeta
		LEFT JOIN {$wpdb->postmeta} as postmeta_2 ON postmeta.post_id = postmeta_2.post_id
		LEFT JOIN {$wpdb->postmeta} as postmeta_3 ON postmeta.post_id = postmeta_3.post_id
		WHERE postmeta.meta_key = '_sale_price_dates_to'
		AND postmeta_2.meta_key = '_price'
		AND postmeta_3.meta_key = '_regular_price'
		AND postmeta.meta_value > 0
		AND postmeta.meta_value < %s
		AND postmeta_2.meta_value != postmeta_3.meta_value
	", current_time( 'timestamp' ) ) );

	if ( $product_ids ) {
		foreach ( $product_ids as $product_id ) {
			$regular_price = get_post_meta( $product_id, '_regular_price', true );

			update_post_meta( $product_id, '_price', $regular_price );
			update_post_meta( $product_id, '_sale_price', '' );
			update_post_meta( $product_id, '_sale_price_dates_from', '' );
			update_post_meta( $product_id, '_sale_price_dates_to', '' );

			$parent = wp_get_post_parent_id( $product_id );

			// Sync parent
			if ( $parent ) {
                // Clear prices transient for variable products.
				delete_transient( 'wc_var_prices_' . $parent );

				// Grouped products need syncing via a function
				$this_product = wc_get_product( $product_id );
				if ( $this_product->is_type( 'simple' ) ) {
					$this_product->grouped_product_sync();
				}
			}
		}

		WC_Cache_Helper::get_transient_version( 'product', true );
		delete_transient( 'wc_products_onsale' );
	}
}
add_action( 'woocommerce_scheduled_sales', 'wc_scheduled_sales' );

/**
 * Get attachment image attributes.
 *
 * @access public
 * @param array $attr
 * @return array
 */
function wc_get_attachment_image_attributes( $attr ) {
	if ( strstr( $attr['src'], 'woocommerce_uploads/' ) ) {
		$attr['src'] = wc_placeholder_img_src();
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'wc_get_attachment_image_attributes' );


/**
 * Prepare attachment for JavaScript.
 *
 * @access public
 * @param array $response
 * @return array
 */
function wc_prepare_attachment_for_js( $response ) {

	if ( isset( $response['url'] ) && strstr( $response['url'], 'woocommerce_uploads/' ) ) {
		$response['full']['url'] = wc_placeholder_img_src();
		if ( isset( $response['sizes'] ) ) {
			foreach ( $response['sizes'] as $size => $value ) {
				$response['sizes'][ $size ]['url'] = wc_placeholder_img_src();
			}
		}
	}

	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'wc_prepare_attachment_for_js' );

/**
 * Track product views.
 */
function wc_track_product_view() {
	if ( ! is_singular( 'product' ) || ! is_active_widget( false, false, 'woocommerce_recently_viewed_products', true ) ) {
		return;
	}

	global $post;

	if ( empty( $_COOKIE['woocommerce_recently_viewed'] ) )
		$viewed_products = array();
	else
		$viewed_products = (array) explode( '|', $_COOKIE['woocommerce_recently_viewed'] );

	if ( ! in_array( $post->ID, $viewed_products ) ) {
		$viewed_products[] = $post->ID;
	}

	if ( sizeof( $viewed_products ) > 15 ) {
		array_shift( $viewed_products );
	}

	// Store for session only
	wc_setcookie( 'woocommerce_recently_viewed', implode( '|', $viewed_products ) );
}

add_action( 'template_redirect', 'wc_track_product_view', 20 );

/**
 * Get product types.
 *
 * @since 2.2
 * @return array
 */
function wc_get_product_types() {
	return (array) apply_filters( 'product_type_selector', array(
		'simple'   => __( 'Simple product', 'woocommerce' ),
		'grouped'  => __( 'Grouped product', 'woocommerce' ),
		'external' => __( 'External/Affiliate product', 'woocommerce' ),
		'variable' => __( 'Variable product', 'woocommerce' ),
	) );
}

/**
 * Check if product sku is unique.
 *
 * @since 2.2
 * @param int $product_id
 * @param string $sku Will be slashed to work around https://core.trac.wordpress.org/ticket/27421
 * @return bool
 */
function wc_product_has_unique_sku( $product_id, $sku ) {
	global $wpdb;

	$sku_found = $wpdb->get_var( $wpdb->prepare( "
		SELECT $wpdb->posts.ID
		FROM $wpdb->posts
		LEFT JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id )
		WHERE $wpdb->posts.post_type IN ( 'product', 'product_variation' )
		AND $wpdb->posts.post_status = 'publish'
		AND $wpdb->postmeta.meta_key = '_sku' AND $wpdb->postmeta.meta_value = '%s'
		AND $wpdb->postmeta.post_id <> %d LIMIT 1
	 ", wp_slash( $sku ), $product_id ) );

	if ( apply_filters( 'wc_product_has_unique_sku', $sku_found, $product_id, $sku ) ) {
		return false;
	} else {
		return true;
	}
}

/**
 * Get product ID by SKU.
 *
 * @since  2.3.0
 * @param  string $sku
 * @return int
 */
function wc_get_product_id_by_sku( $sku ) {
	global $wpdb;

	$product_id = $wpdb->get_var( $wpdb->prepare( "
		SELECT posts.ID
		FROM $wpdb->posts AS posts
		LEFT JOIN $wpdb->postmeta AS postmeta ON ( posts.ID = postmeta.post_id )
		WHERE posts.post_type IN ( 'product', 'product_variation' )
		AND postmeta.meta_key = '_sku' AND postmeta.meta_value = '%s'
		LIMIT 1
	 ", $sku ) );

	return ( $product_id ) ? intval( $product_id ) : 0;
}

/**
 * Save product price.
 *
 * This is a private function (internal use ONLY) used until a data manipulation api is built.
 *
 * @since 2.4.0
 * @todo  look into Data manipulation API
 *
 * @param int $product_id
 * @param float $regular_price
 * @param float $sale_price
 * @param string $date_from
 * @param string $date_to
 */
function _wc_save_product_price( $product_id, $regular_price, $sale_price = '', $date_from = '', $date_to = '' ) {
	$product_id    = absint( $product_id );
	$regular_price = wc_format_decimal( $regular_price );
	$sale_price    = '' === $sale_price ? '' : wc_format_decimal( $sale_price );
	$date_from     = wc_clean( $date_from );
	$date_to       = wc_clean( $date_to );

	update_post_meta( $product_id, '_regular_price', $regular_price );
	update_post_meta( $product_id, '_sale_price', $sale_price );

	// Save Dates
	update_post_meta( $product_id, '_sale_price_dates_from', $date_from ? strtotime( $date_from ) : '' );
	update_post_meta( $product_id, '_sale_price_dates_to', $date_to ? strtotime( $date_to ) : '' );

	if ( $date_to && ! $date_from ) {
		$date_from = strtotime( 'NOW', current_time( 'timestamp' ) );
		update_post_meta( $product_id, '_sale_price_dates_from', $date_from );
	}

	// Update price if on sale
	if ( '' !== $sale_price && '' === $date_to && '' === $date_from ) {
		update_post_meta( $product_id, '_price', $sale_price );
	} else {
		update_post_meta( $product_id, '_price', $regular_price );
	}

	if ( '' !== $sale_price && $date_from && strtotime( $date_from ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
		update_post_meta( $product_id, '_price', $sale_price );
	}

	if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
		update_post_meta( $product_id, '_price', $regular_price );
		update_post_meta( $product_id, '_sale_price_dates_from', '' );
		update_post_meta( $product_id, '_sale_price_dates_to', '' );
	}
}

/**
 * Get attibutes/data for an individual variation from the database and maintain it's integrity.
 * @since  2.4.0
 * @param  int $variation_id
 * @return array
 */
function wc_get_product_variation_attributes( $variation_id ) {
	// Build variation data from meta
	$all_meta                = get_post_meta( $variation_id );
	$parent_id               = wp_get_post_parent_id( $variation_id );
	$parent_attributes       = array_filter( (array) get_post_meta( $parent_id, '_product_attributes', true ) );
	$found_parent_attributes = array();
	$variation_attributes    = array();

	// Compare to parent variable product attributes and ensure they match
	foreach ( $parent_attributes as $attribute_name => $options ) {
		if ( ! empty( $options['is_variation'] ) ) {
			$attribute                 = 'attribute_' . sanitize_title( $attribute_name );
			$found_parent_attributes[] = $attribute;
			if ( ! array_key_exists( $attribute, $variation_attributes ) ) {
				$variation_attributes[ $attribute ] = ''; // Add it - 'any' will be asumed
			}
		}
	}

	// Get the variation attributes from meta
	foreach ( $all_meta as $name => $value ) {
		// Only look at valid attribute meta, and also compare variation level attributes and remove any which do not exist at parent level
		if ( 0 !== strpos( $name, 'attribute_' ) || ! in_array( $name, $found_parent_attributes ) ) {
			unset( $variation_attributes[ $name ] );
			continue;
		}
		/**
		 * Pre 2.4 handling where 'slugs' were saved instead of the full text attribute.
		 * Attempt to get full version of the text attribute from the parent.
		 */
		if ( sanitize_title( $value[0] ) === $value[0] && version_compare( get_post_meta( $parent_id, '_product_version', true ), '2.4.0', '<' ) ) {
			foreach ( $parent_attributes as $attribute ) {
				if ( 'attribute_' . sanitize_title( $attribute['name'] ) !== $name ) {
					continue;
				}
				$text_attributes = wc_get_text_attributes( $attribute['value'] );

				foreach ( $text_attributes as $text_attribute ) {
					if ( sanitize_title( $text_attribute ) === $value[0] ) {
						$value[0] = $text_attribute;
						break;
					}
				}
			}
		}

		$variation_attributes[ $name ] = $value[0];
	}

	return $variation_attributes;
}

/**
 * Get all product cats for a product by ID, including hierarchy
 * @since  2.5.0
 * @param  int $product_id
 * @return array
 */
function wc_get_product_cat_ids( $product_id ) {
	$product_cats = wp_get_post_terms( $product_id, 'product_cat', array( "fields" => "ids" ) );

	foreach ( $product_cats as $product_cat ) {
		$product_cats = array_merge( $product_cats, get_ancestors( $product_cat, 'product_cat' ) );
	}

	return $product_cats;
}

/**
 * Gets data about an attachment, such as alt text and captions.
 * @since 2.6.0
 * @param object|bool $product
 * @return array
 */
function wc_get_product_attachment_props( $attachment_id, $product = false ) {
	$props = array(
		'title'   => '',
		'caption' => '',
		'url'     => '',
		'alt'     => '',
	);
	if ( $attachment_id ) {
		$attachment       = get_post( $attachment_id );
		$props['title']   = trim( strip_tags( $attachment->post_title ) );
		$props['caption'] = trim( strip_tags( $attachment->post_excerpt ) );
		$props['url']     = wp_get_attachment_url( $attachment_id );
		$props['alt']     = trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );

		// Alt text fallbacks
		$props['alt']     = empty( $props['alt'] ) ? $props['caption'] : $props['alt'];
		$props['alt']     = empty( $props['alt'] ) ? trim( strip_tags( $attachment->post_title ) ) : $props['alt'];
		$props['alt']     = empty( $props['alt'] ) && $product ? trim( strip_tags( get_the_title( $product->ID ) ) ) : $props['alt'];
	}
	return $props;
}

/**
 * Get product visibility options.
 *
 * @since 2.7.0
 * @return array
 */
function wc_get_product_visibility_options() {
	return apply_filters( 'woocommerce_product_visibility_options', array(
		'visible' => __( 'Catalog/search', 'woocommerce' ),
		'catalog' => __( 'Catalog', 'woocommerce' ),
		'search'  => __( 'Search', 'woocommerce' ),
		'hidden'  => __( 'Hidden', 'woocommerce' ),
	) );
}

/**
 * Get relateed products.
 *
 * @since  2.7.0
 * @param  int   $product_id  Product ID
 * @param  int   $limit       Limit of results.
 * @param  array $exclude_ids Exclude IDs from the results.
 * @return array
 */
function wc_get_related_products( $product_id, $limit = 5, $exclude_ids = array() ) {
	global $wpdb;

	$product_id     = absint( $product_id );
	$exclude_ids    = array_map( 'absint', array_merge( array( 0, $product_id ), $exclude_ids ) );
	$transient_name = 'wc_related_' . $product_id;
	$related_posts  = get_transient( $transient_name );
	$limit          = $limit > 0 ? $limit : 5;

	// We want to query related posts if they are not cached, or we don't have enough.
	if ( false === $related_posts || sizeof( $related_posts ) < $limit ) {
		// Related products are found from category and tags.
		if ( apply_filters( 'woocommerce_product_related_posts_relate_by_category', true, $product_id ) ) {
			$cats_array = wc_get_related_terms( $product_id, 'product_cat' );
		} else {
			$cats_array = array();
		}

		if ( apply_filters( 'woocommerce_product_related_posts_relate_by_tag', true, $product_id ) ) {
			$tags_array = wc_get_related_terms( $product_id, 'product_tag' );
		} else {
			$tags_array = array();
		}

		// Don't bother if none are set, unless woocommerce_product_related_posts_force_display is set to true in which case all products are related.
		if ( empty( $cats_array ) && empty( $tags_array ) && ! apply_filters( 'woocommerce_product_related_posts_force_display', false, $product_id ) ) {
			$related_posts = array();
		} else {
			// Generate query - but query an extra 10 results to give the appearance of random results.
			$query = apply_filters( 'woocommerce_product_related_posts_query', wc_get_related_products_query( $cats_array, $tags_array, $exclude_ids, $limit + 10 ), $product_id );

			// Get the posts.
			$related_posts = $wpdb->get_col( implode( ' ', $query ) );
		}

		set_transient( $transient_name, $related_posts, DAY_IN_SECONDS );
	}

	// Randomise the results.
	shuffle( $related_posts );

	// Limit the returned results.
	return array_slice( $related_posts, 0, $limit );
}

/**
 * Retrieves related product terms.
 *
 * @since  2.7.0
 * @param  int    $product_id Product ID.
 * @param  string $taxonomy   Taxonomy slug.
 * @return array
 */
function wc_get_related_terms( $product_id, $taxonomy ) {
	$terms = apply_filters( 'woocommerce_get_related_' . $taxonomy . '_terms', wp_get_post_terms( $product_id, $term ), $product_id );

	return array_map( 'absint', wp_list_pluck( $terms, 'term_id' ) );
}

/**
 * Builds the related posts query.
 *
 * @since 2.7.0
 * @param array $cats_array  List of categories IDs.
 * @param array $tags_array  List of tags IDs.
 * @param array $exclude_ids Excluded IDs.
 * @param int   $limit       Limit of results.
 * @return string
 */
function wc_get_related_products_query( $cats_array, $tags_array, $exclude_ids, $limit ) {
	global $wpdb;

	// Arrays to string.
	$exclude_ids = implode( ',', $exclude_ids );
	$cats_array  = implode( ',', $cats_array );
	$tags_array  = implode( ',', $tags_array );

	$limit           = absint( $limit );
	$query           = array();
	$query['fields'] = "SELECT DISTINCT ID FROM {$wpdb->posts} p";
	$query['join']   = " INNER JOIN {$wpdb->postmeta} pm ON ( pm.post_id = p.ID AND pm.meta_key='_visibility' )";
	$query['join']  .= " INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)";
	$query['join']  .= " INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)";
	$query['join']  .= " INNER JOIN {$wpdb->terms} t ON (t.term_id = tt.term_id)";

	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$query['join'] .= " INNER JOIN {$wpdb->postmeta} pm2 ON ( pm2.post_id = p.ID AND pm2.meta_key='_stock_status' )";
	}

	$query['where']  = ' WHERE 1=1';
	$query['where'] .= " AND p.post_status = 'publish'";
	$query['where'] .= " AND p.post_type = 'product'";
	$query['where'] .= " AND p.ID NOT IN ( {$exclude_ids} )";
	$query['where'] .= " AND pm.meta_value IN ( 'visible', 'catalog' )";

	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$query['where'] .= " AND pm2.meta_value = 'instock'";
	}

	if ( $cats_array || $tags_array ) {
		$query['where'] .= ' AND (';

		if ( $cats_array ) {
			$query['where'] .= " ( tt.taxonomy = 'product_cat' AND t.term_id IN ( {$cats_array} ) ) ";
			if ( $relate_by_tag ) {
				$query['where'] .= ' OR ';
			}
		}

		if ( $tags_array ) {
			$query['where'] .= " ( tt.taxonomy = 'product_tag' AND t.term_id IN ( {$tags_array} ) ) ";
		}

		$query['where'] .= ')';
	}

	$query['limits'] = " LIMIT {$limit} ";

	return $query;
}
