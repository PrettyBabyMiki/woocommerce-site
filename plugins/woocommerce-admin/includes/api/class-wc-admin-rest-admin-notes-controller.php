<?php
/**
 * REST API Admin Notes controller
 *
 * Handles requests to the admin notes endpoint.
 *
 * @package WooCommerce Admin/API
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Admin Notes controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_CRUD_Controller
 */
class WC_Admin_REST_Admin_Notes_Controller extends WC_REST_CRUD_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'admin/notes';

	/**
	 * Register the routes for admin notes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique ID for the resource.', 'wc-admin' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get a single note.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$note = WC_Admin_Notes::get_note( $request->get_param( 'id' ) );
		if ( is_wp_error( $note ) ) {
			return $note;
		}

		$data = $note->get_data();
		$data = $this->prepare_item_for_response( $data, $request );
		$data = $this->prepare_response_for_collection( $data );

		return rest_ensure_response( $data );
	}

	/**
	 * Get all notes.
	 *
	 * @param WP_REST_Request $request Request data.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$per_page = isset( $request['per_page'] ) ? intval( $request['per_page'] ) : 10;
		if ( $per_page <= 0 ) {
			$per_page = 10;
		}

		$page = isset( $request['page'] ) ? intval( $request['page'] ) : 1;
		if ( $page <= 0 ) {
			$page = 1;
		}

		$args = array(
			'per_page' => $per_page,
			'page'     => $page,
		);

		$type = isset( $request['type'] ) ? $request['type'] : '';
		$type = sanitize_text_field( $type );
		if ( ! empty( $type ) ) {
			$args['type'] = $type;
		}

		$notes = WC_Admin_Notes::get_notes( 'edit', $args );

		$data = array();
		foreach ( (array) $notes as $note_obj ) {
			$note   = $this->prepare_item_for_response( $note_obj, $request );
			$note   = $this->prepare_response_for_collection( $note );
			$data[] = $note;
		}

		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', WC_Admin_Notes::get_notes_count() );

		return $response;
	}

	/**
	 * Check whether a given request has permission to read a single note.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'wc-admin' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check whether a given request has permission to read notes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'system_status', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'wc-admin' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Prepare a path or query for serialization to the client.
	 *
	 * @param string $query The query, path, or URL to transform.
	 * @return string A fully formed URL.
	 */
	public function prepare_query_for_response( $query ) {
		if ( 'https://' === substr( $query, 0, 8 ) ) {
			return $query;
		}
		if ( 'http://' === substr( $query, 0, 7 ) ) {
			return $query;
		}
		if ( '?' === substr( $query, 0, 1 ) ) {
			return admin_url( 'admin.php' . $query );
		}

		return admin_url( $query );
	}

	/**
	 * Prepare a note object for serialization.
	 *
	 * @param array           $data Note data.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $data, $request ) {
		$context                   = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data                      = $this->add_additional_fields_to_object( $data, $request );
		$data['date_created_gmt']  = wc_rest_prepare_date_response( $data['date_created'] );
		$data['date_created']      = wc_rest_prepare_date_response( $data['date_created'], false );
		$data['date_reminder_gmt'] = wc_rest_prepare_date_response( $data['date_reminder'] );
		$data['date_reminder']     = wc_rest_prepare_date_response( $data['date_reminder'], false );
		$data['title']             = stripslashes( $data['title'] );
		$data['content']           = stripslashes( $data['content'] );
		foreach ( (array) $data['actions'] as $key => $value ) {
			$data['actions'][ $key ]->label = stripslashes( $data['actions'][ $key ]->label );
			$data['actions'][ $key ]->url   = $this->prepare_query_for_response( $data['actions'][ $key ]->query );
		}
		$data = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$response->add_links(
			array(
				'self'       => array(
					'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $data['id'] ) ),
				),
				'collection' => array(
					'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
				),
			)
		);
		/**
		 * Filter a note returned from the API.
		 *
		 * Allows modification of the note data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $data The original note.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_admin_note', $response, $data, $request );
	}

	/**
	 * Get the note's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'note',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __( 'ID of the note record.', 'wc-admin' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'              => array(
					'description' => __( 'Name of the note.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'type'              => array(
					'description' => __( 'The type of the note (e.g. error, warning, etc.).', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'locale'            => array(
					'description' => __( 'Locale used for the note title and content.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'             => array(
					'description' => __( 'Title of the note.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'content'           => array(
					'description' => __( 'Content of the note.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'icon'              => array(
					'description' => __( 'Icon (gridicon) for the note.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'content_data'      => array(
					'description' => __( 'Content data for the note. JSON string. Available for re-localization.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'            => array(
					'description' => __( 'The status of the note (e.g. unactioned, actioned).', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'source'            => array(
					'description' => __( 'Source of the note.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created'      => array(
					'description' => __( 'Date the note was created.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'  => array(
					'description' => __( 'Date the note was created (GMT).', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_reminder'     => array(
					'description' => __( 'Date after which the user should be reminded of the note, if any.', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_reminder_gmt' => array(
					'description' => __( 'Date after which the user should be reminded of the note, if any (GMT).', 'wc-admin' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'actions'           => array(
					'description' => __( 'An array of actions, if any, for the note.', 'wc-admin' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);
		return $this->add_additional_fields_schema( $schema );
	}
}
