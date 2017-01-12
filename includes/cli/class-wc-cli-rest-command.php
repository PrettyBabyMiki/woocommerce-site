<?php
/**
 * Main Command for WooCommere CLI.
 *
 * Since a lot of WC operations can be handled via the REST API, we base our CLI
 * off of Restful to generate commands for each WooCommerce REST API endpoint
 * so most of the logic is shared.
 *
 * Forked from wp-cli/restful (by Daniel Bachhuber, released under the MIT license https://opensource.org/licenses/MIT).
 * https://github.com/wp-cli/restful
 *
 * @version 2.7.0
 * @package WooCommerce
 */
class WC_CLI_REST_Command {
	/**
	 * Endpoints that have a parent ID.
	 * Ex: Product reviews, which has a product ID and a review ID.
	 */
	protected $routes_with_parent_id = array(
		'customer_download',
		'product_review',
		'order_note',
		'shop_order_refund',
	);

	/**
	 * Name of command/endpoint object.
	 */
	private $name;

	/**
	 * Endpoint route.
	 */
	private $route;

	/**
	 * Main resource ID.
	 */
	private $resource_identifier;

	/**
	 * Schema for command.
	 */
	private $schema;

	/**
	 * Nesting level.
	 */
	private $output_nesting_level = 0;

	/**
	 * Sets up REST Command.
	 *
	 * @param string $name   Name of endpoint object (comes from schema)
	 * @param string $route  Path to route of this endpoint
	 * @param array  $schema Schema object
	 */
	public function __construct( $name, $route, $schema ) {
		$this->name   = $name;
		$parsed_args  = preg_match_all( '#\([^\)]+\)#', $route, $matches );
		$first_match  = $matches[0];
		$resource_id  = ! empty( $matches[0] ) ? array_pop( $matches[0] ) : null;
		$this->route  = rtrim( $route );
		$this->schema = $schema;

		$this->resource_identifier = $resource_id;
		if ( in_array( $name, $this->routes_with_parent_id ) ) {
			$is_singular = substr( $this->route, - strlen( $resource_id ) ) === $resource_id;
			if ( ! $is_singular ) {
				$this->resource_identifier = $first_match[0];
			}
		}
	}

	/**
	 * Create a new item.
	 *
	 * @subcommand create
	 */
	public function create_item( $args, $assoc_args ) {
		$assoc_args = self::decode_json( $assoc_args );
		list( $status, $body ) = $this->do_request( 'POST', $this->get_filled_route( $args ), $assoc_args );
		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $body['id'] );
		} else {
			WP_CLI::success( "Created {$this->name} {$body['id']}." );
		}
	}

	/**
	 * Delete an existing item.
	 *
	 * @subcommand delete
	 */
	public function delete_item( $args, $assoc_args ) {
		list( $status, $body ) = $this->do_request( 'DELETE', $this->get_filled_route( $args ), $assoc_args );
		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $body['id'] );
		} else {
			if ( empty( $assoc_args['force'] ) ) {
				WP_CLI::success( __( 'Trashed', 'woocommerce' ) . " {$this->name} {$body['id']}" );
			} else {
				WP_CLI::success( __( 'Deleted', 'woocommerce' ) . " {$this->name} {$body['id']}." );
			}
		}
	}

	/**
	 * Get a single item.
	 *
	 * @subcommand get
	 */
	public function get_item( $args, $assoc_args ) {
		$route = $this->get_filled_route( $args );
		list( $status, $body, $headers ) = $this->do_request( 'GET', $route, $assoc_args );

		if ( ! empty( $assoc_args['fields'] ) ) {
			$body = self::limit_item_to_fields( $body, $fields );
		}

		if ( 'headers' === $assoc_args['format'] ) {
			echo json_encode( $headers );
		} elseif ( 'body' === $assoc_args['format'] ) {
			echo json_encode( $body );
		} elseif ( 'envelope' === $assoc_args['format'] ) {
			echo json_encode( array(
				'body'    => $body,
				'headers' => $headers,
				'status'  => $status,
			) );
		} else {
			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_item( $body );
		}
	}

	/**
	 * List all items.
	 *
	 * @subcommand list
	 */
	public function list_items( $args, $assoc_args ) {
		if ( ! empty( $assoc_args['format'] ) && 'count' === $assoc_args['format'] ) {
			$method = 'HEAD';
		} else {
			$method = 'GET';
		}

		list( $status, $body, $headers ) = $this->do_request( $method, $this->get_filled_route( $args ), $assoc_args );
		if ( ! empty( $assoc_args['format'] ) && 'ids' === $assoc_args['format'] ) {
			$items = array_column( $body, 'id' );
		} else {
			$items = $body;
		}

		if ( ! empty( $assoc_args['fields'] ) ) {
			foreach ( $items as $key => $item ) {
				$items[ $key ] = self::limit_item_to_fields( $item, $fields );
			}
		}

		if ( ! empty( $assoc_args['format'] ) && 'count' === $assoc_args['format'] ) {
			echo (int) $headers['X-WP-Total'];
		} elseif ( 'headers' === $assoc_args['format'] ) {
			echo json_encode( $headers );
		} elseif ( 'body' === $assoc_args['format'] ) {
			echo json_encode( $body );
		} elseif ( 'envelope' === $assoc_args['format'] ) {
			echo json_encode( array(
				'body'    => $body,
				'headers' => $headers,
				'status'  => $status,
				'api_url' => $this->api_url,
			) );
		} else {
			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_items( $items );
		}
	}

	/**
	 * Update an existing item.
	 *
	 * @subcommand update
	 */
	public function update_item( $args, $assoc_args ) {
		$assoc_args = self::decode_json( $assoc_args );
		list( $status, $body ) = $this->do_request( 'POST', $this->get_filled_route( $args ), $assoc_args );
		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'porcelain' ) ) {
			WP_CLI::line( $body['id'] );
		} else {
			WP_CLI::success( __( 'Updated', 'woocommerce' ) . " {$this->name} {$body['id']}." );
		}
	}

	/**
	 * Do a REST Request
	 *
	 * @param string $method
	 *
	 */
	private function do_request( $method, $route, $assoc_args ) {
		if ( ! defined( 'REST_REQUEST' ) ) {
			define( 'REST_REQUEST', true );
		}
		$request = new WP_REST_Request( $method, $route );
		if ( in_array( $method, array( 'POST', 'PUT' ) ) ) {
			$request->set_body_params( $assoc_args );
		} else {
			foreach ( $assoc_args as $key => $value ) {
				$request->set_param( $key, $value );
			}
		}
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$original_queries = is_array( $GLOBALS['wpdb']->queries ) ? array_keys( $GLOBALS['wpdb']->queries ) : array();
		}
		$response = rest_do_request( $request );
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$performed_queries = array();
			foreach ( (array) $GLOBALS['wpdb']->queries as $key => $query ) {
				if ( in_array( $key, $original_queries ) ) {
					continue;
				}
				$performed_queries[] = $query;
			}
			usort( $performed_queries, function( $a, $b ) {
				if ( $a[1] === $b[1] ) {
					return 0;
				}
				return ( $a[1] > $b[1] ) ? -1 : 1;
			});

			$query_count = count( $performed_queries );
			$query_total_time = 0;
			foreach ( $performed_queries as $query ) {
				$query_total_time += $query[1];
			}
			$slow_query_message = '';
			if ( $performed_queries && 'wc' === WP_CLI::get_config( 'debug' ) ) {
				$slow_query_message .= '. Ordered by slowness, the queries are:' . PHP_EOL;
				foreach ( $performed_queries as $i => $query ) {
					$i++;
					$bits = explode( ', ', $query[2] );
					$backtrace = implode( ', ', array_slice( $bits, 13 ) );
					$seconds = round( $query[1], 6 );
					$slow_query_message .= <<<EOT
{$i}:
- {$seconds} seconds
- {$backtrace}
- {$query[0]}
EOT;
					$slow_query_message .= PHP_EOL;
				}
			} elseif ( 'wc' !== WP_CLI::get_config( 'debug' ) ) {
				$slow_query_message = '. Use --debug=wc to see all queries.';
			}
			$query_total_time = round( $query_total_time, 6 );
			WP_CLI::debug( "wc command executed {$query_count} queries in {$query_total_time} seconds{$slow_query_message}", 'wc' );
		}
		if ( $error = $response->as_error() ) {
			WP_CLI::error( $error );
		}
		return array( $response->get_status(), $response->get_data(), $response->get_headers() );
	}

	/**
	 * Get Formatter object based on supplied parameters.
	 *
	 * @param array $assoc_args Parameters passed to command. Determines formatting.
	 * @return \WP_CLI\Formatter
	 */
	protected function get_formatter( &$assoc_args ) {
		if ( ! empty( $assoc_args['fields'] ) ) {
			if ( is_string( $assoc_args['fields'] ) ) {
				$fields = explode( ',', $assoc_args['fields'] );
			} else {
				$fields = $assoc_args['fields'];
			}
		} else {
			if ( ! empty( $assoc_args['context'] ) ) {
				$fields = $this->get_context_fields( $assoc_args['context'] );
			} else {
				$fields = $this->get_context_fields( 'view' );
			}
		}
		return new \WP_CLI\Formatter( $assoc_args, $fields );
	}

	/**
	 * Get a list of fields present in a given context
	 *
	 * @param string $context
	 * @return array
	 */
	private function get_context_fields( $context ) {
		$fields = array();
		foreach ( $this->schema['properties'] as $key => $args ) {
			if ( empty( $args['context'] ) || in_array( $context, $args['context'] ) ) {
				$fields[] = $key;
			}
		}
		return $fields;
	}

	/**
	 * Get the route for this resource
	 *
	 * @param  array $args
	 * @return string
	 */
	private function get_filled_route( $args = array() ) {
		$parent_id_matched = false;
		$route             = $this->route;
		if ( strpos( $route, '<customer_id>' ) !== false && ! empty( $args ) ) {
			$route             = str_replace( '(?P<customer_id>[\d]+)', $args[0], $route );
			$parent_id_matched = true;
		} elseif ( strpos( $this->route, '<product_id>' ) !== false && ! empty( $args ) ) {
			$route             = str_replace( '(?P<product_id>[\d]+)', $args[0], $route );
			$parent_id_matched = true;
		} elseif ( strpos( $this->route, '<order_id>' ) !== false && ! empty( $args ) ) {
			$route             = str_replace( '(?P<order_id>[\d]+)', $args[0], $route );
			$parent_id_matched = true;
		} elseif ( strpos( $this->route, '<refund_id>' ) !== false && ! empty( $args ) ) {
			$route             = str_replace( '(?P<refund_id>[\d]+)', $args[0], $route );
			$parent_id_matched = true;
		}

		$route = str_replace( array( '(?P<id>[\d]+)', '(?P<id>[\w-]+)' ), ( $parent_id_matched && ! empty( $args[1] ) ? $args[1] : $args[0] ), $route );
		return rtrim( $route );
	}

	/**
	 * Output a line to be added
	 *
	 * @param string
	 */
	private function add_line( $line ) {
		$this->nested_line( $line, 'add' );
	}

	/**
	 * Output a line to be removed
	 *
	 * @param string
	 */
	private function remove_line( $line ) {
		$this->nested_line( $line, 'remove' );
	}

	/**
	 * Output a line that's appropriately nested
	 */
	private function nested_line( $line, $change = false ) {
		if ( 'add' == $change ) {
			$label = '+ ';
		} elseif ( 'remove' == $change ) {
			$label = '- ';
		} else {
			$label = false;
		}

		$spaces = ( $this->output_nesting_level * 2 ) + 2;
		if ( $label ) {
			$line = $label . $line;
			$spaces = $spaces - 2;
		}
		WP_CLI::line( str_pad( ' ', $spaces ) . $line );
	}

	/**
	 * Whether or not this is an associative array
	 *
	 * @param array
	 * @return bool
	 */
	private function is_assoc_array( $array ) {
		if ( ! is_array( $array ) ) {
			return false;
		}
		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}

	/**
	 * Reduce an item to specific fields.
	 *
	 * @param  array $item
	 * @param  array $fields
	 * @return array
	 */
	private static function limit_item_to_fields( $item, $fields ) {
		if ( empty( $fields ) ) {
			return $item;
		}
		if ( is_string( $fields ) ) {
			$fields = explode( ',', $fields );
		}
		foreach ( $item as $i => $field ) {
			if ( ! in_array( $i, $fields ) ) {
				unset( $item[ $i ] );
			}
		}
		return $item;
	}

	/**
	 * JSON can be passed in some more complicated objects, like the payment gateway settings array.
	 * This function decodes the json (if present) and tries to get it's value.
	 *
	 * @param array $arr
	 */
	protected function decode_json( $arr ) {
		foreach ( $arr as $key => $value ) {
			if ( '[' === substr( $value, 0, 1 ) || '{' === substr( $value, 0, 1 ) ) {
				$arr[ $key ] = json_decode( $value, true );
			} else {
				continue;
			}
		}
		return $arr;
	}

}
