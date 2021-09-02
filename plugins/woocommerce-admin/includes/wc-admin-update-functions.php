<?php
/**
 * WooCommerce Admin Updates
 *
 * Functions for updating data, used by the background updater.
 *
 * @package WooCommerce\Admin
 */

use \Automattic\WooCommerce\Admin\Install as Installer;
use \Automattic\WooCommerce\Admin\Notes\Notes;
use \Automattic\WooCommerce\Admin\Notes\DeactivatePlugin;

/**
 * Update order stats `status` index length.
 * See: https://github.com/woocommerce/woocommerce-admin/issues/2969.
 */
function wc_admin_update_0201_order_status_index() {
	global $wpdb;

	// Max DB index length. See wp_get_db_schema().
	$max_index_length = 191;

	$index = $wpdb->get_row( "SHOW INDEX FROM {$wpdb->prefix}wc_order_stats WHERE key_name = 'status'" );

	if ( property_exists( $index, 'Sub_part' ) ) {
		// The index was created with the right length. Time to bail.
		if ( $max_index_length === $index->Sub_part ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			return;
		}

		// We need to drop the index so it can be recreated.
		$wpdb->query( "DROP INDEX `status` ON {$wpdb->prefix}wc_order_stats" );
	}

	// Recreate the status index with a max length.
	$wpdb->query( $wpdb->prepare( "ALTER TABLE {$wpdb->prefix}wc_order_stats ADD INDEX status (status(%d))", $max_index_length ) );
}

/**
 * Update DB Version.
 */
function wc_admin_update_0201_db_version() {
	Installer::update_db_version( '0.20.1' );
}

/**
 * Rename "gross_total" to "total_sales".
 * See: https://github.com/woocommerce/woocommerce-admin/issues/3175
 */
function wc_admin_update_0230_rename_gross_total() {
	global $wpdb;

	// We first need to drop the new `total_sales` column, since dbDelta() will have created it.
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}wc_order_stats DROP COLUMN `total_sales`" );
	// Then we can rename the existing `gross_total` column.
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}wc_order_stats CHANGE COLUMN `gross_total` `total_sales` double DEFAULT 0 NOT NULL" );
}

/**
 * Update DB Version.
 */
function wc_admin_update_0230_db_version() {
	Installer::update_db_version( '0.23.0' );
}

/**
 * Remove the note unsnoozing scheduled action.
 */
function wc_admin_update_0251_remove_unsnooze_action() {
	as_unschedule_action( Notes::UNSNOOZE_HOOK, null, 'wc-admin-data' );
	as_unschedule_action( Notes::UNSNOOZE_HOOK, null, 'wc-admin-notes' );
}

/**
 * Update DB Version.
 */
function wc_admin_update_0251_db_version() {
	Installer::update_db_version( '0.25.1' );
}

/**
 * Remove Facebook Extension note.
 */
function wc_admin_update_110_remove_facebook_note() {
	Notes::delete_notes_with_name( 'wc-admin-facebook-extension' );
}

/**
 * Update DB Version.
 */
function wc_admin_update_110_db_version() {
	Installer::update_db_version( '1.1.0' );
}

/**
 * Remove Dismiss action from tracking opt-in admin note.
 */
function wc_admin_update_130_remove_dismiss_action_from_tracking_opt_in_note() {
	global $wpdb;

	$wpdb->query( "DELETE actions FROM {$wpdb->prefix}wc_admin_note_actions actions INNER JOIN {$wpdb->prefix}wc_admin_notes notes USING (note_id) WHERE actions.name = 'tracking-dismiss' AND notes.name = 'wc-admin-usage-tracking-opt-in'" );
}

/**
 * Update DB Version.
 */
function wc_admin_update_130_db_version() {
	Installer::update_db_version( '1.3.0' );
}

/**
 * Change the deactivate plugin note type to 'info'.
 */
function wc_admin_update_140_change_deactivate_plugin_note_type() {
	global $wpdb;

	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}wc_admin_notes SET type = 'info' WHERE name = %s", DeactivatePlugin::NOTE_NAME ) );
}

/**
 * Update DB Version.
 */
function wc_admin_update_140_db_version() {
	Installer::update_db_version( '1.4.0' );
}

/**
 * Remove Facebook Experts note.
 */
function wc_admin_update_160_remove_facebook_note() {
	Notes::delete_notes_with_name( 'wc-admin-facebook-marketing-expert' );
}

/**
 * Update DB Version.
 */
function wc_admin_update_160_db_version() {
	Installer::update_db_version( '1.6.0' );
}

/**
 * Set "two column" homescreen layout as default for existing stores.
 */
function wc_admin_update_170_homescreen_layout() {
	add_option( 'woocommerce_default_homepage_layout', 'two_columns', '', 'no' );
}

/**
 * Update DB Version.
 */
function wc_admin_update_170_db_version() {
	Installer::update_db_version( '1.7.0' );
}

/**
 * Update the old task list options.
 */
function wc_admin_update_270_update_task_list_options() {
	$hidden_lists         = get_option( 'woocommerce_task_list_hidden_lists', array() );
	$setup_list_hidden    = get_option( 'woocommerce_task_list_hidden', 'no' );
	$extended_list_hidden = get_option( 'woocommerce_extended_task_list_hidden', 'no' );
	if ( 'yes' === $setup_list_hidden ) {
		$hidden_lists[] = 'setup';
	}
	if ( 'yes' === $extended_list_hidden ) {
		$hidden_lists[] = 'extended';
	}

	update_option( 'woocommerce_task_list_hidden_lists', array_unique( $hidden_lists ) );
	delete_option( 'woocommerce_task_list_hidden' );
	delete_option( 'woocommerce_extended_task_list_hidden' );
}

/**
 * Update DB Version.
 */
function wc_admin_update_270_db_version() {
	Installer::update_db_version( '2.7.0' );
}
