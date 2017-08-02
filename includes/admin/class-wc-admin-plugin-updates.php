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
	 * The upgrade notice shown inline.
	 * @var string
	 */
	protected $upgrade_notice = '';

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
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'extra_plugin_headers', array( $this, 'enable_wc_plugin_headers' ) );

		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		if ( 'plugins' === $screen->id ) {
			add_action( 'in_plugin_update_message-woocommerce/woocommerce.php', array( $this, 'in_plugin_update_message' ), 10, 2 );
		}

		if ( 'update-core' === $screen->id ) {
			add_action( 'admin_print_footer_scripts', array( $this, 'update_screen_modal' ) );
		}
	}

	/**
	 * Read in WooCommerce headers when reading plugin headers.
	 *
	 * @param array $headers
	 * @return array $headers
	 */
	public function enable_wc_plugin_headers( $headers ) {
		$headers['WCRequires'] = self::VERSION_REQUIRED_HEADER;
		$headers['WCTested']   = self::VERSION_TESTED_HEADER;
		return $headers;
	}

	/**
	 * Show plugin changes on the plugins screen. Code adapted from W3 Total Cache.
	 *
	 * @param array $args
	 */
	public function in_plugin_update_message( $args, $response ) {
		$this->new_version            = $response->new_version;
		$this->upgrade_notice         = $this->get_upgrade_notice( $response->new_version );
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
			add_action( 'admin_print_footer_scripts', array( $this, 'plugin_screen_modal_js' ) );
		}

		echo apply_filters( 'woocommerce_in_plugin_update_message', $this->upgrade_notice ? '</p>' . wp_kses_post( $this->upgrade_notice ) . '<p class="dummy">' : '' );
	}

	/**
	 * Show a warning message on the upgrades screen if the user tries to upgrade and has untested plugins.
	 */
	public function update_screen_modal() {
		$updateable_plugins = get_plugin_updates();
		if ( empty( $updateable_plugins['woocommerce/woocommerce.php'] )
			|| empty( $updateable_plugins['woocommerce/woocommerce.php']->update )
			|| empty( $updateable_plugins['woocommerce/woocommerce.php']->update->new_version ) ) {
			return;
		}

		$this->new_version = wc_clean( $updateable_plugins['woocommerce/woocommerce.php']->update->new_version );
		$this->major_untested_plugins = $this->get_untested_plugins( $this->new_version, 'major' );
		if ( empty( $this->major_untested_plugins ) ) {
			return;
		}

		echo $this->get_extensions_modal_warning();
		$this->update_screen_modal_js();
	}

	/*
	|--------------------------------------------------------------------------
	| Modal JS
	|--------------------------------------------------------------------------
	|
	| JS snippets for handling modals.
	*/

	/**
	 * Common JS for initializing and managing the modals.
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

	/**
	 * JS for the modal window on the plugins screen.
	 */
	public function plugin_screen_modal_js() {
		?>
		<script>
			( function( $ ) {
				var $update_box = $( '#woocommerce-update' );
				var $update_link = $update_box.find('a.update-link').first();
				var update_url = $update_link.attr( 'href' );

				// Set up thickbox.
				$update_link.removeClass( 'update-link' );
				$update_link.addClass( 'wc-thickbox' );
				$update_link.attr( 'href', '#TB_inline?height=600&width=550&inlineId=wc_untested_extensions_modal' );

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
		$this->generic_modal_js();
	}

	/**
	 * JS for the modal window on the updates screen.
	 */
	protected function update_screen_modal_js() {
		?>
		<script>
			( function( $ ) {
				var modal_dismissed = false;

				// Show the modal if the WC upgrade checkbox is checked.
				var show_modal_if_checked = function() {
					if ( modal_dismissed ) {
						return;
					}
					var $checkbox = $( 'input[value="woocommerce/woocommerce.php"]' );
					if ( $checkbox.prop( 'checked' ) ) {
						$( '#wc-upgrade-warning' ).click();
					}
				}

				$( '#plugins-select-all, input[value="woocommerce/woocommerce.php"]' ).on( 'change', function() {
					show_modal_if_checked();
				} );

				// Add a hidden thickbox link to use for bringing up the modal.
				$('body').append( '<a href="#TB_inline?height=600&width=550&inlineId=wc_untested_extensions_modal" class="wc-thickbox" id="wc-upgrade-warning" style="display:none"></a>' );

				// Don't show the modal again once it's been accepted.
				$( '#wc_untested_extensions_modal .accept' ).on( 'click', function( evt ) {
					evt.preventDefault();
					modal_dismissed = true;
					tb_remove();
				});

				// Uncheck the WC update checkbox if the modal is canceled.
				$( '#wc_untested_extensions_modal .cancel a' ).on( 'click', function( evt ) {
					evt.preventDefault();
					$( 'input[value="woocommerce/woocommerce.php"]' ).prop( 'checked', false );
					tb_remove();
				});
			})( jQuery );
		</script>
		<?php
		$this->generic_modal_js();
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

	/**
	 * Get the upgrade notice from WordPress.org.
	 *
	 * @param  string $version
	 * @return string
	 */
	protected function get_upgrade_notice( $version ) {
		$transient_name = 'wc_upgrade_notice_' . $version;

		if ( false === ( $upgrade_notice = get_transient( $transient_name ) ) ) {
			$response = wp_safe_remote_get( 'https://plugins.svn.wordpress.org/woocommerce/trunk/readme.txt' );

			if ( ! is_wp_error( $response ) && ! empty( $response['body'] ) ) {
				$upgrade_notice = $this->parse_update_notice( $response['body'], $version );
				set_transient( $transient_name, $upgrade_notice, DAY_IN_SECONDS );
			}
		}
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
		$version_parts     = explode( '.', $new_version );
		$check_for_notices = array(
			$version_parts[0] . '.0', // Major
			$version_parts[0] . '.0.0', // Major
			$version_parts[0] . '.' . $version_parts[1], // Minor
			$version_parts[0] . '.' . $version_parts[1] . '.' . $version_parts[2], // Patch
		);

		foreach ( $check_for_notices as $check_version ) {
			if ( version_compare( WC_VERSION, $check_version, '>' ) ) {
				continue;
			}
			$matches        = null;
			$regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*' . preg_quote( $new_version ) . '\s*=|$)~Uis';
			$upgrade_notice = '';

			if ( preg_match( $regexp, $content, $matches ) ) {
				$notices = (array) preg_split( '~[\r\n]+~', trim( $matches[2] ) );

				if ( version_compare( trim( $matches[1] ), $check_version, '=' ) ) {
					$upgrade_notice .= '<p class="wc_plugin_upgrade_notice">';

					foreach ( $notices as $index => $line ) {
						$upgrade_notice .= preg_replace( '~\[([^\]]*)\]\(([^\)]*)\)~', '<a href="${2}">${1}</a>', $line );
					}

					$upgrade_notice .= '</p>';
				}
				break;
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
	 * Get active plugins that have a tested version lower than the input version.
	 *
	 * @param string $version
	 * @param string $release 'major' or 'minor'.
	 * @return array of plugin info arrays
	 */
	protected function get_untested_plugins( $version, $release ) {
		$extensions    = $this->get_plugins_with_header( self::VERSION_TESTED_HEADER );
		$untested      = array();
		$version_parts = explode( '.', $version );
		$version       = $version_parts[0];

		if ( 'minor' === $release ) {
			$version .= '.' . $version_parts[1];
		}

		foreach ( $extensions as $file => $plugin ) {
			$plugin_version_parts = explode( '.', $plugin[ self::VERSION_TESTED_HEADER ] );
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

		return $matches;
	}
}
new WC_Admin_Plugin_Updates();
