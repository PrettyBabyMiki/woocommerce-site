<?php
/**
 * REST API WC System Status Tools Controller
 *
 * Handles requests to the /system_status/tools/* endpoints.
 *
 * @package WooCommerce/RestApi
 */

namespace WooCommerce\RestApi\Version4\Controllers;

defined( 'ABSPATH' ) || exit;

/**
 * REST API System Status Tools controller class.
 */
class SystemStatusTools extends AbstractController {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'system_status/tools';

	/**
	 * Register the routes for /system_status/tools/*.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
			true
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
			true
		);
	}

	/**
	 * Check whether a given request has permission to view system status tools.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check whether a given request has permission to view a specific system status tool.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check whether a given request has permission to execute a specific system status tool.
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'edit' ) ) {
			return new \WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot update resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * A list of available tools for use in the system status section.
	 * 'button' becomes 'action' in the API.
	 *
	 * @return array
	 */
	public function get_tools() {
		$tools = array(
			'clear_transients'                   => array(
				'name'   => __( 'WooCommerce transients', 'woocommerce' ),
				'button' => __( 'Clear transients', 'woocommerce' ),
				'desc'   => __( 'This tool will clear the product/shop transients cache.', 'woocommerce' ),
			),
			'clear_expired_transients'           => array(
				'name'   => __( 'Expired transients', 'woocommerce' ),
				'button' => __( 'Clear transients', 'woocommerce' ),
				'desc'   => __( 'This tool will clear ALL expired transients from WordPress.', 'woocommerce' ),
			),
			'delete_orphaned_variations'         => array(
				'name'   => __( 'Orphaned variations', 'woocommerce' ),
				'button' => __( 'Delete orphaned variations', 'woocommerce' ),
				'desc'   => __( 'This tool will delete all variations which have no parent.', 'woocommerce' ),
			),
			'clear_expired_download_permissions' => array(
				'name'   => __( 'Used-up download permissions', 'woocommerce' ),
				'button' => __( 'Clean up download permissions', 'woocommerce' ),
				'desc'   => __( 'This tool will delete expired download permissions and permissions with 0 remaining downloads.', 'woocommerce' ),
			),
			'regenerate_product_lookup_tables' => array(
				'name'   => __( 'Product lookup tables', 'woocommerce' ),
				'button' => __( 'Regenerate', 'woocommerce' ),
				'desc'   => __( 'This tool will regenerate product lookup table data. This process may take a while.', 'woocommerce' ),
			),
			'recount_terms'                      => array(
				'name'   => __( 'Term counts', 'woocommerce' ),
				'button' => __( 'Recount terms', 'woocommerce' ),
				'desc'   => __( 'This tool will recount product terms - useful when changing your settings in a way which hides products from the catalog.', 'woocommerce' ),
			),
			'reset_roles'                        => array(
				'name'   => __( 'Capabilities', 'woocommerce' ),
				'button' => __( 'Reset capabilities', 'woocommerce' ),
				'desc'   => __( 'This tool will reset the admin, customer and shop_manager roles to default. Use this if your users cannot access all of the WooCommerce admin pages.', 'woocommerce' ),
			),
			'clear_sessions'                     => array(
				'name'   => __( 'Clear customer sessions', 'woocommerce' ),
				'button' => __( 'Clear', 'woocommerce' ),
				'desc'   => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This tool will delete all customer session data from the database, including current carts and saved carts in the database.', 'woocommerce' )
				),
			),
			'install_pages'                      => array(
				'name'   => __( 'Create default WooCommerce pages', 'woocommerce' ),
				'button' => __( 'Create pages', 'woocommerce' ),
				'desc'   => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This tool will install all the missing WooCommerce pages. Pages already defined and set up will not be replaced.', 'woocommerce' )
				),
			),
			'delete_taxes'                       => array(
				'name'   => __( 'Delete WooCommerce tax rates', 'woocommerce' ),
				'button' => __( 'Delete tax rates', 'woocommerce' ),
				'desc'   => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This option will delete ALL of your tax rates, use with caution. This action cannot be reversed.', 'woocommerce' )
				),
			),
			'regenerate_thumbnails'              => array(
				'name'   => __( 'Regenerate shop thumbnails', 'woocommerce' ),
				'button' => __( 'Regenerate', 'woocommerce' ),
				'desc'   => __( 'This will regenerate all shop thumbnails to match your theme and/or image settings.', 'woocommerce' ),
			),
			'db_update_routine'                  => array(
				'name'   => __( 'Update database', 'woocommerce' ),
				'button' => __( 'Update database', 'woocommerce' ),
				'desc'   => sprintf(
					'<strong class="red">%1$s</strong> %2$s',
					__( 'Note:', 'woocommerce' ),
					__( 'This tool will update your WooCommerce database to the latest version. Please ensure you make sufficient backups before proceeding.', 'woocommerce' )
				),
			),
		);

		// Jetpack does the image resizing heavy lifting so you don't have to.
		if ( ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'photon' ) ) || ! apply_filters( 'woocommerce_background_image_regeneration', true ) ) {
			unset( $tools['regenerate_thumbnails'] );
		}

		return apply_filters( 'woocommerce_debug_tools', $tools );
	}

	/**
	 * Get a list of system status tools.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$tools = array();
		foreach ( $this->get_tools() as $id => $tool ) {
			$tools[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response(
					array(
						'id'          => $id,
						'name'        => $tool['name'],
						'action'      => $tool['button'],
						'description' => $tool['desc'],
					),
					$request
				)
			);
		}

		$response = rest_ensure_response( $tools );
		return $response;
	}

	/**
	 * Return a single tool.
	 *
	 * @param  \WP_REST_Request $request Request data.
	 * @return \WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$tools = $this->get_tools();
		if ( empty( $tools[ $request['id'] ] ) ) {
			return new \WP_Error( 'woocommerce_rest_system_status_tool_invalid_id', __( 'Invalid tool ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		$tool = $tools[ $request['id'] ];
		return rest_ensure_response(
			$this->prepare_item_for_response(
				array(
					'id'          => $request['id'],
					'name'        => $tool['name'],
					'action'      => $tool['button'],
					'description' => $tool['desc'],
				),
				$request
			)
		);
	}

	/**
	 * Update (execute) a tool.
	 *
	 * @param  \WP_REST_Request $request Request data.
	 * @return \WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$tools = $this->get_tools();
		if ( empty( $tools[ $request['id'] ] ) ) {
			return new \WP_Error( 'woocommerce_rest_system_status_tool_invalid_id', __( 'Invalid tool ID.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$tool = $tools[ $request['id'] ];
		$tool = array(
			'id'          => $request['id'],
			'name'        => $tool['name'],
			'action'      => $tool['button'],
			'description' => $tool['desc'],
		);

		$execute_return = $this->execute_tool( $request['id'] );
		$tool           = array_merge( $tool, $execute_return );

		/**
		 * Fires after a WooCommerce REST system status tool has been executed.
		 *
		 * @param array           $tool    Details about the tool that has been executed.
		 * @param \WP_REST_Request $request The current \WP_REST_Request object.
		 */
		do_action( 'woocommerce_rest_insert_system_status_tool', $tool, $request );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $tool, $request );
		return rest_ensure_response( $response );
	}

	/**
	 * Prepare a tool item for serialization.
	 *
	 * @param  array           $item     Object.
	 * @param  \WP_REST_Request $request  Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$context = empty( $request['context'] ) ? 'view' : $request['context'];
		$data    = $this->add_additional_fields_to_object( $item, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item['id'] ) );

		return $response;
	}

	/**
	 * Get the system status tools schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'system_status_tool',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'A unique identifier for the tool.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'name'        => array(
					'description' => __( 'Tool name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'action'      => array(
					'description' => __( 'What running the tool will do.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'description' => array(
					'description' => __( 'Tool description.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'success'     => array(
					'description' => __( 'Did the tool run successfully?', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'edit' ),
				),
				'message'     => array(
					'description' => __( 'Tool return message.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param string $id ID.
	 * @return array
	 */
	protected function prepare_links( $id ) {
		$base  = '/' . $this->namespace . '/' . $this->rest_base;
		$links = array(
			'item' => array(
				'href'       => rest_url( trailingslashit( $base ) . $id ),
				'embeddable' => true,
			),
		);

		return $links;
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

	/**
	 * Actually executes a tool.
	 *
	 * @param  string $tool Tool.
	 * @return array
	 */
	public function execute_tool( $tool ) {
		global $wpdb;
		$ran = true;
		switch ( $tool ) {
			case 'clear_transients':
				wc_delete_product_transients();
				wc_delete_shop_order_transients();
				delete_transient( 'wc_count_comments' );

				$attribute_taxonomies = wc_get_attribute_taxonomies();

				if ( $attribute_taxonomies ) {
					foreach ( $attribute_taxonomies as $attribute ) {
						delete_transient( 'wc_layered_nav_counts_pa_' . $attribute->attribute_name );
					}
				}

				\WC_Cache_Helper::get_transient_version( 'shipping', true );
				$message = __( 'Product transients cleared', 'woocommerce' );
				break;

			case 'clear_expired_transients':
				/* translators: %d: amount of expired transients */
				$message = sprintf( __( '%d transients rows cleared', 'woocommerce' ), wc_delete_expired_transients() );
				break;

			case 'delete_orphaned_variations':
				// Delete orphans.
				$result = absint(
					$wpdb->query(
						"DELETE products
					FROM {$wpdb->posts} products
					LEFT JOIN {$wpdb->posts} wp ON wp.ID = products.post_parent
					WHERE wp.ID IS NULL AND products.post_type = 'product_variation';"
					)
				);
				/* translators: %d: amount of orphaned variations */
				$message = sprintf( __( '%d orphaned variations deleted', 'woocommerce' ), $result );
				break;

			case 'clear_expired_download_permissions':
				// Delete expired download permissions and ones with 0 downloads remaining.
				$result = absint(
					$wpdb->query(
						$wpdb->prepare(
							"DELETE FROM {$wpdb->prefix}woocommerce_downloadable_product_permissions
							WHERE ( downloads_remaining != '' AND downloads_remaining = 0 ) OR ( access_expires IS NOT NULL AND access_expires < %s )",
							date( 'Y-m-d', current_time( 'timestamp' ) )
						)
					)
				);
				/* translators: %d: amount of permissions */
				$message = sprintf( __( '%d permissions deleted', 'woocommerce' ), $result );
				break;

			case 'regenerate_product_lookup_tables':
				if ( ! wc_update_product_lookup_tables_is_running() ) {
					wc_update_product_lookup_tables();
				}
				$message = __( 'Lookup tables are regenerating', 'woocommerce' );
				break;
			case 'reset_roles':
				// Remove then re-add caps and roles.
				\WC_Install::remove_roles();
				\WC_Install::create_roles();
				$message = __( 'Roles successfully reset', 'woocommerce' );
				break;

			case 'recount_terms':
				$product_cats = get_terms(
					'product_cat',
					array(
						'hide_empty' => false,
						'fields'     => 'id=>parent',
					)
				);
				_wc_term_recount( $product_cats, get_taxonomy( 'product_cat' ), true, false );
				$product_tags = get_terms(
					'product_tag',
					array(
						'hide_empty' => false,
						'fields'     => 'id=>parent',
					)
				);
				_wc_term_recount( $product_tags, get_taxonomy( 'product_tag' ), true, false );
				$message = __( 'Terms successfully recounted', 'woocommerce' );
				break;

			case 'clear_sessions':
				$wpdb->query( "TRUNCATE {$wpdb->prefix}woocommerce_sessions" );
				$result = absint( $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key='_woocommerce_persistent_cart_" . get_current_blog_id() . "';" ) ); // WPCS: unprepared SQL ok.
				wp_cache_flush();
				/* translators: %d: amount of sessions */
				$message = sprintf( __( 'Deleted all active sessions, and %d saved carts.', 'woocommerce' ), absint( $result ) );
				break;

			case 'install_pages':
				\WC_Install::create_pages();
				$message = __( 'All missing WooCommerce pages successfully installed', 'woocommerce' );
				break;

			case 'delete_taxes':
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_tax_rates;" );
				$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_tax_rate_locations;" );
				\WC_Cache_Helper::incr_cache_prefix( 'taxes' );
				$message = __( 'Tax rates successfully deleted', 'woocommerce' );
				break;

			case 'regenerate_thumbnails':
				\WC_Regenerate_Images::queue_image_regeneration();
				$message = __( 'Thumbnail regeneration has been scheduled to run in the background.', 'woocommerce' );
				break;

			case 'db_update_routine':
				$blog_id = get_current_blog_id();
				// Used to fire an action added in WP_Background_Process::_construct() that calls WP_Background_Process::handle_cron_healthcheck().
				// This method will make sure the database updates are executed even if cron is disabled. Nothing will happen if the updates are already running.
				do_action( 'wp_' . $blog_id . '_wc_updater_cron' );
				$message = __( 'Database upgrade routine has been scheduled to run in the background.', 'woocommerce' );
				break;

			default:
				$tools = $this->get_tools();
				if ( isset( $tools[ $tool ]['callback'] ) ) {
					$callback = $tools[ $tool ]['callback'];
					$return   = call_user_func( $callback );
					if ( is_string( $return ) ) {
						$message = $return;
					} elseif ( false === $return ) {
						$callback_string = is_array( $callback ) ? get_class( $callback[0] ) . '::' . $callback[1] : $callback;
						$ran             = false;
						/* translators: %s: callback string */
						$message = sprintf( __( 'There was an error calling %s', 'woocommerce' ), $callback_string );
					} else {
						$message = __( 'Tool ran.', 'woocommerce' );
					}
				} else {
					$ran     = false;
					$message = __( 'There was an error calling this tool. There is no callback present.', 'woocommerce' );
				}
				break;
		}

		return array(
			'success' => $ran,
			'message' => $message,
		);
	}
}
