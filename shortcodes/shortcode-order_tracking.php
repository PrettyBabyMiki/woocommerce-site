<?php
/**
 * Order Tracking Shortcode
 * 
 * Lets a user see the status of an order by entering their order details.
 *
 * @package		WooCommerce
 * @category	Shortcode
 * @author		WooThemes
 */
function get_woocommerce_order_tracking($atts) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_order_tracking', $atts); 
}

function woocommerce_order_tracking( $atts ) {
	global $woocommerce;
	
	$woocommerce->nocache();

	extract(shortcode_atts(array(
	), $atts));
	
	global $post;
	
	if ( ! empty( $_POST ) ) {
		
		$woocommerce->verify_nonce( 'order_tracking' );
		
		$order_id 		= empty( $_POST['orderid'] ) ? 0 : absint( $_POST['orderid'] );
		$order_email	= empty( $_POST['order_email'] ) ? '' : esc_attr( $_POST['order_email']) ;
		
		if ( ! $order_id ) {
			
			echo '<p class="woocommerce_error">' . __('Please enter a valid order ID', 'woocommerce') . '</p>';
			
		} elseif ( ! $order_email ) {
			
			echo '<p class="woocommerce_error">' . __('Please enter a valid order email', 'woocommerce') . '</p>';
			
		} else {
		
			$order = new WC_Order( apply_filters( 'woocommerce_shortcode_order_tracking_order_id', $order_id ) );
		
			if ( $order->id && $order_email ) {
	
				if ( strtolower( $order->billing_email ) == strtolower( $order_email ) ) {
				
					woocommerce_get_template( 'order/tracking.php', array(
						'order' => $order
					) );
					
					return;
				}
						
			} else {
				
				echo '<p class="woocommerce_error">' . sprintf( __('Sorry, we could not find that order id in our database.', 'woocommerce'), get_permalink($post->ID ) ) . '</p>';
				
			}
		
		}
		
	}
	
	woocommerce_get_template( 'order/form-tracking.php' );
	
}