<?php
/**
 * Customer processing order email
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/customer-processing-order.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer)
 * will need to copy the new files to your theme to maintain compatibility. We try to do this
 * as little as possible, but it does happen. When this occurs the version of the template file will
 * be bumped and the readme will list any important changes.
 *
 * @see 	    http://docs.woothemes.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates/Emails
 * @version     2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<?php do_action('woocommerce_email_header', $email_heading); ?>

<p><?php _e( "Your order has been received and is now being processed. Your order details are shown below for your reference:", 'woocommerce' ); ?></p>

<?php do_action( 'woocommerce_email_before_order_table', $order, $sent_to_admin, $plain_text ); ?>

<h2><?php printf( __( 'Order #%s', 'woocommerce' ), $order->get_order_number() ); ?></h2>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
	<thead>
		<tr>
			<th class="td" scope="col" style="text-align:left;"><?php _e( 'Product', 'woocommerce' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php _e( 'Quantity', 'woocommerce' ); ?></th>
			<th class="td" scope="col" style="text-align:left;"><?php _e( 'Price', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php echo $order->email_order_items_table( $order->is_download_permitted(), true, $order->has_status( 'processing' ) ); ?>
	</tbody>
	<tfoot>
		<?php
			if ( $totals = $order->get_order_item_totals() ) {
				$i = 0;
				foreach ( $totals as $total ) {
					$i++;
					?><tr>
						<th class="td" scope="row" colspan="2" style="text-align:left; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['label']; ?></th>
						<td class="td" style="text-align:left; <?php if ( $i == 1 ) echo 'border-top-width: 4px;'; ?>"><?php echo $total['value']; ?></td>
					</tr><?php
				}
			}
		?>
	</tfoot>
</table>

<?php do_action( 'woocommerce_email_after_order_table', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text ); ?>

<?php do_action( 'woocommerce_email_footer' ); ?>
