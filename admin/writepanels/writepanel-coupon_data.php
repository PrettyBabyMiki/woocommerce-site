<?php
/**
 * Coupon Data
 * 
 * Functions for displaying the coupon data meta box
 *
 * @author 		WooThemes
 * @category 	Admin Write Panels
 * @package 	WooCommerce
 */

/**
 * Coupon data meta box
 * 
 * Displays the meta box
 */
function woocommerce_coupon_data_meta_box($post) {

	wp_nonce_field( 'woocommerce_save_data', 'woocommerce_meta_nonce' );
	
	?>
	<style type="text/css">
		#edit-slug-box { display:none }
	</style>
	<div id="coupon_options" class="panel woocommerce_options_panel">
		<?php

			// Type
			$discount_types = apply_filters('woocommerce_coupon_discount_types', array(
    			'fixed_cart' 	=> __('Cart Discount', 'woothemes'),
    			'percent' 		=> __('Cart % Discount', 'woothemes'),
    			'fixed_product'	=> __('Product Discount', 'woothemes')
    		));
    		woocommerce_wp_select( array( 'id' => 'discount_type', 'label' => __('Discount type', 'woothemes'), 'options' => $discount_types ) );
				
			// Amount
			woocommerce_wp_text_input( array( 'id' => 'coupon_amount', 'label' => __('Coupon amount', 'woothemes'), 'placeholder' => __('0.00', 'woothemes'), 'description' => __('Enter an amount e.g. 2.99 or an integer for percentages e.g. 20%', 'woothemes') ) );
				
			// Individual use
			woocommerce_wp_checkbox( array( 'id' => 'individual_use', 'label' => __('Individual use', 'woothemes'), 'description' => __('Check this box if the coupon cannot be used in conjunction with other coupons', 'woothemes') ) );
			
			// Product ids
			woocommerce_wp_text_input( array( 'id' => 'product_ids', 'label' => __('Product IDs', 'woothemes'), 'placeholder' => __('N/A', 'woothemes'), 'description' => __('(optional) Comma separate product IDs which need to be in the cart to use this coupon, or for "Product Discounts" are discounted.', 'woothemes') ) );
			
			// Usage limit
			woocommerce_wp_text_input( array( 'id' => 'usage_limit', 'label' => __('Usage limit', 'woothemes'), 'placeholder' => __('Unlimited usage', 'woothemes'), 'description' => __('(optional) How many times this coupon can be used before it is void', 'woothemes') ) );
				
			// Expiry date
			woocommerce_wp_text_input( array( 'id' => 'expiry_date', 'label' => __('Expiry date', 'woothemes'), 'placeholder' => __('Never expire', 'woothemes'), 'description' => __('(optional) The date this coupon will expire, <code>YYYY-MM-DD</code>', 'woothemes'), 'class' => 'short date-picker' ) );
			
			do_action('woocommerce_coupon_options');
			
		?>
	</div>
	<?php	
}

/**
 * Coupon data meta box
 * 
 * Displays the meta box
 */
add_filter('enter_title_here', 'woocommerce_coupon_enter_title_here', 1, 2);

function woocommerce_coupon_enter_title_here( $text, $post ) {
	if ($post->post_type=='shop_coupon') return __('Coupon code', 'woothemes');
	return $text;
}

/**
 * Coupon Data Save
 * 
 * Function for processing and storing all coupon data.
 */
add_action('woocommerce_process_shop_coupon_meta', 'woocommerce_process_shop_coupon_meta', 1, 2);

function woocommerce_process_shop_coupon_meta( $post_id, $post ) {
	global $wpdb;
	
	$woocommerce_errors = array();
	
	if (!$_POST['coupon_amount']) $woocommerce_errors[] = __('Coupon amount is required', 'woothemes');
	if ($_POST['discount_type']=='fixed_product' && !$_POST['product_ids']) $woocommerce_errors[] = __('Product discount coupons require you to set "Product IDs" to work.', 'woothemes');

	// Add/Replace data to array
		$type 			= strip_tags(stripslashes( $_POST['discount_type'] ));
		$amount 		= strip_tags(stripslashes( $_POST['coupon_amount'] ));
		$product_ids 	= strip_tags(stripslashes( $_POST['product_ids'] ));
		$usage_limit 	= (isset($_POST['usage_limit']) && $_POST['usage_limit']>0) ? (int) $_POST['usage_limit'] : '';
		$individual_use = isset($_POST['individual_use']) ? 'yes' : 'no';
		$expiry_date 	= strip_tags(stripslashes( $_POST['expiry_date'] ));
	
	// Save
		update_post_meta( $post_id, 'discount_type', $type );
		update_post_meta( $post_id, 'coupon_amount', $amount );
		update_post_meta( $post_id, 'individual_use', $individual_use );
		update_post_meta( $post_id, 'product_ids', $product_ids );
		update_post_meta( $post_id, 'usage_limit', $usage_limit );
		update_post_meta( $post_id, 'expiry_date', $expiry_date );
		
		do_action('woocommerce_coupon_options');
	
	// Error Handling
		if (sizeof($woocommerce_errors)>0) update_option('woocommerce_errors', $woocommerce_errors);
}