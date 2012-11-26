<?php
/**
 * Cash on Delivery Gateway
 *
 * Provides a Cash on Delivery Payment Gateway.
 *
 * @class 		WC_COD
 * @extends		WC_Payment_Gateway
 * @version		1.6.4
 * @package		WooCommerce/Classes/Payment
 * @author 		Patrick Garman
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_COD extends WC_Payment_Gateway {

    /**
     * Constructor for the gateway.
     *
     * @access public
     * @return void
     */
	function __construct() {
		$this->id = 'cod';
		$this->icon 		= apply_filters('woocommerce_cod_icon', '');
		$this->method_title = __( 'Cash on Delivery', 'woocommerce' );
		$this->has_fields 	= false;

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->title = $this->settings['title'];
		$this->description = $this->settings['description'];
		$this->instructions = $this->settings['instructions'];
		$this->enable_for_methods = empty( $this->settings['enable_for_methods'] ) ? array() : $this->settings['enable_for_methods'];

		add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
		add_action('woocommerce_thankyou_cod', array(&$this, 'thankyou'));
	}


	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @access public
	 * @return void
	 */
	function admin_options() {
		?>
		<h3><?php _e('Cash on Delivery','woocommerce'); ?></h3>
    	<p><?php _e('Have your customers pay with cash (or by other means) upon delivery.', 'woocommerce' ); ?></p>
    	<table class="form-table">
    		<?php $this->generate_settings_html(); ?>
		</table> <?php
    }


    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    	global $woocommerce;
    	
    	$shipping_methods = array();
    	
    	foreach ( $woocommerce->shipping->load_shipping_methods() as $method ) {
	    	$shipping_methods[ $method->id ] = $method->get_title();
    	}
    
    	$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Enable COD', 'woocommerce' ),
				'label' => __( 'Enable Cash on Delivery', 'woocommerce' ),
				'type' => 'checkbox',
				'description' => '',
				'default' => 'no'
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'Payment method title that the customer will see on your website.', 'woocommerce' ),
				'default' => __( 'Cash on Delivery', 'woocommerce' )
			),
			'description' => array(
				'title' => __( 'Description', 'woocommerce' ),
				'type' => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your website.', 'woocommerce' ),
				'default' => 'Pay with cash upon delivery.'
			),
			'instructions' => array(
				'title' => __( 'Instructions', 'woocommerce' ),
				'type' => 'textarea',
				'description' => __( 'Instructions that will be added to the thank you page.', 'woocommerce' ),
				'default' => 'Pay with cash upon delivery.'
			),
			'enable_for_methods' => array(
				'title' 		=> __( 'Enable for shipping methods', 'woocommerce' ),
				'type' 			=> 'multiselect',
				'class'			=> 'chosen_select',
				'css'			=> 'width: 450px;',
				'default' 		=> '',
				'description' 	=> __( 'If COD is only available for certain methods, set it up here. Leave blank to enable for all methods.', 'woocommerce' ),
				'options'		=> $shipping_methods
			)
 	   );
    }


	/**
	 * Check If The Gateway Is Available For Use
	 *
	 * @access public
	 * @return bool
	 */
	function is_available() {
		global $woocommerce;
		
		if ( ! empty( $this->enable_for_methods ) ) {
			
			if ( is_page( woocommerce_get_page_id( 'pay' ) ) ) {
				
				$order_id = (int) $_GET['order_id'];
				$order = new WC_Order( $order_id );
		
				if ( ! $order->shipping_method ) 
					return false;
					
				$chosen_method = $order->shipping_method;
				
			} elseif ( empty( $woocommerce->session->chosen_shipping_method ) ) {
				return false;
			} else {
				$chosen_method = $woocommerce->session->chosen_shipping_method;
			}
				
			$found = false;
			
			foreach ( $this->enable_for_methods as $method_id ) {
				if ( strpos( $chosen_method, $method_id ) === 0 ) {
					$found = true;
					break;
				}
			}
			
			if ( ! $found )
				return false;
		}
		
		return parent::is_available();
	}
	

    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
	function process_payment ($order_id) {
		global $woocommerce;

		$order = new WC_Order( $order_id );

		// Mark as on-hold (we're awaiting the cheque)
		$order->update_status('on-hold', __( 'Payment to be made upon delivery.', 'woocommerce' ));

		// Reduce stock levels
		$order->reduce_order_stock();

		// Remove cart
		$woocommerce->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result' 	=> 'success',
			'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(woocommerce_get_page_id('thanks'))))
		);
	}


    /**
     * Output for the order received page.
     *
     * @access public
     * @return void
     */
	function thankyou() {
		if ($this->instructions!='') { echo wpautop($this->instructions); }
	}

}


/**
 * Add the gateway to WooCommerce
 *
 * @access public
 * @param array $methods
 * @package		WooCommerce/Classes/Payment
 * @return array
 */
function woocommerce_cod_add_gateway( $methods ) {
	$methods[] = 'WC_COD';
	return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'woocommerce_cod_add_gateway' );