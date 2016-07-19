<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Settings Groups Controller.
 * Handles requests to the /settings and /settings/<group> endpoints.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @version  2.7.0
 * @since    2.7.0
 */
class WC_Rest_Settings_Controller extends WC_REST_Controller {

	/**
	 * WP REST API namespace/version.
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Register routes.
	 *
	 * @since 2.7.0
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Get all settings groups items.
	 *
	 * @since  2.7.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$groups = apply_filters( 'woocommerce_settings_groups', array() );
		if ( empty( $groups ) ) {
			return new WP_Error( 'rest_setting_groups_empty', __( 'No setting groups have been registered.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		$defaults        = $this->group_defaults();
		$filtered_groups = array();
		foreach ( $groups as $group ) {
			$sub_groups = array();
			foreach ( $groups as $_group ) {
				if ( ! empty( $_group['parent_id'] ) && $group['id'] === $_group['parent_id'] ) {
					$sub_groups[] = $_group['id'];
				}
			}
			$group['sub_groups'] = $sub_groups;

			$group = wp_parse_args( $group, $defaults );
			if ( ! is_null( $group['id'] ) && ! is_null( $group['label'] ) ) {
				$group_obj  = $this->filter_group( $group );
				$group_data = $this->prepare_item_for_response( $group_obj, $request );
				$group_data = $this->prepare_response_for_collection( $group_data );

				$filtered_groups[] = $group_data;
			}
		}

		$response = rest_ensure_response( $filtered_groups );
		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param string $group_id Group ID.
	 * @return array Links for the given group.
	 */
	protected function prepare_links( $group_id ) {
		$base  = '/' . $this->namespace . '/' . $this->rest_base;
		$links = array(
			'item' => array(
				'href'       => rest_url( trailingslashit( $base ) . $group_id ),
				'embeddable' => true,
			),
		);

		return $links;
	}

	/**
	 * Prepare a report sales object for serialization.
	 *
	 * @since  2.7.0
	 * @param array $item Group object.
	 * @param WP_REST_Request $request Request object.
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
	 * Filters out bad values from the groups array/filter so we
	 * only return known values via the API.
	 *
	 * @since 2.7.0
	 * @param  array $group
	 * @return array
	 */
	public function filter_group( $group ) {
		return array_intersect_key(
			$group,
			array_flip( array_filter( array_keys( $group ), array( $this, 'allowed_group_keys' ) ) )
		);
	}

	/**
	 * Callback for allowed keys for each group response.
	 *
	 * @since  2.7.0
	 * @param  string $key Key to check
	 * @return boolean
	 */
	public function allowed_group_keys( $key ) {
		return in_array( $key, array( 'id', 'label', 'description', 'parent_id', 'sub_groups' ) );
	}

	/**
	 * Returns default settings for groups. null means the field is required.
	 *
	 * @since  2.7.0
	 * @return array
	 */
	protected function group_defaults() {
		return array(
			'id'          => null,
			'label'       => null,
			'description' => '',
			'parent_id'   => '',
			'sub_groups'  => array(),
		);
	}

	/**
	 * Makes sure the current user has access to READ the settings APIs.
	 *
	 * @since  2.7.0
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get the groups schema, conforming to JSON Schema.
	 *
	 * @since  2.7.0
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'              => 'http://json-schema.org/draft-04/schema#',
			'title'                => 'settings-group',
			'type'                 => 'object',
			'properties'           => array(
				'id'               => array(
					'description'  => __( 'A unique identifier that can be used to link settings together.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'label'            => array(
					'description'  => __( 'A human readable translation wrapped label. Meant to be used in interfaces.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'description'      => array(
					'description'  => __( 'A human readable translation wrapped description. Meant to be used in interfaces', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'parent_id'        => array(
					'description'  => __( 'ID of parent grouping.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'sub_groups'        => array(
					'description'  => __( 'IDs for settings sub groups.', 'woocommerce' ),
					'type'         => 'string',
					'arg_options'  => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
