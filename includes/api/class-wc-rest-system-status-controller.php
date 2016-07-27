<?php
/**
 * REST API WC System Status controller
 *
 * Handles requests to the /system-status endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_System_Status_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'system-status';

	/**
	 * Register the routes for coupons.
	 */
	public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

    /**
	 * Check whether a given request has permission to view system status.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
        if ( ! wc_rest_check_manager_permissions( 'system-status', 'read' ) ) {
        	return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

    /**
	 * Get a system status info, by section.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$schema    = $this->get_item_schema();
		$mappings  = $this->get_item_mappings();
		$response  = array();

		foreach ( $mappings as $section => $values ) {
			settype( $values, $schema['properties'][ $section ]['type'] );
			foreach ( $values as $key => $value ) {
				if ( isset( $schema['properties'][ $section ]['properties'][ $key ]['type'] ) ) {
					settype( $values[ $key ], $schema['properties'][ $section ]['properties'][ $key ]['type'] );
				}
			}
			$response[ $section ] = $values;
		}

		return rest_ensure_response( $response );
	}

    /**
	 * Get the system status schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'system-status',
			'type'       => 'object',
			'properties' => array(
				'environment' => array(
					'description' => __( 'Environment', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'home_url' => array(
							'description' => __( 'Home URL', 'woocommerce' ),
							'type'        => 'string',
		                    'format'      => 'uri',
							'context'     => array( 'view', 'edit' ),
						),
		                'site_url' => array(
		                    'description' => __( 'Site URL', 'woocommerce' ),
		                    'type'        => 'string',
		                    'format'      => 'uri',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'wc_version' => array(
		                    'description' => __( 'WooCommerce Version', 'woocommerce' ),
		                    'type'        => 'string',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'log_directory' => array(
		                    'description' => __( 'Log Directory', 'woocommerce' ),
		                    'type'        => 'string',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'log_directory_writable' => array(
		                    'description' => __( 'Is Log Directory Writable?', 'woocommerce' ),
		                    'type'        => 'boolean',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'wp_version' => array(
		                    'description' => __( 'WordPress Version', 'woocommerce' ),
		                    'type'        => 'string',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'wp_multisite' => array(
		                    'description' => __( 'Is WordPress Multisite?', 'woocommerce' ),
		                    'type'        => 'boolean',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'wp_memory_limit' => array(
		                    'description' => __( 'WordPress Memory Limit', 'woocommerce' ),
		                    'type'        => 'integer',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'wp_debug_mode' => array(
		                    'description' => __( 'Is WordPress Debug Mode Active?', 'woocommerce' ),
		                    'type'        => 'boolean',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'wp_cron' => array(
		                    'description' => __( 'Are WordPress Cron Jobs Enabled?', 'woocommerce' ),
		                    'type'        => 'boolean',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'language' => array(
		                    'description' => __( 'WordPress Language', 'woocommerce' ),
		                    'type'        => 'string',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'server_info' => array(
		                    'description' => __( 'Server Info', 'woocommerce' ),
		                    'type'        => 'string',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'php_version' => array(
		                    'description' => __( 'PHP Version', 'woocommerce' ),
		                    'type'        => 'string',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'php_post_max_size' => array(
		                    'description' => __( 'PHP Post Max Size', 'woocommerce' ),
		                    'type'        => 'integer',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'php_max_execution_time' => array(
		                    'description' => __( 'PHP Max Execution Time', 'woocommerce' ),
		                    'type'        => 'integer',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'php_max_input_vars' => array(
		                    'description' => __( 'PHP Max Input Vars', 'woocommerce' ),
		                    'type'        => 'integer',
		                    'context'     => array( 'view', 'edit' ),
		                ),
		                'curl_version' => array(
		                    'description' => __( 'cURL Version', 'woocommerce' ),
		                    'type'        => 'string',
		                    'context'     => array( 'view', 'edit' ),
		                ),
						'suhosin_installed' => array(
							'description' => __( 'Is SUHOSIN Installed?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'max_upload_size' => array(
							'description' => __( 'Max Upload Size', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'mysql_version' => array(
							'description' => __( 'MySQL Version', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'default_timezone' => array(
							'description' => __( 'Default Timezone', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'fsockopen_or_curl_enabled' => array(
							'description' => __( 'Is fsockopen/cURL Enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'soapclient_enabled' => array(
							'description' => __( 'Is SoapClient Class Enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'domdocument_enabled' => array(
							'description' => __( 'Is DomDocument Class Enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'gzip_enabled' => array(
							'description' => __( 'Is GZip Enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'mbstring_enabled' => array(
							'description' => __( 'Is mbstring Enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'remote_post_successful' => array(
							'description' => __( 'Remote POST Successful?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'remote_post_response' => array(
							'description' => __( 'Remote POST Response', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'remote_get_successful' => array(
							'description' => __( 'Remote GET Successful?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'remote_get_response' => array(
							'description' => __( 'Remote GET Response', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'database' => array(
					'description' => __( 'Database', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'wc_database_version' => array(
							'description' => __( 'WC Database Version', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'database_prefix' => array(
							'description' => __( 'Database Prefix', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'maxmind_geoip_database' => array(
							'description' => __( 'MaxMind GeoIP Database', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'database_tables' => array(
							'description' => __( 'Database Tables', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
						),
					)
				),
				'active_plugins' => array(
					'description' => __( 'Active Plugins', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
				'theme' => array(
					'description' => __( 'Theme', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'name' => array(
							'description' => __( 'Theme Name', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'version' => array(
							'description' => __( 'Theme Version', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'author_url' => array(
							'description' => __( 'Theme Author URL', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view', 'edit' ),
						),
						'is_child_theme' => array(
							'description' => __( 'Is this theme a child theme?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'has_woocommerce_support' => array(
							'description' => __( 'Does the theme declare WooCommerce support?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'overrides' => array(
							'description' => __( 'Template Overrides', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
						),
						'parent_name' => array(
							'description' => __( 'Parent Theme Name', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'parent_version' => array(
							'description' => __( 'Parent Theme Version', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'parent_author_url' => array(
							'description' => __( 'Parent Theme Author URL', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view', 'edit' ),
						),
					)
				),
				'settings' => array(
					'description' => __( 'Settings', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'api_enabled' => array(
							'description' => __( 'REST API Enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'force_ssl' => array(
							'description' => __( 'SSL Forced?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'currency' => array(
							'description' => __( 'Currency', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'currency_symbol' => array(
							'description' => __( 'Currency Symbol', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'currency_position' => array(
							'description' => __( 'Currency Position', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'thousand_separator' => array(
							'description' => __( 'Thousand Separator', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'decimal_separator' => array(
							'description' => __( 'Decimal Separator', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'number_of_decimals' => array(
							'description' => __( 'Number of Decimals', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'geolocation_enabled' => array(
							'description' => __( 'Geolocation Enabled?', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'taxonomies' => array(
							'description' => __( 'Taxonomy Terms for Product/Order Statuses', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
						),
					)
				),
				'pages' => array(
					'description' => __( 'WooCommerce Pages', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
				),
			)
		);

		return $this->add_additional_fields_schema( $schema );
	}

    /**
	 * Return an array of sections and the data associated with each.
	 *
	 * @return array
	 */
	public function get_item_mappings() {
		return array(
			'environment'    => $this->get_environment_info(),
			'database'       => $this->get_database_info(),
			'active_plugins' => $this->get_active_plugins(),
			'theme'          => $this->get_theme_info(),
			'settings'       => $this->get_settings(),
			'pages'          => $this->get_pages(),
		);
	}

	/**
	 * Get array of environment information. Includes thing like software
	 * versions, and various server settings.
	 *
	 * @return array
	 */
	public function get_environment_info() {
		global $wpdb;

		// Figure out cURL version, if installed.
		$curl_version = '';
		if ( function_exists( 'curl_version' ) ) {
            $curl_version = curl_version();
            $curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
        }

		// WP memory limit
        $wp_memory_limit = wc_let_to_num( WP_MEMORY_LIMIT );
        if ( function_exists( 'memory_get_usage' ) ) {
            $wp_memory_limit = max( $wp_memory_limit, wc_let_to_num( @ini_get( 'memory_limit' ) ) );
        }

		// Test POST requests
		$post_response = wp_safe_remote_post( 'https://www.paypal.com/cgi-bin/webscr', array(
			'timeout'     => 60,
			'user-agent'  => 'WooCommerce/' . WC()->version,
			'httpversion' => '1.1',
			'body'        => array(
				'cmd'    => '_notify-validate'
			)
		) );
		$post_response_successful = false;
		if ( ! is_wp_error( $post_response ) && $post_response['response']['code'] >= 200 && $post_response['response']['code'] < 300 ) {
			$post_response_successful = true;
		}

		// Test GET requests
		$get_response = wp_safe_remote_get( 'https://woocommerce.com/wc-api/product-key-api?request=ping&network=' . ( is_multisite() ? '1' : '0' ) );
		$get_response_successful = false;
		if ( ! is_wp_error( $post_response ) && $post_response['response']['code'] >= 200 && $post_response['response']['code'] < 300 ) {
			$get_response_successful = true;
		}

		// Return all environment info. Described by JSON Schema.
		return array(
            'home_url'                  => get_option( 'home' ),
            'site_url'                  => get_option( 'siteurl' ),
            'version'                => WC()->version,
            'log_directory'             => WC_LOG_DIR,
            'log_directory_writable'    => ( @fopen( WC_LOG_DIR . 'test-log.log', 'a' ) ? true : false ),
            'wp_version'                => get_bloginfo('version'),
            'wp_multisite'              => is_multisite(),
            'wp_memory_limit'           => $wp_memory_limit,
            'wp_debug_mode'             => ( defined( 'WP_DEBUG' ) && WP_DEBUG ),
            'wp_cron'                   => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
            'language'                  => get_locale(),
            'server_info'               => $_SERVER['SERVER_SOFTWARE'],
            'php_version'               => phpversion(),
            'php_post_max_size'         => wc_let_to_num( ini_get( 'post_max_size' ) ),
            'php_max_execution_time'    => ini_get( 'max_execution_time' ),
            'php_max_input_vars'        => ini_get( 'max_input_vars' ),
            'curl_version'              => $curl_version,
			'suhosin_installed'         => extension_loaded( 'suhosin' ),
			'max_upload_size'           => wp_max_upload_size(),
			'mysql_version'             => ( ! empty( $wpdb->is_mysql ) ? $wpdb->db_version() : '' ),
			'default_timezone'          => date_default_timezone_get(),
			'fsockopen_or_curl_enabled' => ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ),
			'soapclient_enabled'        => class_exists( 'SoapClient' ),
			'domdocument_enabled'       => class_exists( 'DOMDocument' ),
			'gzip_enabled'              => is_callable( 'gzopen' ),
			'mbstring_enabled'          => extension_loaded( 'mbstring' ),
			'remote_post_successful'    => $post_response_successful,
			'remote_post_response'      => ( is_wp_error( $post_response ) ? $post_response->get_error_message() : $post_response['response']['code'] ),
			'remote_get_successful'     => $get_response_successful,
			'remote_get_response'       => ( is_wp_error( $get_response ) ? $get_response->get_error_message() : $get_response['response']['code'] ),
        );
	}

	/**
	 * Get array of database information. Version, prefix, and table existence.
	 *
	 * @return array
	 */
	public function get_database_info() {
		global $wpdb;

		// WC Core tables to check existence of
		$tables = array(
			'woocommerce_sessions',
			'woocommerce_api_keys',
			'woocommerce_attribute_taxonomies',
			'woocommerce_downloadable_product_permissions',
			'woocommerce_order_items',
			'woocommerce_order_itemmeta',
			'woocommerce_tax_rates',
			'woocommerce_tax_rate_locations',
			'woocommerce_shipping_zones',
			'woocommerce_shipping_zone_locations',
			'woocommerce_shipping_zone_methods',
			'woocommerce_payment_tokens',
			'woocommerce_payment_tokenmeta',
		);

		if ( get_option( 'db_version' ) < 34370 ) {
			$tables[] = 'woocommerce_termmeta';
		}
		$table_exists = array();
		foreach ( $tables as $table ) {
			$table_exists[ $table ] = ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s;", $wpdb->prefix . $table ) ) === $wpdb->prefix . $table );
		}

		// Return all database info. Described by JSON Schema.
		return array(
			'wc_database_version'    => get_option( 'woocommerce_db_version' ),
			'database_prefix'        => $wpdb->prefix,
			'maxmind_geoip_database' => WC_Geolocation::get_local_database_path(),
			'database_tables'        => $table_exists,
		);
	}

	/**
	 * Get a list of plugins active on the site.
	 *
	 * @return array
	 */
	public function get_active_plugins() {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// Get both site plugins and network plugins
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$network_activated_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins            = array_merge( $active_plugins, $network_activated_plugins );
		}

		$active_plugins_data = array();
		foreach ( $active_plugins as $plugin ) {
			$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			// convert plugin data to json response format.
			$active_plugins_data[] = array(
				'name'        => $data['Name'],
				'version'     => $data['Version'],
				'url'         => $data['PluginURI'],
				'author_name' => $data['AuthorName'],
				'author_url'  => esc_url_raw( $data['AuthorURI'] ),
			);
		}

		return $active_plugins_data;
	}

	/**
	 * Get info on the current active theme, info on parent theme (if presnet)
	 * and a list of template overrides.
	 *
	 * @return array
	 */
	public function get_theme_info() {
		$active_theme = wp_get_theme();

		// Get parent theme info if this theme is a child theme, otherwise
		// pass empty info in the response.
		if ( is_child_theme() ) {
			$parent_theme      = wp_get_theme( $active_theme->Template );
			$parent_theme_info = array(
				'parent_name'       => $parent_theme->Name,
				'parentversion'     => $parent_theme->Version,
				'parent_author_url' => $parent_theme->{'Author URI'},
			);
		} else {
			$parent_theme_info = array( 'parent_theme_name' => '', 'parent_theme_version' => '', 'parent_theme_author_url' => '' );
		}

		/**
		 * Scan the theme directory for all WC templates to see if our theme
		 * overrides any of them.
		 */
		$override_files = array();
		$scan_files  = WC_Admin_Status::scan_template_files( WC()->plugin_path() . '/templates/' );
		foreach ( $scan_files as $file ) {
			if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $file;
			} elseif ( file_exists( get_stylesheet_directory() . '/woocommerce/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/woocommerce/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
				$theme_file = get_template_directory() . '/' . $file;
			} elseif ( file_exists( get_template_directory() . '/woocommerce/' . $file ) ) {
				$theme_file = get_template_directory() . '/woocommerce/' . $file;
			} else {
				$theme_file = false;
			}

			if ( ! empty( $theme_file ) ) {
				$override_files[] = str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file );
			}
		}

		$active_theme_info = array(
			'name'                    => $active_theme->Name,
			'version'                 => $active_theme->Version,
			'author_url'              => esc_url_raw( $active_theme->{'Author URI'} ),
			'is_child_theme'          => is_child_theme(),
			'has_woocommerce_support' => ( current_theme_supports( 'woocommerce' ) || in_array( $active_theme->template, wc_get_core_supported_themes() ) ),
			'overrides'               => $override_files,
		);

		return array_merge( $active_theme_info, $parent_theme_info );
	}

	/**
	 * Get some setting values for the site that are useful for debugging
	 * purposes. For full settings access, use the settings api.
	 *
	 * @return array
	 */
	public function get_settings() {
		// Get a list of terms used for product/order taxonomies
		$term_response = array();
		$terms         = get_terms( 'product_type', array( 'hide_empty' => 0 ) );
		foreach ( $terms as $term ) {
			$term_response[ $term->slug ] = strtolower( $term->name );
		}

		// Return array of useful settings for debugging.
		return array(
			'api_enabled'         => 'yes' === get_option( 'woocommerce_api_enabled' ),
			'force_ssl'           => 'yes' === get_option( 'woocommerce_force_ssl_checkout' ),
			'currency'            => get_woocommerce_currency(),
			'currency_symbol'     => get_woocommerce_currency_symbol(),
			'currency_position'   => get_option( 'woocommerce_currency_pos' ),
			'thousand_separator'  => wc_get_price_thousand_separator(),
			'decimal_separator'   => wc_get_price_decimal_separator(),
			'number_of_decimals'  => wc_get_price_decimals(),
			'geolocation_enabled' => in_array( get_option( 'woocommerce_default_customer_address' ), array( 'geolocation_ajax', 'geolocation' ) ),
			'taxonomies'          => $term_response,
		);
	}

	/**
	 * Returns a mini-report on WC pages and if they are configured correctly:
	 * Present, visible, and including the correct shortcode.
	 *
	 * @return array
	 */
	public function get_pages() {
		// WC pages to check against
		$check_pages = array(
			_x( 'Shop Base', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_shop_page_id',
				'shortcode' => '',
			),
			_x( 'Cart', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_cart_page_id',
				'shortcode' => '[' . apply_filters( 'woocommerce_cart_shortcode_tag', 'woocommerce_cart' ) . ']',
			),
			_x( 'Checkout', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_checkout_page_id',
				'shortcode' => '[' . apply_filters( 'woocommerce_checkout_shortcode_tag', 'woocommerce_checkout' ) . ']',
			),
			_x( 'My Account', 'Page setting', 'woocommerce' ) => array(
				'option'    => 'woocommerce_myaccount_page_id',
				'shortcode' => '[' . apply_filters( 'woocommerce_my_account_shortcode_tag', 'woocommerce_my_account' ) . ']',
			),
		);

		$pages_output = array();
		foreach ( $check_pages as $page_name => $values ) {
			$errors   = array();
			$page_id  = get_option( $values['option'] );
			$page_set = $page_exists = $page_visible = false;
			$shortcode_present = $shortcode_required = false;

			// Page checks
			if ( $page_id ) {
				$page_set = true;
			}
			if ( get_post( $page_id ) ) {
				$page_exists = true;
			}
			if ( 'publish' === get_post_status( $page_id ) ) {
				$page_visible = true;
			}

			// Shortcode checks
			if ( $values['shortcode']  && get_post( $page_id ) ) {
				$shortcode_required = true;
				$page = get_post( $page_id );
				if ( strstr( $page->post_content, $values['shortcode'] ) ) {
					$shortcode_present = true;
				}
			}

			// Wrap up our findings into an output array
			$pages_output[] = array(
					'page_name'          => $page_name,
					'page_id'            => $page_id,
					'page_set'           => $page_set,
					'page_exists'        => $page_exists,
					'page_visible'       => $page_visible,
					'shortcode_required' => $shortcode_required,
					'shortcode_present'  => $shortcode_present,
			);
		}

		return $pages_output;
	}

	/**
	 * Get any query params needed.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}

}
