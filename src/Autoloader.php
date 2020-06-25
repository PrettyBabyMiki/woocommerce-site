<?php
/**
 * Includes the composer Autoloader used for packages and classes in the src/ directory.
 *
 * @package Automattic/WooCommerce
 */

namespace Automattic\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader class.
 *
 * @since 3.7.0
 */
class Autoloader {

	/**
	 * Static-only class.
	 */
	private function __construct() {}

	const NON_CORE_WOO_NAMESPACES = array(
		'Automattic\\WooCommerce\\Admin\\',
		'Automattic\\WooCommerce\\Blocks\\',
		'Automattic\\WooCommerce\\RestApi\\',
	);

	/**
	 * Require the autoloader and return the result.
	 *
	 * If the autoloader is not present, let's log the failure and display a nice admin notice.
	 *
	 * @return boolean
	 */
	public static function init() {
		$autoloader = dirname( __DIR__ ) . '/vendor/autoload_packages.php';

		if ( ! is_readable( $autoloader ) ) {
			self::missing_autoloader();
			return false;
		}

		self::register_psr4_autoloader();

		$autoloader_result = require $autoloader;
		if ( ! $autoloader_result ) {
			return false;
		}

		return $autoloader_result;
	}

	/**
	 * Define a PSR4 autoloader for the dependency injection engine to work.
	 * Function grabbed from https://container.thephpleague.com/3.x
	 *
	 * TODO: Assess if this is still needed after https://github.com/Automattic/jetpack/pull/15106 is merged.
	 *       If it still is, remove this notice. If it isn't, remove the method.
	 */
	protected static function register_psr4_autoloader() {
		spl_autoload_register(
			function ( $class ) {
				foreach ( self::NON_CORE_WOO_NAMESPACES as $non_core_namespace ) {
					if ( substr( $class, 0, strlen( $non_core_namespace ) ) === $non_core_namespace ) {
						return;
					}
				}

				$prefix   = 'Automattic\\WooCommerce\\';
				$base_dir = __DIR__ . '/';
				$len      = strlen( $prefix );
				if ( strncmp( $prefix, $class, $len ) !== 0 ) {
					// no, move to the next registered autoloader.
					return;
				}
				$relative_class = substr( $class, $len );
				$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
				require $file;
			}
		);
	}

	/**
	 * If the autoloader is missing, add an admin notice.
	 */
	protected static function missing_autoloader() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(  // phpcs:ignore
				esc_html__( 'Your installation of WooCommerce is incomplete. If you installed WooCommerce from GitHub, please refer to this document to set up your development environment: https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment', 'woocommerce' )
			);
		}
		add_action(
			'admin_notices',
			function() {
				?>
				<div class="notice notice-error">
					<p>
						<?php
						printf(
							/* translators: 1: is a link to a support document. 2: closing link */
							esc_html__( 'Your installation of WooCommerce is incomplete. If you installed WooCommerce from GitHub, %1$splease refer to this document%2$s to set up your development environment.', 'woocommerce' ),
							'<a href="' . esc_url( 'https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment' ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
					</p>
				</div>
				<?php
			}
		);
	}
}
