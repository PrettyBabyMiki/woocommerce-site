<?php
/**
 * REST API Product Attribute Terms controller
 *
 * Handles requests to the products/attributes/<attribute_id>/terms endpoint.
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
 * REST API Product Attribute Terms controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Product_Attribute_Terms_V1_Controller
 */
class WC_REST_Product_Attribute_Terms_Controller extends WC_REST_Product_Attribute_Terms_V1_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v2';
}
