<?php
/**
 * REST API Network Orders controller
 *
 * Handles requests to the /orders/network endpoint
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    @todo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Network Orders controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Orders_Controller
 */
class WC_REST_Network_Orders_Controller extends WC_REST_Orders_Controller {

	public function register_routes() {
		if ( is_multisite() ) {
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/network', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'network_orders' ),
					'permission_callback' => array( $this, 'network_orders_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			) );
		}
	}

	public function get_public_item_schema() {
		$schema = parent::get_public_item_schema();

		$schema['properties']['blog'] = array(
			'description' => __( 'Blog id of the record on the multisite.', 'woocommerce' ),
			'type'        => 'integer',
			'context'     => array( 'view' ),
			'readonly'    => true,
		);
		$schema['properties']['edit_url'] = array(
			'description' => __( 'URL to edit the order', 'woocommerce' ),
			'type'        => 'string',
			'context'     => array( 'view' ),
			'readonly'    => true,
		);
		$schema['properties']['buyer'][] = array(
			'description' => __( 'Name of the buyer for the order', 'woocommerce' ),
			'type'        => 'string',
			'context'     => array( 'view' ),
			'readonly'    => true,
		);
		$schema['properties']['status_name'][] = array(
			'description' => __( 'Order Status', 'woocommerce' ),
			'type'        => 'string',
			'context'     => array( 'view' ),
			'readonly'    => true,
		);
		$schema['properties']['formatted_total'][] = array(
			'description' => __( 'Order total formatted for locale', 'woocommerce' ),
			'type'        => 'string',
			'context'     => array( 'view' ),
			'readonly'    => true,
		);

		return $schema;
	}

	/**
	 * Does a permissions check for the proper requested blog
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool $permission
	 */
	public function network_orders_permissions_check( $request ) {
		$blog_id = $request->get_param( 'blog_id' );
		$blog_id = $blog_id ?: get_current_blog_id();

		switch_to_blog( $blog_id );

		$permission = $this->get_items_permissions_check( $request );

		restore_current_blog();

		return $permission;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function network_orders( $request ) {
		$blog_id = $request->get_param( 'blog_id' );
		$blog_id = $blog_id ?: get_current_blog_id();

		switch_to_blog( $blog_id );

		add_filter( 'woocommerce_rest_orders_prepare_object_query', array( $this, 'network_orders_filter_args' ) );
		$items = $this->get_items( $request );
		remove_filter( 'woocommerce_rest_orders_prepare_object_query', array( $this, 'network_orders_filter_args' ) );

		foreach ( $items->data as &$current_order ) {
			$order = wc_get_order( $current_order['id'] );

			$current_order['blog'] = get_blog_details( get_current_blog_id() );
			$current_order['edit_url'] = get_admin_url( $blog_id, 'post.php?post=' . absint( $order->get_id() ) . '&action=edit' );
			/* translators: 1: first name 2: last name */
			$current_order['buyer'] = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce' ), $order->get_billing_first_name(), $order->get_billing_last_name() ) );
			$current_order['status_name'] = wc_get_order_status_name( $order->get_status() );
			$current_order['formatted_total'] = $order->get_formatted_order_total();
		}

		restore_current_blog();

		return $items;
	}

	public function network_orders_filter_args( $args ) {
		$args['post_status'] = array(
			'wc-on-hold',
			'wc-processing',
		);

		return $args;
	}
}
