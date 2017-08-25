<?php
/**
 * Products shortcode
 *
 * @author   Automattic
 * @category Shortcodes
 * @package  WooCommerce/Shortcodes
 * @version  3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Products shortcode class.
 */
class WC_Shortcode_Products {

	/**
	 * Shortcode type.
	 *
	 * @since 3.2.0
	 * @var   string
	 */
	protected $type = 'products';

	/**
	 * Attributes.
	 *
	 * @since 3.2.0
	 * @var   array
	 */
	protected $attributes = array();

	/**
	 * Query args.
	 *
	 * @since 3.2.0
	 * @var   array
	 */
	protected $query_args = array();

	/**
	 * Initialize shortcode.
	 *
	 * @since 3.2.0
	 * @param array $attributes Shortcode attributes.
	 * @param array $type       Shortcode type.
	 */
	public function __construct( $attributes = array(), $type = 'products' ) {
		$this->type       = $type;
		$this->attributes = $this->parse_attributes( $attributes );
		$this->query_args = $this->parse_query_args();
	}

	/**
	 * Get shortcode attributes.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Get query args.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_query_args() {
		return $this->query_args;
	}

	/**
	 * Get shortcode type.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get shortcode content.
	 *
	 * @since  3.2.0
	 * @return string
	 */
	public function get_content() {
		return $this->product_loop();
	}

	/**
	 * Parse attributes.
	 *
	 * @param  array $attributes Shortcode attributes.
	 * @return array
	 */
	protected function parse_attributes( $attributes ) {
		return shortcode_atts( array(
			'per_page'  => '-1',
			'columns'   => '4',
			'orderby'   => 'title',
			'order'     => 'ASC',
			'ids'       => '',
			'skus'      => '',
			'category'  => '',   // Slugs.
			'operator'  => 'IN', // Category operator. Possible values are 'IN', 'NOT IN', 'AND'.
			'class'     => '',
			'attribute' => '',
			'filter'    => '',
		), $attributes, $this->type );
	}

	/**
	 * Parse query args.
	 *
	 * @since  3.2.0
	 * @return array
	 */
	protected function parse_query_args() {
		$query_args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'orderby'             => $this->attributes['orderby'],
			'order'               => strtoupper( $this->attributes['order'] ),
		);

		// @codingStandardsIgnoreStart
		$query_args['posts_per_page'] = (int) $this->attributes['per_page'];
		$query_args['meta_query']     = WC()->query->get_meta_query();
		$query_args['tax_query']      = WC()->query->get_tax_query();
		// @codingStandardsIgnoreEnd

		// SKUs.
		if ( ! empty( $this->attributes['skus'] ) ) {
			$skus = array_map( 'trim', explode( ',', $this->attributes['skus'] ) );
			$query_args['meta_query'][] = array(
				'key'     => '_sku',
				'value'   => 1 === count( $skus ) ? $skus[0] : $skus,
				'compare' => 1 === count( $skus ) ? '=' : 'IN',
			);
		}

		// IDs.
		if ( ! empty( $this->attributes['ids'] ) ) {
			$ids = array_map( 'trim', explode( ',', $this->attributes['ids'] ) );

			if ( 1 === count( $ids ) ) {
				$query_args['p'] = $ids[0];
			} else {
				$query_args['post__in'] = $ids;
			}
		}

		// On sale.
		if ( 'sale_products' === $this->type ) {
			$query_args['post__in'] = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
		}

		// Best selling.
		if ( 'best_selling_products' === $this->type ) {
			$query_args['meta_key'] = 'total_sales';
			$query_args['order']    = 'DESC';
			$query_args['orderby']  = 'meta_value_num';
		}

		// Featured products.
		if ( 'featured_products' === $this->type ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'terms'    => 'featured',
				'field'    => 'name',
				'operator' => 'IN',
			);
		}

		// Attributes.
		if ( ! empty( $this->attributes['attribute'] ) || ! empty( $this->attributes['filter'] ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => strstr( $this->attributes['attribute'], 'pa_' ) ? sanitize_title( $this->attributes['attribute'] ) : 'pa_' . sanitize_title( $this->attributes['attribute'] ),
				'terms'    => array_map( 'sanitize_title', explode( ',', $this->attributes['filter'] ) ),
				'field'    => 'slug',
			);
		}

		// Categories.
		if ( ! empty( $this->attributes['category'] ) ) {
			$ordering_args = WC()->query->get_catalog_ordering_args( $query_args['orderby'], $query_args['order'] );
			$query_args['orderby'] = $ordering_args['orderby'];
			$query_args['order']   = $ordering_args['order'];

			if ( isset( $ordering_args['meta_key'] ) ) {
				$query_args['meta_key'] = $ordering_args['meta_key'];
			}

			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'terms'    => array_map( 'sanitize_title', explode( ',', $this->attributes['category'] ) ),
				'field'    => 'slug',
				'operator' => $this->attributes['operator'],
			);
		}

		return apply_filters( 'woocommerce_shortcode_products_query', $query_args, $this->attributes, $this->type );
	}

	/**
	 * Loop over found products.
	 *
	 * @since  3.2.0
	 * @return string
	 */
	protected function product_loop() {
		global $woocommerce_loop;

		$columns                     = absint( $this->attributes['columns'] );
		$woocommerce_loop['columns'] = $columns;
		$woocommerce_loop['name']    = $this->type;
		$transient_name              = 'wc_loop' . substr( md5( wp_json_encode( $this->query_args ) . $this->type ), 28 ) . WC_Cache_Helper::get_transient_version( 'product_query' );
		$products                    = get_transient( $transient_name );

		if ( false === $products || ! is_a( $products, 'WP_Query' ) ) {
			if ( 'top_rated_products' === $this->type ) {
				add_filter( 'posts_clauses', array( __CLASS__, 'order_by_rating_post_clauses' ) );
				$products = new WP_Query( $this->query_args );
				remove_filter( 'posts_clauses', array( __CLASS__, 'order_by_rating_post_clauses' ) );
			} else {
				$products = new WP_Query( $this->query_args );
			}

			set_transient( $transient_name, $products, DAY_IN_SECONDS * 30 );
		}

		// Remove ordering query arguments.
		if ( ! empty( $this->attributes['category'] ) ) {
			WC()->query->remove_ordering_args();
		}

		ob_start();

		if ( $products->have_posts() ) {
			// Prime caches before grabbing objects.
			update_post_caches( $products->posts, array( 'product', 'product_variation' ) );

			do_action( "woocommerce_shortcode_before_{$this->type}_loop", $this->attributes );

			woocommerce_product_loop_start();

			while ( $products->have_posts() ) {
				$products->the_post();
				wc_get_template_part( 'content', 'product' );
			}

			woocommerce_product_loop_end();

			do_action( "woocommerce_shortcode_after_{$this->type}_loop", $this->attributes );
		} else {
			do_action( "woocommerce_shortcode_{$this->type}_loop_no_results", $this->attributes );
		}

		woocommerce_reset_loop();
		wp_reset_postdata();

		$classes = array( 'woocommerce' );
		if ( 'product' !== $this->type ) {
			$classes[] = 'columns-' . $columns;
		}
		$classes[] = $this->attributes['class'];

		return '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">' . ob_get_clean() . '</div>';
	}

	/**
	 * Order by rating.
	 *
	 * @since  3.2.0
	 * @param  array $args Query args.
	 * @return array
	 */
	public static function order_by_rating_post_clauses( $args ) {
		global $wpdb;

		$args['where']   .= " AND $wpdb->commentmeta.meta_key = 'rating' ";
		$args['join']    .= "LEFT JOIN $wpdb->comments ON($wpdb->posts.ID = $wpdb->comments.comment_post_ID) LEFT JOIN $wpdb->commentmeta ON($wpdb->comments.comment_ID = $wpdb->commentmeta.comment_id)";
		$args['orderby'] = "$wpdb->commentmeta.meta_value DESC";
		$args['groupby'] = "$wpdb->posts.ID";

		return $args;
	}
}
