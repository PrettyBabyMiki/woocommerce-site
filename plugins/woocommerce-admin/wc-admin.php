<?php
/**
 * Plugin Name: WooCommerce Admin
 * Plugin URI: https://woocommerce.com/
 * Description: A feature plugin for a modern, javascript-driven WooCommerce admin experience.
 * Author: Automattic
 * Author URI: https://woocommerce.com/
 * Text Domain: wc-admin
 * Domain Path: /languages
 * Version: 0.7.0
 *
 * @package WC_Admin
 */

if ( ! defined( 'WC_ADMIN_APP' ) ) {
	define( 'WC_ADMIN_APP', 'wc-admin-app' );
}

if ( ! defined( 'WC_ADMIN_ABSPATH' ) ) {
	define( 'WC_ADMIN_ABSPATH', dirname( __FILE__ ) );
}

if ( ! defined( 'WC_ADMIN_PLUGIN_FILE' ) ) {
	define( 'WC_ADMIN_PLUGIN_FILE', __FILE__ );
}

/**
 * Notify users of the plugin requirements.
 */
function wc_admin_plugins_notice() {
	// The notice varies by WordPress version.
	$wordpress_version            = get_bloginfo( 'version' );
	$wordpress_includes_gutenberg = version_compare( $wordpress_version, '4.9.9', '>' );

	if ( $wordpress_includes_gutenberg ) {
		$message = sprintf(
			/* translators: URL of WooCommerce plugin */
			__( 'The WooCommerce Admin feature plugin requires <a href="%s">WooCommerce</a> 3.5 or greater to be installed and active.', 'wc-admin' ),
			'https://wordpress.org/plugins/woocommerce/'
		);
	} else {
		$message = sprintf(
			/* translators: 1: URL of WordPress.org, 2: URL of WooCommerce plugin */
			__( 'The WooCommerce Admin feature plugin requires both <a href="%1$s">WordPress</a> 5.0 or greater and <a href="%2$s">WooCommerce</a> 3.5 or greater to be installed and active.', 'wc-admin' ),
			'https://wordpress.org/',
			'https://wordpress.org/plugins/woocommerce/'
		);
	}
	printf( '<div class="error"><p>%s</p></div>', $message ); /* WPCS: xss ok. */
}

/**
 * Notify users that the plugin needs to be built.
 */
function wc_admin_build_notice() {
	$message_one = __( 'You have installed a development version of WooCommerce Admin which requires files to be built. From the plugin directory, run <code>npm install</code> to install dependencies, <code>npm run build</code> to build the files.', 'wc-admin' );
	$message_two = sprintf(
		/* translators: 1: URL of GitHub Repository build page */
		__( 'Or you can download a pre-built version of the plugin by visiting <a href="%1$s">the releases page in the repository</a>.', 'wc-admin' ),
		'https://github.com/woocommerce/wc-admin/releases'
	);
	printf( '<div class="error"><p>%s %s</p></div>', $message_one, $message_two ); /* WPCS: xss ok. */
}

/**
 * Returns true if all dependencies for the wc-admin plugin are loaded.
 *
 * @return bool
 */
function dependencies_satisfied() {
	$woocommerce_minimum_met = class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.5', '>' );
	if ( ! $woocommerce_minimum_met ) {
		return false;
	}

	$wordpress_version = get_bloginfo( 'version' );
	return version_compare( $wordpress_version, '4.9.9', '>' );
}

/**
 * Returns true if build file exists.
 *
 * @return bool
 */
function wc_admin_build_file_exists() {
	return file_exists( plugin_dir_path( __FILE__ ) . '/dist/app/index.js' );
}

/**
 * Daily events to run.
 */
function do_wc_admin_daily() {
	WC_Admin_Notes_New_Sales_Record::possibly_add_sales_record_note();
}
add_action( 'wc_admin_daily', 'do_wc_admin_daily' );

/**
 * Initializes wc-admin daily action when plugin activated.
 */
function activate_wc_admin_plugin() {
	if ( ! dependencies_satisfied() ) {
		return;
	}

	if ( ! wp_next_scheduled( 'wc_admin_daily' ) ) {
		wp_schedule_event( time(), 'daily', 'wc_admin_daily' );
	}
}
register_activation_hook( WC_ADMIN_PLUGIN_FILE, 'activate_wc_admin_plugin' );

/**
 * Deactivate wc-admin plugin if dependencies not satisfied.
 */
function possibly_deactivate_wc_admin_plugin() {
	if ( ! dependencies_satisfied() ) {
		deactivate_plugins( plugin_basename( WC_ADMIN_PLUGIN_FILE ) );
		unset( $_GET['activate'] );
	}
}
add_action( 'admin_init', 'possibly_deactivate_wc_admin_plugin' );

/**
 * On deactivating the wc-admin plugin.
 */
function deactivate_wc_admin_plugin() {
	wp_clear_scheduled_hook( 'wc_admin_daily' );
}
register_deactivation_hook( WC_ADMIN_PLUGIN_FILE, 'deactivate_wc_admin_plugin' );

/**
 * Set up the plugin, only if we can detect both Gutenberg and WooCommerce
 */
function wc_admin_plugins_loaded() {
	if ( ! dependencies_satisfied() ) {
		add_action( 'admin_notices', 'wc_admin_plugins_notice' );
		return;
	}

	if ( ! function_exists( 'wc_admin_get_feature_config' ) ) {
		require_once WC_ADMIN_ABSPATH . '/includes/feature-config.php';
	}

	// Initialize the WC API extensions.
	require_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-reports-sync.php';
	require_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-install.php';
	require_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-api-init.php';

	// Some common utilities.
	require_once WC_ADMIN_ABSPATH . '/lib/common.php';

	// Admin note providers.
	require_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-notes-new-sales-record.php';
	require_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-notes-settings-notes.php';
	require_once WC_ADMIN_ABSPATH . '/includes/class-wc-admin-notes-woo-subscriptions-notes.php';

	// Verify we have a proper build.
	if ( ! wc_admin_build_file_exists() ) {
		add_action( 'admin_notices', 'wc_admin_build_notice' );
		return;
	}

	// Register script files.
	require_once WC_ADMIN_ABSPATH . '/lib/client-assets.php';

	// Create the Admin pages.
	require_once WC_ADMIN_ABSPATH . '/lib/admin.php';
}
add_action( 'plugins_loaded', 'wc_admin_plugins_loaded' );

/**
 * Things to do after WooCommerce updates.
 */
function wc_admin_woocommerce_updated() {
	WC_Admin_Notes_Settings_Notes::add_notes_for_settings_that_have_moved();
}
add_action( 'woocommerce_updated', 'wc_admin_woocommerce_updated' );

/*
 * Remove the emoji script as it always defaults to replacing emojis with Twemoji images.
 * Gutenberg has also disabled emojis. More on that here -> https://github.com/WordPress/gutenberg/pull/6151
 */
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
