<?php
/**
 * Proceed to checkout button
 *
 * Contains the markup for the proceed to checkout button on the cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you (the theme developer)
 * will need to copy the new files to your theme to maintain compatibility. We try to do this
 * as little as possible, but it does happen. When this occurs the version of the template file will
 * be bumped and the readme will list any important changes.
 *
 * @see 	    http://docs.woothemes.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo '<a href="' . esc_url( WC()->cart->get_checkout_url() ) . '" class="checkout-button button alt wc-forward">' . __( 'Proceed to Checkout', 'woocommerce' ) . '</a>';
