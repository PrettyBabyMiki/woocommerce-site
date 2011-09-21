<?php
/**
 * WooCommerce Emails
 * 
 * Email handling for important shop events.
 *
 * @package		WooCommerce
 * @category	Emails
 * @author		WooThemes
 */


/**
 * Mail from name/email
 **/
add_filter( 'wp_mail_from', 'woocommerce_mail_from' );
add_filter( 'wp_mail_from_name', 'woocommerce_mail_from_name' );

function woocommerce_mail_from_name( $name ) {
	$name = get_bloginfo('name');
	$name = esc_attr($name);
	return $name;
}
function woocommerce_mail_from( $email ) {
	$email = get_option('admin_email');
	return $email;
}


/**
 * HTML email template for standard WordPress emails
 **/
add_action('phpmailer_init', 'woocommerce_email_template');

function woocommerce_email_template( $phpmailer ) {
	
	if (!strstr($phpmailer->Body, '<html>')) :
		
		// Standard WordPress email
		global $email_heading;
		
		$subject = $phpmailer->Subject;
		$subject = str_replace('['.get_bloginfo('name').'] ', '', $subject);
		
		$email_heading = $subject;
		
		$content = nl2br(wptexturize($phpmailer->Body));
		
		// Buffer
		ob_start();
		
		// Get mail template
		woocommerce_email_header();
		echo $content;
		woocommerce_email_footer();
		
		// Get contents
		$message = ob_get_clean();
		
		$phpmailer->Body = $message;
		
	else :
		
		// Email already using custom template
		
	endif;
	
	return $phpmailer;

}


/**
 * Email Header
 **/
add_action('woocommerce_email_header', 'woocommerce_email_header');

function woocommerce_email_header() {
	woocommerce_get_template('emails/email_header.php', false);
}


/**
 * Email Footer
 **/
add_action('woocommerce_email_footer', 'woocommerce_email_footer');

function woocommerce_email_footer() {
	woocommerce_get_template('emails/email_footer.php', false);
}
	
	
/**
 * HTML email type
 **/
add_filter('wp_mail_content_type', 'woocommerce_email_content_type');

function woocommerce_email_content_type($content_type){
	return 'text/html';
}


/**
 * Fix recieve password mail links
 **/
function woocommerce_retrieve_password_message($content){
	return htmlspecialchars($content);
}
add_filter('retrieve_password_message', 'woocommerce_retrieve_password_message');
	

/**
 * Hooks for emails
 **/
add_action('woocommerce_low_stock_notification', 'woocommerce_low_stock_notification');
add_action('woocommerce_no_stock_notification', 'woocommerce_no_stock_notification');
add_action('woocommerce_product_on_backorder_notification', 'woocommerce_product_on_backorder_notification', 1, 2);
 
 
/**
 * New order notification email template
 **/
add_action('woocommerce_order_status_pending_to_processing', 'woocommerce_new_order_notification');
add_action('woocommerce_order_status_pending_to_completed', 'woocommerce_new_order_notification');
add_action('woocommerce_order_status_pending_to_on-hold', 'woocommerce_new_order_notification');

function woocommerce_new_order_notification( $id ) {
	
	global $order_id, $email_heading;
	
	$order_id = $id;
	
	$email_heading = __('New Customer Order', 'woothemes');
	
	$subject = sprintf(__('[%s] New Customer Order (# %s)', 'woothemes'), get_bloginfo('name'), $order_id);
	
	// Buffer
	ob_start();
	
	// Get mail template
	woocommerce_get_template('emails/new_order.php', false);
	
	// Get contents
	$message = ob_get_clean();
	
	// Send the mail	
	wp_mail( get_option('admin_email'), $subject, $message );
	
}


/**
 * Processing order notification email template
 **/
add_action('woocommerce_order_status_pending_to_processing', 'woocommerce_processing_order_customer_notification');
add_action('woocommerce_order_status_pending_to_on-hold', 'woocommerce_processing_order_customer_notification');
 
function woocommerce_processing_order_customer_notification( $id ) {
	
	global $order_id, $email_heading;
	
	$order_id = $id;
	
	$order = &new woocommerce_order( $order_id );
	
	$email_heading = __('Order Received', 'woothemes');
	
	$subject = '[' . get_bloginfo('name') . '] ' . __('Order Received', 'woothemes');
	
	// Buffer
	ob_start();
	
	// Get mail template
	woocommerce_get_template('emails/customer_processing_order.php', false);
	
	// Get contents
	$message = ob_get_clean();

	// Send the mail	
	wp_mail( $order->billing_email, $subject, $message );
}


/**
 * Completed order notification email template - this one includes download links for downloadable products
 **/
add_action('woocommerce_order_status_completed', 'woocommerce_completed_order_customer_notification');
 
function woocommerce_completed_order_customer_notification( $id ) {
	
	global $order_id, $email_heading;
	
	$order_id = $id;
	
	$order = &new woocommerce_order( $order_id );
	
	$email_heading = __('Order Complete', 'woothemes');

	$subject = '[' . get_bloginfo('name') . '] ' . __('Order Complete', 'woothemes');
	
	// Buffer
	ob_start();
	
	// Get mail template
	woocommerce_get_template('emails/customer_completed_order.php', false);
	
	// Get contents
	$message = ob_get_clean();

	// Send the mail	
	wp_mail( $order->billing_email, $subject, $message );
}


/**
 * Pay for order notification email template - this one includes a payment link
 **/
function woocommerce_pay_for_order_customer_notification( $id ) {
	
	global $order_id, $email_heading;
	
	$order_id = $id;
	
	$order = &new woocommerce_order( $order_id );
	
	$email_heading = __('Pay for Order', 'woothemes');

	$subject = '[' . get_bloginfo('name') . '] ' . __('Pay for Order', 'woothemes');

	// Buffer
	ob_start();
	
	// Get mail template
	woocommerce_get_template('emails/customer_pay_for_order.php', false);
	
	// Get contents
	$message = ob_get_clean();

	// Send the mail	
	wp_mail( $order->billing_email, $subject, $message );
}


/**
 * Low stock notification email
 **/
function woocommerce_low_stock_notification( $product ) {
	$_product = &new woocommerce_product($product);
	$subject = '[' . get_bloginfo('name') . '] ' . __('Product low in stock', 'woothemes');
	$message = '#' . $_product->id .' '. $_product->get_title() . ' ('. $_product->sku.') ' . __('is low in stock.', 'woothemes');
	$message = wordwrap( html_entity_decode( strip_tags( $message ) ), 70 );
	wp_mail( get_option('admin_email'), $subject, $message );
}


/**
 * No stock notification email
 **/
function woocommerce_no_stock_notification( $product ) {
	$_product = &new woocommerce_product($product);
	$subject = '[' . get_bloginfo('name') . '] ' . __('Product out of stock', 'woothemes');
	$message = '#' . $_product->id .' '. $_product->get_title() . ' ('. $_product->sku.') ' . __('is out of stock.', 'woothemes');
	$message = wordwrap( html_entity_decode( strip_tags( $message ) ), 70 );
	wp_mail( get_option('admin_email'), $subject, $message );
}


/**
 * Backorder notification email
 **/
function woocommerce_product_on_backorder_notification( $product, $amount ) {
	$_product = &new woocommerce_product($product);
	$subject = '[' . get_bloginfo('name') . '] ' . __('Product Backorder', 'woothemes');
	$message = $amount . __(' units of #', 'woothemes') . $_product->id .' '. $_product->get_title() . ' ('. $_product->sku.') ' . __('have been backordered.', 'woothemes');
	$message = wordwrap( html_entity_decode( strip_tags( $message ) ), 70 );
	wp_mail( get_option('admin_email'), $subject, $message );
}