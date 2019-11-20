<?php
/**
 * Register the scripts, styles, and includes needed for pieces of the WooCommerce Admin experience.
 * NOTE: DO NOT edit this file in WooCommerce core, this is generated from woocommerce-admin.
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin;

use \_WP_Dependency;

/**
 * Loader Class.
 */
class Loader {
	/**
	 * App entry point.
	 */
	const APP_ENTRY_POINT = 'wc-admin';

	/**
	 * Class instance.
	 *
	 * @var Loader instance
	 */
	protected static $instance = null;

	/**
	 * An array of classes to load from the includes folder.
	 *
	 * @var array
	 */
	protected static $classes = array();

	/**
	 * Get class instance.
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 * Hooks added here should be removed in `wc_admin_initialize` via the feature plugin.
	 */
	public function __construct() {
		add_action( 'init', array( __CLASS__, 'define_tables' ) );
		add_action( 'init', array( __CLASS__, 'load_features' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'inject_wc_settings_dependencies' ), 14 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_scripts' ), 15 );
		// Old settings injection.
		add_filter( 'woocommerce_components_settings', array( __CLASS__, 'add_component_settings' ) );
		// New settings injection.
		add_filter( 'woocommerce_shared_settings', array( __CLASS__, 'add_component_settings' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'add_admin_body_classes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_page_handler' ) );
		add_filter( 'admin_title', array( __CLASS__, 'update_admin_title' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_user_data' ) );
		add_action( 'in_admin_header', array( __CLASS__, 'embed_page_header' ) );
		add_filter( 'woocommerce_settings_groups', array( __CLASS__, 'add_settings_group' ) );
		add_filter( 'woocommerce_settings-wc_admin', array( __CLASS__, 'add_settings' ) );
		add_filter( 'option_woocommerce_actionable_order_statuses', array( __CLASS__, 'filter_invalid_statuses' ) );
		add_filter( 'option_woocommerce_excluded_report_order_statuses', array( __CLASS__, 'filter_invalid_statuses' ) );
		add_action( 'admin_head', array( __CLASS__, 'remove_notices' ) );
		add_action( 'admin_notices', array( __CLASS__, 'inject_before_notices' ), -9999 );
		add_action( 'admin_notices', array( __CLASS__, 'inject_after_notices' ), PHP_INT_MAX );

		// priority is 20 to run after https://github.com/woocommerce/woocommerce/blob/a55ae325306fc2179149ba9b97e66f32f84fdd9c/includes/admin/class-wc-admin-menus.php#L165.
		add_action( 'admin_head', array( __CLASS__, 'remove_app_entry_page_menu_item' ), 20 );

		/*
		* Remove the emoji script as it always defaults to replacing emojis with Twemoji images.
		* Gutenberg has also disabled emojis. More on that here -> https://github.com/WordPress/gutenberg/pull/6151
		*/
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	}

	/**
	 * Add custom tables to $wpdb object.
	 */
	public static function define_tables() {
		global $wpdb;

		// List of tables without prefixes.
		$tables = array(
			'wc_category_lookup' => 'wc_category_lookup',
		);

		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	/**
	 * Returns true if WooCommerce Admin is currently running in a development environment.
	 */
	public static function is_dev() {
		if ( self::is_feature_enabled( 'devdocs' ) && defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			return true;
		}
		return false;
	}

	/**
	 * Gets an array of enabled WooCommerce Admin features/sections.
	 *
	 * @return bool Enabled Woocommerce Admin features/sections.
	 */
	public static function get_features() {
		return apply_filters( 'woocommerce_admin_features', array() );
	}

	/**
	 * Returns if a specific wc-admin feature is enabled.
	 *
	 * @param  string $feature Feature slug.
	 * @return bool Returns true if the feature is enabled.
	 */
	public static function is_feature_enabled( $feature ) {
		$features = self::get_features();
		return in_array( $feature, $features, true );
	}

	/**
	 * Returns if the onboarding feature of WooCommerce Admin should be enabled.
	 *
	 * While we preform an a/b test of onboarding, the feature will be enabled within the plugin build, but only if the user recieved the test/opted in.
	 *
	 * @return bool Returns true if the onboarding is enabled.
	 */
	public static function is_onboarding_enabled() {
		if ( ! self::is_feature_enabled( 'onboarding' ) ) {
			return false;
		}

		$onboarding_opt_in        = 'yes' === get_option( 'wc_onboarding_opt_in', 'no' );
		$onboarding_filter_opt_in = defined( 'WOOCOMMERCE_ADMIN_ONBOARDING_ENABLED' ) && true === WOOCOMMERCE_ADMIN_ONBOARDING_ENABLED;

		if ( self::is_dev() || $onboarding_filter_opt_in || $onboarding_opt_in ) {
			return true;
		}

		return false;
	}

	/**
	 * Gets the URL to an asset file.
	 *
	 * @param  string $file name.
	 * @return string URL to asset.
	 */
	public static function get_url( $file ) {
		return plugins_url( self::get_path( $file ) . $file, WC_ADMIN_PLUGIN_FILE );
	}

	/**
	 * Gets the file modified time as a cache buster if we're in dev mode, or the plugin version otherwise.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	public static function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$file = trim( $file, '/' );
			return filemtime( WC_ADMIN_ABSPATH . self::get_path( $file ) );
		}
		return WC_ADMIN_VERSION_NUMBER;
	}

	/**
	 * Gets the path for the asset depending on file type.
	 *
	 * @param  string $file name.
	 * @return string Folder path of asset.
	 */
	private static function get_path( $file ) {
		return '.css' === substr( $file, -4 ) ? WC_ADMIN_DIST_CSS_FOLDER : WC_ADMIN_DIST_JS_FOLDER;
	}

	/**
	 * Class loader for enabled WooCommerce Admin features/sections.
	 */
	public static function load_features() {
		$features = self::get_features();
		foreach ( $features as $feature ) {
			$feature = str_replace( '-', '', ucwords( strtolower( $feature ), '-' ) );
			$feature = 'Automattic\\WooCommerce\\Admin\\Features\\' . $feature;

			if ( class_exists( $feature ) ) {
				new $feature();
			}
		}
	}

	/**
	 * Registers a basic page handler for the app entry point.
	 *
	 * @todo The entry point for the embed needs moved to this class as well.
	 */
	public static function register_page_handler() {
		$analytics_cap = apply_filters( 'woocommerce_admin_analytics_menu_capability', 'view_woocommerce_reports' );
		wc_admin_register_page(
			array(
				'id'         => 'woocommerce-dashboard', // Expected to be overridden if dashboard is enabled.
				'parent'     => 'woocommerce',
				'title'      => null,
				'path'       => self::APP_ENTRY_POINT,
				'capability' => $analytics_cap,
			)
		);

		// Connect existing WooCommerce pages.
		require_once WC_ADMIN_ABSPATH . 'includes/connect-existing-pages.php';
	}

	/**
	 * Remove the menu item for the app entry point page.
	 */
	public static function remove_app_entry_page_menu_item() {
		global $submenu;
		// User does not have capabilites to see the submenu.
		if ( ! current_user_can( 'manage_woocommerce' ) || empty( $submenu['woocommerce'] ) ) {
			return;
		}

		$wc_admin_key = null;
		foreach ( $submenu['woocommerce'] as $submenu_key => $submenu_item ) {
			// Our app entry page menu item has no title.
			if ( is_null( $submenu_item[0] ) && self::APP_ENTRY_POINT === $submenu_item[2] ) {
				$wc_admin_key = $submenu_key;
				break;
			}
		}

		if ( ! $wc_admin_key ) {
			return;
		}

		unset( $submenu['woocommerce'][ $wc_admin_key ] );
	}

	/**
	 * Registers all the neccessary scripts and styles to show the admin experience.
	 */
	public static function register_scripts() {
		if ( ! function_exists( 'wp_set_script_translations' ) ) {
			return;
		}

		wp_register_script(
			'wc-csv',
			self::get_url( 'csv-export/index.js' ),
			array(),
			self::get_file_version( 'csv-export/index.js' ),
			true
		);

		wp_register_script(
			'wc-currency',
			self::get_url( 'currency/index.js' ),
			array( 'wc-number' ),
			self::get_file_version( 'currency/index.js' ),
			true
		);

		wp_set_script_translations( 'wc-currency', 'woocommerce-admin' );

		wp_register_script(
			'wc-navigation',
			self::get_url( 'navigation/index.js' ),
			array(),
			self::get_file_version( 'navigation/index.js' ),
			true
		);

		wp_register_script(
			'wc-number',
			self::get_url( 'number/index.js' ),
			array(),
			self::get_file_version( 'number/index.js' ),
			true
		);

		wp_register_script(
			'wc-date',
			self::get_url( 'date/index.js' ),
			array( 'wp-date', 'wp-i18n' ),
			self::get_file_version( 'date/index.js' ),
			true
		);

		wp_set_script_translations( 'wc-date', 'woocommerce-admin' );

		wp_register_script(
			'wc-components',
			self::get_url( 'components/index.js' ),
			array(
				'wp-api-fetch',
				'wp-data',
				'wp-element',
				'wp-hooks',
				'wp-html-entities',
				'wp-i18n',
				'wp-keycodes',
				'wc-csv',
				'wc-currency',
				'wc-date',
				'wc-navigation',
				'wc-number',
			),
			self::get_file_version( 'components/index.js' ),
			true
		);

		wp_set_script_translations( 'wc-components', 'woocommerce-admin' );

		wp_register_style(
			'wc-components',
			self::get_url( 'components/style.css' ),
			array(),
			self::get_file_version( 'components/style.css' )
		);
		wp_style_add_data( 'wc-components', 'rtl', 'replace' );

		wp_register_style(
			'wc-components-ie',
			self::get_url( 'components/ie.css' ),
			array(),
			self::get_file_version( 'components/ie.css' )
		);
		wp_style_add_data( 'wc-components-ie', 'rtl', 'replace' );

		wp_register_script(
			WC_ADMIN_APP,
			self::get_url( 'app/index.js' ),
			array( 'wc-components', 'wc-navigation', 'wp-date', 'wp-html-entities', 'wp-keycodes', 'wp-i18n' ),
			self::get_file_version( 'app/index.js' ),
			true
		);

		wp_set_script_translations( WC_ADMIN_APP, 'woocommerce-admin' );

		wp_register_style(
			WC_ADMIN_APP,
			self::get_url( 'app/style.css' ),
			array( 'wc-components' ),
			self::get_file_version( 'app/style.css' )
		);
		wp_style_add_data( WC_ADMIN_APP, 'rtl', 'replace' );

		wp_register_style(
			'wc-admin-ie',
			self::get_url( 'ie/style.css' ),
			array( WC_ADMIN_APP ),
			self::get_file_version( 'ie/style.css' )
		);
		wp_style_add_data( 'wc-admin-ie', 'rtl', 'replace' );

		wp_register_style(
			'wc-material-icons',
			'https://fonts.googleapis.com/icon?family=Material+Icons+Outlined',
			array(),
			self::get_file_version( 'https://fonts.googleapis.com/icon?family=Material+Icons' )
		);
	}

	/**
	 * Loads the required scripts on the correct pages.
	 */
	public static function load_scripts() {
		if ( ! self::is_admin_page() && ! self::is_embed_page() ) {
			return;
		}

		wp_enqueue_script( WC_ADMIN_APP );
		wp_enqueue_style( WC_ADMIN_APP );
		wp_enqueue_style( 'wc-material-icons' );

		// Use server-side detection to prevent unneccessary stylesheet loading in other browsers.
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : ''; // phpcs:ignore sanitization ok.
		preg_match( '/MSIE (.*?);/', $user_agent, $matches );
		if ( count( $matches ) < 2 ) {
			preg_match( '/Trident\/\d{1,2}.\d{1,2}; rv:([0-9]*)/', $user_agent, $matches );
		}
		if ( count( $matches ) > 1 ) {
			wp_enqueue_style( 'wc-components-ie' );
			wp_enqueue_style( 'wc-admin-ie' );
		}

	}

	/**
	 * Returns true if we are on a JS powered admin page.
	 */
	public static function is_admin_page() {
		return wc_admin_is_registered_page();
	}

	/**
	 *  Returns true if we are on a "classic" (non JS app) powered admin page.
	 *
	 * TODO: See usage in `admin.php`. This needs refactored and implemented properly in core.
	 */
	public static function is_embed_page() {
		return wc_admin_is_connected_page();
	}

	/**
	 * Returns breadcrumbs for the current page.
	 */
	private static function get_embed_breadcrumbs() {
		return wc_admin_get_breadcrumbs();
	}

	/**
	 * Outputs breadcrumbs via PHP for the initial load of an embedded page.
	 *
	 * @param array $section Section to create breadcrumb from.
	 */
	private static function output_breadcrumbs( $section ) {
		?>
		<span>
		<?php if ( is_array( $section ) ) : ?>
			<a href="<?php echo esc_url( admin_url( $section[0] ) ); ?>"><?php echo esc_html( $section[1] ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $section ); ?>
		<?php endif; ?>
		</span>
		<?php
	}

	/**
	 * Set up a div for the header embed to render into.
	 * The initial contents here are meant as a place loader for when the PHP page initialy loads.
	 */
	public static function embed_page_header() {
		if ( ! self::is_embed_page() ) {
			return;
		}

		$sections = self::get_embed_breadcrumbs();
		$sections = is_array( $sections ) ? $sections : array( $sections );
		?>
		<div id="woocommerce-embedded-root" class="is-embed-loading">
			<div class="woocommerce-layout">
				<div class="woocommerce-layout__header is-embed-loading">
					<h1 class="woocommerce-layout__header-breadcrumbs">
					<span>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-admin' ) ); ?>"><?php esc_html_e( 'WooCommerce', 'woocommerce-admin' ); ?></a>
					</span>
						<?php foreach ( $sections as $section ) : ?>
							<?php self::output_breadcrumbs( $section ); ?>
						<?php endforeach; ?>
					</h1>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Adds body classes to the main wp-admin wrapper, allowing us to better target elements in specific scenarios.
	 *
	 * @param string $admin_body_class Body class to add.
	 */
	public static function add_admin_body_classes( $admin_body_class = '' ) {
		if ( ! self::is_admin_page() && ! self::is_embed_page() ) {
			return $admin_body_class;
		}

		$classes   = explode( ' ', trim( $admin_body_class ) );
		$classes[] = 'woocommerce-page';
		if ( self::is_embed_page() ) {
			$classes[] = 'woocommerce-embed-page';
		}

		// Some routes or features like onboarding hide the wp-admin navigation and masterbar. Setting `woocommerce_admin_is_loading` to true allows us
		// to premeptively hide these elements while the JS app loads. This class is removed when `<Layout />` is rendered.
		if ( self::is_admin_page() && apply_filters( 'woocommerce_admin_is_loading', false ) ) {
			$classes[] = 'woocommerce-admin-is-loading';
		}

		$features = self::get_features();
		foreach ( $features as $feature_key ) {
			$classes[] = sanitize_html_class( 'woocommerce-feature-enabled-' . $feature_key );
		}

		$admin_body_class = implode( ' ', array_unique( $classes ) );
		return " $admin_body_class ";
	}


	/**
	 * Removes notices that should not be displayed on WC Admin pages.
	 */
	public static function remove_notices() {
		if ( ! self::is_admin_page() && ! self::is_embed_page() ) {
			return;
		}

		// Hello Dolly.
		if ( function_exists( 'hello_dolly' ) ) {
			remove_action( 'admin_notices', 'hello_dolly' );
		}
	}

	/**
	 * Runs before admin notices action and hides them.
	 */
	public static function inject_before_notices() {
		if ( ( ! self::is_admin_page() && ! self::is_embed_page() ) ) {
			return;
		}
		echo '<div class="woocommerce-layout__notice-list-hide" id="wp__notice-list">';
		echo '<div class="wp-header-end" id="woocommerce-layout__notice-catcher"></div>'; // https://github.com/WordPress/WordPress/blob/f6a37e7d39e2534d05b9e542045174498edfe536/wp-admin/js/common.js#L737.
	}

	/**
	 * Runs after admin notices and closes div.
	 */
	public static function inject_after_notices() {
		if ( ( ! self::is_admin_page() && ! self::is_embed_page() ) ) {
			return;
		}
		echo '</div>';
	}

	/**
	 * Edits Admin title based on section of wc-admin.
	 *
	 * @param string $admin_title Modifies admin title.
	 * @todo Can we do some URL rewriting so we can figure out which page they are on server side?
	 */
	public static function update_admin_title( $admin_title ) {
		if (
			! did_action( 'current_screen' ) ||
			(
				! self::is_admin_page() &&
				! self::is_embed_page()
			)
		) {
			return $admin_title;
		}

		$sections = self::get_embed_breadcrumbs();
		$pieces   = array();

		foreach ( $sections as $section ) {
			$pieces[] = is_array( $section ) ? $section[1] : $section;
		}

		$pieces = array_reverse( $pieces );
		$title  = implode( ' &lsaquo; ', $pieces );

		/* translators: %1$s: updated title, %2$s: blog info name */
		return sprintf( __( '%1$s &lsaquo; %2$s', 'woocommerce-admin' ), $title, get_bloginfo( 'name' ) );
	}

	/**
	 * Set up a div for the app to render into.
	 */
	public static function page_wrapper() {
		?>
		<div class="wrap">
			<div id="root"></div>
		</div>
		<?php
	}

	/**
	 * Hooks extra neccessary data into the component settings array already set in WooCommerce core.
	 *
	 * @param array $settings Array of component settings.
	 * @return array Array of component settings.
	 */
	public static function add_component_settings( $settings ) {
		if ( ! is_admin() ) {
			return $settings;
		}

		if ( ! function_exists( 'wc_blocks_container' ) ) {
			global $wp_locale;
			// inject data not available via older versions of wc_blocks/woo.
			$settings['orderStatuses'] = self::get_order_statuses( wc_get_order_statuses() );
			$settings['currency']      = self::get_currency_settings();
			$settings['locale']        = [
				'siteLocale'    => isset( $settings['siteLocale'] )
					? $settings['siteLocale']
					: get_locale(),
				'userLocale'    => isset( $settings['l10n']['userLocale'] )
					? $settings['l10n']['userLocale']
					: get_user_locale(),
				'weekdaysShort' => isset( $settings['l10n']['weekdaysShort'] )
					? $settings['l10n']['weekdaysShort']
					: array_values( $wp_locale->weekday_abbrev ),
			];
		}

		$preload_data_endpoints = apply_filters( 'woocommerce_component_settings_preload_endpoints', array( '/wc/v3' ) );
		if ( ! empty( $preload_data_endpoints ) ) {
			$preload_data = array_reduce(
				array_values( $preload_data_endpoints ),
				'rest_preload_api_request'
			);
		}

		$preload_options = apply_filters( 'woocommerce_admin_preload_options', array() );
		if ( ! empty( $preload_options ) ) {
			foreach ( $preload_options as $option ) {
				$settings['preloadOptions'][ $option ] = get_option( $option );
			}
		}

		$preload_settings = apply_filters( 'woocommerce_admin_preload_settings', array() );
		if ( ! empty( $preload_settings ) ) {
			$setting_options = new \WC_REST_Setting_Options_V2_Controller();
			foreach ( $preload_settings as $group ) {
				$group_settings   = $setting_options->get_group_settings( $group );
				$preload_settings = [];
				foreach ( $group_settings as $option ) {
					$preload_settings[ $option['id'] ] = $option['value'];
				}
				$settings['preloadSettings'][ $group ] = $preload_settings;
			}
		}

		$current_user_data = array();
		foreach ( self::get_user_data_fields() as $user_field ) {
			$current_user_data[ $user_field ] = json_decode( get_user_meta( get_current_user_id(), 'wc_admin_' . $user_field, true ) );
		}
		$settings['currentUserData']      = $current_user_data;
		$settings['reviewsEnabled']       = get_option( 'woocommerce_enable_reviews' );
		$settings['manageStock']          = get_option( 'woocommerce_manage_stock' );
		$settings['commentModeration']    = get_option( 'comment_moderation' );
		$settings['notifyLowStockAmount'] = get_option( 'woocommerce_notify_low_stock_amount' );
		// @todo On merge, once plugin images are added to core WooCommerce, `wcAdminAssetUrl` can be retired,
		// and `wcAssetUrl` can be used in its place throughout the codebase.
		$settings['wcAdminAssetUrl']   = plugins_url( 'images/', dirname( __DIR__ ) . '/woocommerce-admin.php' );
		$settings['wcVersion']         = WC_VERSION;
		$settings['siteUrl']           = site_url();
		$settings['onboardingEnabled'] = self::is_onboarding_enabled();

		if ( ! empty( $preload_data_endpoints ) ) {
			$settings['dataEndpoints'] = isset( $settings['dataEndpoints'] )
				? $settings['dataEndpoints']
				: [];
			foreach ( $preload_data_endpoints as $key => $endpoint ) {
				// Handle error case: rest_do_request() doesn't guarantee success.
				if ( empty( $preload_data[ $endpoint ] ) ) {
					$settings['dataEndpoints'][ $key ] = array();
				} else {
					$settings['dataEndpoints'][ $key ] = $preload_data[ $endpoint ]['body'];
				}
			}
		}
		$settings = self::get_custom_settings( $settings );
		if ( self::is_embed_page() ) {
			$settings['embedBreadcrumbs'] = self::get_embed_breadcrumbs();
		}
		return $settings;
	}

	/**
	 * Format order statuses by removing a leading 'wc-' if present.
	 *
	 * @param array $statuses Order statuses.
	 * @return array formatted statuses.
	 */
	public static function get_order_statuses( $statuses ) {
		$formatted_statuses = array();
		foreach ( $statuses as $key => $value ) {
			$formatted_key                        = preg_replace( '/^wc-/', '', $key );
			$formatted_statuses[ $formatted_key ] = $value;
		}
		return $formatted_statuses;
	}

	/**
	 * Register the admin settings for use in the WC REST API
	 *
	 * @param array $groups Array of setting groups.
	 * @return array
	 */
	public static function add_settings_group( $groups ) {
		$groups[] = array(
			'id'          => 'wc_admin',
			'label'       => __( 'WooCommerce Admin', 'woocommerce-admin' ),
			'description' => __( 'Settings for WooCommerce admin reporting.', 'woocommerce-admin' ),
		);
		return $groups;
	}

	/**
	 * Add WC Admin specific settings
	 *
	 * @param array $settings Array of settings in wc admin group.
	 * @return array
	 */
	public static function add_settings( $settings ) {
		$statuses   = self::get_order_statuses( wc_get_order_statuses() );
		$settings[] = array(
			'id'          => 'woocommerce_excluded_report_order_statuses',
			'option_key'  => 'woocommerce_excluded_report_order_statuses',
			'label'       => __( 'Excluded report order statuses', 'woocommerce-admin' ),
			'description' => __( 'Statuses that should not be included when calculating report totals.', 'woocommerce-admin' ),
			'default'     => array( 'pending', 'cancelled', 'failed' ),
			'type'        => 'multiselect',
			'options'     => $statuses,
		);
		$settings[] = array(
			'id'          => 'woocommerce_actionable_order_statuses',
			'option_key'  => 'woocommerce_actionable_order_statuses',
			'label'       => __( 'Actionable order statuses', 'woocommerce-admin' ),
			'description' => __( 'Statuses that require extra action on behalf of the store admin.', 'woocommerce-admin' ),
			'default'     => array( 'processing', 'on-hold' ),
			'type'        => 'multiselect',
			'options'     => $statuses,
		);
		$settings[] = array(
			'id'          => 'woocommerce_default_date_range',
			'option_key'  => 'woocommerce_default_date_range',
			'label'       => __( 'Default Date Range', 'woocommerce-admin' ),
			'description' => __( 'Default Date Range', 'woocommerce-admin' ),
			'default'     => 'period=month&compare=previous_year',
			'type'        => 'text',
		);
		return $settings;
	}

	/**
	 * Filter invalid statuses from saved settings to avoid removed statuses throwing errors.
	 *
	 * @param array|null $value Saved order statuses.
	 * @return array|null
	 */
	public static function filter_invalid_statuses( $value ) {
		if ( is_array( $value ) ) {
			$valid_statuses = array_keys( self::get_order_statuses( wc_get_order_statuses() ) );
			$value          = array_intersect( $value, $valid_statuses );
		}

		return $value;
	}

	/**
	 * Gets custom settings used for WC Admin.
	 *
	 * @param array $settings Array of settings to merge into.
	 * @return array
	 */
	public static function get_custom_settings( $settings ) {
		$wc_rest_settings_options_controller = new \WC_REST_Setting_Options_Controller();
		$wc_admin_group_settings             = $wc_rest_settings_options_controller->get_group_settings( 'wc_admin' );
		$settings['wcAdminSettings']         = array();

		foreach ( $wc_admin_group_settings as $setting ) {
			if ( ! empty( $setting['id'] ) ) {
				$settings['wcAdminSettings'][ $setting['id'] ] = $setting['value'];
			}
		}
		return $settings;
	}

	/**
	 * Return an object defining the currecy options for the site's current currency
	 *
	 * @return  array  Settings for the current currency {
	 *     Array of settings.
	 *
	 *     @type string $code       Currency code.
	 *     @type string $precision  Number of decimals.
	 *     @type string $symbol     Symbol for currency.
	 * }
	 */
	public static function get_currency_settings() {
		$code = get_woocommerce_currency();

		return apply_filters(
			'wc_currency_settings',
			array(
				'code'              => $code,
				'precision'         => wc_get_price_decimals(),
				'symbol'            => html_entity_decode( get_woocommerce_currency_symbol( $code ) ),
				'symbolPosition'    => get_option( 'woocommerce_currency_pos' ),
				'decimalSeparator'  => wc_get_price_decimal_separator(),
				'thousandSeparator' => wc_get_price_thousand_separator(),
				'priceFormat'       => html_entity_decode( get_woocommerce_price_format() ),
			)
		);
	}

	/**
	 * Registers WooCommerce specific user data to the WordPress user API.
	 */
	public static function register_user_data() {
		register_rest_field(
			'user',
			'woocommerce_meta',
			array(
				'get_callback'    => array( __CLASS__, 'get_user_data_values' ),
				'update_callback' => array( __CLASS__, 'update_user_data_values' ),
				'schema'          => null,
			)
		);
	}

	/**
	 * For all the registered user data fields (  Loader::get_user_data_fields ), fetch the data
	 * for returning via the REST API.
	 *
	 * @param WP_User $user Current user.
	 */
	public static function get_user_data_values( $user ) {
		$values = array();
		foreach ( self::get_user_data_fields() as $field ) {
			$values[ $field ] = get_user_meta( $user['id'], 'wc_admin_' . $field, true );
		}
		return $values;
	}

	/**
	 * For all the registered user data fields ( Loader::get_user_data_fields ), update the data
	 * for the REST API.
	 *
	 * @param array   $values   The new values for the meta.
	 * @param WP_User $user     The current user.
	 * @param string  $field_id The field id for the user meta.
	 */
	public static function update_user_data_values( $values, $user, $field_id ) {
		if ( empty( $values ) || ! is_array( $values ) || 'woocommerce_meta' !== $field_id ) {
			return;
		}
		$fields  = self::get_user_data_fields();
		$updates = array();
		foreach ( $values as $field => $value ) {
			if ( in_array( $field, $fields, true ) ) {
				$updates[ $field ] = $value;
				update_user_meta( $user->ID, 'wc_admin_' . $field, $value );
			}
		}
		return $updates;
	}

	/**
	 * We store some WooCommerce specific user meta attached to users endpoint,
	 * so that we can track certain preferences or values such as the inbox activity panel last open time.
	 * Additional fields can be added in the function below, and then used via wc-admin's currentUser data.
	 *
	 * @return array Fields to expose over the WP user endpoint.
	 */
	public static function get_user_data_fields() {
		return apply_filters( 'wc_admin_get_user_data_fields', array() );
	}

	/**
	 * Injects wp-shared-settings as a dependency if it's present.
	 */
	public static function inject_wc_settings_dependencies() {
		if ( wp_script_is( 'wc-settings', 'registered' ) ) {
			$handles_for_injection = [
				'wc-csv',
				'wc-currency',
				'wc-navigation',
				'wc-number',
				'wc-date',
				'wc-components',
			];
			foreach ( $handles_for_injection as $handle ) {
				$script = wp_scripts()->query( $handle, 'registered' );
				if ( $script instanceof _WP_Dependency ) {
					$script->deps[] = 'wc-settings';
				}
			}
		}
	}
}
