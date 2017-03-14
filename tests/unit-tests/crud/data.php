<?php

/**
 * Meta
 * @package WooCommerce\Tests\CRUD
 */
class WC_Tests_CRUD_Data extends WC_Unit_Test_Case {

	/**
	 * Create a test post we can add/test meta against.
	 */
	public function create_test_post() {
		$object = new WC_Mock_WC_Data();
		$object->data_store->set_meta_type( 'post' );
		$object->data_store->set_object_id_field( '' );
		$object->set_content( 'testing' );
		$object->save();
		return $object;
	}

	/**
	 * Create a test user we can add/test meta against.
	 */
	public function create_test_user() {
		$object = new WC_Mock_WC_Data();
		$object->data_store->set_meta_type( 'user' );
		$object->data_store->set_object_id_field( 'user_id' );
		$object->set_content( 'testing@woo.dev' );
		$object->save();
		return $object;
	}

	/**
	 * Test: get_data
	 */
	function test_get_data() {
		$object = new WC_Mock_WC_Data();
		$this->assertInternalType( 'array', $object->get_data() );
	}

	/**
	 * Test: delete_meta_data_by_mid
	 */
	function test_delete_meta_data_by_mid() {
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		$meta_id = add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		$object->delete_meta_data_by_mid( $meta_id );
		$this->assertEmpty( $object->get_meta( 'test_meta_key' ) );
	}

	/**
	 * Test: set_props
	 */
	function test_set_props() {
		$object = new WC_Mock_WC_Data();
		$data_to_set = array(
			'content'    => 'I am a fish',
			'bool_value' => true,
		);
		$result = $object->set_props( $data_to_set );
		$this->assertFalse( is_wp_error( $result ) );
		$this->assertEquals( 'I am a fish', $object->get_content() );
		$this->assertTrue( $object->get_bool_value() );

		$data_to_set = array(
			'content'    => 'I am also a fish',
			'bool_value' => 'thisisinvalid',
		);
		$result = $object->set_props( $data_to_set );
		$this->assertTrue( is_wp_error( $result ) );
		$this->assertEquals( 'I am also a fish', $object->get_content() );
		$this->assertNotEquals( 'thisisinvalid', $object->get_bool_value() );
	}

	/**
	 * Tests reading and getting set metadata.
	 */
	function test_get_meta_data() {
		$object    = $this->create_test_post();
		$object_id = $object->get_id();
		$object->add_meta_data( 'test_meta_key', 'val1', true );
		$object->add_meta_data( 'test_multi_meta_key', 'val2' );
		$object->add_meta_data( 'test_multi_meta_key', 'val3' );
		$object->save_meta_data();

		$meta_data = $object->get_meta_data();
		$i         = 1;

		$this->assertNotEmpty( $meta_data );
		foreach ( $meta_data as $mid => $data ) {
			$this->assertEquals( "val{$i}", $data->value );
			$i++;
		}
	}

	/**
	 * Test getting meta by ID.
	 */
	function test_get_meta() {
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		$object->add_meta_data( 'test_meta_key', 'val1', true );
		$object->add_meta_data( 'test_multi_meta_key', 'val2' );
		$object->add_meta_data( 'test_multi_meta_key', 'val3' );
		$object->save_meta_data();
		$object = new WC_Mock_WC_Data( $object_id );

		// test single meta key
		$single_meta = $object->get_meta( 'test_meta_key' );
		$this->assertEquals( 'val1', $single_meta );

		// test getting multiple
		$meta = $object->get_meta( 'test_multi_meta_key', false );
		$i    = 2;
		foreach ( $meta as $data ) {
			$this->assertEquals( 'test_multi_meta_key', $data->key );
			$this->assertEquals( "val{$i}", $data->value );
			$i++;
		}
	}

	/**
	 * Test seeing if meta exists.
	 */
	function test_has_meta() {
		$object    = $this->create_test_post();
		$object_id = $object->get_id();
		$object->add_meta_data( 'test_meta_key', 'val1', true );
		$object->add_meta_data( 'test_multi_meta_key', 'val2' );
		$object->add_meta_data( 'test_multi_meta_key', 'val3' );
		$object->save_meta_data();
		$object = new WC_Mock_WC_Data( $object_id );

		$this->assertTrue( $object->meta_exists( 'test_meta_key' ) );
		$this->assertTrue( $object->meta_exists( 'test_multi_meta_key' ) );
		$this->assertFalse( $object->meta_exists( 'thiskeyisnothere' ) );
	}

	/**
	 * Test getting meta that hasn't been set
	 */
	function test_get_meta_no_meta() {
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		$object = new WC_Mock_WC_Data( $object_id );

		$single_on = $object->get_meta( 'doesnt-exist', true );
		$single_off = $object->get_meta( 'also-doesnt-exist', false );

		$this->assertEquals( '', $single_on );
		$this->assertEquals( array(), $single_off );
	}

	/**
	 * Test setting meta.
	 */
	function test_set_meta_data() {
		global $wpdb;
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		add_metadata( 'post', $object_id, 'test_meta_key_2', 'val2', true );
		$object = new WC_Mock_WC_Data( $object_id );

		$metadata     = array();
		$raw_metadata = $wpdb->get_results( $wpdb->prepare( "
			SELECT meta_id, meta_key, meta_value
			FROM {$wpdb->prefix}postmeta
			WHERE post_id = %d ORDER BY meta_id
		", $object_id ) );

		foreach ( $raw_metadata as $meta ) {
			$metadata[] = (object) array(
				'id'    => $meta->meta_id,
				'key'   => $meta->meta_key,
				'value' => $meta->meta_value,
			);
		}

		$object = new WC_Mock_WC_Data();
		$object->set_meta_data( $metadata );

		$this->assertEquals( $metadata, $object->get_meta_data() );
	}

	/**
	 * Test adding meta data.
	 */
	function test_add_meta_data() {
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		$data = 'add_meta_data_' . time();
		$object->add_meta_data( 'test_new_field', $data );
		$meta = $object->get_meta( 'test_new_field' );
		$this->assertEquals( $data, $meta );
	}

	/**
	 * Test updating meta data.
	 */
	function test_update_meta_data() {
		global $wpdb;
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		$object = new WC_Mock_WC_Data( $object_id );

		$this->assertEquals( 'val1', $object->get_meta( 'test_meta_key' ) );

		$metadata     = array();
		$meta_id = $wpdb->get_var( $wpdb->prepare( "
			SELECT meta_id
			FROM {$wpdb->prefix}postmeta
			WHERE post_id = %d LIMIT 1
		", $object_id ) );

		$object->update_meta_data( 'test_meta_key', 'updated_value', $meta_id );
		$this->assertEquals( 'updated_value', $object->get_meta( 'test_meta_key' ) );
	}

	/**
	 * Test deleting meta.
	 */
	function test_delete_meta_data() {
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		add_metadata( 'post', $object_id, 'test_meta_key', 'val1', true );
		$object = new WC_Mock_WC_Data( $object_id );

		$this->assertEquals( 'val1', $object->get_meta( 'test_meta_key' ) );

		$object->delete_meta_data( 'test_meta_key' );

		$this->assertEmpty( $object->get_meta( 'test_meta_key' ) );
	}


	/**
	 * Test saving metadata.. (Actually making sure changes are written to DB)
	 */
	function test_save_meta_data() {
		global $wpdb;
		$object = $this->create_test_post();
		$object_id = $object->get_id();
		$object->add_meta_data( 'test_meta_key', 'val1', true );
		$object->add_meta_data( 'test_meta_key_2', 'val2', true );
		$object->save_meta_data();
		$object = new WC_Mock_WC_Data( $object_id );

		$raw_metadata = $wpdb->get_results( $wpdb->prepare( "
			SELECT meta_id, meta_key, meta_value
			FROM {$wpdb->prefix}postmeta
			WHERE post_id = %d ORDER BY meta_id
		", $object_id ) );

		$object->delete_meta_data( 'test_meta_key' );
		$object->update_meta_data( 'test_meta_key_2', 'updated_value', $raw_metadata[1]->meta_id );

		$object->save();
		$object = new WC_Mock_WC_Data( $object_id ); // rereads from the DB

		$this->assertEmpty( $object->get_meta( 'test_meta_key' ) );
		$this->assertEquals( 'updated_value', $object->get_meta( 'test_meta_key_2' ) );
	}

	/**
	 * Test reading/getting user meta data too.
	 */
	function test_usermeta() {
		$object = $this->create_test_user();
		$object_id = $object->get_id();
		$object->add_meta_data( 'test_meta_key', 'val1', true );
		$object->add_meta_data( 'test_meta_key_2', 'val2', true );
		$object->save_meta_data();

		$this->assertEquals( 'val1', $object->get_meta( 'test_meta_key' ) );
		$this->assertEquals( 'val2', $object->get_meta( 'test_meta_key_2' ) );
	}

	/**
	 * Test adding meta data/updating meta data just added without keys colliding when changing
	 * data before a save.
	 */
	function test_add_meta_data_overwrite_before_save() {
		$object = new WC_Mock_WC_Data;
		$object->add_meta_data( 'test_field_0', 'another field', true );
		$object->add_meta_data( 'test_field_1', 'another field', true );
		$object->add_meta_data( 'test_field_2', 'val1', true );
		$object->update_meta_data( 'test_field_0', 'another field 2' );
		$this->assertEquals( 'val1', $object->get_meta( 'test_field_2' ) );
	}

	/**
	 * Test applying changes
	 */
	function test_apply_changes() {
		$data = array(
			'prop1' => 'value1',
			'prop2' => 'value2',
		);

		$changes = array(
			'prop1' => 'new_value1',
			'prop3' => 'value3'
		);

		$object = new WC_Mock_WC_Data;
		$object->set_data( $data );
		$object->set_changes( $changes );
		$object->apply_changes();

		$new_data = $object->get_data();
		$new_changes = $object->get_changes();

		$this->assertEquals( 'new_value1', $new_data['prop1'] );
		$this->assertEquals( 'value2', $new_data['prop2'] );
		$this->assertEquals( 'value3', $new_data['prop3'] );
		$this->assertEmpty( $new_changes );
	}

	/**
	 * Test applying changes with a nested array
	 */
	function test_apply_changes_nested() {
		$data = array(
			'prop1' => 'value1',
			'prop2' => array(
				'subprop1' => 1,
				'subprop2' => 2,
			),
		);

		$changes = array(
			'prop2' => array(
				'subprop1' => 1000,
				'subprop3' => 3,
			),
		);

		$object = new WC_Mock_WC_Data;
		$object->set_data( $data );
		$object->set_changes( $changes );
		$object->apply_changes();

		$new_data = $object->get_data();

		$this->assertEquals( 'value1', $new_data['prop1'] );
		$this->assertEquals( 1000, $new_data['prop2']['subprop1'] );
		$this->assertEquals( 2, $new_data['prop2']['subprop2'] );
		$this->assertEquals( 3, $new_data['prop2']['subprop3'] );
	}
}
