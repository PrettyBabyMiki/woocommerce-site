<?php
/**
 * Plugins Helper Tests
 *
 * @package WooCommerce\Admin\Tests\PluginHelper
 */

use \Automattic\WooCommerce\Admin\PluginsHelper;

/**
 * WC_Admin_Tests_Plugin_Helper Class
 *
 * @package WooCommerce\Admin\Tests\PluginHelper
 */
class WC_Admin_Tests_Plugins_Helper extends WP_UnitTestCase {

	/**
	 * Setup test data. Called before every test.
	 */
	public function setUp() {
		parent::setUp();

		$this->user = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	/**
	 * Test get_plugin_path_from_slug()
	 */
	public function test_get_plugin_path_from_slug() {

		// Installed plugin checks.
		$wc_path = PluginsHelper::get_plugin_path_from_slug( 'woocommerce' );
		$this->assertEquals( 'woocommerce/woocommerce.php', $wc_path, 'Path returned is not as expected.' );
		$ak_path = PluginsHelper::get_plugin_path_from_slug( 'akismet' );
		$this->assertEquals( 'akismet/akismet.php', $ak_path, 'Path returned is not as expected.' );

		// Plugin that is not installed.
		$invalid_path = PluginsHelper::get_plugin_path_from_slug( 'invalid-plugin' );
		$this->assertEquals( false, $invalid_path, 'False should be returned when no matching plugin is installed.' );

		// Check for when slug already appears to be a path.
		$wc_path_slug = PluginsHelper::get_plugin_path_from_slug( 'woocommerce/woocommerce' );
		$this->assertEquals( 'woocommerce/woocommerce', $wc_path_slug, 'Slug should be returned if it appears to already be path.' );
	}

	/**
	 * Test get_active_plugin_slugs()
	 */
	public function test_get_active_plugin_slugs() {

		// Get active slugs.
		$active_slugs = PluginsHelper::get_active_plugin_slugs();

		// Phpunit test environment active plugins option is empty.
		$this->assertEquals( array(), $active_slugs, 'Should not be any active slugs.' );

		// Get facebook plugin path.
		$fb_path = PluginsHelper::get_plugin_path_from_slug( 'facebook-for-woocommerce' );

		// Activate facebook plugin.
		activate_plugin( $fb_path );

		// Get active slugs.
		$active_slugs = PluginsHelper::get_active_plugin_slugs();

		// Phpunit test environment active plugins option is empty.
		$this->assertEquals( array( 'facebook-for-woocommerce' ), $active_slugs, 'Facebook for WooCommerce should be listed as active.' );
	}

	/**
	 * Test is_plugin_installed()
	 */
	public function test_is_plugin_installed() {

		// WooCommerce is installed in the test environment.
		$installed = PluginsHelper::is_plugin_installed( 'woocommerce' );
		$this->assertEquals( true, $installed, 'WooCommerce should be installed.' );

		// Invalid plugin is not.
		$installed = PluginsHelper::is_plugin_installed( 'invalid-plugin' );
		$this->assertEquals( false, $installed, 'Invalid plugins should not be installed.' );
	}

	/**
	 * Test is_plugin_active()
	 */
	public function test_is_plugin_active() {

		// Check if facebook is not active. Phpunit test environment active plugins option is empty.
		$active = PluginsHelper::is_plugin_active( 'facebook-for-woocommerce' );
		$this->assertEquals( false, $active, 'Should not be any active slugs.' );

		// Get facebook plugin path.
		$fb_path = PluginsHelper::get_plugin_path_from_slug( 'facebook-for-woocommerce' );

		// Activate facebook plugin.
		activate_plugin( $fb_path );

		// Check if facebook is now active.
		$activated = PluginsHelper::is_plugin_active( 'facebook-for-woocommerce' );
		$this->assertEquals( true, $activated, 'Facebook for WooCommerce should be installed.' );
	}

	/**
	 * Test get_plugin_data()
	 */
	public function test_get_plugin_data() {

		$actual_data = PluginsHelper::get_plugin_data( 'woocommerce' );

		$expected_keys = array(
			'WC requires at least',
			'WC tested up to',
			'Woo',
			'Name',
			'PluginURI',
			'Description',
			'Author',
			'Version',
			'AuthorURI',
			'TextDomain',
			'DomainPath',
			'Network',
			'RequiresWP',
			'RequiresPHP',
			'Title',
			'AuthorName',
		);

		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $actual_data, 'Plugin data does not match expected data.' );
		}

		// Test not installed plugin response.
		$actual_data = PluginsHelper::get_plugin_data( 'my-plugin' );
		$this->assertEquals( false, $actual_data, 'Should return false if plugin is not found.' );
	}
}
