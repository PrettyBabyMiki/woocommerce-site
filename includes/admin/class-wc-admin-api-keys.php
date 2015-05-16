<?php
/**
 * WooCommerce Admin API Keys Class.
 *
 * @author   WooThemes
 * @category Admin
 * @package  WooCommerce/Admin
 * @version  2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_API_Keys
 */
class WC_Admin_API_Keys {

	/**
	 * Initialize the webhooks admin actions
	 */
	public function __construct() {

	}

	/**
	 * Page output
	 */
	public static function page_output() {
		// Hide the save button
		$GLOBALS['hide_save_button'] = true;

		if ( isset( $_GET['create-key'] ) || isset( $_GET['edit-key'] ) ) {
			include( 'settings/views/html-keys-edit.php' );
		} else {
			self::table_list_output();
		}
	}

	/**
	 * Table list output
	 */
	private static function table_list_output() {
		echo '<h3>' . __( 'Keys/Apps', 'woocommerce' ) . ' <a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=api&section=keys&create-key=1' ) ) . '" class="add-new-h2">' . __( 'Add Key', 'woocommerce' ) . '</a></h3>';

		$keys_table_list = new WC_Admin_API_Keys_Table_List();
		$keys_table_list->prepare_items();

		echo '<input type="hidden" name="page" value="wc-settings" />';
		echo '<input type="hidden" name="tab" value="api" />';
		echo '<input type="hidden" name="section" value="keys" />';

		$keys_table_list->views();
		$keys_table_list->search_box( __( 'Search Key', 'woocommerce' ), 'key' );
		$keys_table_list->display();
	}

	/**
	 * Get key data
	 *
	 * @param  int $key_id
	 * @return array
	 */
	private static function get_key_data( $key_id ) {
		global $wpdb;

		$empty = array(
			'key_id'          => 0,
			'user_id'         => '',
			'description'     => '',
			'permissions'     => '',
			'consumer_key'    => '',
			'consumer_secret' => ''
		);

		if ( 0 == $key_id ) {
			return $empty;
		}

		$key = $wpdb->get_row( $wpdb->prepare( "
			SELECT key_id, user_id, description, permissions, consumer_key, consumer_secret
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE key_id = %d
		", $key_id ), ARRAY_A );

		if ( is_null( $key ) ) {
			return $empty;
		}

		return $key;
	}
}

new WC_Admin_API_Keys();
