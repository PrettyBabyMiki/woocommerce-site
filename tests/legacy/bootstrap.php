<?php
/**
 * WooCommerce Unit Tests Bootstrap
 *
 * @since 2.2
 * @package WooCommerce Tests
 */

use Automattic\WooCommerce\Proxies\LegacyProxy;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\CodeHacker;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\FunctionsMockerHack;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\BypassFinalsHack;
use Automattic\WooCommerce\Testing\Tools\DependencyManagement\MockableLegacyProxy;

/**
 * Class WC_Unit_Tests_Bootstrap
 */
class WC_Unit_Tests_Bootstrap {

	/** @var WC_Unit_Tests_Bootstrap instance */
	protected static $instance = null;

	/** @var string directory where wordpress-tests-lib is installed */
	public $wp_tests_dir;

	/** @var string testing directory */
	public $tests_dir;

	/** @var string plugin directory */
	public $plugin_dir;

	/**
	 * Setup the unit testing environment.
	 *
	 * @since 2.2
	 */
	public function __construct() {
		$this->tests_dir  = dirname( __FILE__ );
		$this->plugin_dir = dirname( dirname( $this->tests_dir ) );
		$this->initialize_code_hacker();

		ini_set( 'display_errors', 'on' ); // phpcs:ignore WordPress.PHP.IniSet.display_errors_Blacklisted
		error_reporting( E_ALL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting, WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting

		// Ensure server variable is set for WP email functions.
		// phpcs:disable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
		if ( ! isset( $_SERVER['SERVER_NAME'] ) ) {
			$_SERVER['SERVER_NAME'] = 'localhost';
		}
		// phpcs:enable WordPress.VIP.SuperGlobalInputUsage.AccessDetected

		$this->wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : sys_get_temp_dir() . '/wordpress-tests-lib';

		// load test function so tests_add_filter() is available.
		require_once $this->wp_tests_dir . '/includes/functions.php';

		// load WC.
		tests_add_filter( 'muplugins_loaded', array( $this, 'load_wc' ) );

		// install WC.
		tests_add_filter( 'setup_theme', array( $this, 'install_wc' ) );

		// load the WP testing environment.
		require_once $this->wp_tests_dir . '/includes/bootstrap.php';

		// load WC testing framework.
		$this->includes();

		// register autoloader for tests in 'src'.
		$this->register_psr4_autoloader();

		// re-initialize dependency injection, this needs to be the last operation after everything else is in place.
		$this->initialize_dependency_injection();
	}

	/**
	 * Initialize the code hacker.
	 *
	 * @throws Exception Error when initializing one of the hacks.
	 */
	private function initialize_code_hacker() {
		$hacking_base = $this->plugin_dir . '/tests/Tools/CodeHacking';
		require_once $hacking_base . '/CodeHacker.php';
		require_once $hacking_base . '/Hacks/CodeHack.php';
		require_once $hacking_base . '/Hacks/StaticMockerHack.php';
		require_once $hacking_base . '/Hacks/FunctionsMockerHack.php';
		require_once $hacking_base . '/Hacks/BypassFinalsHack.php';

		CodeHacker::initialize( array( __DIR__ . '/../../includes/' ) );
		$replaceable_functions = include_once __DIR__ . '/mockable-functions.php';
		if ( ! empty( $replaceable_functions ) ) {
			FunctionsMockerHack::initialize( $replaceable_functions );
			CodeHacker::add_hack( FunctionsMockerHack::get_hack_instance() );
		}

		$mockable_static_classes = include_once __DIR__ . '/classes-with-mockable-static-methods.php';
		if ( ! empty( $mockable_static_classes ) ) {
			StaticMockerHack::initialize( $mockable_static_classes );
			CodeHacker::add_hack( StaticMockerHack::get_hack_instance() );
		}

		CodeHacker::add_hack( new BypassFinalsHack() );

		CodeHacker::enable();
	}

	/**
	 * Re-initialize the dependency injection engine.
	 *
	 * The dependency injection engine has been already initialized as part of the Woo initialization, but we need
	 * to replace the registered read-only container with a fully configurable one for testing.
	 * To this end we hack a bit and use reflection to grab the underlying container that the read-only one stores
	 * in a private property.
	 *
	 * Additionally, we replace the legacy/function proxies with mockable versions to easily replace anything
	 * in tests as appropriate.
	 *
	 * @throws \Exception The Container class doesn't have a 'container' property.
	 */
	private function initialize_dependency_injection() {
		try {
			$inner_container_property = new \ReflectionProperty( \Automattic\WooCommerce\Container::class, 'container' );
		} catch ( ReflectionException $ex ) {
			throw new \Exception( "Error when trying to get the private 'container' property from the " . \Automattic\WooCommerce\Container::class . ' class using reflection during unit testing bootstrap, has the property been removed or renamed?' );
		}

		$inner_container_property->setAccessible( true );
		$inner_container = $inner_container_property->getValue( wc_get_container() );

		$inner_container->replace( LegacyProxy::class, MockableLegacyProxy::class );
		$inner_container->reset_resolved();

		$GLOBALS['wc_container'] = $inner_container;
	}

	/**
	 * Register autoloader for the files in the 'tests/php/src' directory.
	 */
	protected static function register_psr4_autoloader() {
		spl_autoload_register(
			function ( $class ) {
				$prefix   = 'Automattic\\WooCommerce\\Tests\\';
				$base_dir = __DIR__ . '/php/src/';
				$len      = strlen( $prefix );
				if ( strncmp( $prefix, $class, $len ) !== 0 ) {
					// no, move to the next registered autoloader.
					return;
				}
				$relative_class = substr( $class, $len );
				$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
				require $file;
			}
		);
	}

	/**
	 * Load WooCommerce.
	 *
	 * @since 2.2
	 */
	public function load_wc() {
		define( 'WC_TAX_ROUNDING_MODE', 'auto' );
		define( 'WC_USE_TRANSACTIONS', false );
		require_once $this->plugin_dir . '/woocommerce.php';
	}

	/**
	 * Install WooCommerce after the test environment and WC have been loaded.
	 *
	 * @since 2.2
	 */
	public function install_wc() {

		// Clean existing install first.
		define( 'WP_UNINSTALL_PLUGIN', true );
		define( 'WC_REMOVE_ALL_DATA', true );
		include $this->plugin_dir . '/uninstall.php';

		WC_Install::install();

		// Initialize the WC API extensions.
		\Automattic\WooCommerce\Admin\Install::create_tables();
		\Automattic\WooCommerce\Admin\Install::create_events();

		// Reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374.
		if ( version_compare( $GLOBALS['wp_version'], '4.7', '<' ) ) {
			$GLOBALS['wp_roles']->reinit();
		} else {
			$GLOBALS['wp_roles'] = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			wp_roles();
		}

		echo esc_html( 'Installing WooCommerce...' . PHP_EOL );
	}

	/**
	 * Load WC-specific test cases and factories.
	 *
	 * @since 2.2
	 */
	public function includes() {

		// framework.
		require_once $this->tests_dir . '/framework/class-wc-unit-test-factory.php';
		require_once $this->tests_dir . '/framework/class-wc-mock-session-handler.php';
		require_once $this->tests_dir . '/framework/class-wc-mock-wc-data.php';
		require_once $this->tests_dir . '/framework/class-wc-mock-wc-object-query.php';
		require_once $this->tests_dir . '/framework/class-wc-mock-payment-gateway.php';
		require_once $this->tests_dir . '/framework/class-wc-payment-token-stub.php';
		require_once $this->tests_dir . '/framework/vendor/class-wp-test-spy-rest-server.php';

		// test cases.
		require_once $this->tests_dir . '/includes/wp-http-testcase.php';
		require_once $this->tests_dir . '/framework/class-wc-unit-test-case.php';
		require_once $this->tests_dir . '/framework/class-wc-api-unit-test-case.php';
		require_once $this->tests_dir . '/framework/class-wc-rest-unit-test-case.php';

		// Helpers.
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-product.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-coupon.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-fee.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-shipping.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-customer.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-order.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-shipping-zones.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-payment-token.php';
		require_once $this->tests_dir . '/framework/helpers/class-wc-helper-settings.php';
	}

	/**
	 * Get the single class instance.
	 *
	 * @since 2.2
	 * @return WC_Unit_Tests_Bootstrap
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

WC_Unit_Tests_Bootstrap::instance();
