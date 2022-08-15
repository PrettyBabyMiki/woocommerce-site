<?php
/**
 * WC_CLI_COM_Command class file.
 *
 * @package WooCommerce\CLI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allows to interact with extensions from WCCOM marketplace via CLI.
 *
 * @version 6.8
 * @package WooCommerce
 */
class WC_CLI_COM_Command {
	/**
	 * Registers a commands for managing WooCommerce.com extensions.
	 */
	public static function register_commands() {
		WP_CLI::add_command( 'wc com extension list', array( 'WC_CLI_COM_Command', 'list_extensions' ) );
		WP_CLI::add_command( 'wc com disconnect', array( 'WC_CLI_COM_Command', 'disconnect' ) );
	}

	/**
	 * List extensions owned by the connected site
	 *
	 * [--format]
	 * : If set, the command will use the specified format. Possible values are table, json, csv and yaml. By default the table format will be used.
	 *
	 * [--fields]
	 * : If set, the command will show only the specified fields instead of showing all the fields in the output.
	 *
	 * ## EXAMPLES
	 *
	 *     # List extensions owned by the connected site in table format with all the fields
	 *     $ wp wc com extension list
	 *
	 *     # List the product slug of the extension owned by the connected site in csv format
	 *     $ wp wc com extension list --format=csv --fields=product_slug
	 *
	 * @param  array $args  WP-CLI positional arguments.
	 * @param  array $assoc_args  WP-CLI associative arguments.
	 */
	public static function list_extensions( array $args, array $assoc_args ) {
		$data = WC_Helper::get_subscriptions();

		$data = array_values( $data );

		$formatter = new \WP_CLI\Formatter(
			$assoc_args,
			array(
				'product_slug',
				'product_name',
				'auto_renew',
				'expires_on',
				'expired',
				'sites_max',
				'sites_active',
				'maxed',
			)
		);

		$data = array_map(
			function( $item ) {
				$product_slug      = '';
				$product_url_parts = explode( '/', $item['product_url'] );
				if ( count( $product_url_parts ) > 2 ) {
					$product_slug = $product_url_parts[ count( $product_url_parts ) - 2 ];
				}
				return array(
					'product_slug' => $product_slug,
					'product_name' => htmlspecialchars_decode( $item['product_name'] ),
					'auto_renew'   => $item['autorenew'] ? 'On' : 'Off',
					'expires_on'   => gmdate( 'Y-m-d', $item['expires'] ),
					'expired'      => $item['expired'] ? 'Yes' : 'No',
					'sites_max'    => $item['sites_max'],
					'sites_active' => $item['sites_active'],
					'maxed'        => $item['maxed'] ? 'Yes' : 'No',
				);
			},
			$data
		);

		$formatter->display_items( $data );
	}

	/**
	 * ## OPTIONS
	 *
	 * [--yes]
	 * : Do not prompt for confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     # Disconnect from site.
	 *     $ wp wc com disconnect
	 *
	 *     # Disconnect without prompt for confirmation.
	 *     $ wp wc com disconnect --yes
	 *
	 * @param array $args Positional arguments to include when calling the command.
	 * @param array $assoc_args Associative arguments to include when calling the command.

	 * @return void
	 * @throws \WP_CLI\ExitException If WP_CLI::$capture_exit is true.
	 */
	public static function disconnect( array $args, array $assoc_args ) {
		if ( ! WC_Helper::is_site_connected() ) {
			WP_CLI::error( 'Your store is not connected to WooCommerce.com. Run `wp wc com connect` command.' );
		}

		WP_CLI::confirm( 'Are you sure you want to disconnect your store from WooCommerce.com?', $assoc_args );
		WC_Helper::disconnect();
		WP_CLI::success( 'You have successfully disconnected your store from WooCommerce.com' );
	}
}
