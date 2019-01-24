<?php
/**
 * REST API Product Categories controller
 *
 * Handles requests to the products/categories endpoint.
 *
 * @package WooCommerce/API
 * @since   2.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Product Categories controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Product_Categories_V2_Controller
 */
class WC_REST_Product_Categories_Controller extends WC_REST_Product_Categories_V2_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Prepare a single product category output for response.
	 *
	 * @param WP_Term         $item    Term object.
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		// Get category display type.
		$display_type = get_woocommerce_term_meta( $item->term_id, 'display_type' );

		// Get category order.
		$menu_order = get_woocommerce_term_meta( $item->term_id, 'order' );

		$data = array(
			'id'          => (int) $item->term_id,
			'name'        => $item->name,
			'slug'        => $item->slug,
			'parent'      => (int) $item->parent,
			'description' => $item->description,
			'display'     => $display_type ? $display_type : 'default',
			'image'       => null,
			'menu_order'  => (int) $menu_order,
			'count'       => (int) $item->count,
		);

		// Get category image.
		$image_id = get_woocommerce_term_meta( $item->term_id, 'thumbnail_id' );
		if ( $image_id ) {
			$attachment = get_post( $image_id );

			$data['image'] = array(
				'id'                => (int) $image_id,
				'date_created'      => wc_rest_prepare_date_response( $attachment->post_date ),
				'date_created_gmt'  => wc_rest_prepare_date_response( $attachment->post_date_gmt ),
				'date_modified'     => wc_rest_prepare_date_response( $attachment->post_modified ),
				'date_modified_gmt' => wc_rest_prepare_date_response( $attachment->post_modified_gmt ),
				'src'               => wp_get_attachment_url( $image_id ),
				'name'              => get_the_title( $attachment ),
				'alt'               => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
			);
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item, $request ) );

		/**
		 * Filter a term item returned from the API.
		 *
		 * Allows modification of the term data right before it is returned.
		 *
		 * @param WP_REST_Response  $response  The response object.
		 * @param object            $item      The original term object.
		 * @param WP_REST_Request   $request   Request used to generate the response.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->taxonomy}", $response, $item, $request );
	}

	/**
	 * Get the Category schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->taxonomy,
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'        => array(
					'description' => __( 'Category name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'slug'        => array(
					'description' => __( 'An alphanumeric identifier for the resource unique to its type.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'parent'      => array(
					'description' => __( 'The ID for the parent of the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'description' => array(
					'description' => __( 'HTML description of the resource.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'wp_filter_post_kses',
					),
				),
				'display'     => array(
					'description' => __( 'Category archive display type.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'default',
					'enum'        => array( 'default', 'products', 'subcategories', 'both' ),
					'context'     => array( 'view', 'edit' ),
				),
				'image'       => array(
					'description' => __( 'Image data.', 'woocommerce' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id'                => array(
							'description' => __( 'Image ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'date_created'      => array(
							'description' => __( "The date the image was created, in the site's timezone.", 'woocommerce' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'date_created_gmt'  => array(
							'description' => __( 'The date the image was created, as GMT.', 'woocommerce' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'date_modified'     => array(
							'description' => __( "The date the image was last modified, in the site's timezone.", 'woocommerce' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'date_modified_gmt' => array(
							'description' => __( 'The date the image was last modified, as GMT.', 'woocommerce' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'src'               => array(
							'description' => __( 'Image URL.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'uri',
							'context'     => array( 'view', 'edit' ),
						),
						'name'             => array(
							'description' => __( 'Image name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'alt'               => array(
							'description' => __( 'Image alternative text.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'menu_order'  => array(
					'description' => __( 'Menu order, used to custom sort the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'count'       => array(
					'description' => __( 'Number of published products for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Update term meta fields.
	 *
	 * @param WP_Term         $term    Term object.
	 * @param WP_REST_Request $request Request instance.
	 * @return bool|WP_Error
	 *
	 * @since 3.5.5
	 */
	protected function update_term_meta_fields( $term, $request ) {
		$id = (int) $term->term_id;

		if ( isset( $request['display'] ) ) {
			update_woocommerce_term_meta( $id, 'display_type', 'default' === $request['display'] ? '' : $request['display'] );
		}

		if ( isset( $request['menu_order'] ) ) {
			update_woocommerce_term_meta( $id, 'order', $request['menu_order'] );
		}

		if ( isset( $request['image'] ) ) {
			if ( empty( $request['image']['id'] ) && ! empty( $request['image']['src'] ) ) {
				$upload = wc_rest_upload_image_from_url( esc_url_raw( $request['image']['src'] ) );

				if ( is_wp_error( $upload ) ) {
					return $upload;
				}

				$image_id = wc_rest_set_uploaded_image_as_attachment( $upload );
			} else {
				$image_id = isset( $request['image']['id'] ) ? absint( $request['image']['id'] ) : 0;
			}

			// Check if image_id is a valid image attachment before updating the term meta.
			if ( $image_id && wp_attachment_is_image( $image_id ) ) {
				update_woocommerce_term_meta( $id, 'thumbnail_id', $image_id );

				// Set the image alt.
				if ( ! empty( $request['image']['alt'] ) ) {
					update_post_meta( $image_id, '_wp_attachment_image_alt', wc_clean( $request['image']['alt'] ) );
				}

				// Set the image title.
				if ( ! empty( $request['image']['name'] ) ) {
					wp_update_post( array(
						'ID'         => $image_id,
						'post_title' => wc_clean( $request['image']['name'] ),
					) );
				}
			} else {
				delete_woocommerce_term_meta( $id, 'thumbnail_id' );
			}
		}

		return true;
	}
}
