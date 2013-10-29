<?php
/**
 * Debug/Status page
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/System Status
 * @version     2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WC_Admin_Status' ) ) :

/**
 * WC_Admin_Status Class
 */
class WC_Admin_Status {

	/**
	 * Handles output of the reports page in admin.
	 */
	public function output() {
		$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : 'status';

		include_once( 'views/html-admin-page-status.php' );
	}

	/**
	 * Handles output of report
	 */
	public function status_report() {
		global $woocommerce, $wpdb;

		include_once( 'views/html-admin-page-status-report.php' );
	}

	/**
	 * Handles output of tools
	 */
	public function status_tools() {
		global $woocommerce, $wpdb;

		$tools = $this->get_tools();

		if ( ! empty( $_GET['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'debug_action' ) ) {

			switch ( $_GET['action'] ) {
				case "clear_transients" :
					wc_delete_product_transients();

					echo '<div class="updated"><p>' . __( 'Product Transients Cleared', 'woocommerce' ) . '</p></div>';
				break;
				case "clear_expired_transients" :

					// http://w-shadow.com/blog/2012/04/17/delete-stale-transients/
					$rows = $wpdb->query( "
						DELETE
							a, b
						FROM
							{$wpdb->options} a, {$wpdb->options} b
						WHERE
							a.option_name LIKE '_transient_%' AND
							a.option_name NOT LIKE '_transient_timeout_%' AND
							b.option_name = CONCAT(
								'_transient_timeout_',
								SUBSTRING(
									a.option_name,
									CHAR_LENGTH('_transient_') + 1
								)
							)
							AND b.option_value < UNIX_TIMESTAMP()
					" );

					$rows2 = $wpdb->query( "
						DELETE
							a, b
						FROM
							{$wpdb->options} a, {$wpdb->options} b
						WHERE
							a.option_name LIKE '_site_transient_%' AND
							a.option_name NOT LIKE '_site_transient_timeout_%' AND
							b.option_name = CONCAT(
								'_site_transient_timeout_',
								SUBSTRING(
									a.option_name,
									CHAR_LENGTH('_site_transient_') + 1
								)
							)
							AND b.option_value < UNIX_TIMESTAMP()
					" );

					echo '<div class="updated"><p>' . sprintf( __( '%d Transients Rows Cleared', 'woocommerce' ), $rows + $rows2 ) . '</p></div>';

				break;
				case "reset_roles" :
					// Remove then re-add caps and roles
					$installer = include( WC()->plugin_path() . '/includes/class-wc-install.php' );
					$installer->remove_roles();
					$installer->create_roles();

					echo '<div class="updated"><p>' . __( 'Roles successfully reset', 'woocommerce' ) . '</p></div>';
				break;
				case "recount_terms" :

					$product_cats = get_terms( 'product_cat', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );

					_woocommerce_term_recount( $product_cats, get_taxonomy( 'product_cat' ), false, false );

					$product_tags = get_terms( 'product_tag', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );

					_woocommerce_term_recount( $product_tags, get_taxonomy( 'product_tag' ), false, false );

					echo '<div class="updated"><p>' . __( 'Terms successfully recounted', 'woocommerce' ) . '</p></div>';
				break;
				case "clear_sessions" :

					$wpdb->query( "
						DELETE FROM {$wpdb->options}
						WHERE option_name LIKE '_wc_session_%' OR option_name LIKE '_wc_session_expires_%'
					" );

					wp_cache_flush();

				break;
				default:
					$action = esc_attr( $_GET['action'] );
					if( isset( $tools[ $action ]['callback'] ) ) {
						$callback = $tools[ $action ]['callback'];
						$return = call_user_func( $callback );
						if( $return === false ) {
							if( is_array( $callback ) ) {
								echo '<div class="error"><p>' . sprintf( __( 'There was an error calling %s::%s', 'woocommerce' ), get_class( $callback[0] ), $callback[1] ) . '</p></div>';

							} else {
								echo '<div class="error"><p>' . sprintf( __( 'There was an error calling %s', 'woocommerce' ), $callback ) . '</p></div>';
							}
						}
					}
				break;
			}
		}

		include_once( 'views/html-admin-page-status-tools.php' );
	}

	/**
	 * Get tools
	 *
	 * @return array of tools
	 */
	public function get_tools() {
		return apply_filters( 'woocommerce_debug_tools', array(
			'clear_transients' => array(
				'name'		=> __( 'WC Transients','woocommerce'),
				'button'	=> __('Clear transients','woocommerce'),
				'desc'		=> __( 'This tool will clear the product/shop transients cache.', 'woocommerce' ),
			),
			'clear_expired_transients' => array(
				'name'		=> __( 'Expired Transients','woocommerce'),
				'button'	=> __('Clear expired transients','woocommerce'),
				'desc'		=> __( 'This tool will clear ALL expired transients from Wordpress.', 'woocommerce' ),
			),
			'recount_terms' => array(
				'name'		=> __('Term counts','woocommerce'),
				'button'	=> __('Recount terms','woocommerce'),
				'desc'		=> __( 'This tool will recount product terms - useful when changing your settings in a way which hides products from the catalog.', 'woocommerce' ),
			),
			'reset_roles' => array(
				'name'		=> __('Capabilities','woocommerce'),
				'button'	=> __('Reset capabilities','woocommerce'),
				'desc'		=> __( 'This tool will reset the admin, customer and shop_manager roles to default. Use this if your users cannot access all of the WooCommerce admin pages.', 'woocommerce' ),
			),
			'clear_sessions' => array(
				'name'		=> __('Customer Sessions','woocommerce'),
				'button'	=> __('Clear all sessions','woocommerce'),
				'desc'		=> __( '<strong class="red">Warning</strong> This tool will delete all customer session data from the database, including any current live carts.', 'woocommerce' ),
			),
		) );
	}

	/**
	 * Scan the template files
	 *
	 * @access public
 	 * @param mixed $template_path
 	 * @return void
	 */
	public function scan_template_files( $template_path ) {
		$files         = scandir( $template_path );
		$result        = array();
		if ( $files ) {
			foreach ( $files as $key => $value ) {
				if ( ! in_array( $value, array( ".",".." ) ) ) {
					if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
						$sub_files = $this->scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
						foreach ( $sub_files as $sub_file ) {
							$result[] = $value . DIRECTORY_SEPARATOR . $sub_file;
						}
					} else {
						$result[] = $value;
					}
				}
			}
		}
		return $result;
	}
}

endif;

return new WC_Admin_Status();