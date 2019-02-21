<?php
/**
 * Send Tracks events on behalf of a user.
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Tracks_Client class.
 */
class WC_Tracks_Client {

	/**
	 * Pixel URL.
	 */
	const PIXEL = 'https://pixel.wp.com/t.gif';

	/**
	 * Browser type.
	 */
	const BROWSER_TYPE = 'php-agent';

	/**
	 * User agent.
	 */
	const USER_AGENT_SLUG = 'tracks-client';

	/**
	 * Record a Tracks event
	 *
	 * @param  array $event Array of event properties.
	 * @return bool|WP_Error         True on success, WP_Error on failure.
	 */
	public static function record_event( $event ) {
		if ( ! $event instanceof WC_Tracks_Event ) {
			$event = new WC_Tracks_Event( $event );
		}

		if ( is_wp_error( $event ) ) {
			return $event;
		}

		$pixel = $event->build_pixel_url( $event );

		if ( ! $pixel ) {
			return new WP_Error( 'invalid_pixel', 'cannot generate tracks pixel for given input', 400 );
		}

		return self::record_pixel( $pixel );
	}

	/**
	 * Synchronously request the pixel.
	 *
	 * @param string $pixel pixel url and query string.
	 * @return bool|WP_Error         True on success, WP_Error on failure.
	 */
	public static function record_pixel( $pixel ) {
		// Add the Request Timestamp and URL terminator just before the HTTP request.
		$pixel .= '&_rt=' . self::build_timestamp() . '&_=_';

		$response = wp_safe_remote_get(
			$pixel,
			array(
				'blocking'    => true, // The default, but being explicit here.
				'redirection' => 2,
				'httpversion' => '1.1',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'request_failed', 'Tracks pixel request failed', $code );
		}

		return true;
	}

	/**
	 * Create a timestap representing milliseconds since 1970-01-01
	 *
	 * @return string A string representing a timestamp.
	 */
	public static function build_timestamp() {
		$ts = round( microtime( true ) * 1000 );

		return number_format( $ts, 0, '', '' );
	}

	/**
	 * Get a user's identity to send to Tracks. If Jetpack exists, default to its implementation.
	 *
	 * @param int $user_id User id.
	 * @return array Identity properties.
	 */
	public static function get_identity( $user_id ) {
		if ( class_exists( 'Jetpack' ) ) {

			include_once trailingslashit( WP_PLUGIN_DIR ) . 'jetpack/_inc/lib/tracks/client.php';

			if ( function_exists( 'jetpack_tracks_get_identity' ) ) {
				return jetpack_tracks_get_identity( $user_id );
			}
		}

		// Start with a previously set cookie.
		$anon_id = isset( $_COOKIE['tk_ai'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) ) : false; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// If there is no cookie, apply a saved id.
		if ( ! $anon_id ) {
			$anon_id = get_user_meta( $user_id, '_woocommerce_tracks_anon_id', true );
		}

		// If an id is still not found, create one and save it.
		if ( ! $anon_id ) {
			$anon_id = self::get_anon_id();
			update_user_meta( $user_id, '_woocommerce_tracks_anon_id', $anon_id );
		}

		if ( ! isset( $_COOKIE['tk_ai'] ) ) {
			wc_setcookie( 'tk_ai', $anon_id );
		}

		return array(
			'_ut' => 'anon',
			'_ui' => $anon_id,
		);
	}

	/**
	 * Grabs the user's anon id from cookies, or generates and sets a new one
	 *
	 * @return string An anon id for the user
	 */
	public static function get_anon_id() {
		static $anon_id = null;

		if ( ! isset( $anon_id ) ) {

			// Did the browser send us a cookie?
			if ( isset( $_COOKIE['tk_ai'] ) ) {
					$anon_id = sanitize_text_field( wp_unslash( $_COOKIE['tk_ai'] ) );
			} else {

				$binary = '';

				// Generate a new anonId and try to save it in the browser's cookies.
				// Note that base64-encoding an 18 character string generates a 24-character anon id.
				for ( $i = 0; $i < 18; ++$i ) {
					$binary .= chr( wp_rand( 0, 255 ) );
				}

				$anon_id = 'woo:' . base64_encode( $binary );

				// Don't set cookie on API requests.
				if (
					! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) &&
					! ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
				) {
					wc_setcookie( 'tk_ai', $anon_id );
				}
			}
		}

		return $anon_id;
	}
}
