<?php
/**
 * WC API to WC CLI Bridge.
 *
 * Hooks into the REST API, figures out which endpoints come from WC,
 * and registers them as CLI commands.
 *
 * Forked from wp-cli/restful (by Daniel Bachhuber, released under the MIT license https://opensource.org/licenses/MIT).
 * https://github.com/wp-cli/restful
 *
 * @version 2.7.0
 * @package WooCommerce
 */
class WC_CLI_Runner {
	/**
	 * Endpoints to disable (meaning they will not be avaiable as CLI commands).
	 * Some of these can either be done via WP already, or are offered with
	 * some other changes (like tools).
	 */
	private static $disabled_endpoints = array(
		'settings',
		'settings/(?P<group>[\w-]+)',
		'settings/(?P<group>[\w-]+)/batch',
		'settings/(?P<group>[\w-]+)/(?P<id>[\w-]+)',
		'system_status',
		'system_status/tools',
		'system_status/tools/(?P<id>[\w-]+)',
		'reports',
		'reports/sales',
		'reports/top_sellers',
	);

	/**
	 * Register's all endpoints as commands once WP and WC have all loaded.
	 */
	public static function after_wp_load() {
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server;
		do_action( 'rest_api_init', $wp_rest_server );

		$request = new WP_REST_Request( 'GET', '/' );
		$request->set_param( 'context', 'help' );
		$response      = $wp_rest_server->dispatch( $request );
		$response_data = $response->get_data();
		if ( empty( $response_data ) ) {
			return;
		}

		// Loop through all of our endpoints and register any valid WC endpoints.
		foreach ( $response_data['routes'] as $route => $route_data ) {
			// Only register WC endpoints
			if ( substr( $route, 0, 4 ) !== '/wc/' ) {
				continue;
			}
			// Only register endpoints with schemas
			if ( empty( $route_data['schema']['title'] ) ) {
				WP_CLI::debug( sprintf( __( 'No schema title found for %s, skipping REST command registration.', 'woocommerce' ), $route ), 'wc' );
				continue;
			}
			// Ignore batch endpoints
			if ( 'batch' === $route_data['schema']['title'] ) {
				continue;
			}
			// Disable specific endpoints
			$route_pieces   = explode( '/', $route );
			$endpoint_piece = str_replace( '/wc/' . $route_pieces[2] . '/', '', $route );
			if ( in_array( $endpoint_piece, self::$disabled_endpoints ) ) {
				continue;
			}

			self::register_route_commands( new WC_CLI_REST_Command( $route_data['schema']['title'], $route, $route_data['schema'] ), $route, $route_data );
		}
	}

	/**
	 * Generates command information and tells WP CLI about all
	 * commands avaiable from a route.
	 *
	 * @param string $rest_command
	 * @param string $route
	 * @param array  $route_data
	 * @param array  $command_args
	 */
	private static function register_route_commands( $rest_command, $route, $route_data, $command_args = array() ) {
		$parent			 = "wc {$route_data['schema']['title']}";
		$supported_commands = array();

		// Get a list of supported commands for each route.
		foreach ( $route_data['endpoints'] as $endpoint ) {
			$parsed_args   = preg_match_all( '#\([^\)]+\)#', $route, $matches );
			$first_match   = $matches[0];
			$resource_id   = ! empty( $matches[0] ) ? array_pop( $matches[0] ) : null;
			$trimmed_route = rtrim( $route );
			$is_singular   = substr( $trimmed_route, - strlen( $resource_id ) ) === $resource_id;
			if ( ! $is_singular ) {
				$resource_id = $first_match;
			}
			$command = '';

			// List a collection
			if ( array( 'GET' ) == $endpoint['methods'] && ! $is_singular ) {
				$supported_commands['list'] = ! empty( $endpoint['args'] ) ? $endpoint['args'] : array();
			}
			// Create a specific resource
			if ( array( 'POST' ) == $endpoint['methods'] && ! $is_singular ) {
				$supported_commands['create'] = ! empty( $endpoint['args'] ) ? $endpoint['args'] : array();
			}
			// Get a specific resource
			if ( array( 'GET' ) == $endpoint['methods'] && $is_singular ) {
				$supported_commands['get'] = ! empty( $endpoint['args'] ) ? $endpoint['args'] : array();
			}
			// Update a specific resource
			if ( in_array( 'POST', $endpoint['methods'] ) && $is_singular ) {
				$supported_commands['update'] = ! empty( $endpoint['args'] ) ? $endpoint['args'] : array();
			}
			// Delete a specific resource
			if ( array( 'DELETE' ) == $endpoint['methods'] && $is_singular ) {
				$supported_commands['delete'] = ! empty( $endpoint['args'] ) ? $endpoint['args'] : array();
			}
		}

		foreach ( $supported_commands as $command => $endpoint_args ) {
			$synopsis = array();
			$arg_regs = array();

			if ( strpos( $route, '<product_id>' ) !== false ) {
				$synopsis[] = array(
					'name'		  => 'product_id',
					'type'		  => 'positional',
					'description' => __( 'Product ID.', 'woocommerce' ),
				);
			}

			if ( strpos( $route, '<customer_id>' ) !== false ) {
				$synopsis[] = array(
					'name'		  => 'customer_id',
					'type'		  => 'positional',
					'description' => __( 'Customer ID.', 'woocommerce' ),
				);
			}

			if ( strpos( $route, '<order_id>' ) !== false ) {
				$synopsis[] = array(
					'name'		  => 'order_id',
					'type'		  => 'positional',
					'description' => __( 'Order ID.', 'woocommerce' ),
				);
			}

			if ( strpos( $route, '<refund_id>' ) !== false ) {
				$synopsis[] = array(
					'name'		  => 'refund_id',
					'type'		  => 'positional',
					'description' => __( 'Refund ID.', 'woocommerce' ),
				);
			}

			if ( in_array( $command, array( 'delete', 'get', 'update' ) ) ) {
				$synopsis[] = array(
					'name'		  => 'id',
					'type'		  => 'positional',
					'description' => __( 'The id for the resource.', 'woocommerce' ),
					'optional'	  => false,
				);
			}

			foreach ( $endpoint_args as $name => $args ) {
				$arg_regs[] = array(
					'name'		  => $name,
					'type'		  => 'assoc',
					'description' => ! empty( $args['description'] ) ? $args['description'] : '',
					'optional'	  => empty( $args['required'] ) ? true : false,
				);
			}

			foreach ( $arg_regs as $arg_reg ) {
				$synopsis[] = $arg_reg;
			}

			if ( in_array( $command, array( 'list', 'get' ) ) ) {
				$synopsis[] = array(
					'name'		  => 'fields',
					'type'		  => 'assoc',
					'description' => __( 'Limit response to specific fields. Defaults to all fields.', 'woocommerce' ),
					'optional'	  => true,
				);
				$synopsis[] = array(
					'name'		  => 'field',
					'type'		  => 'assoc',
					'description' => __( 'Get the value of an individual field.', 'woocommerce' ),
					'optional'	  => true,
				);
				$synopsis[] = array(
					'name'		  => 'format',
					'type'		  => 'assoc',
					'description' => __( 'Render response in a particular format.', 'woocommerce' ),
					'optional'	  => true,
					'default'	  => 'table',
					'options'	  => array(
						'table',
						'json',
						'csv',
						'ids',
						'yaml',
						'count',
						'headers',
						'body',
						'envelope',
					),
				);
			}

			if ( in_array( $command, array( 'create', 'update', 'delete' ) ) ) {
				$synopsis[] = array(
					'name'		  => 'porcelain',
					'type'		  => 'flag',
					'description' => __( 'Output just the id when the operation is successful.', 'woocommerce' ),
					'optional'	  => true,
				);
			}

			$methods = array(
				'list'   => 'list_items',
				'create' => 'create_item',
				'delete' => 'delete_item',
				'get'	 => 'get_item',
				'update' => 'update_item',
			);

			$before_invoke = null;
			if ( empty( $command_args['when'] ) && \WP_CLI::get_config( 'debug' ) ) {
				$before_invoke = function() {
					if ( ! defined( 'SAVEQUERIES' ) ) {
						define( 'SAVEQUERIES', true );
					}
				};
			}

			WP_CLI::add_command( "{$parent} {$command}", array( $rest_command, $methods[ $command ] ), array(
				'synopsis'	    => $synopsis,
				'when'		    => ! empty( $command_args['when'] ) ? $command_args['when'] : '',
				'before_invoke' => $before_invoke,
			) );
		}
	}
}
