<?php
/**
 * Template Loader
 *
 * @class 		WC_Template
 * @package		WooCommerce/Classes
 * @category	Class
 * @author 		Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Template_Loader.
 */
class WC_Template_Loader {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_loader' ) );
		add_filter( 'comments_template', array( __CLASS__, 'comments_template_loader' ) );
		add_filter( 'the_content', array( __CLASS__, 'the_content_filter' ) );
	}

	/**
	 * Filter the content and insert WooCommerce content. @todo
	 *
	 * @since 3.3.0
	 * @param string $content Existing post content.
	 * @return string
	 */
	public static function the_content_filter( $content ) {
		if ( ! current_theme_supports( 'woocommerce' ) && is_page( wc_get_page_id( 'shop' ) ) && is_main_query() ) {
			$page      = max( 1, absint( get_query_var( 'paged' ) ) );
			$columns   = 3;
			$rows      = 3;

			$shortcode = new WC_Shortcode_Products(
				array_merge(
					wc()->query->get_catalog_ordering_args(),
					array(
						'page'     => $page,
						'columns'  => $columns,
						'rows'     => $rows,
						'paginate' => true,
					)
				),
			'products' );

			$content .= $shortcode->get_content();

			// Remove self to avoid nested calls.
			remove_filter( 'the_content', array( __CLASS__, 'the_content_filter' ) );
		}
		return $content;
	}

	/**
	 * Load a template.
	 *
	 * Handles template usage so that we can use our own templates instead of the themes.
	 *
	 * Templates are in the 'templates' folder. woocommerce looks for theme.
	 * overrides in /theme/woocommerce/ by default.
	 *
	 * For beginners, it also looks for a woocommerce.php template first. If the user adds.
	 * this to the theme (containing a woocommerce() inside) this will be used for all.
	 * woocommerce templates.
	 *
	 * @param mixed $template
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_embed() || ! current_theme_supports( 'woocommerce' ) ) {
			return $template;
		}

		if ( $default_file = self::get_template_loader_default_file() ) {
			/**
			 * Filter hook to choose which files to find before WooCommerce does it's own logic.
			 *
			 * @since 3.0.0
			 * @var array
			 */
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );

			if ( ! $template || WC_TEMPLATE_DEBUG_MODE ) {
				$template = WC()->plugin_path() . '/templates/' . $default_file;
			}
		}

		return $template;
	}

	/**
	 * Get the default filename for a template.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	private static function get_template_loader_default_file() {
		if ( is_singular( 'product' ) ) {
			$default_file = 'single-product.php';
		} elseif ( is_product_taxonomy() ) {
			$term = get_queried_object();

			if ( is_tax( 'product_cat' ) || is_tax( 'product_tag' ) ) {
				$default_file = 'taxonomy-' . $term->taxonomy . '.php';
			} else {
				$default_file = 'archive-product.php';
			}
		} elseif ( is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) {
			$default_file = 'archive-product.php';
		} else {
			$default_file = '';
		}
		return $default_file;
	}

	/**
	 * Get an array of filenames to search for a given template.
	 *
	 * @since  3.0.0
	 * @param  string $default_file The default file name.
	 * @return string[]
	 */
	private static function get_template_loader_files( $default_file ) {
		$search_files   = apply_filters( 'woocommerce_template_loader_files', array(), $default_file );
		$search_files[] = 'woocommerce.php';

		if ( is_page_template() ) {
			$search_files[] = get_page_template_slug();
		}

		if ( is_product_taxonomy() ) {
			$term   = get_queried_object();
			$search_files[] = 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$search_files[] = WC()->template_path() . 'taxonomy-' . $term->taxonomy . '-' . $term->slug . '.php';
			$search_files[] = 'taxonomy-' . $term->taxonomy . '.php';
			$search_files[] = WC()->template_path() . 'taxonomy-' . $term->taxonomy . '.php';
		}

		$search_files[] = $default_file;
		$search_files[] = WC()->template_path() . $default_file;

		return array_unique( $search_files );
	}

	/**
	 * Load comments template.
	 *
	 * @param string $template template to load.
	 * @return string
	 */
	public static function comments_template_loader( $template ) {
		if ( get_post_type() !== 'product' || ! current_theme_supports( 'woocommerce' ) ) {
			return $template;
		}

		$check_dirs = array(
			trailingslashit( get_stylesheet_directory() ) . WC()->template_path(),
			trailingslashit( get_template_directory() ) . WC()->template_path(),
			trailingslashit( get_stylesheet_directory() ),
			trailingslashit( get_template_directory() ),
			trailingslashit( WC()->plugin_path() ) . 'templates/',
		);

		if ( WC_TEMPLATE_DEBUG_MODE ) {
			$check_dirs = array( array_pop( $check_dirs ) );
		}

		foreach ( $check_dirs as $dir ) {
			if ( file_exists( trailingslashit( $dir ) . 'single-product-reviews.php' ) ) {
				return trailingslashit( $dir ) . 'single-product-reviews.php';
			}
		}
	}
}

WC_Template_Loader::init();
