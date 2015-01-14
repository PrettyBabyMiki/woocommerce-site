<?php
/**
 * Display notices in admin.
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Admin_Notices' ) ) :

/**
 * WC_Admin_Notices Class
 */
class WC_Admin_Notices {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'switch_theme', array( $this, 'reset_admin_notices' ) );
		add_action( 'woocommerce_updated', array( $this, 'reset_admin_notices' ) );
		add_action( 'admin_print_styles', array( $this, 'add_notices' ) );
	}

	/**
	 * Reset notices for themes when switched or a new version of WC is installed
	 */
	public function reset_admin_notices() {
		update_option( 'woocommerce_admin_notices', array( 'template_files', 'theme_support' ) );
	}

	/**
	 * Add notices + styles if needed.
	 */
	public function add_notices() {
		if ( get_option( '_wc_needs_update' ) == 1 || get_option( '_wc_needs_pages' ) == 1 ) {
			wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ) );
			add_action( 'admin_notices', array( $this, 'install_notice' ) );
		}

		$notices = get_option( 'woocommerce_admin_notices', array() );

		if ( ! empty( $_GET['hide_theme_support_notice'] ) ) {
			$notices = array_diff( $notices, array( 'theme_support' ) );
			update_option( 'woocommerce_admin_notices', $notices );
		}

		if ( ! empty( $_GET['hide_template_files_notice'] ) ) {
			$notices = array_diff( $notices, array( 'template_files' ) );
			update_option( 'woocommerce_admin_notices', $notices );
		}

		if ( ! empty( $_GET['hide_frontend_colors_notice'] ) ) {
			delete_option( 'woocommerce_frontend_css_colors' );
		}

		if ( in_array( 'theme_support', $notices ) && ! current_theme_supports( 'woocommerce' ) ) {
			$template = get_option( 'template' );

			if ( ! in_array( $template, wc_get_core_supported_themes() ) ) {
				wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ) );
				add_action( 'admin_notices', array( $this, 'theme_check_notice' ) );
			}
		}

		if ( in_array( 'template_files', $notices ) ) {
			wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ) );
			add_action( 'admin_notices', array( $this, 'template_file_check_notice' ) );
		}

		if ( in_array( 'translation_upgrade', $notices ) ) {
			wp_enqueue_style( 'woocommerce-activation', plugins_url(  '/assets/css/activation.css', WC_PLUGIN_FILE ) );
			add_action( 'admin_notices', array( $this, 'translation_upgrade_notice' ) );
		}

		if ( $this->has_frontend_colors() ) {
			add_action( 'admin_notices', array( $this, 'frontend_colors_notice' ) );
		}
	}

	/**
	 * Show the install notices
	 */
	public function install_notice() {
		// If we need to update, include a message with the update button
		if ( get_option( '_wc_needs_update' ) == 1 ) {
			include( 'views/html-notice-update.php' );
		}

		// If we have just installed, show a message with the install pages button
		elseif ( get_option( '_wc_needs_pages' ) == 1 ) {
			include( 'views/html-notice-install.php' );
		}
	}

	/**
	 * Show the Theme Check notice
	 */
	public function theme_check_notice() {
		include( 'views/html-notice-theme-support.php' );
	}

	/**
	 * Show the translation upgrade notice
	 */
	public function translation_upgrade_notice() {
		$screen = get_current_screen();

		if ( 'update-core' !== $screen->id ) {
			include( 'views/html-notice-translation-upgrade.php' );
		}
	}

	/**
	 * Show a notice highlighting bad template files
	 */
	public function template_file_check_notice() {
		if ( isset( $_GET['page'] ) && 'wc-status' == $_GET['page'] ) {
			return;
		}

		$core_templates = WC_Admin_Status::scan_template_files( WC()->plugin_path() . '/templates' );
		$outdated       = false;

		foreach ( $core_templates as $file ) {
			$theme_file = false;
			if ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/' . $file;
			} elseif ( file_exists( get_stylesheet_directory() . '/woocommerce/' . $file ) ) {
				$theme_file = get_stylesheet_directory() . '/woocommerce/' . $file;
			} elseif ( file_exists( get_template_directory() . '/' . $file ) ) {
				$theme_file = get_template_directory() . '/' . $file;
			} elseif( file_exists( get_template_directory() . '/woocommerce/' . $file ) ) {
				$theme_file = get_template_directory() . '/woocommerce/' . $file;
			}

			if ( $theme_file ) {
				$core_version  = WC_Admin_Status::get_file_version( WC()->plugin_path() . '/templates/' . $file );
				$theme_version = WC_Admin_Status::get_file_version( $theme_file );

				if ( $core_version && $theme_version && version_compare( $theme_version, $core_version, '<' ) ) {
					$outdated = true;
					break;
				}
			}
		}

		if ( $outdated ) {
			include( 'views/html-notice-template-check.php' );
		}
	}

	/**
	 * Checks if there is any change in woocommerce_frontend_css_colors
	 *
	 * @return bool
	 */
	public function has_frontend_colors() {
		$styles = (array) WC_Frontend_Scripts::get_styles();

		if ( ! array_key_exists( 'woocommerce-general', $styles ) ) {
			return false;
		}

		$colors  = get_option( 'woocommerce_frontend_css_colors' );
		$default = array(
			'primary'    => '#ad74a2',
			'secondary'  => '#f7f6f7',
			'highlight'  => '#85ad74',
			'content_bg' => '#ffffff',
			'subtext'    => '#777777'
		);

		if ( ! $colors || $colors === $default ) {
			return false;
		}

		return true;
	}

	/**
	 * Notice to say Frontend Colors options has been deprecated in 2.3
	 */
	public function frontend_colors_notice() {
		include( 'views/html-notice-frontend-colors.php' );
	}
}

endif;

return new WC_Admin_Notices();
