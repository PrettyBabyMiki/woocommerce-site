<?php
/**
 * REST API Taxes controller
 *
 * Handles requests to the /taxes endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Taxes controller class.
 *
 * @package WooCommerce/API
 * @extends WP_REST_Controller
 */
class WC_REST_Taxes_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'taxes';

	/**
	 * Register the routes for taxes.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				'args'                => array(
					'force' => array(
						'default'     => false,
						'description' => __( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
					),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/bulk', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'bulk_items' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * Check whether a given request has permission to read taxes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list taxes.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access create taxes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read a tax.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access update a tax.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access delete a tax.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'delete' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get all taxes.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		global $wpdb;

		$prepared_args = array();
		$prepared_args['exclude'] = $request['exclude'];
		$prepared_args['include'] = $request['include'];
		$prepared_args['order']   = $request['order'];
		$prepared_args['number']  = $request['per_page'];
		if ( ! empty( $request['offset'] ) ) {
			$prepared_args['offset'] = $request['offset'];
		} else {
			$prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['number'];
		}
		$orderby_possibles = array(
			'id'    => 'tax_rate_id',
			'order' => 'tax_rate_order',
		);
		$prepared_args['orderby'] = $orderby_possibles[ $request['orderby'] ];
		$prepared_args['class']   = $request['class'];

		/**
		 * Filter arguments, before passing to $wpdb->get_results(), when querying taxes via the REST API.
		 *
		 * @param array           $prepared_args Array of arguments for $wpdb->get_results().
		 * @param WP_REST_Request $request       The current request.
		 */
		$prepared_args = apply_filters( 'woocommerce_rest_tax_query', $prepared_args, $request );

		$query = "
			SELECT *
			FROM {$wpdb->prefix}woocommerce_tax_rates
			WHERE 1 = 1
		";

		// Filter by tax class.
		if ( ! empty( $prepared_args['class'] ) ) {
			$class = 'standard' !== $prepared_args['class'] ? sanitize_title( $prepared_args['class'] ) : '';
			$query .= " AND tax_rate_class = '$class'";
		}

		// Order tax rates.
		$order_by = sprintf( ' ORDER BY %s', sanitize_key( $prepared_args['orderby'] ) );

		// Pagination.
		$pagination = sprintf( ' LIMIT %d, %d', $prepared_args['offset'], $prepared_args['number'] );

		// Query taxes.
		$results = $wpdb->get_results( $query . $order_by . $pagination );

		$taxes = array();
		foreach ( $results as $tax ) {
			$data = $this->prepare_item_for_response( $tax, $request );
			$taxes[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $taxes );

		// Store pagation values for headers then unset for count query.
		$per_page = (int) $prepared_args['number'];
		$page = ceil( ( ( (int) $prepared_args['offset'] ) / $per_page ) + 1 );

		// Query only for ids.
		$wpdb->get_results( str_replace( 'SELECT *', 'SELECT tax_rate_id', $query ) );

		// Calcule totals.
		$total_taxes = (int) $wpdb->num_rows;
		$response->header( 'X-WP-Total', (int) $total_taxes );
		$max_pages = ceil( $total_taxes / $per_page );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ) );
		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Create a single tax.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( 'woocommerce_rest_tax_exists', __( 'Cannot create existing resource.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$data = array(
			'tax_rate_country'  => $request['country'],
			'tax_rate_state'    => $request['state'],
			'tax_rate'          => $request['rate'],
			'tax_rate_name'     => $request['name'],
			'tax_rate_priority' => (int) $request['priority'],
			'tax_rate_compound' => (int) $request['compound'],
			'tax_rate_shipping' => (int) $request['shipping'],
			'tax_rate_order'    => (int) $request['order'],
			'tax_rate_class'    => 'standard' !== $request['class'] ? $request['class'] : '',
		);

		// Create tax rate.
		$id = WC_Tax::_insert_tax_rate( $data );

		// Add locales.
		if ( ! empty( $request['postcode'] ) ) {
			WC_Tax::_update_tax_rate_postcodes( $id, wc_clean( $request['postcode'] ) );
		}
		if ( ! empty( $request['city'] ) ) {
			WC_Tax::_update_tax_rate_cities( $id, wc_clean( $request['city'] ) );
		}

		$tax = WC_Tax::_get_tax_rate( $id, OBJECT );

		$this->update_additional_fields_for_object( $tax, $request );

		/**
		 * Fires after a tax is created or updated via the REST API.
		 *
		 * @param stdClass        $tax       Data used to create the tax.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating tax, false when updating tax.
		 */
		do_action( 'woocommerce_rest_insert_tax', $tax, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $tax, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $id ) ) );

		return $response;
	}

	/**
	 * Get a single tax.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$id       = (int) $request['id'];
		$tax_obj = WC_Tax::_get_tax_rate( $id, OBJECT );

		if ( empty( $id ) || empty( $tax_obj ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource id.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$tax = $this->prepare_item_for_response( $tax_obj, $request );
		$response = rest_ensure_response( $tax );

		return $response;
	}

	/**
	 * Update a single tax.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$id          = (int) $request['id'];
		$current_tax = WC_Tax::_get_tax_rate( $id, OBJECT );

		if ( empty( $id ) || empty( $current_tax ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource id.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		$data   = array();
		$fields = array(
			'tax_rate_country',
			'tax_rate_state',
			'tax_rate',
			'tax_rate_name',
			'tax_rate_priority',
			'tax_rate_compound',
			'tax_rate_shipping',
			'tax_rate_order',
			'tax_rate_class'
		);

		foreach ( $fields as $field ) {
			$key = 'tax_rate' === $field ? 'rate' : str_replace( 'tax_rate_', '', $field );

			if ( ! isset( $request[ $key ] ) ) {
				continue;
			}

			$value = $request[ $key ];

			// Fix compund and shipping values.
			if ( in_array( $key, array( 'compound', 'shipping' ) ) ) {
				$value = (int) $request[ $key ];
			}

			// Test new data against current data.
			if ( $current_tax->$field === $value ) {
				continue;
			}

			$data[ $field ] = $request[ $key ];
		}

		// Update tax rate.
		WC_Tax::_update_tax_rate( $id, $data );

		// Update locales.
		if ( ! isset( $request['postcode'] ) ) {
			WC_Tax::_update_tax_rate_postcodes( $id, wc_clean( $request['postcode'] ) );
		}

		if ( ! isset( $request['city'] ) ) {
			WC_Tax::_update_tax_rate_cities( $id, wc_clean( $request['city'] ) );
		}

		$tax = WC_Tax::_get_tax_rate( $id, OBJECT );

		$this->update_additional_fields_for_object( $tax, $request );

		/**
		 * Fires after a tax is created or updated via the REST API.
		 *
		 * @param stdClass        $tax       Data used to create the tax.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating tax, false when updating tax.
		 */
		do_action( 'woocommerce_rest_insert_tax', $tax, $request, false );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $tax, $request );
		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * Delete a single tax.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {
		global $wpdb;

		$id    = (int) $request['id'];
		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for this type, error out.
		if ( ! $force ) {
			return new WP_Error( 'woocommerce_rest_trash_not_supported', __( 'Taxes do not support trashing.', 'woocommerce' ), array( 'status' => 501 ) );
		}

		$tax = WC_Tax::_get_tax_rate( $id, OBJECT );

		if ( empty( $id ) || empty( $tax ) ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource id.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $tax, $request );

		WC_Tax::_delete_tax_rate( $id );

		if ( 0 === $wpdb->rows_affected ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'The resource cannot be deleted.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a tax is deleted via the REST API.
		 *
		 * @param stdClass         $tax      The tax data.
		 * @param WP_REST_Response $response The response returned from the API.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'woocommerce_rest_delete_tax', $tax, $response, $request );

		return $response;
	}

	/**
	 * Prepare a single tax output for response.
	 *
	 * @param stdClass $tax Tax object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $tax, $request ) {
		global $wpdb;

		$id   = (int) $tax->tax_rate_id;
		$data = array(
			'id'       => $id,
			'country'  => $tax->tax_rate_country,
			'state'    => $tax->tax_rate_state,
			'postcode' => '',
			'city'     => '',
			'rate'     => $tax->tax_rate,
			'name'     => $tax->tax_rate_name,
			'priority' => (int) $tax->tax_rate_priority,
			'compound' => (bool) $tax->tax_rate_compound,
			'shipping' => (bool) $tax->tax_rate_shipping,
			'order'    => (int) $tax->tax_rate_order,
			'class'    => $tax->tax_rate_class ? $tax->tax_rate_class : 'standard',
		);

		// Get locales from a tax rate.
		$locales = $wpdb->get_results( $wpdb->prepare( "
			SELECT location_code, location_type
			FROM {$wpdb->prefix}woocommerce_tax_rate_locations
			WHERE tax_rate_id = %d
		", $id ) );

		if ( ! is_wp_error( $tax ) && ! is_null( $tax ) ) {
			foreach ( $locales as $locale ) {
				$data[ $locale->location_type ] = $locale->location_code;
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $tax ) );

		/**
		 * Filter tax object returned from the REST API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param stdClass         $tax      Tax object used to create response.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( 'woocommerce_rest_prepare_tax', $response, $tax, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param stdClass $tax Tax object.
	 * @return array Links for the given tax.
	 */
	protected function prepare_links( $tax ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $tax->tax_rate_id ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Bulk update or create items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array Of WP_Error or WP_REST_Response.
	 */
	public function bulk_items( $request ) {
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;

		// Get the request params.
		$items = $request->get_params();

		// Limit bulk operation.
		$limit = apply_filters( 'woocommerce_rest_bulk_items_limit', 100, 'taxes' );
		if ( count( $items ) > $limit ) {
			return new WP_Error( 'woocommerce_rest_request_entity_too_large', sprintf( __( 'Unable to accept more than %s items for this request.', 'woocommerce' ), $limit ), array( 'status' => 413 ) );
		}

		$response = array();

		foreach ( $items as $item ) {
			// Item exists.
			if ( ! empty( $item['id'] ) ) {
				$_item = new WP_REST_Request( 'PUT' );
				$_item->set_body_params( $item );
				$_response = $this->update_item( $_item );

			// Item don't exists.
			} else {
				$_item  = new WP_REST_Request( 'POST' );
				$_item->set_body_params( $item );
				$_response = $this->create_item( $_item );
			}

			if ( is_wp_error( $_response ) ) {
				$response[] = array(
					'id'    => $item['id'],
					'error' => array( 'code' => $_response->get_error_code(), 'message' => $_response->get_error_message(), 'data' => $_response->get_error_data() ),
				);
			} else {
				$response[] = $wp_rest_server->response_to_data( $_response, '' );
			}
		}

		return $response;
	}

	/**
	 * Get the Taxes schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'tax',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'country' => array(
					'description' => __( 'Country ISO 3166 code.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'state' => array(
					'description' => __( 'State code.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'postcode' => array(
					'description' => __( 'Postcode/ZIP.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'city' => array(
					'description' => __( 'City name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'rate' => array(
					'description' => __( 'Tax rate.', 'woocommerce' ),
					'type'        => 'float',
					'context'     => array( 'view', 'edit' ),
				),
				'name' => array(
					'description' => __( 'Tax rate name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'priority' => array(
					'description' => __( 'Tax priority.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => 1,
					'context'     => array( 'view', 'edit' ),
				),
				'compound' => array(
					'description' => __( 'Whether or not this is a compound rate.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'view', 'edit' ),
				),
				'shipping' => array(
					'description' => __( 'Whether or not this tax rate also gets applied to shipping.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => true,
					'context'     => array( 'view', 'edit' ),
				),
				'order' => array(
					'description' => __( 'Indicates the order that will appear in queries.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'class' => array(
					'description' => __( 'Tax class.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'standard',
					'enum'        => array_merge( array( 'standard' ), array_map( 'sanitize_title', WC_Tax::get_tax_classes() ) ),
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['exclude'] = array(
			'description'        => __( 'Ensure result set excludes specific ids.', 'woocommerce' ),
			'type'               => 'array',
			'default'            => array(),
			'sanitize_callback'  => 'wp_parse_id_list',
		);
		$params['include'] = array(
			'description'        => __( 'Limit result set to specific ids.', 'woocommerce' ),
			'type'               => 'array',
			'default'            => array(),
			'sanitize_callback'  => 'wp_parse_id_list',
		);
		$params['offset'] = array(
			'description'        => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
			'type'               => 'integer',
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['order'] = array(
			'default'            => 'asc',
			'description'        => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'enum'               => array( 'asc', 'desc' ),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['orderby'] = array(
			'default'            => 'order',
			'description'        => __( 'Sort collection by object attribute.', 'woocommerce' ),
			'enum'               => array(
				'id',
				'order',
			),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['class'] = array(
			'description'        => __( 'Sort by tax class.', 'woocommerce' ),
			'enum'               => array_merge( array( 'standard' ), array_map( 'sanitize_title', WC_Tax::get_tax_classes() ) ),
			'sanitize_callback'  => 'sanitize_title',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);

		return $params;
	}
}
