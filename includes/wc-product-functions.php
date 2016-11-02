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
 * Products wrapper for get_posts.
 *
 * This function should be used for product retrieval so that we have a data agnostic
 * way to get a list of products.
 *
 * Args:
 *      status array|string List of statuses to find. Default: any. Options: any, draft, pending, private and publish.
 *      type array|string Product type, e.g. Default: all. Options: all, simple, external, variable, variation, grouped.
 *      parent int post/product parent
 *      skus array Limit result set to products with specific SKUs.
 *      categories array Limit result set to products assigned a specific category, e.g. 9,14.
 *      tags array Limit result set to products assigned a specific tag, e.g. 9,14.
 *      limit int Maximum of products to retrieve.
 *      offset int Offset of products to retrieve.
 *      page int Page of products to retrieve. Ignored when using the 'offset' arg.
 *      exclude array Product IDs to exclude from the query.
 *      orderby string Order by date, title, id, modified, rand etc
 *      order string ASC or DESC
 *      return string Type of data to return. Allowed values:
 *          ids array of Product ids
 *          objects array of product objects (default)
 *      paginate bool If true, the return value will be an array with values:
 *          'products'      => array of data (return value above),
 *          'total'         => total number of products matching the query
 *          'max_num_pages' => max number of pages found
 *
 * @since  2.7.0
 * @param  array $args Array of args (above)
 * @return array|stdClass Number of pages and an array of product objects if
 *                             paginate is true, or just an array of values.
 */
function wc_get_products( $args ) {
	$args = wp_parse_args( $args, array(
		'status'   => array( 'draft', 'pending', 'private', 'publish' ),
		'type'     => array_merge( array_keys( wc_get_product_types() ), array( 'variation' ) ),
		'parent'   => null,
		'sku'      => '',
		'category' => array(),
		'tag'      => array(),
		'limit'    => get_option( 'posts_per_page' ),
		'offset'   => null,
		'page'     => 1,
		'exclude'  => array(),
		'orderby'  => 'date',
		'order'    => 'DESC',
		'return'   => 'objects',
		'paginate' => false,
	) );

	// Handle some BW compatibility arg names where wp_query args differ in naming.
	$map_legacy = array(
		'numberposts'    => 'limit',
		'post_status'    => 'status',
		'post_parent'    => 'parent',
		'posts_per_page' => 'limit',
		'paged'          => 'page',
	);

	foreach ( $map_legacy as $from => $to ) {
		if ( isset( $args[ $from ] ) ) {
			$args[ $to ] = $args[ $from ];
		}
	}

	/**
	 * Generate WP_Query args.
	 */
	$wp_query_args = array(
		'post_type'      => 'variation' === $args['type'] ? 'product_variation' : 'product',
		'post_status'    => $args['status'],
		'posts_per_page' => $args['limit'],
		'meta_query'     => array(),
		'fields'         => 'ids',
		'orderby'        => $args['orderby'],
		'order'          => $args['order'],
		'tax_query'      => array(),
	);

	if ( 'variation' !== $args['type'] ) {
		$wp_query_args['tax_query'][] = array(
			'taxonomy' => 'product_type',
			'field'    => 'slug',
			'terms'    => $args['type'],
		);
	}

	if ( ! empty( $args['sku'] ) ) {
		$wp_query_args['meta_query'][] = array(
			'key'     => '_sku',
			'value'   => $args['sku'],
			'compare' => 'LIKE',
		);
	}

	if ( ! empty( $args['category'] ) ) {
		$wp_query_args['tax_query'][] = array(
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'   => $args['category'],
		);
	}

	if ( ! empty( $args['tag'] ) ) {
		$wp_query_args['tax_query'][] = array(
			'taxonomy' => 'product_tag',
			'field'    => 'term_id',
			'terms'   => $args['tag'],
		);
	}

	if ( ! is_null( $args['parent'] ) ) {
		$wp_query_args['post_parent'] = absint( $args['parent'] );
	}

	if ( ! is_null( $args['offset'] ) ) {
		$wp_query_args['offset'] = absint( $args['offset'] );
	} else {
		$wp_query_args['paged'] = absint( $args['page'] );
	}

	if ( ! empty( $args['exclude'] ) ) {
		$wp_query_args['post__not_in'] = array_map( 'absint', $args['exclude'] );
	}

	if ( ! $args['paginate'] ) {
		$wp_query_args['no_found_rows'] = true;
	}

	// Get results.
	$products = new WP_Query( $wp_query_args );

	if ( 'objects' === $args['return'] ) {
		$return = array_map( 'wc_get_product', $products->posts );
	} else {
		$return = $products->posts;
	}

	if ( $args['paginate'] ) {
		return (object) array(
			'products'      => $return,
			'total'         => $products->found_posts,
			'max_num_pages' => $procuts->max_num_pages,
		);
	} else {
		return $return;
	}
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
	$product_cats = wc_get_product_term_ids( $product_id, 'product_cat' );

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
 * Get min/max price meta query args.
 *
 * @since 2.7.0
 * @param array $args Min price and max price arguments.
 * @return array
 */
function wc_get_min_max_price_meta_query( $args ) {
	$min = isset( $args['min_price'] ) ? floatval( $args['min_price'] ) : 0;
	$max = isset( $args['max_price'] ) ? floatval( $args['max_price'] ) : 9999999999;

	/**
	 * Adjust if the store taxes are not displayed how they are stored.
	 * Max is left alone because the filter was already increased.
	 * Kicks in when prices excluding tax are displayed including tax.
	 */
	if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
		$tax_classes = array_merge( array( '' ), WC_Tax::get_tax_classes() );
		$class_min   = $min;

		foreach ( $tax_classes as $tax_class ) {
			if ( $tax_rates = WC_Tax::get_rates( $tax_class ) ) {
				$class_min = $min - WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $min, $tax_rates ) );
			}
		}

		$min = $class_min;
	}

	return array(
		'key'     => '_price',
		'value'   => array( $min, $max ),
		'compare' => 'BETWEEN',
		'type'    => 'DECIMAL',
	);
}

/**
 * Get product tax class options.
 *
 * @since 2.7.0
 * @return array
 */
function wc_get_product_tax_class_options() {
	$tax_classes           = WC_Tax::get_tax_classes();
	$tax_class_options     = array();
	$tax_class_options[''] = __( 'Standard', 'woocommerce' );

	if ( ! empty( $tax_classes ) ) {
		foreach ( $tax_classes as $class ) {
			$tax_class_options[ sanitize_title( $class ) ] = $class;
		}
	}
	return $tax_class_options;
}

/**
 * Get stock status options.
 *
 * @since 2.7.0
 * @return array
 */
function wc_get_product_stock_status_options() {
	return array(
		'instock'    => __( 'In stock', 'woocommerce' ),
		'outofstock' => __( 'Out of stock', 'woocommerce' ),
	);
}

/**
 * Get backorder options.
 *
 * @since 2.7.0
 * @return array
 */
function wc_get_product_backorder_options() {
	return array(
		'no'     => __( 'Do not allow', 'woocommerce' ),
		'notify' => __( 'Allow, but notify customer', 'woocommerce' ),
		'yes'    => __( 'Allow', 'woocommerce' ),
	);
}

/**
 * Get related products based on product category and tags.
 *
 * @since  2.7.0
 * @param  int   $product_id  Product ID.
 * @param  int   $limit       Limit of results.
 * @param  array $exclude_ids Exclude IDs from the results.
 * @return array
 */
function wc_get_related_products( $product_id, $limit = 5, $exclude_ids = array() ) {
	global $wpdb;

	$product_id     = absint( $product_id );
	$exclude_ids    = array_merge( array( 0, $product_id ), $exclude_ids );
	$transient_name = 'wc_related_' . $product_id;
	$related_posts  = get_transient( $transient_name );
	$limit          = $limit > 0 ? $limit : 5;

	// We want to query related posts if they are not cached, or we don't have enough.
	if ( false === $related_posts || count( $related_posts ) < $limit ) {
		$cats_array = apply_filters( 'woocommerce_product_related_posts_relate_by_category', true, $product_id ) ? apply_filters( 'woocommerce_get_related_product_cat_terms', wc_get_product_term_ids( $product_id, 'product_cat' ), $product_id ) : array();
		$tags_array = apply_filters( 'woocommerce_product_related_posts_relate_by_tag', true, $product_id ) ? apply_filters( 'woocommerce_get_related_product_tag_terms', wc_get_product_term_ids( $product_id, 'product_tag' ), $product_id ) : array();

		// Don't bother if none are set, unless woocommerce_product_related_posts_force_display is set to true in which case all products are related.
		if ( empty( $cats_array ) && empty( $tags_array ) && ! apply_filters( 'woocommerce_product_related_posts_force_display', false, $product_id ) ) {
			$related_posts = array();
		} else {
			// Generate query - but query an extra 10 results to give the appearance of random results when later shuffled.
			$related_posts = $wpdb->get_col( implode( ' ', apply_filters( 'woocommerce_product_related_posts_query', wc_get_related_products_query( $cats_array, $tags_array, $exclude_ids, $limit + 10 ), $product_id ) ) );
		}

		set_transient( $transient_name, $related_posts, DAY_IN_SECONDS );
	}

	shuffle( $related_posts );

	return array_slice( $related_posts, 0, $limit );
}

/**
 * Retrieves product term ids for a taxonomy.
 *
 * @since  2.7.0
 * @param  int    $product_id Product ID.
 * @param  string $taxonomy   Taxonomy slug.
 * @return array
 */
function wc_get_product_term_ids( $product_id, $taxonomy ) {
	$terms = get_the_terms( $product_id, $taxonomy );
	return ! empty( $terms ) ? wp_list_pluck( $terms, 'term_id' ) : array();
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
	$exclude_ids = implode( ',', array_map( 'absint', $exclude_ids ) );
	$cats_array  = implode( ',', array_map( 'absint', $cats_array ) );
	$tags_array  = implode( ',', array_map( 'absint', $tags_array ) );

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

/**
 * For a given product, and optionally price/qty, work out the price with tax included, based on store settings.
 * @since  2.7.0
 * @param  WC_Product $product
 * @param  array $args
 * @return float
 */
function wc_get_price_including_tax( $product, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'qty'   => 1,
		'price' => $product->get_price(),
	) );

	$price = $args['price'];
	$qty   = $args['qty'];

	if ( ! $product->is_taxable() ) {
		$price = $price * $qty;
	} elseif ( wc_prices_include_tax() ) {
		$tax_rates  = WC_Tax::get_rates( $product->get_tax_class() );
		$taxes      = WC_Tax::calc_tax( $price * $qty, $tax_rates, false );
		$tax_amount = WC_Tax::get_tax_total( $taxes );
		$price      = round( $price * $qty + $tax_amount, wc_get_price_decimals() );
	} else {
		$tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
		$base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( true ) );

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
	return apply_filters( 'woocommerce_get_price_including_tax', $price, $qty, $product );
}

/**
 * For a given product, and optionally price/qty, work out the price with tax excluded, based on store settings.
 * @since  2.7.0
 * @param  WC_Product $product
 * @param  array $args
 * @return float
 */
function wc_get_price_excluding_tax( $product, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'qty'   => 1,
		'price' => $product->get_price(),
	) );

	$price = $args['price'];
	$qty   = $args['qty'];

	if ( $product->is_taxable() && wc_prices_include_tax() ) {
		$tax_rates  = WC_Tax::get_base_tax_rates( $product->get_tax_class( true ) );
		$taxes      = WC_Tax::calc_tax( $price * $qty, $tax_rates, true );
		$price      = WC_Tax::round( $price * $qty - array_sum( $taxes ) );
	} else {
		$price = $price * $qty;
	}

	return apply_filters( 'woocommerce_get_price_excluding_tax', $price, $qty, $product );
}

/**
 * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_shop' setting.
 * @since  2.7.0
 * @param  WC_Product $product
 * @param  array $args
 * @return float
 */
function wc_get_price_to_display( $product, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'qty'   => 1,
		'price' => $product->get_price(),
	) );

	$price = $args['price'];
	$qty   = $args['qty'];

	return 'incl' === get_option( 'woocommerce_tax_display_shop' ) ? wc_get_price_including_tax( $product, array( 'qty' => $qty, 'price' => $price ) ) : wc_get_price_excluding_tax( $product, array( 'qty' => $qty, 'price' => $price ) );
}

/**
 * Returns the product categories in a list.
 *
 * @param int $product_id
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function wc_get_product_category_list( $product_id, $sep = ', ', $before = '', $after = '' ) {
	return get_the_term_list( $product_id, 'product_cat', $before, $sep, $after );
}

/**
 * Returns the product tags in a list.
 *
 * @param int $product_id
 * @param string $sep (default: ', ').
 * @param string $before (default: '').
 * @param string $after (default: '').
 * @return string
 */
function wc_get_product_tag_list( $product_id, $sep = ', ', $before = '', $after = '' ) {
	return get_the_term_list( $product_id, 'product_tag', $before, $sep, $after );
}
