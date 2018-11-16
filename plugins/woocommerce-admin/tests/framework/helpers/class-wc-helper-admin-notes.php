<?php

/**
 * Class WC_Helper_Admin_Notes.
 *
 * This helper class should ONLY be used for unit tests!.
 */
class WC_Helper_Admin_Notes {

	/**
	 * Delete everything in the notes tables.
	 */
	public static function reset_notes_dbs() {
		global $wpdb;
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_admin_notes" ); // @codingStandardsIgnoreLine.
		$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}woocommerce_admin_note_actions" ); // @codingStandardsIgnoreLine.
	}

	/**
	 * Create two notes that we can use for notes REST API tests
	 */
	public static function add_notes_for_tests() {
		$data_store = WC_Data_Store::load( 'admin-note' );

		$note_1 = new WC_Admin_Note();
		$note_1->set_title( 'PHPUNIT_TEST_NOTE_1_TITLE' );
		$note_1->set_content( 'PHPUNIT_TEST_NOTE_1_CONTENT' );
		$note_1->set_content_data( (object) array( 'amount' => 1.23 ) );
		$note_1->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_INFORMATIONAL );
		$note_1->set_icon( 'info' );
		$note_1->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note_1->set_source( 'PHPUNIT_TEST' );
		$note_1->add_action(
			'PHPUNIT_TEST_NOTE_1_ACTION_1_SLUG',
			'PHPUNIT_TEST_NOTE_1_ACTION_1_LABEL',
			'?s=PHPUNIT_TEST_NOTE_1_ACTION_1_URL'
		);
		$note_1->add_action(
			'PHPUNIT_TEST_NOTE_1_ACTION_2_SLUG',
			'PHPUNIT_TEST_NOTE_1_ACTION_2_LABEL',
			'?s=PHPUNIT_TEST_NOTE_1_ACTION_2_URL'
		);
		$note_1->save();

		$note_2 = new WC_Admin_Note();
		$note_2->set_title( 'PHPUNIT_TEST_NOTE_2_TITLE' );
		$note_2->set_content( 'PHPUNIT_TEST_NOTE_2_CONTENT' );
		$note_2->set_content_data( (object) array( 'amount' => 4.56 ) );
		$note_2->set_type( WC_Admin_Note::E_WC_ADMIN_NOTE_WARNING );
		$note_2->set_icon( 'info' );
		$note_2->set_name( 'PHPUNIT_TEST_NOTE_NAME' );
		$note_2->set_source( 'PHPUNIT_TEST' );
		// This note has no actions.
		$note_2->save();

	}
}
