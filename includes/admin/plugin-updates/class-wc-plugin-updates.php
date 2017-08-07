<?php
/**
 * Class for displaying plugin warning notifications and determining 3rd party plugin compatibility.
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
class WC_Plugin_Updates {

	/**
	 * This is the header used by extensions to show requirements.
	 * @var string
	 */
	const VERSION_REQUIRED_HEADER = 'WC requires at least';

	/**
	 * This is the header used by extensions to show testing.
	 * @var string
	 */
	const VERSION_TESTED_HEADER = 'WC tested up to';

	/**
	 * The version for the update to WooCommerce.
	 * @var string
	 */
	protected $new_version = '';

	/**
	 * Array of plugins lacking testing with the major version.
	 * @var array
	 */
	protected $major_untested_plugins = array();

	/**
	 * Array of plugins lacking testing with the minor version.
	 * @var array
	 */
	protected $minor_untested_plugins = array();

	/**
	 * Common JS for initializing and managing thickbox-based modals.
	 */
	protected function generic_modal_js() {
		?>
		<script>
			( function( $ ) {
				// Initialize thickbox.
				tb_init( '.wc-thickbox' );

				var old_tb_position = false;

				// Make the WC thickboxes look good when opened.
				$( '.wc-thickbox' ).on( 'click', function( evt ) {
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

	/**
	 * Get the inline warning notice for minor version updates.
	 *
	 * @return string
	 */
	protected function get_extensions_inline_warning_minor() {
		$upgrade_type  = 'minor';
		$plugins       = ! empty( $this->major_untested_plugins ) ? array_diff_key( $this->minor_untested_plugins, $this->major_untested_plugins ) : $this->minor_untested_plugins;
		$version_parts = explode( '.', $this->new_version );
		$new_version   = $version_parts[0] . '.' . $version_parts[1];

		if ( empty( $plugins ) ) {
			return;
		}

		/* translators: %s: version number */
		$message = sprintf( __( 'The installed versions of the following plugin(s) are not tested with WooCommerce %s. If possible, update these plugins before updating WooCommerce:', 'woocommerce' ), $new_version );

		ob_start();
		include( 'views/html-notice-untested-extensions-inline.php' );
		return ob_get_clean();
	}

	/**
	 * Get the inline warning notice for major version updates.
	 *
	 * @return string
	 */
	protected function get_extensions_inline_warning_major() {
		$upgrade_type  = 'major';
		$plugins       = $this->major_untested_plugins;
		$version_parts = explode( '.', $this->new_version );
		$new_version   = $version_parts[0] . '.0';

		if ( empty( $plugins ) ) {
			return;
		}

		/* translators: %s: version number */
		$message = sprintf( __( 'Heads up! The installed versions of the following plugin(s) are not tested with WooCommerce %s and may not be fully-compatible. Please update these extensions or confirm they are compatible first, or you may experience issues:', 'woocommerce' ), $new_version );

		ob_start();
		include( 'views/html-notice-untested-extensions-inline.php' );
		return ob_get_clean();
	}

	/**
	 * Get the warning notice for the modal window.
	 *
	 * @return string
	 */
	protected function get_extensions_modal_warning() {
		$version_parts = explode( '.', $this->new_version );
		$new_version   = $version_parts[0] . '.0';
		$plugins       = $this->major_untested_plugins;

		ob_start();
		include( 'views/html-notice-untested-extensions-modal.php' );
		return ob_get_clean();
	}

	/*
	|--------------------------------------------------------------------------
	| Data Helpers
	|--------------------------------------------------------------------------
	|
	| Methods for getting & manipulating data.
	*/

	/**
	 * Get active plugins that have a tested version lower than the input version.
	 *
	 * @param string $version
	 * @param string $release 'major' or 'minor'.
	 * @return array of plugin info arrays
	 */
	public function get_untested_plugins( $version, $release ) {
		$extensions    = $this->get_plugins_with_header( self::VERSION_TESTED_HEADER );
		$untested      = array();
		$version_parts = explode( '.', $version );
		$version       = $version_parts[0];

		if ( 'minor' === $release ) {
			$version .= '.' . $version_parts[1];
		}

		foreach ( $extensions as $file => $plugin ) {
			$plugin_version_parts = explode( '.', $plugin[ self::VERSION_TESTED_HEADER ] );

			if ( ! is_numeric( $plugin_version_parts[0] )
				|| ( 'minor' === $release && ! isset( $plugin_version_parts[1] ) )
				|| ( 'minor' === $release && ! is_numeric( $plugin_version_parts[1] ) )
				) {
				continue;
			}

			$plugin_version = $plugin_version_parts[0];

			if ( 'minor' === $release ) {
				$plugin_version .= '.' . $plugin_version_parts[1];
			}

			if ( version_compare( $plugin_version, $version, '<' ) && is_plugin_active( $file ) ) {
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
			if ( ! empty( $plugin[ $header ] ) ) {
				$matches[ $file ] = $plugin;
			}
		}

		return apply_filters( 'woocommerce_get_plugins_with_header', $matches, $header, $plugins );
	}
}
