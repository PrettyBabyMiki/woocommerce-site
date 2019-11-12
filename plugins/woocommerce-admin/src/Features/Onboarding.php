<?php
/**
 * WooCommerce Onboarding
 * NOTE: DO NOT edit this file in WooCommerce core, this is generated from woocommerce-admin.
 *
 * @package Woocommerce Admin
 */

namespace Automattic\WooCommerce\Admin\Features;

use \Automattic\WooCommerce\Admin\Loader;

/**
 * Contains backend logic for the onboarding profile and checklist feature.
 */
class Onboarding {
	/**
	 * Class instance.
	 *
	 * @var Onboarding instance
	 */
	protected static $instance = null;

	/**
	 * Name of themes transient.
	 *
	 * @var string
	 */
	const THEMES_TRANSIENT = 'wc_onboarding_themes';

	/**
	 * Name of product data transient.
	 *
	 * @var string
	 */
	const PRODUCT_DATA_TRANSIENT = 'wc_onboarding_product_data';

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
	 * Hook into WooCommerce.
	 */
	public function __construct() {

		if ( ! Loader::is_onboarding_enabled() ) {
			return;
		}

		// Include WC Admin Onboarding classes.
		if ( self::should_show_tasks() ) {
			OnboardingTasks::get_instance();
		}

		if ( ! is_admin() ) {
			return;
		}

		// Old settings injection.
		// Run after Automattic\WooCommerce\Admin\Loader.
		add_filter( 'woocommerce_components_settings', array( $this, 'component_settings' ), 20 );
		// New settings injection.
		add_filter( 'woocommerce_shared_settings', array( $this, 'component_settings' ), 20 );
		add_filter( 'woocommerce_component_settings_preload_endpoints', array( $this, 'add_preload_endpoints' ) );
		add_filter( 'woocommerce_admin_preload_options', array( $this, 'preload_options' ) );
		add_filter( 'woocommerce_admin_preload_settings', array( $this, 'preload_settings' ) );
		add_action( 'woocommerce_theme_installed', array( $this, 'delete_themes_transient' ) );
		add_action( 'after_switch_theme', array( $this, 'delete_themes_transient' ) );
		add_action( 'current_screen', array( $this, 'finish_paypal_connect' ) );
		add_action( 'current_screen', array( $this, 'finish_square_connect' ) );
		add_action( 'current_screen', array( $this, 'update_help_tab' ), 60 );
		add_action( 'current_screen', array( $this, 'reset_profiler' ) );
		add_action( 'current_screen', array( $this, 'reset_task_list' ) );
		add_action( 'current_screen', array( $this, 'calypso_tests' ) );
		add_filter( 'woocommerce_admin_is_loading', array( $this, 'is_loading' ) );
		add_filter( 'woocommerce_rest_prepare_themes', array( $this, 'add_uploaded_theme_data' ) );
	}

	/**
	 * Returns true if the profiler should be displayed (not completed).
	 *
	 * @return bool
	 */
	public static function should_show_profiler() {
		$onboarding_data = get_option( 'wc_onboarding_profile', array() );

		$is_completed = isset( $onboarding_data['completed'] ) && true === $onboarding_data['completed'];

		// @todo When merging to WooCommerce Core, we should set the `completed` flag to true during the upgrade progress.
		// https://github.com/woocommerce/woocommerce-admin/pull/2300#discussion_r287237498.
		return ! $is_completed;
	}

	/**
	 * Returns true if the task list should be displayed (not completed or hidden off the dashboard).
	 *
	 * @return bool
	 */
	public static function should_show_tasks() {
		return 'no' === get_option( 'woocommerce_task_list_hidden', 'no' );
	}

	/**
	 * Get a list of allowed industries for the onboarding wizard.
	 *
	 * @return array
	 */
	public static function get_allowed_industries() {
		return apply_filters(
			'woocommerce_admin_onboarding_industries',
			array(
				'fashion-apparel-accessories' => __( 'Fashion, apparel, and accessories', 'woocommerce-admin' ),
				'health-beauty'               => __( 'Health and beauty', 'woocommerce-admin' ),
				'art-music-photography'       => __( 'Art, music, and photography', 'woocommerce-admin' ),
				'electronics-computers'       => __( 'Electronics and computers', 'woocommerce-admin' ),
				'food-drink'                  => __( 'Food and drink', 'woocommerce-admin' ),
				'home-furniture-garden'       => __( 'Home, furniture, and garden', 'woocommerce-admin' ),
				'other'                       => __( 'Other', 'woocommerce-admin' ),
			)
		);
	}

	/**
	 * Get a list of allowed product types for the onboarding wizard.
	 *
	 * @return array
	 */
	public static function get_allowed_product_types() {
		$product_types = self::append_product_data(
			array(
				'physical'      => array(
					'label'       => __( 'Physical products', 'woocommerce-admin' ),
					'description' => __( 'Products you ship to customers.', 'woocommerce-admin' ),
				),
				'downloads'     => array(
					'label'       => __( 'Downloads', 'woocommerce-admin' ),
					'description' => __( 'Virtual products that customers download.', 'woocommerce-admin' ),
				),
				'subscriptions' => array(
					'label'   => __( 'Subscriptions', 'woocommerce-admin' ),
					'product' => 27147,
				),
				'memberships'   => array(
					'label'   => __( 'Memberships', 'woocommerce-admin' ),
					'product' => 958589,
				),
				'composite'     => array(
					'label'   => __( 'Composite Products', 'woocommerce-admin' ),
					'product' => 216836,
				),
				'bookings'      => array(
					'label'   => __( 'Bookings', 'woocommerce-admin' ),
					'product' => 390890,
				),
			)
		);

		return apply_filters( 'woocommerce_admin_onboarding_product_types', $product_types );
	}

	/**
	 * Get a list of themes for the onboarding wizard.
	 *
	 * @return array
	 */
	public static function get_themes() {
		$themes = get_transient( self::THEMES_TRANSIENT );
		if ( false === $themes ) {
			$theme_data = wp_remote_get( 'https://woocommerce.com/wp-json/wccom-extensions/1.0/search?category=themes' );
			$themes     = array();

			if ( ! is_wp_error( $theme_data ) ) {
				$theme_data = json_decode( $theme_data['body'] );
				usort(
					$theme_data->products,
					function ( $product_1, $product_2 ) {
						if ( 'Storefront' === $product_1->slug ) {
							return -1;
						}
						return $product_1->id < $product_2->id ? 1 : -1;
					}
				);

				foreach ( $theme_data->products as $theme ) {
					$slug                                       = sanitize_title( $theme->slug );
					$themes[ $slug ]                            = (array) $theme;
					$themes[ $slug ]['is_installed']            = false;
					$themes[ $slug ]['has_woocommerce_support'] = true;
				}
			}

			$installed_themes = wp_get_themes();
			$active_theme     = get_option( 'stylesheet' );

			foreach ( $installed_themes as $slug => $theme ) {
				$theme_data = self::get_theme_data( $theme );

				if ( ! $theme_data['has_woocommerce_support'] && $active_theme !== $slug ) {
					continue;
				}

				$installed_themes = wp_get_themes();
				$themes[ $slug ]  = $theme_data;
			}

			$themes = array( $active_theme => $themes[ $active_theme ] ) + $themes;

			set_transient( self::THEMES_TRANSIENT, $themes, DAY_IN_SECONDS );
		}

		$themes = apply_filters( 'woocommerce_admin_onboarding_themes', $themes );
		return array_values( $themes );
	}

	/**
	 * Get theme data used in onboarding theme browser.
	 *
	 * @param WP_Theme $theme Theme to gather data from.
	 * @return array
	 */
	public static function get_theme_data( $theme ) {
		return array(
			'slug'                    => sanitize_title( $theme->stylesheet ),
			'title'                   => $theme->get( 'Name' ),
			'price'                   => '0.00',
			'is_installed'            => true,
			'image'                   => $theme->get_screenshot(),
			'has_woocommerce_support' => self::has_woocommerce_support( $theme ),
		);
	}

	/**
	 * Add theme data to response from themes controller.
	 *
	 * @param WP_REST_Response $response Rest response.
	 * @return WP_REST_Response
	 */
	public static function add_uploaded_theme_data( $response ) {
		if ( ! isset( $response->data['theme'] ) ) {
			return $response;
		}

		$theme                        = wp_get_theme( $response->data['theme'] );
		$response->data['theme_data'] = self::get_theme_data( $theme );

		return $response;
	}

	/**
	 * Check if theme has declared support for WooCommerce
	 *
	 * @param WP_Theme $theme Theme to check.
	 * @return bool
	 */
	public static function has_woocommerce_support( $theme ) {
		$themes = array( $theme );
		if ( $theme->get( 'Template' ) ) {
			$parent_theme = wp_get_theme( $theme->get( 'Template' ) );
			$themes[]     = $parent_theme;
		}

		foreach ( $themes as $theme ) {
			$directory = new \RecursiveDirectoryIterator( $theme->theme_root . '/' . $theme->stylesheet );
			$iterator  = new \RecursiveIteratorIterator( $directory );
			$files     = new \RegexIterator( $iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH );

			foreach ( $files as $file ) {
				$content = file_get_contents( $file[0] );
				if ( preg_match( '/add_theme_support\(([^(]*)(\'|\")woocommerce(\'|\")([^(]*)/si', $content, $matches ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Append dynamic product data from API.
	 *
	 * @param array $product_types Array of product types.
	 * @return array
	 */
	public static function append_product_data( $product_types ) {
		$woocommerce_products = get_transient( self::PRODUCT_DATA_TRANSIENT );
		if ( false === $woocommerce_products ) {
			$woocommerce_products = wp_remote_get( 'https://woocommerce.com/wp-json/wccom-extensions/1.0/search?category=product-type' );
			if ( is_wp_error( $woocommerce_products ) ) {
				return $product_types;
			}

			set_transient( self::PRODUCT_DATA_TRANSIENT, $woocommerce_products, DAY_IN_SECONDS );
		}

		$product_data = json_decode( $woocommerce_products['body'] );
		$products     = array();

		// Map product data by ID.
		foreach ( $product_data->products as $product_datum ) {
			$products[ $product_datum->id ] = $product_datum;
		}

		// Loop over product types and append data.
		foreach ( $product_types as $key => $product_type ) {
			if ( isset( $product_type['product'] ) ) {
				/* translators: Amount of product per year (e.g. Bookings - $240.00 per year) */
				$product_types[ $key ]['label']      .= sprintf( __( ' — %s per year', 'woocommerce-admin' ), html_entity_decode( $products[ $product_type['product'] ]->price ) );
				$product_types[ $key ]['description'] = $products[ $product_type['product'] ]->excerpt;
				$product_types[ $key ]['more_url']    = $products[ $product_type['product'] ]->link;
			}
		}

		return $product_types;
	}

	/**
	 * Delete the stored themes transient.
	 */
	public static function delete_themes_transient() {
		delete_transient( self::THEMES_TRANSIENT );
	}

	/**
	 * Add profiler items to component settings.
	 *
	 * @param array $settings Component settings.
	 */
	public function component_settings( $settings ) {
		$profile = get_option( 'wc_onboarding_profile', array() );

		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-options.php';
		$wccom_auth                 = \WC_Helper_Options::get( 'auth' );
		$profile['wccom_connected'] = empty( $wccom_auth['access_token'] ) ? false : true;

		$settings['onboarding'] = array(
			'industries' => self::get_allowed_industries(),
			'profile'    => $profile,
		);

		// Only fetch if the onboarding wizard is incomplete.
		if ( self::should_show_profiler() ) {
			$settings['onboarding']['productTypes'] = self::get_allowed_product_types();
			$settings['onboarding']['themes']       = self::get_themes();
			$settings['onboarding']['activeTheme']  = get_option( 'stylesheet' );
		}

		// Only fetch if the onboarding wizard OR the task list is incomplete.
		if ( self::should_show_profiler() || self::should_show_tasks() ) {
			$settings['onboarding']['activePlugins']            = self::get_active_plugins();
			$settings['onboarding']['stripeSupportedCountries'] = self::get_stripe_supported_countries();
			$settings['onboarding']['euCountries']              = WC()->countries->get_european_union_countries();
			$settings['onboarding']['connectNonce']             = wp_create_nonce( 'connect' );
			$current_user                                       = wp_get_current_user();
			$settings['onboarding']['userEmail']                = esc_html( $current_user->user_email );
		}

		return $settings;
	}

	/**
	 * Preload options to prime state of the application.
	 *
	 * @param array $options Array of options to preload.
	 * @return array
	 */
	public function preload_options( $options ) {
		$options[] = 'woocommerce_task_list_hidden';

		if ( ! self::should_show_tasks() && ! self::should_show_profiler() ) {
			return $options;
		}

		$options[] = 'wc_connect_options';
		$options[] = 'woocommerce_task_list_welcome_modal_dismissed';
		$options[] = 'woocommerce_task_list_prompt_shown';
		$options[] = 'woocommerce_onboarding_payments';
		$options[] = 'woocommerce_allow_tracking';
		$options[] = 'woocommerce_stripe_settings';
		$options[] = 'woocommerce_default_country';

		return $options;
	}

	/**
	 * Preload WC setting options to prime state of the application.
	 *
	 * @param array $options Array of options to preload.
	 * @return array
	 */
	public function preload_settings( $options ) {
		if ( ! self::should_show_profiler() ) {
			return $options;
		}

		$options[] = 'general';

		return $options;
	}

	/**
	 * Preload data from API endpoints.
	 *
	 * @param array $endpoints Array of preloaded endpoints.
	 * @return array
	 */
	public function add_preload_endpoints( $endpoints ) {
		if ( ! class_exists( 'Jetpack' ) ) {
			return $endpoints;
		}
		$endpoints['jetpackStatus'] = '/jetpack/v4/connection';
		return $endpoints;
	}

	/**
	 * Returns a list of Stripe supported countries. This method can be removed once merged to core.
	 *
	 * @return array
	 */
	private static function get_stripe_supported_countries() {
		// https://stripe.com/global.
		return array(
			'AU',
			'AT',
			'BE',
			'CA',
			'DK',
			'EE',
			'FI',
			'FR',
			'DE',
			'GR',
			'HK',
			'IE',
			'IT',
			'JP',
			'LV',
			'LT',
			'LU',
			'MY',
			'NL',
			'NZ',
			'NO',
			'PL',
			'PT',
			'SG',
			'SK',
			'SI',
			'ES',
			'SE',
			'CH',
			'GB',
			'US',
		);
	}

	/**
	 * Gets an array of plugins that can be installed & activated via the onboarding wizard.
	 *
	 * @todo Handle edgecase of where installed plugins may have versioned folder names (i.e. `jetpack-master/jetpack.php`).
	 */
	public static function get_allowed_plugins() {
		return apply_filters(
			'woocommerce_onboarding_plugins_whitelist',
			array(
				'facebook-for-woocommerce'            => 'facebook-for-woocommerce/facebook-for-woocommerce.php',
				'mailchimp-for-woocommerce'           => 'mailchimp-for-woocommerce/mailchimp-woocommerce.php',
				'jetpack'                             => 'jetpack/jetpack.php',
				'woocommerce-services'                => 'woocommerce-services/woocommerce-services.php',
				'woocommerce-gateway-stripe'          => 'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
				'woocommerce-gateway-paypal-express-checkout' => 'woocommerce-gateway-paypal-express-checkout/woocommerce-gateway-paypal-express-checkout.php',
				'klarna-checkout-for-woocommerce'     => 'klarna-checkout-for-woocommerce/klarna-checkout-for-woocommerce.php',
				'klarna-payments-for-woocommerce'     => 'klarna-payments-for-woocommerce/klarna-payments-for-woocommerce.php',
				'woocommerce-square'                  => 'woocommerce-square/woocommerce-square.php',
				'woocommerce-shipstation-integration' => 'woocommerce-shipstation-integration/woocommerce-shipstation.php',
			)
		);
	}
	/**
	 * Get a list of active plugins, relevent to the onboarding wizard.
	 *
	 * @return array
	 */
	public static function get_active_plugins() {
		$all_active_plugins   = get_option( 'active_plugins', array() );
		$allowed_plugins      = self::get_allowed_plugins();
		$active_plugin_files  = array_intersect( $all_active_plugins, $allowed_plugins );
		$allowed_plugin_slugs = array_flip( $allowed_plugins );
		$active_plugins       = array();
		foreach ( $active_plugin_files as $file ) {
			$slug             = $allowed_plugin_slugs[ $file ];
			$active_plugins[] = $slug;
		}
		return $active_plugins;
	}

	/**
	 * Let the app know that we will be showing the onboarding route, so wp-admin elements should be hidden while loading.
	 *
	 * @param bool $is_loading Indicates if the `woocommerce-admin-is-loading` should be appended or not.
	 * @return bool
	 */
	public function is_loading( $is_loading ) {
		$show_profiler = self::should_show_profiler();
		$is_dashboard  = ! isset( $_GET['path'] ); // WPCS: csrf ok.

		if ( ! $show_profiler || ! $is_dashboard ) {
			return $is_loading;
		}
		return true;
	}

	/**
	 * Instead of redirecting back to the payment settings page, we will redirect back to the payments task list with our status.
	 *
	 * @param string $location URL of redirect.
	 * @param int    $status HTTP response status code.
	 * @return string URL of redirect.
	 */
	public function overwrite_paypal_redirect( $location, $status ) {
		$settings_page = 'tab=checkout&section=ppec_paypal';
		if ( substr( $location, -strlen( $settings_page ) ) === $settings_page ) {
			$settings_array = (array) get_option( 'woocommerce_ppec_paypal_settings', array() );
			$connected      = isset( $settings_array['api_username'] ) && isset( $settings_array['api_password'] ) ? true : false;
			return wc_admin_url( '&task=payments&paypal-connect=' . $connected );
		}
		return $location;
	}

	/**
	 * Finishes the PayPal connection process by saving the correct settings.
	 */
	public function finish_paypal_connect() {
		if (
			! Loader::is_admin_page() ||
			! isset( $_GET['paypal-connect-finish'] ) // WPCS: CSRF ok.
		) {
			return;
		}

		if ( ! function_exists( 'wc_gateway_ppec' ) ) {
			return false;
		}

		// @todo This is a bit hacky but works. Ideally, woocommerce-gateway-paypal-express-checkout would contain a filter for us.
		add_filter( 'wp_redirect', array( $this, 'overwrite_paypal_redirect' ), 10, 2 );
		wc_gateway_ppec()->ips->maybe_received_credentials();
		remove_filter( 'wp_redirect', array( $this, 'overwrite_paypal_redirect' ) );
	}

	/**
	 * Instead of redirecting back to the payment settings page, we will redirect back to the payments task list with our status.
	 *
	 * @param string $location URL of redirect.
	 * @param int    $status HTTP response status code.
	 * @return string URL of redirect.
	 */
	public function overwrite_square_redirect( $location, $status ) {
		$settings_page = 'page=wc-settings&tab=square';
		if ( substr( $location, -strlen( $settings_page ) ) === $settings_page ) {
			return wc_admin_url( '&task=payments&square-connect=1' );
		}
		return $location;
	}

	/**
	 * Finishes the Square connection process by saving the correct settings.
	 */
	public function finish_square_connect() {
		if (
			! Loader::is_admin_page() ||
			! isset( $_GET['square-connect-finish'] ) // WPCS: CSRF ok.
		) {
			return;
		}

		if ( ! class_exists( '\WooCommerce\Square\Plugin' ) ) {
			return false;
		}

		$square = \WooCommerce\Square\Plugin::instance();

		// @todo This is a bit hacky but works. Ideally, woocommerce-square would contain a filter for us.
		add_filter( 'wp_redirect', array( $this, 'overwrite_square_redirect' ), 10, 2 );
		$square->get_connection_handler()->handle_connected();
		remove_filter( 'wp_redirect', array( $this, 'overwrite_square_redirect' ) );
	}

	/**
	 * Update the help tab setup link to reset the onboarding profiler.
	 */
	public static function update_help_tab() {
		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, wc_get_screen_ids(), true ) ) {
			return;
		}

		$help_tabs = $screen->get_help_tabs();

		foreach ( $help_tabs as $help_tab ) {
			if ( 'woocommerce_onboard_tab' !== $help_tab['id'] ) {
				continue;
			}

			$screen->remove_help_tab( 'woocommerce_onboard_tab' );

			$task_list_hidden = get_option( 'woocommerce_task_list_hidden', 'no' );
			$onboarding_data  = get_option( 'wc_onboarding_profile', array() );
			$is_completed     = isset( $onboarding_data['completed'] ) && true === $onboarding_data['completed'];
			$is_enabled       = ! $is_completed;

			$help_tab['content'] = '<h2>' . __( 'WooCommerce Onboarding', 'woocommerce-admin' ) . '</h2>';

			$help_tab['content'] .= '<h3>' . __( 'Profile Setup Wizard', 'woocommerce-admin' ) . '</h3>';
			$help_tab['content'] .= '<p>' . __( 'If you need to enable or disable the setup wizard again, please click on the button below.', 'woocommerce-admin' ) . '</p>' .
			( $is_enabled
				? '<p><a href="' . wc_admin_url( '&reset_profiler=0' ) . '" class="button button-primary">' . __( 'Disable', 'woocommerce-admin' ) . '</a></p>'
				: '<p><a href="' . wc_admin_url( '&reset_profiler=1' ) . '" class="button button-primary">' . __( 'Enable', 'woocommerce-admin' ) . '</a></p>'
			);

			$help_tab['content'] .= '<h3>' . __( 'Task List', 'woocommerce-admin' ) . '</h3>';
			$help_tab['content'] .= '<p>' . __( 'If you need to enable or disable the task list, please click on the button below.', 'woocommerce-admin' ) . '</p>' .
			( 'yes' === $task_list_hidden
				? '<p><a href="' . wc_admin_url( '&reset_task_list=1' ) . '" class="button button-primary">' . __( 'Enable', 'woocommerce-admin' ) . '</a></p>'
				: '<p><a href="' . wc_admin_url( '&reset_task_list=0' ) . '" class="button button-primary">' . __( 'Disable', 'woocommerce-admin' ) . '</a></p>'
			);

			if ( Loader::is_feature_enabled( 'devdocs' ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$help_tab['content'] .= '<h3>' . __( 'Calypso / WordPress.com', 'woocommerce-admin' ) . '</h3>';
				if ( class_exists( 'Jetpack' ) ) {
					$help_tab['content'] .= '<p>' . __( 'Quickly access the Jetpack connection flow in Calypso.', 'woocommerce-admin' ) . '</p>';
					$help_tab['content'] .= '<p><a href="' . wc_admin_url( '&test_wc_jetpack_connect=1' ) . '" class="button button-primary">' . __( 'Connect', 'woocommerce-admin' ) . '</a></p>';
				}

				$help_tab['content'] .= '<p>' . __( 'Quickly access the WooCommerce.com connection flow in Calypso.', 'woocommerce-admin' ) . '</p>';
				$help_tab['content'] .= '<p><a href="' . wc_admin_url( '&test_wc_helper_connect=1' ) . '" class="button button-primary">' . __( 'Connect', 'woocommerce-admin' ) . '</a></p>';
			}

			$screen->add_help_tab( $help_tab );
		}
	}

	/**
	 * Allows quick access to testing the calypso parts of onboarding.
	 */
	public static function calypso_tests() {
		// @todo When implementing user-facing split testing, this should be abled to a default of 'production'.
		$calypso_env = defined( 'WOOCOMMERCE_CALYPSO_ENVIRONMENT' ) && in_array( WOOCOMMERCE_CALYPSO_ENVIRONMENT, array( 'development', 'wpcalypso', 'horizon', 'stage' ) ) ? WOOCOMMERCE_CALYPSO_ENVIRONMENT : 'wpcalypso';

		if ( Loader::is_admin_page() && class_exists( 'Jetpack' ) && isset( $_GET['test_wc_jetpack_connect'] ) && 1 === absint( $_GET['test_wc_jetpack_connect'] ) ) { // WPCS: CSRF ok.
			$redirect_url = esc_url_raw(
				add_query_arg(
					array(
						'page' => 'wc-admin',
					),
					admin_url( 'admin.php' )
				)
			);

			$connect_url = \Jetpack::init()->build_connect_url( true, $redirect_url, 'woocommerce-setup-wizard' );
			$connect_url = add_query_arg( array( 'calypso_env' => $calypso_env ), $connect_url );

			wp_redirect( $connect_url );
			exit;
		}

		if ( Loader::is_admin_page() && isset( $_GET['test_wc_helper_connect'] ) && 1 === absint( $_GET['test_wc_helper_connect'] ) ) { // WPCS: CSRF ok.
			include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-api.php';

			$redirect_uri = wc_admin_url( '&task=connect&wccom-connected=1' );

			$request = \WC_Helper_API::post(
				'oauth/request_token',
				array(
					'body' => array(
						'home_url'     => home_url(),
						'redirect_uri' => $redirect_uri,
					),
				)
			);

			$code = wp_remote_retrieve_response_code( $request );
			if ( 200 !== $code ) {
				wp_die( esc_html__( 'WooCommerce Helper was not able to connect to WooCommerce.com.', 'woocommerce-admin' ) );
				exit;
			}

			$secret = json_decode( wp_remote_retrieve_body( $request ) );
			if ( empty( $secret ) ) {
				wp_die( esc_html__( 'WooCommerce Helper was not able to connect to WooCommerce.com.', 'woocommerce-admin' ) );
				exit;
			}

			$connect_url = add_query_arg(
				array(
					'home_url'     => rawurlencode( home_url() ),
					'redirect_uri' => rawurlencode( $redirect_uri ),
					'secret'       => rawurlencode( $secret ),
					'wccom-from'   => 'onboarding',
				),
				\WC_Helper_API::url( 'oauth/authorize' )
			);

			$connect_url = add_query_arg( array( 'calypso_env' => $calypso_env ), $connect_url );

			wp_redirect( $connect_url );
			exit;
		}
	}

	/**
	 * Reset the onboarding profiler and redirect to the profiler.
	 */
	public static function reset_profiler() {
		if (
			! Loader::is_admin_page() ||
			! isset( $_GET['reset_profiler'] ) // WPCS: CSRF ok.
		) {
			return;
		}

		$previous  = 1 === absint( $_GET['reset_profiler'] );
		$new_value = ! $previous;

		wc_admin_record_tracks_event(
			'wcadmin_storeprofiler_toggled',
			array(
				'previous'  => $previous,
				'new_value' => $new_value,
			)
		);

		$request = new \WP_REST_Request( 'POST', '/wc-admin/v1/onboarding/profile' );
		$request->set_headers( array( 'content-type' => 'application/json' ) );
		$request->set_body(
			wp_json_encode(
				array(
					'completed' => $new_value,
				)
			)
		);
		$response = rest_do_request( $request );
		wp_safe_redirect( wc_admin_url() );
		exit;
	}

	/**
	 * Reset the onboarding task list and redirect to the dashboard.
	 */
	public static function reset_task_list() {
		if (
			! Loader::is_admin_page() ||
			! isset( $_GET['reset_task_list'] ) // WPCS: CSRF ok.
		) {
			return;
		}

		$new_value = 1 === absint( $_GET['reset_task_list'] ) ? 'no' : 'yes'; // WPCS: CSRF ok.
		update_option( 'woocommerce_task_list_hidden', $new_value );
		wp_safe_redirect( wc_admin_url() );
		exit;
	}
}
