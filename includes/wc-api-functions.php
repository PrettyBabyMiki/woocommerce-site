<?php
/**
 * WooCommerce API Functions
 *
 * Functions for API specific things.
 *
 * @author   WooThemes
 * @category Core
 * @package  WooCommerce/Functions
 * @version  2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses and formats a MySQL datetime (Y-m-d H:i:s) for ISO8601/RFC3339.
 *
 * Requered WP 4.4 or later.
 * See https://developer.wordpress.org/reference/functions/mysql_to_rfc3339/
 *
 * @since 2.6.0
 * @param string       $date_gmt
 * @param string|null  $date
 * @return string|null ISO8601/RFC3339 formatted datetime.
 */
function wc_rest_api_prepare_date_response( $date_gmt, $date = null ) {
	// Check if mysql_to_rfc3339 exists first!
	if ( ! function_exists( 'mysql_to_rfc3339' ) ) {
		return null;
	}

	// Use the date if passed.
	if ( isset( $date ) ) {
		return mysql_to_rfc3339( $date );
	}

	// Return null if $date_gmt is empty/zeros.
	if ( '0000-00-00 00:00:00' === $date_gmt ) {
		return null;
	}

	// Return the formatted datetime.
	return mysql_to_rfc3339( $date_gmt );
}

/**
 * Upload image from URL.
 *
 * @since 2.6.0
 * @param string $image_url
 * @return array|WP_Error Attachment data or error message.
 */
function wc_rest_api_upload_image_from_url( $image_url ) {
	$file_name   = basename( current( explode( '?', $image_url ) ) );
	$wp_filetype = wp_check_filetype( $file_name, null );
	$parsed_url  = @parse_url( $image_url );

	// Check parsed URL.
	if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
		return new WP_Error( 'woocommerce_rest_invalid_image_url', sprintf( __( 'Invalid URL %s', 'woocommerce' ), $image_url ), array( 'status' => 400 ) );
	}

	// Ensure url is valid.
	$image_url = str_replace( ' ', '%20', $image_url );

	// Get the file.
	$response = wp_safe_remote_get( $image_url, array(
		'timeout' => 10
	) );

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'woocommerce_rest_invalid_remote_image_url', sprintf( __( 'Error getting remote image %s', 'woocommerce' ), $image_url ), array( 'status' => 400 ) );
	}

	// Ensure we have a file name and type.
	if ( ! $wp_filetype['type'] ) {
		$headers = wp_remote_retrieve_headers( $response );
		if ( isset( $headers['content-disposition'] ) && strstr( $headers['content-disposition'], 'filename=' ) ) {
			$disposition = end( explode( 'filename=', $headers['content-disposition'] ) );
			$disposition = sanitize_file_name( $disposition );
			$file_name   = $disposition;
		} elseif ( isset( $headers['content-type'] ) && strstr( $headers['content-type'], 'image/' ) ) {
			$file_name = 'image.' . str_replace( 'image/', '', $headers['content-type'] );
		}
		unset( $headers );
	}

	// Upload the file.
	$upload = wp_upload_bits( $file_name, '', wp_remote_retrieve_body( $response ) );

	if ( $upload['error'] ) {
		return new WP_Error( 'woocommerce_rest_image_upload_error', $upload['error'], array( 'status' => 400 ) );
	}

	// Get filesize.
	$filesize = filesize( $upload['file'] );

	if ( 0 == $filesize ) {
		@unlink( $upload['file'] );
		unset( $upload );

		return new WP_Error( 'woocommerce_rest_image_upload_file_error', __( 'Zero size file downloaded', 'woocommerce' ), array( 'status' => 400 ) );
	}

	do_action( 'woocommerce_rest_api_uploaded_image_from_url', $upload, $image_url );

	return $upload;
}

/**
 * Set uploaded image as attachment.
 *
 * @since 2.6.0
 * @param array $upload Upload information from wp_upload_bits.
 * @param int $id Post ID. Default to 0.
 * @return int Attachment ID
 */
function wc_rest_api_set_uploaded_image_as_attachment( $upload, $id = 0 ) {
	$info    = wp_check_filetype( $upload['file'] );
	$title   = '';
	$content = '';

	if ( $image_meta = @wp_read_image_metadata( $upload['file'] ) ) {
		if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
			$title = $image_meta['title'];
		}
		if ( trim( $image_meta['caption'] ) ) {
			$content = $image_meta['caption'];
		}
	}

	$attachment = array(
		'post_mime_type' => $info['type'],
		'guid'           => $upload['url'],
		'post_parent'    => $id,
		'post_title'     => $title,
		'post_content'   => $content
	);

	$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $id );
	if ( ! is_wp_error( $attachment_id ) ) {
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	}

	return $attachment_id;
}
