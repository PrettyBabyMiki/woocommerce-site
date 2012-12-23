<?php
/**
 * My Account page
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $woocommerce;

$woocommerce->show_messages(); ?>

<p class="myaccount_user"><?php printf( __( 'Hello, <strong>%s</strong>. From your account dashboard you can view your recent orders, manage your shipping and billing addresses and <a href="%s">change your password</a>.', 'woocommerce' ), $current_user->display_name, get_permalink( woocommerce_get_page_id( 'change_password' ) ) ); ?></p>

<?php do_action( 'woocommerce_before_my_account' ); ?>

<?php woocommerce_get_template( 'myaccount/my-downloads.php' ); ?>

<?php do_action( 'woocommerce_between_my_account_downloads_and_orders' ); ?>

<?php woocommerce_get_template( 'myaccount/my-orders.php', array( 'recent_orders' => $recent_orders ) ); ?>

<?php do_action( 'woocommerce_between_my_account_orders_and_address' ); ?>

<?php woocommerce_get_template( 'myaccount/my-address.php' ); ?>

<?php do_action( 'woocommerce_after_my_account' ); ?>