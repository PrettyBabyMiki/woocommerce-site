<?php
/**
 * Edit Address Form
 */
 
global $woocommerce, $current_user;

get_currentuserinfo();
?>

<?php $woocommerce->show_messages(); ?>

<?php if (!$load_address) : ?>

	<?php woocommerce_get_template('myaccount/my-address.php'); ?>

<?php else : ?>

	<form action="<?php echo esc_url( add_query_arg( 'address', $load_address, get_permalink( woocommerce_get_page_id('edit_address') ) ) ); ?>" method="post">
		
		<h3><?php if ($load_address=='billing') _e('Billing Address', 'woocommerce'); else _e('Shipping Address', 'woocommerce'); ?></h3>
		
		<?php 
		foreach ($address as $key => $field) :
			$value = (isset($_POST[$key])) ? $_POST[$key] : get_user_meta( get_current_user_id(), $key, true );
			if (!$value && $key=='billing_email') $value = $current_user->user_email;
			woocommerce_form_field( $key, $field, $value );
		endforeach;
		?>
		
		<input type="submit" class="button" name="save_address" value="<?php _e('Save Address', 'woocommerce'); ?>" />
		
		<?php $woocommerce->nonce_field('edit_address') ?>
		<input type="hidden" name="action" value="edit_address" />
	
	</form>

<?php endif; ?>