<?php
/**
 * My Addresses
 */
 
global $woocommerce;

$customer_id = get_current_user_id();
?>
<div class="col2-set addresses">

	<div class="col-1">
	
		<header class="title">				
			<h3><?php _e('Billing Address', 'woocommerce'); ?></h3>
			<a href="<?php echo esc_url( add_query_arg('address', 'billing', get_permalink(woocommerce_get_page_id('edit_address'))) ); ?>" class="edit"><?php _e('Edit', 'woocommerce'); ?></a>	
		</header>
		<address>
			<?php
				$address = array(
					'first_name' 	=> get_user_meta( $customer_id, 'billing_first_name', true ),
					'last_name'		=> get_user_meta( $customer_id, 'billing_last_name', true ),
					'company'		=> get_user_meta( $customer_id, 'billing_company', true ),
					'address_1'		=> get_user_meta( $customer_id, 'billing_address_1', true ),
					'address_2'		=> get_user_meta( $customer_id, 'billing_address_2', true ),
					'city'			=> get_user_meta( $customer_id, 'billing_city', true ),			
					'state'			=> get_user_meta( $customer_id, 'billing_state', true ),
					'postcode'		=> get_user_meta( $customer_id, 'billing_postcode', true ),
					'country'		=> get_user_meta( $customer_id, 'billing_country', true )
				);

				$formatted_address = $woocommerce->countries->get_formatted_address( $address );
				
				if (!$formatted_address) _e('You have not set up a billing address yet.', 'woocommerce'); else echo $formatted_address;
			?>
		</address>
	
	</div><!-- /.col-1 -->
	
	<div class="col-2">
	
		<header class="title">
			<h3><?php _e('Shipping Address', 'woocommerce'); ?></h3>
			<a href="<?php echo esc_url( add_query_arg('address', 'shipping', get_permalink(woocommerce_get_page_id('edit_address'))) ); ?>" class="edit"><?php _e('Edit', 'woocommerce'); ?></a>
		</header>
		<address>
			<?php
				$address = array(
					'first_name' 	=> get_user_meta( $customer_id, 'shipping_first_name', true ),
					'last_name'		=> get_user_meta( $customer_id, 'shipping_last_name', true ),
					'company'		=> get_user_meta( $customer_id, 'shipping_company', true ),
					'address_1'		=> get_user_meta( $customer_id, 'shipping_address_1', true ),
					'address_2'		=> get_user_meta( $customer_id, 'shipping_address_2', true ),
					'city'			=> get_user_meta( $customer_id, 'shipping_city', true ),			
					'state'			=> get_user_meta( $customer_id, 'shipping_state', true ),
					'postcode'		=> get_user_meta( $customer_id, 'shipping_postcode', true ),
					'country'		=> get_user_meta( $customer_id, 'shipping_country', true )
				);

				$formatted_address = $woocommerce->countries->get_formatted_address( $address );
				
				if (!$formatted_address) _e('You have not set up a shipping address yet.', 'woocommerce'); else echo $formatted_address;
			?>
		</address>
	
	</div><!-- /.col-2 -->

</div><!-- /.col2-set -->