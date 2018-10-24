<?php
/**
 * Register javascript & css files.
 *
 * @package WC_Admin
 */

/**
 * Registers the JS & CSS for the admin and admin embed
 */
function wc_admin_register_script() {
	// Are we displaying the full React app or just embedding the header on a classic screen?
	$screen_id = wc_admin_get_current_screen_id();

	if ( in_array( $screen_id, wc_admin_get_embed_enabled_screen_ids() ) ) {
		$js_entry  = 'dist/embedded.js';
		$css_entry = 'dist/css/embedded.css';
	} else {
		$js_entry  = 'dist/index.js';
		$css_entry = 'dist/css/index.css';
	}

	wp_register_script(
		'wc-components',
		wc_admin_url( 'dist/components.js' ),
		array( 'wp-components', 'wp-data', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-keycodes' ),
		filemtime( wc_admin_dir_path( 'dist/components.js' ) ),
		true
	);

	wp_register_style(
		'wc-components',
		wc_admin_url( 'dist/css/components.css' ),
		array( 'wp-edit-blocks' ),
		filemtime( wc_admin_dir_path( 'dist/css/components.css' ) )
	);

	wp_register_script(
		WC_ADMIN_APP,
		wc_admin_url( $js_entry ),
		array( 'wc-components', 'wp-date', 'wp-html-entities', 'wp-keycodes' ),
		filemtime( wc_admin_dir_path( $js_entry ) ),
		true
	);

	wp_register_style(
		WC_ADMIN_APP,
		wc_admin_url( $css_entry ),
		array( 'wc-components' ),
		filemtime( wc_admin_dir_path( $css_entry ) )
	);

	// Set up the text domain and translations.
	$locale_data = gutenberg_get_jed_locale_data( 'wc-admin' );
	$content     = 'wp.i18n.setLocaleData( ' . json_encode( $locale_data ) . ', "wc-admin" );';
	wp_add_inline_script( 'wc-components', $content, 'before' );

	// Add Tracks script to the DOM if tracking is opted in, and Jetpack is installed/activated.
	$tracking_enabled = 'yes' === get_option( 'woocommerce_allow_tracking', 'no' );
	if ( $tracking_enabled && defined( 'JETPACK__VERSION' ) ) {
		$tracking_script  = "var wc_tracking_script = document.createElement( 'script' );\n";
		$tracking_script .= "wc_tracking_script.src = '//stats.wp.com/w.js';\n"; // TODO Version/cache buster.
		$tracking_script .= "wc_tracking_script.type = 'text/javascript';\n";
		$tracking_script .= "wc_tracking_script.async = true;\n";
		$tracking_script .= "wc_tracking_script.defer = true;\n";
		$tracking_script .= "window._tkq = window._tkq || [];\n";
		$tracking_script .= "document.head.appendChild( wc_tracking_script );\n";
		wp_add_inline_script( 'wc-components', $tracking_script, 'before' );
	}

	/**
	 * TODO: On merge, once plugin images are added to core WooCommerce, `wcAdminAssetUrl` can be retired, and
	 * `wcAssetUrl` can be used in its place throughout the codebase.
	 */

	// Settings and variables can be passed here for access in the app.
	$settings = array(
		'adminUrl'         => admin_url(),
		'wcAssetUrl'       => plugins_url( 'assets/', WC_PLUGIN_FILE ),
		'wcAdminAssetUrl'  => plugins_url( 'images/', wc_admin_dir_path( 'wc-admin.php' ) ), // Temporary for plugin. See above.
		'embedBreadcrumbs' => wc_admin_get_embed_breadcrumbs(),
		'siteLocale'       => esc_attr( get_bloginfo( 'language' ) ),
		'currency'         => wc_admin_currency_settings(),
		'date'             => array(
			'dow' => get_option( 'start_of_week', 0 ),
		),
		'orderStatuses'    => wc_get_order_statuses(),
		'stockStatuses'    => wc_get_product_stock_status_options(),
		'siteTitle'        => get_bloginfo( 'name' ),
		'trackingEnabled'  => $tracking_enabled,
	);

	wp_add_inline_script(
		'wc-components',
		'var wcSettings = ' . json_encode( $settings ) . ';',
		'before'
	);

	// Resets lodash to wp-admin's version of lodash.
	wp_add_inline_script(
		WC_ADMIN_APP,
		'_.noConflict();',
		'after'
	);

}
add_action( 'admin_enqueue_scripts', 'wc_admin_register_script' );

/**
 * Load plugin text domain for translations.
 */
function wc_admin_load_plugin_textdomain() {
	load_plugin_textdomain( 'wc-admin', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'wc_admin_load_plugin_textdomain' );
