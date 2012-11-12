<?php
/**
 * Review order form
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

$available_methods = $woocommerce->shipping->get_available_shipping_methods();
?>
<div id="order_review">

	<table class="shop_table">
		<thead>
			<tr>
				<th class="product-name"><?php _e( 'Product', 'woocommerce' ); ?></th>
				<th class="product-quantity"><?php _e( 'Qty', 'woocommerce' ); ?></th>
				<th class="product-total"><?php _e( 'Totals', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tfoot>

			<tr class="cart-subtotal">
				<th colspan="2"><strong><?php _e( 'Cart Subtotal', 'woocommerce' ); ?></strong></th>
				<td><?php echo $woocommerce->cart->get_cart_subtotal(); ?></td>
			</tr>

			<?php if ($woocommerce->cart->get_discounts_before_tax()) : ?>

			<tr class="discount">
				<th colspan="2"><?php _e( 'Cart Discount', 'woocommerce' ); ?></th>
				<td>-<?php echo $woocommerce->cart->get_discounts_before_tax(); ?></td>
			</tr>

			<?php endif; ?>

			<?php if ( $woocommerce->cart->needs_shipping() && $woocommerce->cart->show_shipping() ) : ?>

				<?php do_action('woocommerce_review_order_before_shipping'); ?>
	
				<tr class="shipping">
					<th colspan="2"><?php _e( 'Shipping', 'woocommerce' ); ?></th>
					<td><?php woocommerce_get_template( 'cart/shipping-methods.php', array( 'available_methods' => $available_methods ) ); ?></td>
				</tr>
	
				<?php do_action('woocommerce_review_order_after_shipping'); ?>

			<?php endif; ?>
			
			<?php foreach ( $woocommerce->cart->get_fees() as $fee ) : ?>
					
				<tr class="fee fee-<?php echo $fee->id ?>">
					<th colspan="2"><?php echo $fee->name ?></th>
					<td><?php 
						if ( $woocommerce->cart->display_totals_ex_tax || ! $woocommerce->cart->prices_include_tax )
							echo woocommerce_price( $fee->amount );
						else
							echo woocommerce_price( $fee->amount + $fee->tax );
					?></td>
				</tr>
				
			<?php endforeach; ?>

			<?php
				// Show the tax row if showing prices exlcusive of tax only
				if ( $woocommerce->cart->display_totals_ex_tax || ! $woocommerce->cart->prices_include_tax ) {
					if ( $woocommerce->cart->get_cart_tax() ) {

						$taxes = $woocommerce->cart->get_formatted_taxes();

						if ( sizeof( $taxes ) > 0 ) {

							$has_compound_tax = false;

							foreach ( $taxes as $key => $tax ) {
								if ( $woocommerce->cart->tax->is_compound( $key ) ) {
									$has_compound_tax = true; 
									continue;
								}
								?>
								<tr class="tax-rate tax-rate-<?php echo $key; ?>">
									<th colspan="2"><?php echo $woocommerce->cart->tax->get_rate_label( $key ); ?></th>
									<td><?php echo $tax; ?></td>
								</tr>
								<?php
							}

							if ( $has_compound_tax ) {
								?>
								<tr class="order-subtotal">
									<th colspan="2"><strong><?php _e( 'Subtotal', 'woocommerce' ); ?></strong></th>
									<td><strong><?php echo $woocommerce->cart->get_cart_subtotal( true ); ?></strong></td>
								</tr>
								<?php
							}

							foreach ( $taxes as $key => $tax ) {
								if ( ! $woocommerce->cart->tax->is_compound( $key ) ) 
									continue;
								?>
								<tr class="tax-rate tax-rate-<?php echo $key; ?>">
									<th colspan="2"><?php echo $woocommerce->cart->tax->get_rate_label( $key ); ?></th>
									<td><?php echo $tax; ?></td>
								</tr>
								<?php
							}

						} else { 
							?>
							<tr class="tax">
								<th colspan="2" colspan="2"><?php _e( 'Tax', 'woocommerce' ); ?></th>
								<td><?php echo $woocommerce->cart->get_cart_tax(); ?></td>
							</tr>
							<?php
						}
						
					} elseif ( get_option( 'woocommerce_display_cart_taxes_if_zero' ) == 'yes' ) {
						?>
						<tr class="tax">
							<th colspan="2"><?php _e( 'Tax', 'woocommerce' ); ?></th>
							<td><?php _ex( 'N/A', 'Relating to tax', 'woocommerce' ); ?></td>
						</tr>
						<?php
					}
				}
			?>

			<?php if ($woocommerce->cart->get_discounts_after_tax()) : ?>

			<tr class="discount">
				<th colspan="2"><?php _e( 'Order Discount', 'woocommerce' ); ?></th>
				<td>-<?php echo $woocommerce->cart->get_discounts_after_tax(); ?></td>
			</tr>

			<?php endif; ?>

			<?php do_action('woocommerce_before_order_total'); ?>

			<tr class="total">
				<th colspan="2"><strong><?php _e( 'Order Total', 'woocommerce' ); ?></strong></th>
				<td>
					<strong><?php echo $woocommerce->cart->get_total(); ?></strong>
					<?php
						// If prices are tax inclusive, show taxes here
						if ( ! $woocommerce->cart->display_totals_ex_tax && $woocommerce->cart->prices_include_tax ) {
							
							if ( $woocommerce->cart->get_cart_tax() ) {
								$tax_string_array = array();
								$taxes = $woocommerce->cart->get_formatted_taxes();
								
								if ( sizeof( $taxes ) > 0 ) {
									foreach ( $taxes as $key => $tax ) {
										$tax_string_array[] = sprintf( '%s %s', $tax, $woocommerce->cart->tax->get_rate_label( $key ) );
									}
								} else {
									$tax_string_array[] = sprintf( '%s tax', $tax );
								}
								
								if ( ! empty( $tax_string_array ) ) {
									?><small class="includes_tax"><?php printf( __( '(Includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) ); ?></small><?php	
								}
							} elseif ( get_option( 'woocommerce_display_cart_taxes_if_zero' ) == 'yes' ) {
								?><small class="includes_tax"><?php printf( __( '(Includes %s tax)', 'woocommerce' ), woocommerce_price( 0 ) ); ?></small><?php
							}
							
						}
					?>
				</td>
			</tr>

			<?php do_action('woocommerce_after_order_total'); ?>

		</tfoot>
		<tbody>
			<?php
				if (sizeof($woocommerce->cart->get_cart())>0) :
					foreach ($woocommerce->cart->get_cart() as $item_id => $values) :
						$_product = $values['data'];
						if ($_product->exists() && $values['quantity']>0) :
							echo '
								<tr class = "' . esc_attr( apply_filters('woocommerce_checkout_table_item_class', 'checkout_table_item', $values, $item_id ) ) . '">
									<td class="product-name">'.$_product->get_title().$woocommerce->cart->get_item_data( $values ).'</td>
									<td class="product-quantity">'.$values['quantity'].'</td>
									<td class="product-total">' . apply_filters( 'woocommerce_checkout_item_subtotal', $woocommerce->cart->get_product_subtotal( $_product, $values['quantity'] ), $values, $item_id ) . '</td>
								</tr>';
						endif;
					endforeach;
				endif;

				do_action( 'woocommerce_cart_contents_review_order' );
			?>
		</tbody>
	</table>

	<div id="payment">
		<?php if ($woocommerce->cart->needs_payment()) : ?>
		<ul class="payment_methods methods">
			<?php
				$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
				if ($available_gateways) :
					// Chosen Method
					if (sizeof($available_gateways)) :
						$default_gateway = get_option('woocommerce_default_gateway');
						
						if ( isset( $woocommerce->session->chosen_payment_method ) && isset( $available_gateways[ $woocommerce->session->chosen_payment_method ] ) ) {
							$available_gateways[ $woocommerce->session->chosen_payment_method ]->set_current();
						} elseif ( isset( $available_gateways[ $default_gateway ] ) ) {
							$available_gateways[ $default_gateway ]->set_current();
						} else {
							current( $available_gateways )->set_current();
						}
						
					endif;
					foreach ($available_gateways as $gateway ) :
						?>
						<li>
						<input type="radio" id="payment_method_<?php echo $gateway->id; ?>" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php if ($gateway->chosen) echo 'checked="checked"'; ?> />
						<label for="payment_method_<?php echo $gateway->id; ?>"><?php echo $gateway->get_title(); ?> <?php echo $gateway->get_icon(); ?></label>
							<?php
								if ( $gateway->has_fields() || $gateway->get_description() ) :
									echo '<div class="payment_box payment_method_' . $gateway->id . '" style="display:none;">';
									$gateway->payment_fields();
									echo '</div>';
								endif;
							?>
						</li>
						<?php
					endforeach;
				else :

					if ( !$woocommerce->customer->get_country() ) :
						echo '<p>'.__( 'Please fill in your details above to see available payment methods.', 'woocommerce' ).'</p>';
					else :
						echo '<p>'.__( 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ).'</p>';
					endif;

				endif;
			?>
		</ul>
		<?php endif; ?>

		<div class="form-row place-order">

			<noscript><?php _e( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ); ?><br/><input type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php _e( 'Update totals', 'woocommerce' ); ?>" /></noscript>

			<?php $woocommerce->nonce_field('process_checkout')?>

			<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

			<input type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="<?php echo apply_filters('woocommerce_order_button_text', __( 'Place order', 'woocommerce' )); ?>" />

			<?php if (woocommerce_get_page_id('terms')>0) : ?>
			<p class="form-row terms">
				<label for="terms" class="checkbox"><?php _e( 'I accept the', 'woocommerce' ); ?> <a href="<?php echo esc_url( get_permalink(woocommerce_get_page_id('terms')) ); ?>" target="_blank"><?php _e( 'terms &amp; conditions', 'woocommerce' ); ?></a></label>
				<input type="checkbox" class="input-checkbox" name="terms" <?php if (isset($_POST['terms'])) echo 'checked="checked"'; ?> id="terms" />
			</p>
			<?php endif; ?>

			<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		</div>

		<div class="clear"></div>

	</div>

</div>