<?php
/**
 * Enables WooCommerce, via the the command line.
 *
 * @version	2.7.0
 * @package WooCommerce
 * @author  WooCommerce
 */
class WC_CLI {
	/**
	 * Load required files and hooks to make the CLI work.
	 */
	public function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Load command files.
	 */
	private function includes() {
		require_once __DIR__ . '/cli/class-wc-cli-runner.php';
		require_once __DIR__ . '/cli/class-wc-cli-rest-command.php';
		require_once __DIR__ . '/cli/class-wc-cli-tool-command.php';
	}

	/**
	 * Sets up and hooks WP CLI to our CLI code.
	 */
	private function hooks() {
		WP_CLI::add_hook( 'after_wp_load', 'WC_CLI_Runner::after_wp_load' );
		WP_CLI::add_hook( 'after_wp_load', 'WC_CLI_Tool_Command::register_tool_commands' );
	}
}
