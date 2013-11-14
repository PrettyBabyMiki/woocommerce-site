<?php
/**
 * WooCommerce API
 *
 * Handles REST API requests
 *
 * This class and related code (JSON response handler, resource classes) are based on WP-API v0.6 (https://github.com/WP-API/WP-API)
 * Many thanks to Ryan McCue and any other contributors!
 *
 * @author      WooThemes
 * @category    API
 * @package     WooCommerce/API
 * @since       2.1
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once ABSPATH . 'wp-admin/includes/admin.php';

class WC_API_Server {

	const METHOD_GET    = 1;
	const METHOD_POST   = 2;
	const METHOD_PUT    = 4;
	const METHOD_PATCH  = 8;
	const METHOD_DELETE = 16;

	const READABLE   = 1;  // GET
	const CREATABLE  = 2;  // POST
	const EDITABLE   = 14; // POST | PUT | PATCH
	const DELETABLE  = 16; // DELETE
	const ALLMETHODS = 31; // GET | POST | PUT | PATCH | DELETE

	/**
	 * Does the endpoint accept a raw request body?
	 */
	const ACCEPT_RAW_DATA = 64;

	/** Does the endpoint accept a request body? (either JSON or XML) */
	const ACCEPT_DATA = 128;

	/**
	 * Should we hide this endpoint from the index?
	 */
	const HIDDEN_ENDPOINT = 256;

	/**
	 * Map of HTTP verbs to constants
	 * @var array
	 */
	public static $method_map = array(
		'HEAD'   => self::METHOD_GET,
		'GET'    => self::METHOD_GET,
		'POST'   => self::METHOD_POST,
		'PUT'    => self::METHOD_PUT,
		'PATCH'  => self::METHOD_PATCH,
		'DELETE' => self::METHOD_DELETE,
	);

	/**
	 * Requested path (relative to the API root, wp-json.php)
	 *
	 * @var string
	 */
	public $path = '';

	/**
	 * Requested method (GET/HEAD/POST/PUT/PATCH/DELETE)
	 *
	 * @var string
	 */
	public $method = 'HEAD';

	/**
	 * Request parameters
	 *
	 * This acts as an abstraction of the superglobals
	 * (GET => $_GET, POST => $_POST)
	 *
	 * @var array
	 */
	public $params = array( 'GET' => array(), 'POST' => array() );

	/**
	 * Request headers
	 *
	 * @var array
	 */
	public $headers = array();

	/**
	 * Request files (matches $_FILES)
	 *
	 * @var array
	 */
	public $files = array();

	/**
	 * Request/Response handler, either JSON by default
	 * or XML if requested by client
	 *
	 * @var WC_API_Handler
	 */
	public $handler;


	/**
	 * Setup class and set request/response handler
	 *
	 * @since 2.1
	 * @param $path
	 * @return WC_API_Server
	 */
	public function __construct( $path ) {

		if ( empty( $path ) ) {
			if ( isset( $_SERVER['PATH_INFO'] ) )
				$path = $_SERVER['PATH_INFO'];
			else
				$path = '/';
		}

		$this->path           = $path;
		$this->method         = $_SERVER['REQUEST_METHOD'];
		$this->params['GET']  = $_GET;
		$this->params['POST'] = $_POST;
		$this->headers        = $this->get_headers( $_SERVER );
		$this->files          = $_FILES;

		// Compatibility for clients that can't use PUT/PATCH/DELETE
		if ( isset( $_GET['_method'] ) ) {
			$this->method = strtoupper( $_GET['_method'] );
		}

		// determine type of request/response and load handler, JSON by default
		if ( $this->is_json_request() )
			$handler_class = 'WC_API_JSON_Handler';

		elseif ( $this->is_xml_request() )
			$handler_class = 'WC_API_XML_Handler';

		else
			$handler_class = apply_filters( 'woocommerce_api_default_response_handler', 'WC_API_JSON_Handler', $this->path, $this );

		$this->handler = new $handler_class();
	}

	/**
	 * Check authentication for the request
	 *
	 * @since 2.1
	 * @return WP_User|WP_Error WP_User object indicates successful login, WP_Error indicates unsuccessful login
	 */
	public function check_authentication() {

		// allow plugins to remove default authentication or add their own authentication
		$user = apply_filters( 'woocommerce_api_check_authentication', null, $this );

		// API requests run under the context of the authenticated user
		if ( is_a( $user, 'WP_User' ) )
			wp_set_current_user( $user->ID );

		// WP_Errors are handled in serve_request()
		elseif ( ! is_wp_error( $user ) )
			$user = new WP_Error( 'woocommerce_api_authentication_error', __( 'Invalid authentication method', 'woocommerce' ), array( 'code' => '500' ) );

		return $user;
	}

	/**
	 * Convert an error to an array
	 *
	 * This iterates over all error codes and messages to change it into a flat
	 * array. This enables simpler client behaviour, as it is represented as a
	 * list in JSON rather than an object/map
	 *
	 * @since 2.1
	 * @param WP_Error $error
	 * @return array List of associative arrays with code and message keys
	 */
	protected function error_to_array( $error ) {
		$errors = array();
		foreach ( (array) $error->errors as $code => $messages ) {
			foreach ( (array) $messages as $message ) {
				$errors[] = array( 'code' => $code, 'message' => $message );
			}
		}
		return $errors;
	}

	/**
	 * Handle serving an API request
	 *
	 * Matches the current server URI to a route and runs the first matching
	 * callback then outputs a JSON representation of the returned value.
	 *
	 * @since 2.1
	 * @uses WC_API_Server::dispatch()
	 */
	public function serve_request() {

		do_action( 'woocommerce_api_server_before_serve', $this );

		$this->header( 'Content-Type', $this->handler->get_content_type(), true );

		// TODO: can we prevent wc_cookie from being sent for API requests?

		// the API is enabled by default TODO: implement check for enabled setting here
		if ( ! apply_filters( 'woocommerce_api_enabled', true, $this ) ) {

			$this->send_status( 404 );

			echo $this->handler->generate_response( array( array( 'code' => 'woocommerce_api_disabled', 'message' => 'The WooCommerce API is disabled on this site' ) ) );

			return;
		}

		$result = $this->check_authentication();

		// if authorization check was successful, dispatch the request
		if ( ! is_wp_error( $result ) ) {
			$result = $this->dispatch();
		}

		// handle any dispatch errors
		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();
			if ( is_array( $data ) && isset( $data['status'] ) ) {
				$this->send_status( $data['status'] );
			}

			$result = $this->error_to_array( $result );
		}

		// This is a filter rather than an action, since this is designed to be
		// re-entrant if needed
		$served = apply_filters( 'woocommerce_api_serve_request', false, $result, $this );

		if ( ! $served ) {

			if ( 'HEAD' === $this->method )
				return;

			echo $this->handler->generate_response( $result );
		}
	}

	/**
	 * Retrieve the route map
	 *
	 * The route map is an associative array with path regexes as the keys. The
	 * value is an indexed array with the callback function/method as the first
	 * item, and a bitmask of HTTP methods as the second item (see the class
	 * constants).
	 *
	 * Each route can be mapped to more than one callback by using an array of
	 * the indexed arrays. This allows mapping e.g. GET requests to one callback
	 * and POST requests to another.
	 *
	 * Note that the path regexes (array keys) must have @ escaped, as this is
	 * used as the delimiter with preg_match()
	 *
	 * @since 2.1
	 * @return array `'/path/regex' => array( $callback, $bitmask )` or `'/path/regex' => array( array( $callback, $bitmask ), ...)`
	 */
	public function get_routes() {

		// index added by default
		$endpoints = array(

			'/' => array( array( $this, 'get_index' ), self::READABLE ),
		);

		$endpoints = apply_filters( 'woocommerce_api_endpoints', $endpoints );

		// Normalise the endpoints
		foreach ( $endpoints as $route => &$handlers ) {
			if ( count( $handlers ) <= 2 && isset( $handlers[1] ) && ! is_array( $handlers[1] ) ) {
				$handlers = array( $handlers );
			}
		}

		return $endpoints;
	}

	/**
	 * Match the request to a callback and call it
	 *
	 * @since 2.1
	 * @return mixed The value returned by the callback, or a WP_Error instance
	 */
	public function dispatch() {

		switch ( $this->method ) {

			case 'HEAD':
			case 'GET':
				$method = self::METHOD_GET;
				break;

			case 'POST':
				$method = self::METHOD_POST;
				break;

			case 'PUT':
				$method = self::METHOD_PUT;
				break;

			case 'PATCH':
				$method = self::METHOD_PATCH;
				break;

			case 'DELETE':
				$method = self::METHOD_DELETE;
				break;

			default:
				return new WP_Error( 'woocommerce_api_unsupported_method', __( 'Unsupported request method' ), array( 'status' => 400 ) );
		}

		foreach ( $this->get_routes() as $route => $handlers ) {
			foreach ( $handlers as $handler ) {
				$callback = $handler[0];
				$supported = isset( $handler[1] ) ? $handler[1] : self::METHOD_GET;

				if ( !( $supported & $method ) )
					continue;

				$match = preg_match( '@^' . $route . '$@i', $this->path, $args );

				if ( !$match )
					continue;

				if ( ! is_callable( $callback ) )
					return new WP_Error( 'woocommerce_api_invalid_handler', __( 'The handler for the route is invalid' ), array( 'status' => 500 ) );

				$args = array_merge( $args, $this->params['GET'] );
				if ( $method & self::METHOD_POST ) {
					$args = array_merge( $args, $this->params['POST'] );
				}
				if ( $supported & self::ACCEPT_DATA ) {
					$data = $this->handler->parse_body( $this->get_raw_data() );
					$args = array_merge( $args, array( 'data' => $data ) );
				}
				elseif ( $supported & self::ACCEPT_RAW_DATA ) {
					$data = $this->get_raw_data();
					$args = array_merge( $args, array( 'data' => $data ) );
				}

				$args['_method']  = $method;
				$args['_route']   = $route;
				$args['_path']    = $this->path;
				$args['_headers'] = $this->headers;
				$args['_files']   = $this->files;

				$args = apply_filters( 'woocommerce_api_dispatch_args', $args, $callback );

				// Allow plugins to halt the request via this filter
				if ( is_wp_error( $args ) ) {
					return $args;
				}

				$params = $this->sort_callback_params( $callback, $args );
				if ( is_wp_error( $params ) )
					return $params;

				return call_user_func_array( $callback, $params );
			}
		}

		return new WP_Error( 'woocommerce_api_no_route', __( 'No route was found matching the URL and request method' ), array( 'status' => 404 ) );
	}

	/**
	 * Sort parameters by order specified in method declaration
	 *
	 * Takes a callback and a list of available params, then filters and sorts
	 * by the parameters the method actually needs, using the Reflection API
	 *
	 * @since 2.1
	 * @param array $callback the endpoint callback
	 * @param array $provided the provided request parameters
	 * @return array
	 */
	protected function sort_callback_params( $callback, $provided ) {
		if ( is_array( $callback ) )
			$ref_func = new ReflectionMethod( $callback[0], $callback[1] );
		else
			$ref_func = new ReflectionFunction( $callback );

		$wanted = $ref_func->getParameters();
		$ordered_parameters = array();

		foreach ( $wanted as $param ) {
			if ( isset( $provided[ $param->getName() ] ) ) {
				// We have this parameters in the list to choose from

				$ordered_parameters[] = is_array( $provided[ $param->getName() ] ) ? array_map( 'urldecode', $provided[ $param->getName() ] ) : urldecode( $provided[ $param->getName() ] );
			}
			elseif ( $param->isDefaultValueAvailable() ) {
				// We don't have this parameter, but it's optional
				$ordered_parameters[] = $param->getDefaultValue();
			}
			else {
				// We don't have this parameter and it wasn't optional, abort!
				return new WP_Error( 'woocommerce_api_missing_callback_param', sprintf( __( 'Missing parameter %s' ), $param->getName() ), array( 'status' => 400 ) );
			}
		}
		return $ordered_parameters;
	}

	/**
	 * Get the site index.
	 *
	 * This endpoint describes the capabilities of the site.
	 *
	 * @since 2.1
	 * @return array Index entity
	 */
	public function get_index() {

		// General site data
		$available = array(
			'name'        => get_option( 'blogname' ),
			'description' => get_option( 'blogdescription' ),
			'URL'         => get_option( 'siteurl' ),
			'routes'      => array(),
			'meta'        => array(
				'timezone'       => $this->get_timezone(),
				'currency'       => get_woocommerce_currency(),
				'money_format'   => get_woocommerce_currency_symbol(),
				'tax_included'   => ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) ),
				'weight_unit'    => get_option( 'woocommerce_weight_unit' ),
				'dimension_unit' => get_option( 'woocommerce_dimension_unit' ),
				'ssl_enabled'    => ( 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ),
				'links'          => array(
					'help'    => 'http://docs.woothemes.com/document/woocommerce-rest-api/',
					'profile' => 'https://raw.github.com/rmccue/WP-API/master/docs/schema.json', // TODO: update this
				),
			),
		);

		// Find the available routes
		foreach ( $this->get_routes() as $route => $callbacks ) {
			$data = array();

			$route = preg_replace( '#\(\?P(<\w+?>).*?\)#', '$1', $route );
			$methods = array();
			foreach ( self::$method_map as $name => $bitmask ) {
				foreach ( $callbacks as $callback ) {
					// Skip to the next route if any callback is hidden
					if ( $callback[1] & self::HIDDEN_ENDPOINT )
						continue 3;

					if ( $callback[1] & $bitmask )
						$data['supports'][] = $name;

					if ( $callback[1] & self::ACCEPT_DATA )
						$data['accepts_data'] = true;

					// For non-variable routes, generate links
					if ( strpos( $route, '<' ) === false ) {
						$data['meta'] = array(
							'self' => get_woocommerce_api_url( $route ),
						);
					}
				}
			}
			$available['routes'][$route] = apply_filters( 'woocommerce_api_endpoints_description', $data );
		}
		return apply_filters( 'woocommerce_api_index', $available );
	}

	/**
	 * Send a HTTP status code
	 *
	 * @since 2.1
	 * @param int $code HTTP status
	 */
	public function send_status( $code ) {
		status_header( $code );
	}

	/**
	 * Send a HTTP header
	 *
	 * @since 2.1
	 * @param string $key Header key
	 * @param string $value Header value
	 * @param boolean $replace Should we replace the existing header?
	 */
	public function header( $key, $value, $replace = true ) {
		header( sprintf( '%s: %s', $key, $value ), $replace );
	}

	/**
	 * Send a Link header
	 *
	 * @internal The $rel parameter is first, as this looks nicer when sending multiple
	 *
	 * @link http://tools.ietf.org/html/rfc5988
	 * @link http://www.iana.org/assignments/link-relations/link-relations.xml
	 *
	 * @since 2.1
	 * @param string $rel Link relation. Either a registered type, or an absolute URL
	 * @param string $link Target IRI for the link
	 * @param array $other Other parameters to send, as an assocative array
	 */
	public function link_header( $rel, $link, $other = array() ) {
		$header = 'Link: <' . $link . '>; rel="' . $rel . '"';
		foreach ( $other as $key => $value ) {
			if ( 'title' == $key )
				$value = '"' . $value . '"';
			$header .= '; ' . $key . '=' . $value;
		}
		$this->header( 'Link', $header, false );
	}

	/**
	 * Send navigation-related headers for post collections
	 *
	 * @since 2.1
	 * @param WP_Query $query
	 */
	public function query_navigation_headers( $query ) {
		$max_page = $query->max_num_pages;
		$paged = $query->get('paged');

		if ( !$paged )
			$paged = 1;

		$nextpage = intval($paged) + 1;
		// TODO: change from `page` query arg to filter[page] arg
		if ( ! $query->is_single() ) {
			if ( $paged > 1 ) {
				$request = remove_query_arg( 'page' );
				$request = add_query_arg( 'page', $paged - 1, $request );
				$this->link_header( 'prev', $request );
			}

			if ( $nextpage <= $max_page ) {
				$request = remove_query_arg( 'page' );
				$request = add_query_arg( 'page', $nextpage, $request );
				$this->link_header( 'next', $request );
			}
		}

		$this->header( 'X-WP-Total', $query->found_posts );
		$this->header( 'X-WP-TotalPages', $max_page );

		do_action('woocommerce_api_query_navigation_headers', $this, $query);
	}


	/**
	 * Retrieve the raw request entity (body)
	 *
	 * @since 2.1
	 * @return string
	 */
	public function get_raw_data() {
		global $HTTP_RAW_POST_DATA;

		// A bug in PHP < 5.2.2 makes $HTTP_RAW_POST_DATA not set by default,
		// but we can do it ourself.
		if ( !isset( $HTTP_RAW_POST_DATA ) ) {
			$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
		}

		return $HTTP_RAW_POST_DATA;
	}

	/**
	 * Parse an RFC3339 timestamp into a DateTime
	 *
	 * @param string $date RFC3339 timestamp
	 * @param boolean $force_utc Force UTC timezone instead of using the timestamp's TZ?
	 * @return DateTime
	 */
	public function parse_date( $date, $force_utc = false ) {
		// Default timezone to the server's current one
		$timezone = self::get_timezone();
		if ( $force_utc ) {
			$date = preg_replace( '/[+-]\d+:?\d+$/', '+00:00', $date );
			$timezone = new DateTimeZone( 'UTC' );
		}

		// Strip millisecond precision (a full stop followed by one or more digits)
		if ( strpos( $date, '.' ) !== false ) {
			$date = preg_replace( '/\.\d+/', '', $date );
		}
		$datetime = DateTime::createFromFormat( DateTime::RFC3339, $date ); // TODO: rewrite, PHP 5.3+ required for this

		return $datetime;
	}

	/**
	 * Get a local date with its GMT equivalent, in MySQL datetime format
	 *
	 * @param string $date RFC3339 timestamp
	 * @param boolean $force_utc Should we force UTC timestamp?
	 * @return array Local and UTC datetime strings, in MySQL datetime format (Y-m-d H:i:s)
	 */
	public function get_date_with_gmt( $date, $force_utc = false ) {
		$datetime = $this->parse_date( $date, $force_utc );

		$datetime->setTimezone( self::get_timezone() );
		$local = $datetime->format( 'Y-m-d H:i:s' );

		$datetime->setTimezone( new DateTimeZone( 'UTC' ) );
		$utc = $datetime->format('Y-m-d H:i:s');

		return array( $local, $utc );
	}

	/**
	 * Get the timezone object for the site
	 *
	 * @return DateTimeZone
	 */
	public function get_timezone() {
		static $zone = null;
		if ($zone !== null)
			return $zone;

		$tzstring = get_option( 'timezone_string' );
		if ( ! $tzstring ) {
			// Create a UTC+- zone if no timezone string exists
			$current_offset = get_option( 'gmt_offset' );
			if ( 0 == $current_offset )
				$tzstring = 'UTC';
			elseif ($current_offset < 0)
				$tzstring = 'Etc/GMT' . $current_offset;
			else
				$tzstring = 'Etc/GMT+' . $current_offset;
		}
		$zone = new DateTimeZone( $tzstring );
		return $zone;
	}

	/**
	 * Extract headers from a PHP-style $_SERVER array
	 *
	 * @since 2.1
	 * @param array $server Associative array similar to $_SERVER
	 * @return array Headers extracted from the input
	 */
	public function get_headers($server) {
		$headers = array();
		// CONTENT_* headers are not prefixed with HTTP_
		$additional = array('CONTENT_LENGTH' => true, 'CONTENT_MD5' => true, 'CONTENT_TYPE' => true);

		foreach ($server as $key => $value) {
			if ( strpos( $key, 'HTTP_' ) === 0) {
				$headers[ substr( $key, 5 ) ] = $value;
			}
			elseif ( isset( $additional[ $key ] ) ) {
				$headers[ $key ] = $value;
			}
		}

		return $headers;
	}

	/**
	 * Check if the current request accepts a JSON response by checking the endpoint suffix (.json) or
	 * the HTTP ACCEPT header
	 *
	 * @since 2.1
	 * @return bool
	 */
	private function is_json_request() {

		// check path
		if ( false !== stripos( $this->path, '.json' ) )
			return true;

		// check ACCEPT header, only 'application/json' is acceptable, see RFC 4627
		if ( isset( $this->headers['ACCEPT'] ) && 'application/json' == $this->headers['ACCEPT'] )
			return true;

		return false;
	}

	/**
	 * Check if the current request accepts an XML response by checking the endpoint suffix (.xml) or
	 * the HTTP ACCEPT header
	 *
	 * @since 2.1
	 * @return bool
	 */
	private function is_xml_request() {

		// check path
		if ( false !== stripos( $this->path, '.xml' ) )
			return true;

		// check headers, 'application/xml' or 'text/xml' are acceptable, see RFC 2376
		if ( isset( $this->headers['ACCEPT'] ) && ( 'application/xml' == $this->headers['ACCEPT'] || 'text/xml' == $this->headers['ACCEPT'] ) )
			return true;

		return false;
	}
}
