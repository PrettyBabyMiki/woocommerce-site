<?php
/**
 * WooCommerce.com Product Installation Requirements Check.
 *
 * @package WooCommerce\WooCommerce_Site
 * @since   3.8.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_WCCOM_Site_Installer_Requirements_Check Class
 * Contains functionality to check the necessary requirements for the installer.
 */
class WC_WCCOM_Site_Installer_Requirements_Check {
	/**
	 * Check if the site met the requirements
	 *
	 * @version 3.8.0
	 * @return bool|WP_Error Does the site met the requirements?
	 */
	public static function met_requirements() {
		$errs = [];

		if ( ! self::met_wp_cron_requirement() ) {
			$errs[] = 'wp-cron';
		}

		if ( ! self::met_filesystem_requirement() ) {
			$errs[] = 'filesystem';
		}

		if ( ! empty( $errs ) ) {
			// translators: %s: Requirements unmet.
			return new WP_Error( 'requirements_not_met', sprintf( __( 'Server requirements not met, missing requirement(s): %s.', 'woocommerce' ), implode( ', ', $errs ) ), array( 'status' => 503 ) );
		}

		return true;
	}

	/**
	 * Validates if WP CRON is enabled.
	 *
	 * @since 3.8.0
	 * @return bool
	 */
	private static function met_wp_cron_requirement() {
		return ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON );
	}

	/**
	 * Validates if `WP_CONTENT_DIR` is writable.
	 *
	 * @since 3.8.0
	 * @return bool
	 */
	private static function met_filesystem_requirement() {
		return is_writable( WP_CONTENT_DIR );
	}
}
