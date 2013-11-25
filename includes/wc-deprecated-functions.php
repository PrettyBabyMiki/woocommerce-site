<?php
/**
 * Deprecated functions
 *
 * Where functions come to die.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function woocommerce_show_messages() {
	_deprecated_function( 'woocommerce_show_messages', '2.1', 'wc_print_notices' );
	wc_print_notices();
}
function woocommerce_weekend_area_js() {
	_deprecated_function( 'woocommerce_weekend_area_js', '2.1', '' );
}
function woocommerce_tooltip_js() {
	_deprecated_function( 'woocommerce_tooltip_js', '2.1', '' );
}
function woocommerce_datepicker_js() {
	_deprecated_function( 'woocommerce_datepicker_js', '2.1', '' );
}
function woocommerce_admin_scripts() {
	_deprecated_function( 'woocommerce_admin_scripts', '2.1', '' );
}
function woocommerce_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
	_deprecated_function( 'woocommerce_create_page', '2.1', 'wc_create_page' );
	return wc_create_page( $slug, $option, $page_title, $page_content, $post_parent );
}
function woocommerce_readfile_chunked( $file, $retbytes = true ) {
	_deprecated_function( 'woocommerce_readfile_chunked', '2.1', 'WC_Download_Handler::readfile_chunked()' );
	return WC_Download_Handler::readfile_chunked( $file, $retbytes );
}

/**
 * Formal total costs - format to the number of decimal places for the base currency.
 *
 * @access public
 * @param mixed $number
 * @deprecated 2.1
 * @return string
 */
function woocommerce_format_total( $number ) {
	_deprecated_function( __FUNCTION__, '2.1', 'wc_format_decimal()' );
	return wc_format_decimal( $number, get_option( 'woocommerce_price_num_decimals' ), false );
}

/**
 * Get product name with extra details such as SKU price and attributes. Used within admin.
 *
 * @access public
 * @param mixed $product
 * @deprecated 2.1
 * @return void
 */
function woocommerce_get_formatted_product_name( $product ) {
	_deprecated_function( __FUNCTION__, '2.1', 'WC_Product::get_formatted_name()' );
	return $product->get_formatted_name();
}

/**
 * Handle IPN requests for the legacy paypal gateway by calling gateways manually if needed.
 *
 * @access public
 * @return void
 */
function woocommerce_legacy_paypal_ipn() {
	if ( ! empty( $_GET['paypalListener'] ) && $_GET['paypalListener'] == 'paypal_standard_IPN' ) {
		global $woocommerce;

		$woocommerce->payment_gateways();

		do_action( 'woocommerce_api_wc_gateway_paypal' );
	}
}
add_action( 'init', 'woocommerce_legacy_paypal_ipn' );

/**
 * Cart functions (soft deprecated)
 */
function woocommerce_protected_product_add_to_cart( $passed, $product_id ) {
	wc_protected_product_add_to_cart( $passed, $product_id );
}
function woocommerce_empty_cart() {
	wc_empty_cart();
}
function woocommerce_load_persistent_cart( $user_login, $user = 0 ) {
	wc_load_persistent_cart( $user_login, $user );
}
function woocommerce_add_to_cart_message( $product_id ) {
	wc_add_to_cart_message( $product_id );
}
function woocommerce_clear_cart_after_payment() {
	wc_clear_cart_after_payment();
}

/**
 * Core functions (soft deprecated)
 */
function woocommerce_get_template_part( $slug, $name = '' ) {
	wc_get_template_part( $slug, $name );
}
function woocommerce_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	wc_get_template( $template_name, $args, $template_path, $default_path );
}
function woocommerce_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	wc_locate_template( $template_name, $template_path, $default_path );
}
function woocommerce_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = "" ) {
	wc_mail( $to, $subject, $message, $headers, $attachments );
}

/**
 * Customer functions (soft deprecated)
 */
function woocommerce_disable_admin_bar( $show_admin_bar ) {
	wc_disable_admin_bar( $show_admin_bar );
}
function woocommerce_create_new_customer( $email, $username = '', $password = '' ) {
	wc_create_new_customer( $email, $username, $password );
}
function woocommerce_set_customer_auth_cookie( $customer_id ) {
	wc_set_customer_auth_cookie( $customer_id );
}
function woocommerce_update_new_customer_past_orders( $customer_id ) {
	wc_update_new_customer_past_orders( $customer_id );
}
function wc_paying_customer( $order_id ) {
	woocommerce_paying_customer( $order_id );
}
function woocommerce_customer_bought_product( $customer_email, $user_id, $product_id ) {
	wc_customer_bought_product( $customer_email, $user_id, $product_id );
}
function woocommerce_customer_has_capability( $allcaps, $caps, $args ) {
	wc_customer_has_capability( $allcaps, $caps, $args );
}

/**
 * Formatting functions (soft deprecated)
 */
function woocommerce_sanitize_taxonomy_name( $taxonomy ) {
	wc_sanitize_taxonomy_name( $taxonomy );
}
function woocommerce_get_filename_from_url( $file_url ) {
	wc_get_filename_from_url( $file_url );
}
function woocommerce_get_dimension( $dim, $to_unit ) {
	wc_get_dimension( $dim, $to_unit );
}
function woocommerce_get_weight( $weight, $to_unit ) {
	wc_get_weight( $weight, $to_unit );
}
function woocommerce_trim_zeros( $price ) {
	wc_trim_zeros( $price );
}
function woocommerce_round_tax_total( $tax ) {
	wc_round_tax_total( $tax );
}
function woocommerce_format_decimal( $number, $dp = false, $trim_zeros = false ) {
	wc_format_decimal( $number, $dp, $trim_zeros );
}
function woocommerce_clean( $var ) {
	wc_clean( $var );
}
function woocommerce_array_overlay( $a1, $a2 ) {
	wc_array_overlay( $a1, $a2 );
}
function woocommerce_price( $price, $args = array() ) {
	wc_price( $price, $args );
}
function woocommerce_let_to_num( $size ) {
	wc_let_to_num( $size );
}
function woocommerce_date_format() {
	wc_date_format();
}
function woocommerce_time_format() {
	wc_time_format();
}
function woocommerce_timezone_string() {
	wc_timezone_string();
}
if ( ! function_exists( 'woocommerce_rgb_from_hex' ) ) {
	function woocommerce_rgb_from_hex( $color ) {
		wc_rgb_from_hex( $color );
	}
}
if ( ! function_exists( 'woocommerce_hex_darker' ) ) {
	function woocommerce_hex_darker( $color, $factor = 30 ) {
		wc_hex_darker( $color, $factor );
	}
}
if ( ! function_exists( 'woocommerce_hex_lighter' ) ) {
	function woocommerce_hex_lighter( $color, $factor = 30 ) {
		wc_hex_lighter( $color, $factor );
	}
}
if ( ! function_exists( 'woocommerce_light_or_dark' ) ) {
	function woocommerce_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
		wc_light_or_dark( $color, $dark, $light );
	}
}
if ( ! function_exists( 'woocommerce_format_hex' ) ) {
	function woocommerce_format_hex( $hex ) {
		wc_format_hex( $hex );
	}
}

/**
 * Handle renamed filters
 */
global $wc_map_deprecated_filters;

$wc_map_deprecated_filters = array(
	'woocommerce_cart_item_class'       => 'woocommerce_cart_table_item_class',
	'woocommerce_cart_item_product_id'  => 'hook_woocommerce_in_cart_product_id',
	'woocommerce_cart_item_thumbnail'   => 'hook_woocommerce_in_cart_product_thumbnail',
	'woocommerce_cart_item_price'       => 'woocommerce_cart_item_price_html',
	'woocommerce_cart_item_name'        => 'woocommerce_in_cart_product_title',
	'woocommerce_order_item_class'      => 'woocommerce_order_table_item_class',
	'woocommerce_order_item_name'       => 'woocommerce_order_table_product_title',
	'woocommerce_order_amount_shipping' => 'woocommerce_order_amount_total_shipping'
);

foreach ( $wc_map_deprecated_filters as $new => $old )
	add_filter( $new, 'woocommerce_deprecated_filter_mapping' );

function woocommerce_deprecated_filter_mapping( $data, $arg_1 = '', $arg_2 = '', $arg_3 = '' ) {
	global $wc_map_deprecated_filters;

	$filter = current_filter();

	if ( isset( $wc_map_deprecated_filters[ $filter ] ) )
		if ( has_filter( $wc_map_deprecated_filters[ $filter ] ) ) {
			$data = apply_filters( $wc_map_deprecated_filters[ $filter ], $arg_1, $arg_2, $arg_3 );
			_deprecated_function( 'The ' . $wc_map_deprecated_filters[ $filter ] . ' filter', '2.1', $filter );
		}

	return $data;
}