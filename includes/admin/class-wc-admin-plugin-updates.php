<?php
/**
 * Manages WooCommerce plugin updating on the plugins screen.
 *
 * @author      Automattic
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Plugin_Updates Class.
 */
class WC_Admin_Plugin_Updates {

	const VERSION_REQUIRED_HEADER = 'WC requires at least';
	const VERSION_TESTED_HEADER = 'WC tested up to';

	protected $upgrade_notice = '';
	protected $new_version = '';
	protected $major_untested_plugins = array();
	protected $minor_untested_plugins = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'extra_plugin_headers', array( $this, 'enable_wc_plugin_headers' ) );
		add_action( 'in_plugin_update_message-woocommerce/woocommerce.php', array( $this, 'in_plugin_update_message' ), 10, 2 );
	}

	/**
	 * Read in WooCommerce headers when reading plugin headers.
	 *
	 * @param array $headers
	 * @return array $headers
	 */
	public function enable_wc_plugin_headers( $headers ) {
		$headers['WCRequires'] = self::VERSION_REQUIRED_HEADER;
		$headers['WCTested'] =  self::VERSION_TESTED_HEADER;
		return $headers;
	}

	/**
	 * Show plugin changes. Code adapted from W3 Total Cache.
	 *
	 * @param array $args
	 */
	public function in_plugin_update_message( $args, $response ) {

		$this->new_version = $response->new_version;
		$this->upgrade_notice = $this->get_upgrade_notice( $response->new_version );

		$this->major_untested_plugins = $this->get_untested_plugins( $response->new_version, 'major' );
		$this->minor_untested_plugins = $this->get_untested_plugins( $response->new_version, 'minor' );

		if ( ! empty( $this->major_untested_plugins ) ) {
			$this->upgrade_notice .= $this->get_extensions_inline_warning_major();
		}

		if ( ! empty( $this->minor_untested_plugins ) ) {
			$this->upgrade_notice .= $this->get_extensions_inline_warning_minor();
		}

		if ( ! empty( $this->major_untested_plugins ) ) {
			$this->upgrade_notice .= $this->get_extensions_modal_warning();
			add_action( 'admin_print_footer_scripts', array( $this, 'modal_js' ) );
		}

		echo apply_filters( 'woocommerce_in_plugin_update_message', wp_kses_post( $this->upgrade_notice ) );
	}

	public function modal_js() {
		?>
		<script>
			( function( $ ) {
				var $update_box = $( '#woocommerce-update' );
				var $update_link = $update_box.find('a.update-link').first();

				var update_url = $update_link.attr( 'href' );
				var old_tb_position = false;

				// Initialize thickbox.
				$update_link.removeClass( 'update-link' );
				$update_link.addClass( 'wc-thickbox' );
				$update_link.attr( 'href', '#TB_inline?height=600&width=550&inlineId=wc_untested_extensions_modal' );
				tb_init( '.wc-thickbox' );

				// Set up a custom thickbox overlay.
				$update_link.on( 'click', function( evt ) {
					var $overlay = $( '#TB_overlay' );
					if ( ! $overlay.length ) {
						$( 'body' ).append( '<div id="TB_overlay"></div><div id="TB_window" class="wc_untested_extensions_modal_container"></div>' );
					} else {
						$( '#TB_window' ).removeClass( 'thickbox-loading' ).addClass( 'wc_untested_extensions_modal_container' );
					}

					// WP overrides the tb_position function. We need to use a different tb_position function than that one.
					// This is based on the original tb_position.
					if ( ! old_tb_position ) {
						old_tb_position = tb_position;
					}
					tb_position = function() {
						$( '#TB_window' ).css( { marginLeft: '-' + parseInt( ( TB_WIDTH / 2 ), 10 ) + 'px', width: TB_WIDTH + 'px' } );
						$( '#TB_window' ).css( { marginTop: '-' + parseInt( ( TB_HEIGHT / 2 ), 10 ) + 'px' } );
					};
				});

				// Reset tb_position to WP default when modal is closed.
				$( 'body' ).on( 'thickbox:removed', function() {
					if ( old_tb_position ) {
						tb_position = old_tb_position;
					}
				});

				// Trigger the update if the user accepts the modal's warning.
				$( '#wc_untested_extensions_modal .accept' ).on( 'click', function( evt ) {
					evt.preventDefault();
					tb_remove();
					$update_link.removeClass( 'wc-thickbox open-plugin-details-modal' );
					$update_link.addClass( 'update-link' );
					$update_link.attr( 'href', update_url );
					$update_link.click();
				});

				$( '#wc_untested_extensions_modal .cancel a' ).on( 'click', function( evt ) {
					evt.preventDefault();
					tb_remove();
				});
			})( jQuery );
		</script>
		<?php
	}

	/*
	|--------------------------------------------------------------------------
	| Message Helpers
	|--------------------------------------------------------------------------
	|
	| Methods for getting messages.
	*/

	protected function get_extensions_inline_warning_minor() {
		$upgrade_type = 'minor';
		$plugins = ! empty( $this->major_untested_plugins ) ? array_diff_key( $this->minor_untested_plugins, $this->major_untested_plugins ) : $this->minor_untested_plugins;

		$version_parts = explode( '.', $this->new_version );
		$new_version = $version_parts[0] . '.' . $version_parts[1];

		/* translators: %s: version number */
		$message = sprintf( __( 'The following plugin(s) are not listed fully-compatible with WooCommerce %s yet. If possible, upgrade these plugins before upgrading WooCommerce:', 'woocommerce' ), $new_version );

		ob_start();
		include( 'views/html-notice-untested-extensions-inline.php' );
		return ob_get_clean();
	}

	protected function get_extensions_inline_warning_major() {
		$upgrade_type = 'major';
		$plugins = $this->major_untested_plugins;

		$version_parts = explode( '.', $this->new_version );
		$new_version = $version_parts[0] . '.0';

		/* translators: %s: version number */
		$message = sprintf( __( 'Heads up! The following plugin(s) are not listed compatible with WooCommerce %s yet. If you upgrade without upgrading these extensions first, you may experience issues:', 'woocommerce' ), $new_version );

		ob_start();
		include( 'views/html-notice-untested-extensions-inline.php' );
		return ob_get_clean();
	}

	protected function get_extensions_modal_warning() {
		$version_parts = explode( '.', $this->new_version );
		$new_version = $version_parts[0] . '.0';

		$plugins = $this->major_untested_plugins;

		ob_start();
		include( 'views/html-notice-untested-extensions-modal.php' );
		return ob_get_clean();
	}

	protected function get_upgrade_notice( $version ) {
		$transient_name = 'wc_upgrade_notice_' . $version;

		//if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {
			//$response = wp_safe_remote_get( 'https://plugins.svn.wordpress.org/woocommerce/trunk/readme.txt' );
			$response = wp_safe_remote_get( 'http://local.wordpress.dev/wp-content/plugins/woocommerce/readme.txt' );
			if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
				$upgrade_notice = $this->parse_update_notice( $response['body'], $version );
			//	set_transient( $transient_name, $upgrade_notice, 1/*DAY_IN_SECONDS*/ );
			}
		//}

		return $upgrade_notice;
	}


	/**
	 * Parse update notice from readme file.
	 *
	 * @param  string $content
	 * @param  string $new_version
	 * @return string
	 */
	private function parse_update_notice( $content, $new_version ) {
		// Output Upgrade Notice.
		$matches        = null;
		$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( $new_version ) . '\s*=|$)~Uis';
		$upgrade_notice = '';

		if ( preg_match( $regexp, $content, $matches ) ) {
			$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

			// Convert the full version strings to minor versions.
			$notice_version_parts  = explode( '.', trim( $matches[1] ) );
			$current_version_parts = explode( '.', WC_VERSION );

			if ( 3 !== sizeof( $notice_version_parts ) ) {
				return;
			}

			$notice_version  = $notice_version_parts[0] . '.' . $notice_version_parts[1];
			$current_version = $current_version_parts[0] . '.' . $current_version_parts[1];

			// Check the latest stable version and ignore trunk.
			if ( version_compare( $current_version, $notice_version, '<' ) ) {

				$upgrade_notice .= '</p><p class="wc_plugin_upgrade_notice">';

				foreach ( $notices as $index => $line ) {
					$upgrade_notice .= preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line );
				}
			}
		}

		return wp_kses_post( $upgrade_notice );
	}

	/*
	|--------------------------------------------------------------------------
	| Data Helpers
	|--------------------------------------------------------------------------
	|
	| Methods for getting & manipulating data.
	*/

	/**
	 * Get plugins that have a tested version lower than the input version.
	 *
	 * @param string $version
	 * @param string $release 'major' or 'minor'.
	 * @return array of plugin info arrays
	 */
	protected function get_untested_plugins( $version, $release ) {
		$extensions = $this->get_plugins_with_header( self::VERSION_TESTED_HEADER );
		$untested = array();

		$version_parts = explode( '.', $version );
		$version = $version_parts[0];

		if ( 'minor' === $release ) {
			$version .= '.' . $version_parts[1];
		}

		foreach ( $extensions as $file => $plugin ) {
			$plugin_version_parts = explode( '.', $plugin[ self::VERSION_TESTED_HEADER ] );
			$plugin_version = $plugin_version_parts[0];

			if ( 'minor' === $release ) {
				$plugin_version .= '.' . $plugin_version_parts[1];
			}

			if ( version_compare( $plugin_version, $version, '<' ) ) {
				$untested[ $file ] = $plugin;
			}
		}

		return $untested;
	}

	/**
	 * Get plugins that have a valid value for a specific header.
	 *
	 * @param string $header
	 * @return array of plugin info arrays
	 */
	protected function get_plugins_with_header( $header ) {
		$plugins = get_plugins();
		$matches = array();

		foreach ( $plugins as $file => $plugin ) {
			if ( ! empty ( $plugin[ $header ] ) ) {
				$matches[ $file ] = $plugin;
			}
		}

		return $matches;
	}
}
new WC_Admin_Plugin_Updates();
