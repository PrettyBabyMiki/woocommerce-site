<?php
/**
 * REST API Customer Downloads controller
 *
 * Handles requests to the /customers/<customer_id>/downloads endpoint.
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
 * REST API Customers controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Customer_Downloads_V1_Controller
 */
class WC_REST_Customer_Downloads_Controller extends WC_REST_Customer_Downloads_V1_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';
}
