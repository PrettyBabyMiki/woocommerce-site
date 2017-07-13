<?php
/**
 * Plugin Name: WooCommerce
 * Plugin URI: https://woocommerce.com/
 * Description: An e-commerce toolkit that helps you sell anything. Beautifully.
 * Version: 3.2.0-dev
 * Author: Automattic
 * Author URI: https://woocommerce.com
 * Requires at least: 4.4
 * Tested up to: 4.7
 *
 * Text Domain: woocommerce
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerce
 * @category Core
 * @author Automattic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define WC_PLUGIN_FILE.
if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
	define( 'WC_PLUGIN_FILE', __FILE__ );
}

// Define WC_ABSPATH.
if ( ! defined( 'WC_ABSPATH' ) ) {
	define( 'WC_ABSPATH', dirname( __FILE__ ) . '/' );
}

// Define WC_PLUGIN_BASENAME.
if ( ! defined( 'WC_PLUGIN_BASENAME' ) ) {
	define( 'WC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

// Include the main WooCommerce class.
if ( ! class_exists( 'WooCommerce' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-woocommerce.php';
}

/**
 * Main instance of WooCommerce.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @since  2.1
 * @return WooCommerce
 */
function wc() {
	return WooCommerce::instance();
}

// Global for backwards compatibility.
$GLOBALS['woocommerce'] = wc();
