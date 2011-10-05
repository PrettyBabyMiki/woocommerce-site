<?php
/**
 * WooCommerce Templates
 * 
 * Handles template usage so that we can use our own templates instead of the theme's.
 *
 * Templates are in the 'templates' folder. woocommerce looks for theme 
 * overides in /theme/woocommerce/ by default  but this can be overwritten with WOOCOMMERCE_TEMPLATE_URL
 *
 * @package		WooCommerce
 * @category	Core
 * @author		WooThemes
 */
function woocommerce_template_loader( $template ) {
	global $woocommerce;
	
	if ( is_single() && get_post_type() == 'product' ) {
		
		$template = locate_template( array( 'single-product.php', WOOCOMMERCE_TEMPLATE_URL . 'single-product.php' ) );
		
		if ( ! $template ) $template = $woocommerce->plugin_path() . '/templates/single-product.php';
		
	}
	elseif ( is_tax('product_cat') ) {
		
		$template = locate_template(  array( 'taxonomy-product_cat.php', WOOCOMMERCE_TEMPLATE_URL . 'taxonomy-product_cat.php' ) );
		
		if ( ! $template ) $template = $woocommerce->plugin_path() . '/templates/taxonomy-product_cat.php';
	}
	elseif ( is_tax('product_tag') ) {
		
		$template = locate_template( array( 'taxonomy-product_tag.php', WOOCOMMERCE_TEMPLATE_URL . 'taxonomy-product_tag.php' ) );
		
		if ( ! $template ) $template = $woocommerce->plugin_path() . '/templates/taxonomy-product_tag.php';
	}
	elseif ( is_post_type_archive('product') ||  is_page( get_option('woocommerce_shop_page_id') )) {

		$template = locate_template( array( 'archive-product.php', WOOCOMMERCE_TEMPLATE_URL . 'archive-product.php' ) );
		
		if ( ! $template ) $template = $woocommerce->plugin_path() . '/templates/archive-product.php';
		
	}
	
	return $template;

}
add_filter( 'template_include', 'woocommerce_template_loader' );

/**
 * Get template part (for templates like loop)
 */
function woocommerce_get_template_part( $slug, $name = '' ) {
	global $woocommerce;
	if ($name=='shop') :
		if (!locate_template(array( 'loop-shop.php', WOOCOMMERCE_TEMPLATE_URL . 'loop-shop.php' ))) :
			load_template( $woocommerce->plugin_path() . '/templates/loop-shop.php',false );
			return;
		endif;
	endif;
	get_template_part( WOOCOMMERCE_TEMPLATE_URL . $slug, $name );
}

/**
 * Get the reviews template (comments)
 */
function woocommerce_comments_template($template) {
	global $woocommerce;
		
	if(get_post_type() !== 'product') return $template;
	
	if (file_exists( STYLESHEETPATH . '/' . WOOCOMMERCE_TEMPLATE_URL . 'single-product-reviews.php' ))
		return STYLESHEETPATH . '/' . WOOCOMMERCE_TEMPLATE_URL . 'single-product-reviews.php'; 
	else
		return $woocommerce->plugin_path() . '/templates/single-product-reviews.php';
}

add_filter('comments_template', 'woocommerce_comments_template' );


/**
 * Get other templates (e.g. product attributes)
 */
function woocommerce_get_template($template_name, $require_once = true) {
	global $woocommerce;
	if (file_exists( STYLESHEETPATH . '/' . WOOCOMMERCE_TEMPLATE_URL . $template_name )) load_template( STYLESHEETPATH . '/' . WOOCOMMERCE_TEMPLATE_URL . $template_name, $require_once ); 
	elseif (file_exists( STYLESHEETPATH . '/' . $template_name )) load_template( STYLESHEETPATH . '/' . $template_name , $require_once); 
	else load_template( $woocommerce->plugin_path() . '/templates/' . $template_name , $require_once);
}


/**
 * Front page archive/shop template
 */
if (!function_exists('woocommerce_front_page_archive')) {
	function woocommerce_front_page_archive() {
			
		global $paged, $woocommerce;
		
		if ( is_front_page() && is_page( get_option('woocommerce_shop_page_id') )) :
			
			if ( get_query_var('paged') ) {
			    $paged = get_query_var('paged');
			} else if ( get_query_var('page') ) {
			    $paged = get_query_var('page');
			} else {
			    $paged = 1;
			}
			
			add_filter( 'parse_query', array( &$woocommerce->query, 'parse_query') );
			
			query_posts( array( 'page_id' => '', 'post_type' => 'product', 'paged' => $paged ) );
			
			define('SHOP_IS_ON_FRONT', true);

		endif;
	}
}
add_action('wp', 'woocommerce_front_page_archive', 1);


/**
 * Add Body classes based on page/template
 **/
global $woocommerce_body_classes;

function woocommerce_page_body_classes() {
	
	global $woocommerce_body_classes;
	
	$woocommerce_body_classes = (array) $woocommerce_body_classes;
	
	$woocommerce_body_classes[] = 'theme-' . strtolower( get_current_theme() );
	
	if (is_woocommerce()) $woocommerce_body_classes[] = 'woocommerce';
	
	if (is_checkout()) $woocommerce_body_classes[] = 'woocommerce-checkout';
	
	if (is_cart()) $woocommerce_body_classes[] = 'woocommerce-cart';
	
	if (is_account_page()) $woocommerce_body_classes[] = 'woocommerce-account';
}
add_action('wp_head', 'woocommerce_page_body_classes');

function woocommerce_body_class($classes) {
	
	global $woocommerce_body_classes;
	
	$woocommerce_body_classes = (array) $woocommerce_body_classes;
	
	$classes = array_merge($classes, $woocommerce_body_classes);
	
	return $classes;
}
add_filter('body_class','woocommerce_body_class');

/**
 * Fix active class in nav for shop page
 **/
function woocommerce_nav_menu_item_classes( $menu_items, $args ) {
	
	if (!is_woocommerce()) return $menu_items;
	
	$shop_page 		= (int) get_option('woocommerce_shop_page_id');
	$page_for_posts = (int) get_option( 'page_for_posts' );

	foreach ( (array) $menu_items as $key => $menu_item ) :

		$classes = (array) $menu_item->classes;

		// Unset active class for blog page
		if ( $page_for_posts == $menu_item->object_id ) :
			$menu_items[$key]->current = false;
			unset( $classes[ array_search('current_page_parent', $classes) ] );
			unset( $classes[ array_search('current-menu-item', $classes) ] );

		// Set active state if this is the shop page link
		elseif ( is_shop() && $shop_page == $menu_item->object_id ) :
			$menu_items[$key]->current = true;
			$classes[] = 'current-menu-item';
			$classes[] = 'current_page_item';
		
		endif;

		$menu_items[$key]->classes = array_unique( $classes );
	
	endforeach;

	return $menu_items;
}
add_filter( 'wp_nav_menu_objects',  'woocommerce_nav_menu_item_classes', 2, 20 );