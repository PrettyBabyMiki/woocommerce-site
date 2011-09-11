<?php
/**
 * Cart Shortcode
 * 
 * Used on the cart page, the cart shortcode displays the cart contents and interface for coupon codes and other cart bits and pieces.
 *
 * @package		WooCommerce
 * @category	Shortcode
 * @author		WooThemes
 */
 
function get_woocommerce_cart( $atts ) {
	global $woocommerce;
	return $woocommerce->shortcode_wrapper('woocommerce_cart', $atts);
}

function woocommerce_cart( $atts ) {
	global $woocommerce;
	$errors = array();
	$validation = &new woocommerce_validation();
	
	// Process Discount Codes
	if (isset($_POST['apply_coupon']) && $_POST['apply_coupon'] && $woocommerce->verify_nonce('cart')) :
	
		$coupon_code = stripslashes(trim($_POST['coupon_code']));
		$woocommerce->cart->add_discount($coupon_code);

	// Update Shipping
	elseif (isset($_POST['calc_shipping']) && $_POST['calc_shipping'] && $woocommerce->verify_nonce('cart')) :

		unset($_SESSION['_chosen_shipping_method']);
		$country 	= $_POST['calc_shipping_country'];
		$state 		= $_POST['calc_shipping_state'];
		
		$postcode 	= $_POST['calc_shipping_postcode'];
		
		if ($postcode && !$validation->is_postcode( $postcode, $country )) : 
			$woocommerce->add_error( __('Please enter a valid postcode/ZIP.', 'woothemes') ); 
			$postcode = '';
		elseif ($postcode) :
			$postcode = $validation->format_postcode( $postcode, $country );
		endif;
		
		if ($country) :
		
			// Update customer location
			$woocommerce->customer->set_location( $country, $state, $postcode );
			$woocommerce->customer->set_shipping_location( $country, $state, $postcode );
			
			// Re-calc price
			$woocommerce->cart->calculate_totals();
			
			$woocommerce->add_message(  __('Shipping costs updated.', 'woothemes') );
		
		else :
		
			$woocommerce->customer->set_shipping_location( '', '', '' );
			
			$woocommerce->add_message(  __('Shipping costs updated.', 'woothemes') );
			
		endif;
			
	endif;
	
	$result = $woocommerce->cart->check_cart_item_stock();
	if (is_wp_error($result)) :
		$woocommerce->add_error( $result->get_error_message() );
	endif;
	
	$woocommerce->show_messages();
	
	if (sizeof($woocommerce->cart->cart_contents)==0) :
		echo '<p>'.__('Your cart is currently empty.', 'woothemes').'</p>';
		do_action('woocommerce_empty_cart');
		echo '<p><a class="button" href="'.get_permalink(get_option('woocommerce_shop_page_id')).'">'.__('&larr; Return To Shop', 'woothemes').'</a></p>';
		return;
	endif;
	
	?>
	<form action="<?php echo $woocommerce->cart->get_cart_url(); ?>" method="post">
	<table class="shop_table cart" cellspacing="0">
		<thead>
			<tr>
				<th class="product-remove"></th>
				<th class="product-thumbnail"></th>
				<th class="product-name"><span class="nobr"><?php _e('Product Name', 'woothemes'); ?></span></th>
				<th class="product-price"><span class="nobr"><?php _e('Unit Price', 'woothemes'); ?></span></th>
				<th class="product-quantity"><?php _e('Quantity', 'woothemes'); ?></th>
				<th class="product-subtotal"><?php _e('Price', 'woothemes'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			if (sizeof($woocommerce->cart->cart_contents)>0) : 
				foreach ($woocommerce->cart->cart_contents as $cart_item_key => $values) :
					$_product = $values['data'];
					if ($_product->exists() && $values['quantity']>0) :
					
						?>
						<tr>
							<td class="product-remove"><a href="<?php echo $woocommerce->cart->get_remove_url($cart_item_key); ?>" class="remove" title="<?php _e('Remove this item', 'woothemes'); ?>">&times;</a></td>
							<td class="product-thumbnail">
								<a href="<?php echo get_permalink($values['product_id']); ?>">
								<?php 
									if ($values['variation_id'] && has_post_thumbnail($values['variation_id'])) :
										echo get_the_post_thumbnail($values['variation_id'], 'shop_thumbnail'); 
									elseif (has_post_thumbnail($values['product_id'])) :
										echo get_the_post_thumbnail($values['product_id'], 'shop_thumbnail'); 
									else :
										echo '<img src="'.$woocommerce->plugin_url(). '/assets/images/placeholder.png" alt="Placeholder" width="'.$woocommerce->get_image_size('shop_thumbnail_image_width').'" height="'.$woocommerce->get_image_size('shop_thumbnail_image_height').'" />'; 
									endif;
								?>
								</a>
							</td>
							<td class="product-name">
								<a href="<?php echo get_permalink($values['product_id']); ?>"><?php echo apply_filters('woocommerce_cart_product_title', $_product->get_title(), $_product); ?></a>
								<?php
									if($_product instanceof woocommerce_product_variation && is_array($values['variation'])) :
                            			echo woocommerce_get_formatted_variation( $values['variation'] );
                       				endif;
								?>
							</td>
							<td class="product-price"><?php echo woocommerce_price($_product->get_price()); ?></td>
							<td class="product-quantity"><div class="quantity"><input name="cart[<?php echo $cart_item_key; ?>][qty]" value="<?php echo $values['quantity']; ?>" size="4" title="Qty" class="input-text qty text" maxlength="12" /></div></td>
							<td class="product-subtotal"><?php echo woocommerce_price($_product->get_price()*$values['quantity']); ?></td>
						</tr>
						<?php
					endif;
				endforeach; 
			endif;
			
			do_action( 'woocommerce_shop_table_cart' );
			?>
			<tr>
				<td colspan="6" class="actions">
					<div class="coupon">
						<label for="coupon_code"><?php _e('Coupon', 'woothemes'); ?>:</label> <input name="coupon_code" class="input-text" id="coupon_code" value="" /> <input type="submit" class="button" name="apply_coupon" value="<?php _e('Apply Coupon', 'woothemes'); ?>" />
					</div>
					<?php $woocommerce->nonce_field('cart') ?>
					<input type="submit" class="button" name="update_cart" value="<?php _e('Update Shopping Cart', 'woothemes'); ?>" /> <a href="<?php echo $woocommerce->cart->get_checkout_url(); ?>" class="checkout-button button alt"><?php _e('Proceed to Checkout &rarr;', 'woothemes'); ?></a>
				</td>
			</tr>
		</tbody>
	</table>
	</form>
	<div class="cart-collaterals">
		
		<?php do_action('cart-collaterals'); ?>
		
		<?php woocommerce_cart_totals(); ?>
		
		<?php woocommerce_shipping_calculator(); ?>
		
	</div>
	<?php		
}