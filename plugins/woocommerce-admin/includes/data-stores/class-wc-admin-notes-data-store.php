<?php

/**
 * WC_Admin_Note_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC Admin Note Data Store (Custom Tables)
 */
class WC_Admin_Notes_Data_Store extends WC_Data_Store_WP implements WC_Admin_Notes_Data_Store_Interface, WC_Object_Data_Store_Interface {
	/**
	 * Method to create a new note in the database.
	 *
	 * @param WC_Admin_Note
	 */
	public function create( &$note ) {
		$note->set_date_created( current_time( 'timestamp' ) );

		global $wpdb;

		$note_to_be_inserted = array(
			'name'          => $note->get_name(),
			'type'          => $note->get_type(),
			'locale'        => $note->get_locale(),
			'title'         => $note->get_title(),
			'content'       => $note->get_content(),
			'icon'          => $note->get_icon(),
			'content_data'  => '', // TODO $note->get_content_data(),
			'status'        => $note->get_status(),
			'source'        => $note->get_source(),
			'date_created'  => '2018-09-21 22:45:16', // TODO $note->get_date_created(),
			'date_reminder' => '', // TODO $note->get_date_reminder(),
		);

		$wpdb->insert( $wpdb->prefix . 'woocommerce_admin_notes', $note_to_be_inserted );
		$note_id = $wpdb->insert_id;
		$note->set_id( $note_id );
		$note->save_meta_data();
		// TODO $note->save_actions( $note );
		$note->apply_changes();

		do_action( 'woocommerce_new_note', $note_id );
	}

	/**
	 * Method to read a note.
	 *
	 * @param WC_Admin_Note
	 */
	public function read( &$note ) {
		global $wpdb;

		$note->set_defaults();
		$note_row = false;

		$note_id = $note->get_id();
		if ( 0 !== $note_id || '0' !== $note_id ) {
			$note_row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT name, type, locale, title, content, icon, content_data, status, source, date_created, date_reminder FROM {$wpdb->prefix}woocommerce_admin_notes WHERE note_id = %d LIMIT 1",
					$note->get_id()
				)
			);
		}

		if ( 0 === $note->get_id() || '0' === $note->get_id() ) {
			$this->read_actions( $note );
			$note->read_meta_data();
			$note->set_object_read( true );
			do_action( 'woocommerce_admin_note_loaded', $note );
		} elseif ( $note_row ) {
			$note->set_name( $note_row->name );
			$note->set_type( $note_row->type );
			$note->set_locale( $note_row->locale );
			$note->set_title( $note_row->title );
			$note->set_content( $note_row->content );
			$note->set_icon( $note_row->icon );
			$note->set_content_data( $note_row->content_data );
			$note->set_status( $note_row->status );
			$note->set_source( $note_row->source );
			$note->set_date_created( $note_row->date_created );
			$note->set_date_reminder( $note_row->date_reminder );
			$this->read_actions( $note );
			$note->read_meta_data();
			$note->set_object_read( true );
			do_action( 'woocommerce_admin_note_loaded', $note );
		} else {
			throw new Exception( __( 'Invalid data store for admin note.', 'woocommerce' ) );
		}
	}

	/**
	 * Updates a note in the database.
	 *
	 * @param WC_Admin_Note
	 */
	public function update( &$note ) {
		global $wpdb;
		if ( $note->get_id() ) {
			$wpdb->update(
				$wpdb->prefix . 'woocommerce_admin_notes', array(
					'name'          => $note->get_name(),
					'type'          => $note->get_type(),
					'locale'        => $note->get_locale(),
					'title'         => $note->get_title(),
					'content'       => $note->get_content(),
					'icon'          => $note->get_icon(),
					'content_data'  => $note->get_content_data(),
					'status'        => $note->get_status(),
					'source'        => $note->get_source(),
					'date_created'  => $note->get_date_created(),
					'date_reminder' => $note->get_date_reminder(),
				), array( 'note_id' => $note->get_id() )
			);
		}

		$note->save_meta_data();
		// TODO $note->save_actions( $note );
		$note->apply_changes();
		do_action( 'woocommerce_update_note', $note->get_id() );
	}

	/**
	 * Deletes a note from the database.
	 *
	 * @param WC_Admin_Note
	 * @param array $args Array of args to pass to the delete method (not used).
	 */
	public function delete( &$note, $args = array() ) {
		$note_id = $note->get_id();
		if ( $note->get_id() ) {
			global $wpdb;
			$wpdb->delete( $wpdb->prefix . 'woocommerce_admin_notes', array( 'note_id' => $note_id ) );
			$wpdb->delete( $wpdb->prefix . 'woocommerce_admin_note_actions', array( 'note_id' => $note_id ) );
			$note->set_id( null );
		}
		do_action( 'woocommerce_trash_note', $id );
	}

	/**
	 * Read actions from the database.
	 *
	 * @param WC_Admin_Note $note Note object.
	 *
	 */
	private function read_actions( &$note ) {
		global $wpdb;

		$actions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT name, label, query FROM {$wpdb->prefix}woocommerce_admin_note_actions WHERE note_id = %d",
				$note->get_id()
			)
		);
		if ( $actions ) {
			foreach ( $actions as $action ) {
				$note->add_action( $action->name, $action->label, $action->query );
			}
		}
	}

	/**
	 * Save actions to the database.
	 * This function clears old actions, then re-inserts new if any changes are found.
	 *
	 * @param WC_Admin_Note $note Note object.
	 *
	 * @return bool|void
	 */
	private function save_actions( &$note ) {
		$changed_props = array_keys( $note->get_changes() );
		if ( ! in_array( 'note_actions', $changed_props, true ) ) {
			return false;
		}

		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'woocommerce_admin_note_actions', array( 'note_id' => $note->get_id() ) );
		foreach ( $note->get_actions( 'edit' ) as $action ) {
			$wpdb->insert(
				$wpdb->prefix . 'woocommerce_admin_note_actions', array(
					'note_id' => $note->get_id(),
					'name'    => $action->name,
					'label'   => $action->label,
					'query'   => $action->query,
				)
			);
		}
	}

	/**
	 * Mark a note as actioned
	 *
	 * @param WC_Admin_Note $note Note object.
	 */
	public function mark_note_as_actioned( &$note ) {
		$note->set_status( E_WC_ADMIN_NOTE_ACTIONED );
	}

	/**
	 * Mark a note for reminder
	 *
	 * @param WC_Admin_Note $note Note object.
	 * @param int           $days Number of days until reminder
	 */
	public function mark_note_for_reminder( &$note, $days = 3 ) {
		$when = current_time( 'timestamp', 1 ) + intval( $days ) * DAY_IN_SECONDS;
		$note->set_date_reminder( $when );
	}

	/**
	 * Return an ordered list of notes.
	 *
	 * @return array An array of objects containing a note id.
	 */
	public function get_notes() {
		global $wpdb;
		return $wpdb->get_results( "SELECT note_id, title, content FROM {$wpdb->prefix}woocommerce_admin_notes order by note_id ASC;" );
	}
}
