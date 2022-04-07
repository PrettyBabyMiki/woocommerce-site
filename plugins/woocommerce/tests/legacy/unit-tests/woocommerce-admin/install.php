<?php
/**
 * Install tests
 *
 * @package WooCommerce\Admin\Tests
 */

/**
 * Tests for \Automattic\WooCommerce\Internal\Admin\Install class.
 */
class WC_Admin_Tests_Install extends WP_UnitTestCase {

	const VERSION_OPTION = 'woocommerce_admin_version';

	/**
	 * Integration test for database table creation.
	 *
	 * @group database
	 */
	public function test_create_tables() {
		global $wpdb;

		// Remove the Test Suite’s use of temporary tables https://wordpress.stackexchange.com/a/220308.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		// List of tables created by Install::create_tables.
		$tables = array(
			"{$wpdb->prefix}wc_order_stats",
			"{$wpdb->prefix}wc_order_product_lookup",
			"{$wpdb->prefix}wc_order_tax_lookup",
			"{$wpdb->prefix}wc_order_coupon_lookup",
			"{$wpdb->prefix}wc_admin_notes",
			"{$wpdb->prefix}wc_admin_note_actions",
			"{$wpdb->prefix}wc_customer_lookup",
			"{$wpdb->prefix}wc_category_lookup",
		);

		// Remove any existing tables in the environment.
		$query = 'DROP TABLE IF EXISTS ' . implode( ',', $tables );
		$wpdb->query( $query ); // phpcs:ignore.

		WC_Install::create_tables();
		$result = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );

		// Check all the tables exist.
		foreach ( $tables as $table ) {
			$this->assertContains( $table, $result );
		}
	}

	/**
	 * Test missed DB version number update.
	 * See: https:// github.com/woocommerce/woocommerce-admin/issues/5058
	 */
	public function test_missed_version_number_update() {
		$this->markTestSkipped('We no longer update WooCommerce Admin versions');
		$old_version = '1.6.0'; // This should get updated to later versions as we add more migrations.

		// Simulate an upgrade from an older version.
		update_option( self::VERSION_OPTION, '1.6.0' );
		WC_Install::install();
		WC_Helper_Queue::run_all_pending();

		// Simulate a collision/failure in version updating.
		update_option( self::VERSION_OPTION, '1.6.0' );

		// The next update check should force update the skipped version number.
		WC_Install::install();
		$this->assertTrue( version_compare( $old_version, get_option( self::VERSION_OPTION ), '<' ) );

		// The following update check should bump the version to the current (no migrations left).
		WC_Install::install();
		$this->assertEquals( get_option( self::VERSION_OPTION ), WC_ADMIN_VERSION_NUMBER );
	}
}
