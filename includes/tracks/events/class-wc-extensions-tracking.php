<?php
/**
 * WooCommerce Extensions Tracking
 *
 * @package WooCommerce\Tracks
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class adds actions to track usage of the WooCommerce Extensions page.
 */
class WC_Extensions_Tracking {
	/**
	 * Init tracking.
	 */
	public static function init() {
		add_action( 'load-woocommerce_page_wc-addons', array( __CLASS__, 'track_extensions_page' ) );
		add_action( 'woocommerce_helper_connect_start', array( __CLASS__, 'track_helper_connection_start' ) );
		add_action( 'woocommerce_helper_denied', array( __CLASS__, 'track_helper_connection_cancelled' ) );
		add_action( 'woocommerce_helper_connected', array( __CLASS__, 'track_helper_connection_complete' ) );
		add_action( 'woocommerce_helper_disconnected', array( __CLASS__, 'track_helper_disconnected' ) );
		add_action( 'woocommerce_helper_subscriptions_refresh', array( __CLASS__, 'track_helper_subscriptions_refresh' ) );
	}

	/**
	 * Send a Tracks event when an Extensions page is viewed.
	 */
	public static function track_extensions_page() {
		// phpcs:disable WordPress.Security.NonceVerification.NoNonceVerification
		$event      = 'extensions_view';
		$properties = array(
			'section' => empty( $_REQUEST['section'] ) ? '_featured' : wc_clean( wp_unslash( $_REQUEST['section'] ) ),
		);

		if ( ! empty( $_REQUEST['search'] ) ) {
			$event                     = 'extensions_view_search';
			$properties['search_term'] = wc_clean( wp_unslash( $_REQUEST['search'] ) );
		}
		// phpcs:enable

		WC_Tracks::record_event( $event, $properties );
	}

	/**
	 * Send a Tracks even when a Helper connection process is initiated.
	 */
	public static function track_helper_connection_start() {
		WC_Tracks::record_event( 'extensions_subscriptions_connect' );
	}

	/**
	 * Send a Tracks even when a Helper connection process is cancelled.
	 */
	public static function track_helper_connection_cancelled() {
		WC_Tracks::record_event( 'extensions_subscriptions_cancelled' );
	}

	/**
	 * Send a Tracks even when a Helper connection process completed successfully.
	 */
	public static function track_helper_connection_complete() {
		WC_Tracks::record_event( 'extensions_subscriptions_connected' );
	}

	/**
	 * Send a Tracks even when a Helper has been disconnected.
	 */
	public static function track_helper_disconnected() {
		WC_Tracks::record_event( 'extensions_subscriptions_disconnect' );
	}

	/**
	 * Send a Tracks even when Helper subscriptions are refreshed.
	 */
	public static function track_helper_subscriptions_refresh() {
		WC_Tracks::record_event( 'extensions_subscriptions_update' );
	}
}
