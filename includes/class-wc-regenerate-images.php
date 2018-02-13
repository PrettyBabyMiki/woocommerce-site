<?php
/**
 * Regenerate Images Functionality
 *
 * All functionality pertaining to regenerating product images in realtime.
 *
 * @package WooCommerce/Classes
 * @version 3.3.0
 * @since 3.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Regenerate Images Class
 */
class WC_Regenerate_Images {

	/**
	 * Background process to regenerate all images
	 *
	 * @var WC_Regenerate_Images_Request
	 */
	protected static $background_process;

	/**
	 * Stores size being generated on the fly.
	 *
	 * @var string
	 */
	protected static $requested_size;

	/**
	 * Init function
	 */
	public static function init() {
		add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'add_uncropped_metadata' ) );

		// Resize WooCommerce images on the fly when browsing site through customizer as to showcase image setting changes in real time.
		if ( is_customize_preview() ) {
			add_filter( 'wp_get_attachment_image_src', array( __CLASS__, 'maybe_resize_image' ), 10, 4 );
		}

		// Regenerate thumbnails in the background after settings change. Not ran on multisite to avoid multiple simultanious jobs..
		if ( apply_filters( 'woocommerce_background_image_regeneration', ! is_multisite() ) ) {
			include_once WC_ABSPATH . 'includes/class-wc-regenerate-images-request.php';

			self::$background_process = new WC_Regenerate_Images_Request();

			add_action( 'admin_init', array( __CLASS__, 'regenerating_notice' ) );
			add_action( 'woocommerce_hide_regenerating_thumbnails_notice', array( __CLASS__, 'dismiss_regenerating_notice' ) );
			add_action( 'customize_save_after', array( __CLASS__, 'maybe_regenerate_images' ) );
			add_action( 'after_switch_theme', array( __CLASS__, 'maybe_regenerate_images' ) );
		}
	}

	/**
	 * Show notice when job is running in background.
	 */
	public static function regenerating_notice() {
		if ( ! self::$background_process->is_running() ) {
			WC_Admin_Notices::add_notice( 'regenerating_thumbnails' );
		} else {
			WC_Admin_Notices::remove_notice( 'regenerating_thumbnails' );
		}
	}

	/**
	 * Dismiss notice and cancel jobs.
	 */
	public static function dismiss_regenerating_notice() {
		if ( self::$background_process ) {
			self::$background_process->kill_process();

			$log = wc_get_logger();
			$log->info( __( 'Cancelled product image regeneration job.', 'woocommerce' ),
				array(
					'source' => 'wc-image-regeneration',
				)
			);
		}
		WC_Admin_Notices::remove_notice( 'regenerating_thumbnails' );
	}

	/**
	 * Regenerate images if the settings have changed since last re-generation.
	 *
	 * @return void
	 */
	public static function maybe_regenerate_images() {
		$size_hash = md5( wp_json_encode( array(
			wc_get_image_size( 'thumbnail' ),
			wc_get_image_size( 'single' ),
			wc_get_image_size( 'gallery_thumbnail' ),
		) ) );

		if ( update_option( 'woocommerce_maybe_regenerate_images_hash', $size_hash ) ) {
			// Size settings have changed. Trigger regen.
			self::queue_image_regeneration();
		}
	}

	/**
	 * We need to track if uncropped was on or off when generating the images.
	 *
	 * @param array $metadata Array of meta data.
	 * @return array
	 */
	public static function add_uncropped_metadata( $metadata ) {
		$size_settings = wc_get_image_size( 'woocommerce_thumbnail' );
		$metadata['woocommerce_thumbnail_uncropped'] = empty( $size_settings['height'] );
		return $metadata;
	}

	/**
	 * Check if we should maybe generate a new image size if not already there.
	 *
	 * @param array        $image Properties of the image.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|array $size Image size.
	 * @param bool         $icon If icon or not.
	 * @return array
	 */
	public static function maybe_resize_image( $image, $attachment_id, $size, $icon ) {
		if ( ! apply_filters( 'woocommerce_resize_images', true ) ) {
			return $image;
		}

		// Use a whitelist of sizes we want to resize. Ignore others.
		if ( ! in_array( $size, apply_filters( 'woocommerce_image_sizes_to_resize', array( 'woocommerce_thumbnail', 'woocommerce_gallery_thumbnail', 'woocommerce_single', 'shop_thumbnail', 'shop_catalog', 'shop_single' ) ), true ) ) {
			return $image;
		}

		// Get image metadata - we need it to proceed.
		$imagemeta = wp_get_attachment_metadata( $attachment_id );

		if ( empty( $imagemeta ) ) {
			return $image;
		}

		$size_settings = wc_get_image_size( $size );

		// If size differs from image meta, or height differs and we're cropping, regenerate the image.
		if ( ! isset( $imagemeta['sizes'], $imagemeta['sizes'][ $size ] ) || $imagemeta['sizes'][ $size ]['width'] !== $size_settings['width'] || ( $size_settings['crop'] && $imagemeta['sizes'][ $size ]['height'] !== $size_settings['height'] ) ) {
			return self::resize_and_return_image( $attachment_id, $image, $size, $icon );
		}

		// If cropping mode has changed, regenerate the image.
		if ( '' === $size_settings['height'] && empty( $imagemeta['woocommerce_thumbnail_uncropped'] ) ) {
			return self::resize_and_return_image( $attachment_id, $image, $size, $icon );
		}

		return $image;
	}

	/**
	 * Ensure we are dealing with the correct image attachment
	 *
	 * @param WP_Post $attachment Attachment object.
	 * @return boolean
	 */
	public static function is_regeneratable( $attachment ) {
		if ( 'site-icon' === get_post_meta( $attachment->ID, '_wp_attachment_context', true ) ) {
			return false;
		}

		if ( wp_attachment_is_image( $attachment ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Only regenerate images for the requested size.
	 *
	 * @param array $sizes Array of image sizes.
	 * @return array
	 */
	public static function adjust_intermediate_image_sizes( $sizes ) {
		return array( self::$requested_size );
	}

	/**
	 * Generate the thumbnail filename and dimensions for a given file.
	 *
	 * @param string $fullsizepath Path to full size image.
	 * @param int    $thumbnail_width  The width of the thumbnail.
	 * @param int    $thumbnail_height The height of the thumbnail.
	 * @param bool   $crop             Whether to crop or not.
	 * @return array|false An array of the filename, thumbnail width, and thumbnail height, or false on failure to resize such as the thumbnail being larger than the fullsize image.
	 */
	private static function get_image( $fullsizepath, $thumbnail_width, $thumbnail_height, $crop ) {
		list( $fullsize_width, $fullsize_height ) = getimagesize( $fullsizepath );

		$dimensions = image_resize_dimensions( $fullsize_width, $fullsize_height, $thumbnail_width, $thumbnail_height, $crop );
		$editor     = wp_get_image_editor( $fullsizepath );

		if ( is_wp_error( $editor ) ) {
			return false;
		}

		if ( ! $dimensions || ! is_array( $dimensions ) ) {
			return false;
		}

		list( , , , , $dst_w, $dst_h ) = $dimensions;
		$suffix   = "{$dst_w}x{$dst_h}";
		$file_ext = strtolower( pathinfo( $fullsizepath, PATHINFO_EXTENSION ) );

		return array(
			'filename' => $editor->generate_filename( $suffix, null, $file_ext ),
			'width'    => $dst_w,
			'height'   => $dst_h,
		);
	}

	/**
	 * Regenerate the image according to the required size
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param array  $image Original Image.
	 * @param string $size Size to return for new URL.
	 * @param bool   $icon If icon or not.
	 * @return string
	 */
	private static function resize_and_return_image( $attachment_id, $image, $size, $icon ) {
		self::$requested_size = $size;
		$image_size           = wc_get_image_size( $size );
		$wp_uploads           = wp_upload_dir( null, false );
		$wp_uploads_dir       = $wp_uploads['basedir'];
		$wp_uploads_url       = $wp_uploads['baseurl'];
		$attachment           = get_post( $attachment_id );

		if ( ! $attachment || 'attachment' !== $attachment->post_type || ! self::is_regeneratable( $attachment ) ) {
			return $image;
		}

		$fullsizepath = get_attached_file( $attachment_id );

		if ( false === $fullsizepath || is_wp_error( $fullsizepath ) || ! file_exists( $fullsizepath ) ) {
			return $image;
		}

		if ( ! function_exists( 'wp_crop_image' ) ) {
			include ABSPATH . 'wp-admin/includes/image.php';
		}

		// Make sure registered image size matches the size we're requesting.
		add_image_size( $size, $image_size['width'], $image_size['height'], $image_size['crop'] );

		$thumbnail = self::get_image( $fullsizepath, $image_size['width'], $image_size['height'], $image_size['crop'] );

		// If the file is already there perhaps just load it.
		if ( $thumbnail && file_exists( $thumbnail['filename'] ) ) {
			$wp_uploads     = wp_upload_dir( null, false );
			$wp_uploads_dir = $wp_uploads['basedir'];
			$wp_uploads_url = $wp_uploads['baseurl'];

			return array(
				0 => str_replace( $wp_uploads_dir, $wp_uploads_url, $thumbnail['filename'] ),
				1 => $thumbnail['width'],
				2 => $thumbnail['height'],
			);
		}

		$metadata = wp_get_attachment_metadata( $attachment_id );

		// We only want to regen WC images.
		add_filter( 'intermediate_image_sizes', array( __CLASS__, 'adjust_intermediate_image_sizes' ) );

		// This function will generate the new image sizes.
		$new_metadata = wp_generate_attachment_metadata( $attachment_id, $fullsizepath );

		// Remove custom filter.
		remove_filter( 'intermediate_image_sizes', array( __CLASS__, 'adjust_intermediate_image_sizes' ) );

		// If something went wrong lets just return the original image.
		if ( is_wp_error( $new_metadata ) || empty( $new_metadata ) ) {
			return $image;
		}

		// Since this is only a preview we should not update the actual size. That will be done later by the background job.
		if ( isset( $new_metadata['sizes'][ $size ] ) ) {
			if ( $metadata && isset( $metadata['sizes'] ) ) {
				$metadata['sizes'][ $size . '_preview' ] = $new_metadata['sizes'][ $size ];
			} else {
				$metadata = $new_metadata;
			}
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		// Now we've done our regen, attempt to return the new size.
		$new_image = image_downsize( $attachment_id, $size . '_preview' );

		return $new_image ? $new_image : $image;
	}

	/**
	 * Get list of images and queue them for regeneration
	 *
	 * @return void
	 */
	public static function queue_image_regeneration() {
		global $wpdb;
		// First lets cancel existing running queue to avoid running it more than once.
		self::$background_process->kill_process();

		// Now lets find all product image attachments IDs and pop them onto the queue.
		$images = $wpdb->get_results( // @codingStandardsIgnoreLine
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE 'image/%'
			ORDER BY ID DESC"
		);
		foreach ( $images as $image ) {
			self::$background_process->push_to_queue( array(
				'attachment_id' => $image->ID,
			) );
		}

		// Lets dispatch the queue to start processing.
		self::$background_process->save()->dispatch();
	}
}

add_action( 'init', array( 'WC_Regenerate_Images', 'init' ) );
