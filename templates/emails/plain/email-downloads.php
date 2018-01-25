<?php
/**
 * Email Downloads.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/email-downloads.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	https://docs.woocommerce.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo strtoupper( __( 'Downloads', 'woocommerce' ) ) . "\n\n";

foreach ( $downloads as $download ) {
	foreach ( $columns as $column_id => $column_name ) {
		echo $column_name . ": ";

		if ( has_action( 'woocommerce_email_downloads_column_' . $column_id ) ) {
			do_action( 'woocommerce_email_downloads_column_' . $column_id, $download, false );
		} else {
			switch ( $column_id ) {
				case 'download-product' :
					echo esc_html( $download['product_name'] );
				break;
				case 'download-file' :
					echo esc_html( $download['download_name'] ) . ' - ' . esc_url( $download['download_url'] );
				break;
				case 'download-expires' :
					if ( ! empty( $download['access_expires'] ) ) {
						echo date_i18n( get_option( 'date_format' ), strtotime( $download['access_expires'] ) );
					} else {
						_e( 'Never', 'woocommerce' );
					}
				break;
			}
		}
		echo "\n";
	}
	echo "\n";
}
echo '=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=';
echo "\n\n";
