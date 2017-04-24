<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract WC Object Query Class
 *
 * Extended by classes to provide a query abstraction layer for safe object searching.
 *
 * @version  3.1.0
 * @package  WooCommerce/Abstracts
 * @category Abstract Class
 * @author   Automattic
 */
abstract class WC_Object_Query {

	/**
	 * Stores query data.
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * Create a new query.
	 * @param array $args Criteria to query on in a format similar to WP_Query.
	 */
	public function __construct( $args = array() ) {
		$this->query_vars = wp_parse_args( $args, $this->get_default_query_vars() );
	}

	/**
	 * Get the value of a query variable.
	 * @param string $query_var Query variable to get value for.
	 * @param mixed $default Default value if query variable is not set.
	 * @return mixed Query variable value if set, otherwise default.
	 */
	public function get( $query_var, $default = '' ) {
		if ( isset( $this->query_vars[ $query_var ] ) ) {
			return $this->query_vars[ $query_var ];
		}
		return $default;
	}

	/**
	 * Set a query variable.
	 * @param string $query_var Query variable to set.
	 * @param mixed $value Value to set for query variable.
	 */
	public function set( $query_var, $value ) {
		$this->query_vars[$query_var] = $value;
	}

	/**
	 * Get the default, unset allowed query vars.
	 * @return array
	 */
	protected function get_default_query_vars() {

		return array(
			'tax_query'            => array(),
			'meta_query'           => array(),
			'date_query'           => array(),

			'p'                    => '',
			'name'                 => '',
			'post_parent'          => '',
			'post_parent__in'      => array(),
			'post_parent__not_in'  => array(),
			'post__in'             => array(),
			'post__not_in'         => array(),

			'has_password'         => null,
			'post_password'        => '',

			'post_status'          => array( 'publish', 'pending', 'draft', 'future', 'private', 'inherit' ),

			'posts_per_page'       => 10,
			'nopaging'             => false,
			'page'                 => 1,
			'offset'               => 0,

			'order'                => 'DESC',
			'orderby'              => 'date',

			'year'                 => '',
			'monthnum'             => '',
			'w'                    => '',
			'day'                  => '',
			'hour'                 => '',
			'minute'               => '',
			'second'               => '',
			'm'                    => '',

			'meta_key'             => '',
			'meta_value'           => '',
			'meta_value_num'       => '',
			'meta_compare'         => '=',

			's'                    => '',
			'exact'                => true,
			'sentence'             => '',

			'fields'               => '',
		);
	}
}
