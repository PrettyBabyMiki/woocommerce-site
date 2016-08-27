<?php
/**
 * WooCommerce Page Functions
 *
 * Functions related to pages and menus.
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Replace a page title with the endpoint title.
 * @param  string $title
 * @return string
 */
function wc_page_endpoint_title( $title ) {
	global $wp_query;

	if ( ! is_null( $wp_query ) && ! is_admin() && is_main_query() && in_the_loop() && is_page() && is_wc_endpoint_url() ) {
		$endpoint = WC()->query->get_current_endpoint();

		if ( $endpoint_title = WC()->query->get_endpoint_title( $endpoint ) ) {
			$title = $endpoint_title;
		}

		remove_filter( 'the_title', 'wc_page_endpoint_title' );
	}

	return $title;
}

add_filter( 'the_title', 'wc_page_endpoint_title' );

/**
 * Retrieve page ids - used for myaccount, edit_address, shop, cart, checkout, pay, view_order, terms. returns -1 if no page is found.
 *
 * @param string $page
 * @return int
 */
function wc_get_page_id( $page ) {

	if ( $page == 'pay' || $page == 'thanks' ) {
		_deprecated_argument( __FUNCTION__, '2.1', 'The "pay" and "thanks" pages are no-longer used - an endpoint is added to the checkout instead. To get a valid link use the WC_Order::get_checkout_payment_url() or WC_Order::get_checkout_order_received_url() methods instead.' );

		$page = 'checkout';
	}
	if ( $page == 'change_password' || $page == 'edit_address' || $page == 'lost_password' ) {
		_deprecated_argument( __FUNCTION__, '2.1', 'The "change_password", "edit_address" and "lost_password" pages are no-longer used - an endpoint is added to the my-account instead. To get a valid link use the wc_customer_edit_account_url() function instead.' );

		$page = 'myaccount';
	}

	$page = apply_filters( 'woocommerce_get_' . $page . '_page_id', get_option('woocommerce_' . $page . '_page_id' ) );

	return $page ? absint( $page ) : -1;
}

/**
 * Retrieve page permalink.
 *
 * @param string $page
 * @return string
 */
function wc_get_page_permalink( $page ) {
	$page_id   = wc_get_page_id( $page );
	$permalink = 0 < $page_id ? get_permalink( $page_id ) : get_home_url();
	return apply_filters( 'woocommerce_get_' . $page . '_page_permalink', $permalink );
}

/**
 * Get endpoint URL.
 *
 * Gets the URL for an endpoint, which varies depending on permalink settings.
 *
 * @param  string $endpoint
 * @param  string $value
 * @param  string $permalink
 *
 * @return string
 */
function wc_get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
	if ( ! $permalink ) {
		$permalink = get_permalink();
	}

	// Map endpoint to options
	$endpoint = ! empty( WC()->query->query_vars[ $endpoint ] ) ? WC()->query->query_vars[ $endpoint ] : $endpoint;
	$value    = ( 'edit-address' == $endpoint ) ? wc_edit_address_i18n( $value ) : $value;

	if ( get_option( 'permalink_structure' ) ) {
		if ( strstr( $permalink, '?' ) ) {
			$query_string = '?' . parse_url( $permalink, PHP_URL_QUERY );
			$permalink    = current( explode( '?', $permalink ) );
		} else {
			$query_string = '';
		}
		$url = trailingslashit( $permalink ) . $endpoint . '/' . $value . $query_string;
	} else {
		$url = add_query_arg( $endpoint, $value, $permalink );
	}

	return apply_filters( 'woocommerce_get_endpoint_url', $url, $endpoint, $value, $permalink );
}

/**
 * Hide menu items conditionally.
 *
 * @param array $items
 * @return array
 */
function wc_nav_menu_items( $items ) {
	if ( ! is_user_logged_in() ) {
		$customer_logout = get_option( 'woocommerce_logout_endpoint', 'customer-logout' );

		if ( ! empty( $customer_logout ) ) {
			foreach ( $items as $key => $item ) {
				$path = parse_url( $item->url, PHP_URL_PATH );
				$query = parse_url( $item->url, PHP_URL_QUERY );
				if ( strstr( $path, $customer_logout ) || strstr( $query, $customer_logout ) ) {
					unset( $items[ $key ] );
				}
			}
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'wc_nav_menu_items', 10 );


/**
 * Fix active class in nav for shop page.
 *
 * @param array $menu_items
 * @return array
 */
function wc_nav_menu_item_classes( $menu_items ) {

	if ( ! is_woocommerce() ) {
		return $menu_items;
	}

	$shop_page 		= (int) wc_get_page_id('shop');
	$page_for_posts = (int) get_option( 'page_for_posts' );

	foreach ( (array) $menu_items as $key => $menu_item ) {

		$classes = (array) $menu_item->classes;

		// Unset active class for blog page
		if ( $page_for_posts == $menu_item->object_id ) {
			$menu_items[ $key ]->current = false;

			if ( in_array( 'current_page_parent', $classes ) ) {
				unset( $classes[ array_search('current_page_parent', $classes) ] );
			}

			if ( in_array( 'current-menu-item', $classes ) ) {
				unset( $classes[ array_search('current-menu-item', $classes) ] );
			}

		// Set active state if this is the shop page link
		} elseif ( is_shop() && $shop_page == $menu_item->object_id && 'page' === $menu_item->object ) {
			$menu_items[ $key ]->current = true;
			$classes[] = 'current-menu-item';
			$classes[] = 'current_page_item';

		// Set parent state if this is a product page
		} elseif ( is_singular( 'product' ) && $shop_page == $menu_item->object_id ) {
			$classes[] = 'current_page_parent';
		}

		$menu_items[ $key ]->classes = array_unique( $classes );

	}

	return $menu_items;
}
add_filter( 'wp_nav_menu_objects', 'wc_nav_menu_item_classes', 2 );


/**
 * Fix active class in wp_list_pages for shop page.
 *
 * https://github.com/woothemes/woocommerce/issues/177.
 *
 * @author Jessor, Peter Sterling
 * @param string $pages
 * @return string
 */
function wc_list_pages( $pages ) {
	if ( is_woocommerce() ) {
		// Remove current_page_parent class from any item.
		$pages = str_replace( 'current_page_parent', '', $pages );
		// Find shop_page_id through woocommerce options.
		$shop_page = 'page-item-' . wc_get_page_id( 'shop' );

		if ( is_shop() ) {
			// Add current_page_item class to shop page.
			$pages = str_replace( $shop_page, $shop_page . ' current_page_item', $pages );
		} else {
			// Add current_page_parent class to shop page.
			$pages = str_replace( $shop_page, $shop_page . ' current_page_parent', $pages );
		}
	}

	return $pages;
}
add_filter( 'wp_list_pages', 'wc_list_pages' );
