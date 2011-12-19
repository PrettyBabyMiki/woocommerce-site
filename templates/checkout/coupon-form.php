<?php
/**
 * Checkout Coupon Form
 */
if (get_option('woocommerce_enable_coupon_form_on_checkout')=="no") return;

$info_message = apply_filters('woocommerce_checkout_coupon_message', __('Have a coupon?', 'woothemes'));
?>

<p class="info"><?php echo $info_message; ?> <a href="#" class="showcoupon"><?php _e('Click here to enter your code', 'woothemes'); ?></a></p>

<form class="checkout_coupon" method="post">

	<p class="form-row form-row-first">
		<input name="coupon_code" class="input-text" placeholder="<?php _e('Coupon code', 'woothemes'); ?>" id="coupon_code" value="" />
	</p>

	<p class="form-row form-row-last">
		<input type="submit" class="button" name="apply_coupon" value="<?php _e('Apply Coupon', 'woothemes'); ?>" />
	</p>
	
	<div class="clear"></div>
</form>