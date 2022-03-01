<?php
/**
 * WooCommerce Onboarding
 * NOTE: DO NOT edit this file in WooCommerce core, this is generated from woocommerce-admin.
 */

namespace Automattic\WooCommerce\Admin\Features;

use \Automattic\WooCommerce\Admin\Loader;
use Automattic\WooCommerce\Admin\PageController;
use Automattic\WooCommerce\Admin\WCAdminHelper;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\Init as OnboardingTasks;
use Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists;
use Automattic\WooCommerce\Internal\Admin\Schedulers\MailchimpScheduler;

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
	 * Profile data option name.
	 */
	const PROFILE_DATA_OPTION = 'woocommerce_onboarding_profile';

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
		$this->add_toggle_actions();

		OnboardingTasks::get_instance();

		// Add actions and filters.
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the ability to toggle the new onboarding experience on or off.
	 */
	private function add_toggle_actions() {
		add_action( 'woocommerce_updated', array( $this, 'maybe_mark_complete' ) );
		add_action( 'update_option_' . self::PROFILE_DATA_OPTION, array( $this, 'send_profile_data_on_update' ), 10, 2 );
		add_action( 'woocommerce_helper_connected', array( $this, 'send_profile_data_on_connect' ) );
	}

	/**
	 * Add onboarding actions.
	 */
	private function add_actions() {
		// Rest API hooks need to run before is_admin() checks.
		add_action( 'woocommerce_theme_installed', array( $this, 'delete_themes_transient' ) );
		add_action( 'after_switch_theme', array( $this, 'delete_themes_transient' ) );
		add_action(
			'update_option_' . self::PROFILE_DATA_OPTION,
			array(
				$this,
				'trigger_profile_completed_action',
			),
			10,
			2
		);
		add_action( 'woocommerce_admin_plugins_pre_activate', array( $this, 'activate_and_install_jetpack_ahead_of_wcpay' ) );
		add_action( 'woocommerce_admin_plugins_pre_install', array( $this, 'activate_and_install_jetpack_ahead_of_wcpay' ) );

		// Always hook into Jetpack connection even if outside of admin.
		add_action( 'jetpack_site_registered', array( $this, 'set_woocommerce_setup_jetpack_opted_in' ) );

		add_action( 'woocommerce_onboarding_profile_data_updated', array( $this, 'on_profile_data_updated' ), 10, 2 );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'current_screen', array( $this, 'add_help_tab' ), 60 );
		add_action( 'current_screen', array( $this, 'reset_task_list' ) );
		add_action( 'current_screen', array( $this, 'reset_extended_task_list' ) );
		add_action( 'current_screen', array( $this, 'redirect_wccom_install' ) );
		add_action( 'current_screen', array( $this, 'redirect_to_profiler' ) );
	}

	/**
	 * Test whether the context of execution comes from async action scheduler.
	 * Note: this is a polyfill for wc_is_running_from_async_action_scheduler()
	 *       which was introduced in WC 4.0.
	 *
	 * @return bool
	 */
	public static function is_running_from_async_action_scheduler() {
		if ( function_exists( '\wc_is_running_from_async_action_scheduler' ) ) {
			return \wc_is_running_from_async_action_scheduler();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_REQUEST['action'] ) && 'as_async_request_queue_runner' === $_REQUEST['action'];
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		// Don't run this fn from Action Scheduler requests, as it would clear _wc_activation_redirect transient.
		// That means OBW would never be shown.
		if ( self::is_running_from_async_action_scheduler() ) {
			return;
		}

		// Setup wizard redirect.
		if ( get_transient( '_wc_activation_redirect' ) && apply_filters( 'woocommerce_enable_setup_wizard', true ) ) {
			$do_redirect        = true;
			$current_page       = isset( $_GET['page'] ) ? wc_clean( wp_unslash( $_GET['page'] ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification
			$is_onboarding_path = ! isset( $_GET['path'] ) || '/setup-wizard' === wc_clean( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

			// On these pages, or during these events, postpone the redirect.
			if ( wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_woocommerce' ) ) {
				$do_redirect = false;
			}

			// On these pages, or during these events, disable the redirect.
			if (
				( 'wc-admin' === $current_page && $is_onboarding_path ) ||
				apply_filters( 'woocommerce_prevent_automatic_wizard_redirect', false ) ||
				isset( $_GET['activate-multi'] ) // phpcs:ignore WordPress.Security.NonceVerification
			) {
				delete_transient( '_wc_activation_redirect' );
				$do_redirect = false;
			}

			if ( $do_redirect ) {
				delete_transient( '_wc_activation_redirect' );
				wp_safe_redirect( wc_admin_url() );
				exit;
			}
		}
	}

	/**
	 * Sets the woocommerce_setup_jetpack_opted_in to true when Jetpack connects to WPCOM.
	 */
	public function set_woocommerce_setup_jetpack_opted_in() {
		update_option( 'woocommerce_setup_jetpack_opted_in', true );
	}

	/**
	 * Trigger the woocommerce_onboarding_profile_completed action
	 *
	 * @param array $old_value Previous value.
	 * @param array $value Current value.
	 */
	public function trigger_profile_completed_action( $old_value, $value ) {
		if ( isset( $old_value['completed'] ) && $old_value['completed'] ) {
			return;
		}

		if ( ! isset( $value['completed'] ) || ! $value['completed'] ) {
			return;
		}

		/**
		 * Action hook fired when the onboarding profile (or onboarding wizard,
		 * or profiler) is completed.
		 *
		 * @since 1.5.0
		 */
		do_action( 'woocommerce_onboarding_profile_completed' );
	}

	/**
	 * Add onboarding filters.
	 */
	private function add_filters() {
		// Rest API hooks need to run before is_admin() checks.
		add_filter( 'woocommerce_rest_prepare_themes', array( $this, 'add_uploaded_theme_data' ) );

		if ( ! is_admin() ) {
			return;
		}

		// Old settings injection.
		// Run after Automattic\WooCommerce\Admin\Loader.
		add_filter( 'woocommerce_components_settings', array( $this, 'component_settings' ), 20 );
		// New settings injection.
		add_filter( 'woocommerce_admin_shared_settings', array( $this, 'component_settings' ), 20 );
		add_filter( 'woocommerce_admin_preload_settings', array( $this, 'preload_settings' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'add_loading_classes' ) );
		add_filter( 'woocommerce_show_admin_notice', array( $this, 'remove_install_notice' ), 10, 2 );
	}

	/**
	 * Send profile data to WooCommerce.com.
	 */
	public static function send_profile_data() {
		if ( 'yes' !== get_option( 'woocommerce_allow_tracking', 'no' ) ) {
			return;
		}

		if ( ! class_exists( '\WC_Helper_API' ) || ! method_exists( '\WC_Helper_API', 'put' ) ) {
			return;
		}

		if ( ! class_exists( '\WC_Helper_Options' ) ) {
			return;
		}

		$auth = \WC_Helper_Options::get( 'auth' );
		if ( empty( $auth['access_token'] ) || empty( $auth['access_token_secret'] ) ) {
			return false;
		}

		$profile       = get_option( self::PROFILE_DATA_OPTION, array() );
		$base_location = wc_get_base_location();
		$defaults      = array(
			'plugins'             => 'skipped',
			'industry'            => array(),
			'product_types'       => array(),
			'product_count'       => '0',
			'selling_venues'      => 'no',
			'number_employees'    => '1',
			'revenue'             => 'none',
			'other_platform'      => 'none',
			'business_extensions' => array(),
			'theme'               => get_stylesheet(),
			'setup_client'        => false,
			'store_location'      => $base_location['country'],
			'default_currency'    => get_woocommerce_currency(),
		);

		// Prepare industries as an array of slugs if they are in array format.
		if ( isset( $profile['industry'] ) && is_array( $profile['industry'] ) ) {
			$industry_slugs = array();
			foreach ( $profile['industry'] as $industry ) {
				$industry_slugs[] = is_array( $industry ) ? $industry['slug'] : $industry;
			}
			$profile['industry'] = $industry_slugs;
		}
		$body = wp_parse_args( $profile, $defaults );

		\WC_Helper_API::put(
			'profile',
			array(
				'authenticated' => true,
				'body'          => wp_json_encode( $body ),
				'headers'       => array(
					'Content-Type' => 'application/json',
				),
			)
		);
	}

	/**
	 * Send profiler data on profiler change to completion.
	 *
	 * @param array $old_value Previous value.
	 * @param array $value Current value.
	 */
	public static function send_profile_data_on_update( $old_value, $value ) {
		if ( ! isset( $value['completed'] ) || ! $value['completed'] ) {
			return;
		}

		self::send_profile_data();
	}

	/**
	 * Send profiler data after a site is connected.
	 */
	public static function send_profile_data_on_connect() {
		$profile = get_option( self::PROFILE_DATA_OPTION, array() );
		if ( ! isset( $profile['completed'] ) || ! $profile['completed'] ) {
			return;
		}

		self::send_profile_data();
	}

	/**
	 * Returns true if the profiler should be displayed (not completed and not skipped).
	 *
	 * @return bool
	 */
	public static function should_show_profiler() {
		if ( self::is_profile_wizard() ) {
			return true;
		}

		return self::profiler_needs_completion();
	}

	/**
	 * Redirect to the profiler on homepage if completion is needed.
	 */
	public static function redirect_to_profiler() {
		if ( ! self::is_homepage() || ! self::profiler_needs_completion() ) {
			return;
		}

		wp_safe_redirect( wc_admin_url( '&path=/setup-wizard' ) );
		exit;
	}

	/**
	 * Check if the profiler still needs to be completed.
	 *
	 * @return bool
	 */
	public static function profiler_needs_completion() {
		$onboarding_data = get_option( self::PROFILE_DATA_OPTION, array() );

		$is_completed = isset( $onboarding_data['completed'] ) && true === $onboarding_data['completed'];
		$is_skipped   = isset( $onboarding_data['skipped'] ) && true === $onboarding_data['skipped'];

		// @todo When merging to WooCommerce Core, we should set the `completed` flag to true during the upgrade progress.
		// https://github.com/woocommerce/woocommerce-admin/pull/2300#discussion_r287237498.
		return ! $is_completed && ! $is_skipped;
	}

	/**
	 * Check if the current page is the profile wizard.
	 *
	 * @return bool
	 */
	public static function is_profile_wizard() {
		/* phpcs:disable WordPress.Security.NonceVerification */
		return isset( $_GET['page'] ) &&
			'wc-admin' === $_GET['page'] &&
			isset( $_GET['path'] ) &&
			'/setup-wizard' === $_GET['path'];
		/* phpcs: enable */
	}

	/**
	 * Check if the current page is the homepage.
	 *
	 * @return bool
	 */
	public static function is_homepage() {
		/* phpcs:disable WordPress.Security.NonceVerification */
		return isset( $_GET['page'] ) &&
			'wc-admin' === $_GET['page'] &&
			! isset( $_GET['path'] );
		/* phpcs: enable */
	}

	/**
	 * Returns true if the task list should be displayed (not completed or hidden off the dashboard).
	 *
	 * @deprecated 2.7.0
	 * @return bool
	 */
	public static function should_show_tasks() {
		wc_deprecated_function( 'should_show_tasks', '4.4', '\Automattic\WooCommerce\Admin\Features\OnboardingTasks\TaskLists::get_list( $list_id )->is_hidden()' );

		$setup_list    = TaskLists::get_list( 'setup' );
		$extended_list = TaskLists::get_list( 'extended' );

		return ( $setup_list && ! $setup_list->is_hidden() ) || ( $extended_list && ! $extended_list->is_hidden() );
	}

	/**
	 * Get a list of allowed industries for the onboarding wizard.
	 *
	 * @return array
	 */
	public static function get_allowed_industries() {
		/* With "use_description" we turn the description input on. With "description_label" we set the input label */
		return apply_filters(
			'woocommerce_admin_onboarding_industries',
			array(
				'fashion-apparel-accessories'     => array(
					'label'             => __( 'Fashion, apparel, and accessories', 'woocommerce-admin' ),
					'use_description'   => false,
					'description_label' => '',
				),
				'health-beauty'                   => array(
					'label'             => __( 'Health and beauty', 'woocommerce-admin' ),
					'use_description'   => false,
					'description_label' => '',
				),
				'electronics-computers'           => array(
					'label'             => __( 'Electronics and computers', 'woocommerce-admin' ),
					'use_description'   => false,
					'description_label' => '',
				),
				'food-drink'                      => array(
					'label'             => __( 'Food and drink', 'woocommerce-admin' ),
					'use_description'   => false,
					'description_label' => '',
				),
				'home-furniture-garden'           => array(
					'label'             => __( 'Home, furniture, and garden', 'woocommerce-admin' ),
					'use_description'   => false,
					'description_label' => '',
				),
				'cbd-other-hemp-derived-products' => array(
					'label'             => __( 'CBD and other hemp-derived products', 'woocommerce-admin' ),
					'use_description'   => false,
					'description_label' => '',
				),
				'education-and-learning'          => array(
					'label'             => __( 'Education and learning', 'woocommerce-admin' ),
					'use_description'   => false,
					'description_label' => '',
				),
				'other'                           => array(
					'label'             => __( 'Other', 'woocommerce-admin' ),
					'use_description'   => true,
					'description_label' => __( 'Description', 'woocommerce-admin' ),
				),
			)
		);
	}

	/**
	 * Get a list of allowed product types for the onboarding wizard.
	 *
	 * @return array
	 */
	public static function get_allowed_product_types() {
		$products         = array(
			'physical'        => array(
				'label'   => __( 'Physical products', 'woocommerce-admin' ),
				'default' => true,
			),
			'downloads'       => array(
				'label' => __( 'Downloads', 'woocommerce-admin' ),
			),
			'subscriptions'   => array(
				'label' => __( 'Subscriptions', 'woocommerce-admin' ),
			),
			'memberships'     => array(
				'label'   => __( 'Memberships', 'woocommerce-admin' ),
				'product' => 958589,
			),
			'bookings'        => array(
				'label'   => __( 'Bookings', 'woocommerce-admin' ),
				'product' => 390890,
			),
			'product-bundles' => array(
				'label'   => __( 'Bundles', 'woocommerce-admin' ),
				'product' => 18716,
			),
			'product-add-ons' => array(
				'label'   => __( 'Customizable products', 'woocommerce-admin' ),
				'product' => 18618,
			),
		);
		$base_location    = wc_get_base_location();
		$has_cbd_industry = false;
		if ( 'US' === $base_location['country'] ) {
			$profile = get_option( self::PROFILE_DATA_OPTION, array() );
			if ( ! empty( $profile['industry'] ) ) {
				$has_cbd_industry = in_array( 'cbd-other-hemp-derived-products', array_column( $profile['industry'], 'slug' ), true );
			}
		}
		if ( ! Features::is_enabled( 'subscriptions' ) || 'US' !== $base_location['country'] || $has_cbd_industry ) {
			$products['subscriptions']['product'] = 27147;
		}

		return apply_filters( 'woocommerce_admin_onboarding_product_types', $products );
	}

	/**
	 * Sort themes returned from WooCommerce.com
	 *
	 * @param  array $themes Array of themes from WooCommerce.com.
	 * @return array
	 */
	public static function sort_woocommerce_themes( $themes ) {
		usort(
			$themes,
			function ( $product_1, $product_2 ) {
				if ( ! property_exists( $product_1, 'id' ) || ! property_exists( $product_1, 'slug' ) ) {
					return 1;
				}
				if ( ! property_exists( $product_2, 'id' ) || ! property_exists( $product_2, 'slug' ) ) {
					return 1;
				}
				if ( in_array( 'Storefront', array( $product_1->slug, $product_2->slug ), true ) ) {
					return 'Storefront' === $product_1->slug ? -1 : 1;
				}
				return $product_1->id < $product_2->id ? 1 : -1;
			}
		);
		return $themes;
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
				$theme_data    = json_decode( $theme_data['body'] );
				$woo_themes    = property_exists( $theme_data, 'products' ) ? $theme_data->products : array();
				$sorted_themes = self::sort_woocommerce_themes( $woo_themes );

				foreach ( $sorted_themes as $theme ) {
					$slug                                       = sanitize_title_with_dashes( $theme->slug );
					$themes[ $slug ]                            = (array) $theme;
					$themes[ $slug ]['is_installed']            = false;
					$themes[ $slug ]['has_woocommerce_support'] = true;
					$themes[ $slug ]['slug']                    = $slug;
				}
			}

			$installed_themes = wp_get_themes();
			$active_theme     = get_option( 'stylesheet' );

			foreach ( $installed_themes as $slug => $theme ) {
				$theme_data      = self::get_theme_data( $theme );
				$themes[ $slug ] = $theme_data;
			}

			// Add the WooCommerce support tag for default themes that don't explicitly declare support.
			if ( function_exists( 'wc_is_wp_default_theme_active' ) && wc_is_wp_default_theme_active() ) {
				$themes[ $active_theme ]['has_woocommerce_support'] = true;
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
			'slug'                    => sanitize_text_field( $theme->stylesheet ),
			'title'                   => $theme->get( 'Name' ),
			'price'                   => '0.00',
			'is_installed'            => true,
			'image'                   => $theme->get_screenshot(),
			'has_woocommerce_support' => true,
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
	 * Check if theme has declared support for WooCommerce.
	 *
	 * @param WP_Theme $theme Theme to check.
	 * @link https://developer.woocommerce.com/2017/12/09/wc-3-3-will-look-great-on-all-the-themes/
	 * @deprecated 2.2.0
	 * @return bool
	 */
	public static function has_woocommerce_support( $theme ) {
		wc_deprecated_function( 'Onboarding::has_woocommerce_support', '5.3' ); // Deprecated since WooCommerce 5.3.
		return true; // All themes are supported since WooCommerce 3.3.
	}

	/**
	 * Get dynamic product data from API.
	 *
	 * @param array $product_types Array of product types.
	 * @return array
	 */
	public static function get_product_data( $product_types ) {
		$woocommerce_products = get_transient( self::PRODUCT_DATA_TRANSIENT );
		if ( false === $woocommerce_products ) {
			$woocommerce_products = wp_remote_get( 'https://woocommerce.com/wp-json/wccom-extensions/1.0/search' );
			if ( is_wp_error( $woocommerce_products ) ) {
				return $product_types;
			}

			set_transient( self::PRODUCT_DATA_TRANSIENT, $woocommerce_products, DAY_IN_SECONDS );
		}

		$data         = json_decode( $woocommerce_products['body'] );
		$products     = array();
		$product_data = array();

		// Map product data by ID.
		if ( isset( $data ) && isset( $data->products ) ) {
			foreach ( $data->products as $product_datum ) {
				if ( isset( $product_datum->id ) ) {
					$products[ $product_datum->id ] = $product_datum;
				}
			}
		}

		// Loop over product types and append data.
		foreach ( $product_types as $key => $product_type ) {
			$product_data[ $key ] = $product_types[ $key ];

			if ( isset( $product_type['product'] ) && isset( $products[ $product_type['product'] ] ) ) {
				$price        = html_entity_decode( $products[ $product_type['product'] ]->price );
				$yearly_price = (float) str_replace( '$', '', $price );

				$product_data[ $key ]['yearly_price'] = $yearly_price;
				$product_data[ $key ]['description']  = $products[ $product_type['product'] ]->excerpt;
				$product_data[ $key ]['more_url']     = $products[ $product_type['product'] ]->link;
				$product_data[ $key ]['slug']         = strtolower( preg_replace( '~[^\pL\d]+~u', '-', $products[ $product_type['product'] ]->slug ) );
			} elseif ( isset( $product_type['product'] ) ) {
				/* translators: site currency symbol (used to show that the product costs money) */
				$product_data[ $key ]['label'] .= sprintf( __( ' — %s', 'woocommerce-admin' ), html_entity_decode( get_woocommerce_currency_symbol() ) );
			}
		}

		return $product_data;
	}

	/**
	 * Delete the stored themes transient.
	 */
	public static function delete_themes_transient() {
		delete_transient( self::THEMES_TRANSIENT );
	}

	/**
	 * Determine if the current page is one of the WC Admin pages.
	 *
	 * @return bool
	 */
	protected function is_wc_pages() {
		$current_page = PageController::get_instance()->get_current_page();
		if ( ! $current_page || ! isset( $current_page['path'] ) ) {
			return false;
		}

		return 0 === strpos( $current_page['path'], 'wc-admin' );
	}

	/**
	 * Add profiler items to component settings.
	 *
	 * @param array $settings Component settings.
	 *
	 * @return array
	 */
	public function component_settings( $settings ) {
		$profile                = (array) get_option( self::PROFILE_DATA_OPTION, array() );
		$settings['onboarding'] = array(
			'profile' => $profile,
		);

		// Only fetch if the onboarding wizard OR the task list is incomplete or currently shown
		// or the current page is one of the WooCommerce Admin pages.
		if (
			( ! self::should_show_profiler() && ! count( TaskLists::get_visible() )
			||
			! $this->is_wc_pages()
		)
		) {
			return $settings;
		}

		include_once WC_ABSPATH . 'includes/admin/helper/class-wc-helper-options.php';
		$wccom_auth                 = \WC_Helper_Options::get( 'auth' );
		$profile['wccom_connected'] = empty( $wccom_auth['access_token'] ) ? false : true;

		$settings['onboarding']['activeTheme']     = get_option( 'stylesheet' );
		$settings['onboarding']['currencySymbols'] = get_woocommerce_currency_symbols();
		$settings['onboarding']['euCountries']     = WC()->countries->get_european_union_countries();
		$settings['onboarding']['industries']      = self::get_allowed_industries();
		$settings['onboarding']['localeInfo']      = include WC()->plugin_path() . '/i18n/locale-info.php';
		$settings['onboarding']['profile']         = $profile;
		$settings['onboarding']['themes']          = self::get_themes();

		return $settings;
	}

	/**
	 * Preload WC setting options to prime state of the application.
	 *
	 * @param array $options Array of options to preload.
	 * @return array
	 */
	public function preload_settings( $options ) {
		$options[] = 'general';

		return $options;
	}

	/**
	 * Gets an array of themes that can be installed & activated via the onboarding wizard.
	 *
	 * @return array
	 */
	public static function get_allowed_themes() {
		$allowed_themes = array();
		$themes         = self::get_themes();

		foreach ( $themes as $theme ) {
			$price = preg_replace( '/&#?[a-z0-9]+;/i', '', $theme['price'] );

			if ( $theme['is_installed'] || '0.00' === $price ) {
				$allowed_themes[] = $theme['slug'];
			}
		}

		return apply_filters( 'woocommerce_admin_onboarding_themes_whitelist', $allowed_themes );
	}

	/**
	 * Set the admin full screen class when loading to prevent flashes of unstyled content.
	 *
	 * @param bool $classes Body classes.
	 * @return array
	 */
	public static function add_loading_classes( $classes ) {
		/* phpcs:disable WordPress.Security.NonceVerification */
		if ( self::is_profile_wizard() ) {
			$classes .= ' woocommerce-admin-full-screen';
		}
		/* phpcs: enable */

		return $classes;
	}

	/**
	 * Track changes to the onboarding option.
	 *
	 * @param mixed  $mixed Option name or previous value if option previously existed.
	 * @param string $value Value of the updated option.
	 */
	public static function track_onboarding_toggle( $mixed, $value ) {
		if ( defined( 'WC_ADMIN_MIGRATING_OPTIONS' ) && WC_ADMIN_MIGRATING_OPTIONS ) {
			return;
		};

		wc_admin_record_tracks_event(
			'onboarding_toggled',
			array(
				'previous_value' => ! $value,
				'new_value'      => $value,
			)
		);
	}

	/**
	 * Update the help tab setup link to reset the onboarding profiler.
	 */
	public static function add_help_tab() {
		if ( ! function_exists( 'wc_get_screen_ids' ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, wc_get_screen_ids(), true ) ) {
			return;
		}

		// Remove the old help tab if it exists.
		$help_tabs = $screen->get_help_tabs();
		foreach ( $help_tabs as $help_tab ) {
			if ( 'woocommerce_onboard_tab' !== $help_tab['id'] ) {
				continue;
			}

			$screen->remove_help_tab( 'woocommerce_onboard_tab' );
		}

		// Add the new help tab.
		$help_tab = array(
			'title' => __( 'Setup wizard', 'woocommerce-admin' ),
			'id'    => 'woocommerce_onboard_tab',
		);

		$setup_list    = TaskLists::get_list( 'setup' );
		$extended_list = TaskLists::get_list( 'extended' );

		if ( $setup_list ) {
			$help_tab['content'] = '<h2>' . __( 'WooCommerce Onboarding', 'woocommerce-admin' ) . '</h2>';

			$help_tab['content'] .= '<h3>' . __( 'Profile Setup Wizard', 'woocommerce-admin' ) . '</h3>';
			$help_tab['content'] .= '<p>' . __( 'If you need to access the setup wizard again, please click on the button below.', 'woocommerce-admin' ) . '</p>' .
				'<p><a href="' . wc_admin_url( '&path=/setup-wizard' ) . '" class="button button-primary">' . __( 'Setup wizard', 'woocommerce-admin' ) . '</a></p>';

			$help_tab['content'] .= '<h3>' . __( 'Task List', 'woocommerce-admin' ) . '</h3>';
			$help_tab['content'] .= '<p>' . __( 'If you need to enable or disable the task lists, please click on the button below.', 'woocommerce-admin' ) . '</p>' .
			( $setup_list->is_hidden()
				? '<p><a href="' . wc_admin_url( '&reset_task_list=1' ) . '" class="button button-primary">' . __( 'Enable', 'woocommerce-admin' ) . '</a></p>'
				: '<p><a href="' . wc_admin_url( '&reset_task_list=0' ) . '" class="button button-primary">' . __( 'Disable', 'woocommerce-admin' ) . '</a></p>'
			);
		}

		if ( $extended_list ) {
			$help_tab['content'] .= '<h3>' . __( 'Extended task List', 'woocommerce-admin' ) . '</h3>';
			$help_tab['content'] .= '<p>' . __( 'If you need to enable or disable the extended task lists, please click on the button below.', 'woocommerce-admin' ) . '</p>' .
			( $extended_list->is_hidden()
				? '<p><a href="' . wc_admin_url( '&reset_extended_task_list=1' ) . '" class="button button-primary">' . __( 'Enable', 'woocommerce-admin' ) . '</a></p>'
				: '<p><a href="' . wc_admin_url( '&reset_extended_task_list=0' ) . '" class="button button-primary">' . __( 'Disable', 'woocommerce-admin' ) . '</a></p>'
			);
		}

		$screen->add_help_tab( $help_tab );
	}

	/**
	 * Reset the onboarding task list and redirect to the dashboard.
	 */
	public static function reset_task_list() {
		if (
			! Loader::is_admin_page() ||
			! isset( $_GET['reset_task_list'] ) // phpcs:ignore CSRF ok.
		) {
			return;
		}

		$task_list = TaskLists::get_list( 'setup' );

		if ( ! $task_list ) {
			return;
		}
		$show   = 1 === absint( $_GET['reset_task_list'] );
		$update = $show ? $task_list->unhide() : $task_list->hide(); // phpcs:ignore CSRF ok.

		if ( $update ) {
			wc_admin_record_tracks_event(
				'tasklist_toggled',
				array(
					'status' => $show ? 'enabled' : 'disabled',
				)
			);
		}

		wp_safe_redirect( wc_admin_url() );
		exit;
	}

	/**
	 * Reset the extended task list and redirect to the dashboard.
	 */
	public static function reset_extended_task_list() {
		if (
			! Loader::is_admin_page() ||
			! isset( $_GET['reset_extended_task_list'] ) // phpcs:ignore CSRF ok.
		) {
			return;
		}

		$task_list = TaskLists::get_list( 'extended' );

		if ( ! $task_list ) {
			return;
		}
		$show   = 1 === absint( $_GET['reset_extended_task_list'] );
		$update = $show ? $task_list->unhide() : $task_list->hide(); // phpcs:ignore CSRF ok.

		if ( $update ) {
			wc_admin_record_tracks_event(
				'extended_tasklist_toggled',
				array(
					'status' => $show ? 'disabled' : 'enabled',
				)
			);
		}

		wp_safe_redirect( wc_admin_url() );
		exit;
	}

	/**
	 * Remove the install notice that prompts the user to visit the old onboarding setup wizard.
	 *
	 * @param bool   $show Show or hide the notice.
	 * @param string $notice The slug of the notice.
	 * @return bool
	 */
	public static function remove_install_notice( $show, $notice ) {
		if ( 'install' === $notice ) {
			return false;
		}

		return $show;
	}

	/**
	 * Redirects the user to the task list if the task list is enabled and finishing a wccom checkout.
	 *
	 * @todo Once URL params are added to the redirect, we can check those instead of the referer.
	 */
	public static function redirect_wccom_install() {
		$task_list = TaskLists::get_list( 'setup' );

		if (
			! $task_list ||
			$task_list->is_hidden() ||
			! isset( $_SERVER['HTTP_REFERER'] ) ||
			0 !== strpos( $_SERVER['HTTP_REFERER'], 'https://woocommerce.com/checkout?utm_medium=product' ) // phpcs:ignore sanitization ok.
		) {
			return;
		}

		wp_safe_redirect( wc_admin_url() );
	}

	/**
	 * When updating WooCommerce, mark the profiler and task list complete.
	 *
	 * @todo The `maybe_enable_setup_wizard()` method should be revamped on onboarding enable in core.
	 * See https://github.com/woocommerce/woocommerce/blob/1ca791f8f2325fe2ee0947b9c47e6a4627366374/includes/class-wc-install.php#L341
	 */
	public static function maybe_mark_complete() {
		// The install notice still exists so don't complete the profiler.
		if ( ! class_exists( 'WC_Admin_Notices' ) || \WC_Admin_Notices::has_notice( 'install' ) ) {
			return;
		}

		$onboarding_data = get_option( self::PROFILE_DATA_OPTION, array() );
		// Don't make updates if the profiler is completed or skipped, but task list is potentially incomplete.
		if (
			( isset( $onboarding_data['completed'] ) && $onboarding_data['completed'] ) ||
			( isset( $onboarding_data['skipped'] ) && $onboarding_data['skipped'] )
		) {
			return;
		}

		$onboarding_data['completed'] = true;
		update_option( self::PROFILE_DATA_OPTION, $onboarding_data );

		if ( ! WCAdminHelper::is_wc_admin_active_for( DAY_IN_SECONDS ) ) {
			$task_list = TaskLists::get_list( 'setup' );
			if ( ! $task_list ) {
				return;
			}
			$task_list->hide();
		}
	}

	/**
	 * Ensure that Jetpack gets installed and activated ahead of WooCommerce Payments
	 * if both are being installed/activated at the same time.
	 *
	 * See: https://github.com/Automattic/woocommerce-payments/issues/1663
	 * See: https://github.com/Automattic/jetpack/issues/19624
	 *
	 * @param array $plugins A list of plugins to install or activate.
	 *
	 * @return array
	 */
	public static function activate_and_install_jetpack_ahead_of_wcpay( $plugins ) {
		if ( in_array( 'jetpack', $plugins, true ) && in_array( 'woocommerce-payments', $plugins, true ) ) {
			array_unshift( $plugins, 'jetpack' );
			$plugins = array_unique( $plugins );
		}
		return $plugins;
	}

	/**
	 * Reset MailchimpScheduler if profile data is being updated with a new email.
	 *
	 * @param array $existing_data Existing option data.
	 * @param array $updating_data Updating option data.
	 */
	public function on_profile_data_updated( $existing_data, $updating_data ) {
		if (
			isset( $existing_data['store_email'] ) &&
			isset( $updating_data['store_email'] ) &&
			$existing_data['store_email'] !== $updating_data['store_email']
		) {
			MailchimpScheduler::reset();
		}
	}
}
