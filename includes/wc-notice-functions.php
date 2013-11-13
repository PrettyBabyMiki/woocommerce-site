<?php
/**
 * WooCommerce Message Functions
 *
 * Functions for error/message handling and display.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get the count of notices added, either for all notices or for one particular notice type.
 *
 * @param  string $notice_type The internal name of the notice type - either wc_errors, wc_messages or wc_notices. [optional]
 * @return int
 */
function wc_notice_count( $notice_type = '' ) {

	$notice_count = 0;

	if ( ! empty( $notice_type ) ) {
		$notice_count += absint( sizeof( WC()->session->get( $notice_type, array() ) ) );
	} else {
		foreach ( array( 'wc_errors', 'wc_messages', 'wc_notices' ) as $notice_type ) {
			$notice_count += absint( sizeof( WC()->session->get( $notice_type, array() ) ) );
		}
	}

	return $notice_count;
}

/**
 * Add and store an error
 *
 * @param  string $error
 */
function wc_add_error( $error ) {
	$errors   = WC()->session->get( 'wc_errors', array() );
	$errors[] = apply_filters( 'woocommerce_add_error', $error );

	WC()->session->set( 'wc_errors', $errors );
}

/**
 * Add and store a message
 *
 * @param  string $message
 */
function wc_add_message( $message ) {
	$messages   = WC()->session->get( 'wc_messages', array() );
	$messages[] = apply_filters( 'woocommerce_add_message', $message );

	WC()->session->set( 'wc_messages', $messages );
}

/**
 * Unset all errors
 */
function wc_clear_errors() {
	WC()->session->set( 'wc_errors', null );
}

/**
 * Unset all messages
 */
function wc_clear_messages() {
	WC()->session->set( 'wc_messages', null );
}

/**
 * Prints messages and errors which are stored in the session, then clears them.
 */
function wc_print_messages() {
	if ( wc_error_count() > 0  )
		woocommerce_get_template( 'shop/errors.php', array(
			'errors' => WC()->session->get( 'wc_errors', array() )
		) );


	if ( wc_message_count() > 0  )
		woocommerce_get_template( 'shop/messages.php', array(
			'messages' => WC()->session->get( 'wc_messages', array() )
		) );

	wc_clear_errors();
	wc_clear_messages();
}
add_action( 'woocommerce_before_shop_loop', 'wc_print_messages', 10 );
add_action( 'woocommerce_before_single_product', 'wc_print_messages', 10 );
