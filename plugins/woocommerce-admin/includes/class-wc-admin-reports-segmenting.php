<?php
/**
 * Class for adding segmenting support without cluttering the data stores.
 *
 * @package  WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Date & time interval and numeric range handling class for Reporting API.
 */
class WC_Admin_Reports_Segmenting {

	/**
	 * Array of all segment ids.
	 *
	 * @var array|bool
	 */
	protected $all_segment_ids = false;

	/**
	 * Query arguments supplied by the user for data store.
	 *
	 * @var array
	 */
	protected $query_args = '';

	/**
	 * SQL definition for each column.
	 *
	 * @var array
	 */
	protected $report_columns = array();

	/**
	 * WC_Admin_Reports_Segmenting constructor.
	 *
	 * @param array $query_args Query arguments supplied by the user for data store.
	 * @param array $report_columns Report columns lookup from data store.
	 */
	public function __construct( $query_args, $report_columns ) {
		$this->query_args     = $query_args;
		$this->report_columns = $report_columns;
	}

	/**
	 * Filters definitions for SELECT clauses based on query_args and joins them into one string usable in SELECT clause.
	 *
	 * @param array $columns_mapping Column name -> SQL statememt mapping.
	 *
	 * @return string to be used in SELECT clause statements.
	 */
	protected function prepare_selections( $columns_mapping ) {
		if ( isset( $this->query_args['fields'] ) && is_array( $this->query_args['fields'] ) ) {
			$keep = array();
			foreach ( $this->query_args['fields'] as $field ) {
				if ( isset( $columns_mapping[ $field ] ) ) {
					$keep[ $field ] = $columns_mapping[ $field ];
				}
			}
			$selections = implode( ', ', $keep );
		} else {
			$selections = implode( ', ', $columns_mapping );
		}

		if ( $selections ) {
			$selections = ',' . $selections;
		}

		return $selections;
	}

	/**
	 * Update row-level db result for segments in 'totals' section to the format used for output.
	 *
	 * @param array  $segments_db_result Results from the SQL db query for segmenting.
	 * @param string $segment_dimension Name of column used for grouping the result.
	 *
	 * @return array Reformatted array.
	 */
	protected function reformat_totals_segments( $segments_db_result, $segment_dimension ) {
		$segment_result = array();

		if ( strpos( $segment_dimension, '.' ) ) {
			$segment_dimension = substr( strstr( $segment_dimension, '.' ), 1 );
		}

		foreach ( $segments_db_result as $segment_data ) {
			$segment_id = $segment_data[ $segment_dimension ];
			unset( $segment_data[ $segment_dimension ] );
			$segment_datum                 = array(
				'segment_id' => $segment_id,
				'subtotals'  => $segment_data,
			);
			$segment_result[ $segment_id ] = $segment_datum;
		}

		return $segment_result;
	}

	/**
	 * Merges segmented results for totals response part.
	 *
	 * E.g. $r1 = array(
	 *     0 => array(
	 *          'product_id' => 3,
	 *          'net_amount' => 15,
	 *     ),
	 * );
	 * $r2 = array(
	 *     0 => array(
	 *          'product_id'      => 3,
	 *          'avg_order_value' => 25,
	 *     ),
	 * );
	 *
	 * $merged = array(
	 *     3 => array(
	 *          'segment_id' => 3,
	 *          'subtotals'  => array(
	 *              'net_amount'      => 15,
	 *              'avg_order_value' => 25,
	 *          )
	 *     ),
	 * );
	 *
	 * @param string $segment_dimension Name of the segment dimension=key in the result arrays used to match records from result sets.
	 * @param array  $result1 Array 1 of segmented figures.
	 * @param array  $result2 Array 2 of segmented figures.
	 *
	 * @return array
	 */
	protected function merge_segment_totals_results( $segment_dimension, $result1, $result2 ) {
		$result_segments = array();

		foreach ( $result1 as $segment_data ) {
			$segment_id = $segment_data[ $segment_dimension ];
			unset( $segment_data[ $segment_dimension ] );
			$result_segments[ $segment_id ] = array(
				'segment_id' => $segment_id,
				'subtotals'  => $segment_data,
			);
		}

		foreach ( $result2 as $segment_data ) {
			$segment_id = $segment_data[ $segment_dimension ];
			unset( $segment_data[ $segment_dimension ] );
			if ( ! isset( $result_segments[ $segment_id ] ) ) {
				$result_segments[ $segment_id ] = array(
					'segment_id' => $segment_id,
					'subtotals'  => array(),
				);
			}
			$result_segments[ $segment_id ]['subtotals'] = array_merge( $result_segments[ $segment_id ]['subtotals'], $segment_data );
		}
		return $result_segments;
	}
	/**
	 * Merges segmented results for intervals response part.
	 *
	 * E.g. $r1 = array(
	 *     0 => array(
	 *          'product_id'    => 3,
	 *          'time_interval' => '2018-12'
	 *          'net_amount'    => 15,
	 *     ),
	 * );
	 * $r2 = array(
	 *     0 => array(
	 *          'product_id'      => 3,
	 *          'time_interval' => '2018-12'
	 *          'avg_order_value' => 25,
	 *     ),
	 * );
	 *
	 * $merged = array(
	 *     '2018-12' => array(
	 *          'segments' => array(
	 *              3 => array(
	 *                  'segment_id' => 3,
	 *                  'subtotals'  => array(
	 *                      'net_amount'      => 15,
	 *                      'avg_order_value' => 25,
	 *                  ),
	 *              ),
	 *          ),
	 *     ),
	 * );
	 *
	 * @param string $segment_dimension Name of the segment dimension=key in the result arrays used to match records from result sets.
	 * @param array  $result1 Array 1 of segmented figures.
	 * @param array  $result2 Array 2 of segmented figures.
	 *
	 * @return array
	 */
	protected function merge_segment_intervals_results( $segment_dimension, $result1, $result2 ) {
		$result_segments = array();

		foreach ( $result1 as $segment_data ) {
			$time_interval = $segment_data['time_interval'];
			if ( ! isset( $result_segments[ $time_interval ] ) ) {
				$result_segments[ $time_interval ]             = array();
				$result_segments[ $time_interval ]['segments'] = array();
			}
			unset( $segment_data['time_interval'] );
			unset( $segment_data['datetime_anchor'] );
			$segment_id = $segment_data[ $segment_dimension ];
			unset( $segment_data[ $segment_dimension ] );
			$segment_datum = array(
				'segment_id' => $segment_id,
				'subtotals'  => $segment_data,
			);
			$result_segments[ $time_interval ]['segments'][ $segment_id ] = $segment_datum;
		}

		foreach ( $result2 as $segment_data ) {
			$time_interval = $segment_data['time_interval'];
			if ( ! isset( $result_segments[ $time_interval ] ) ) {
				$result_segments[ $time_interval ]             = array();
				$result_segments[ $time_interval ]['segments'] = array();
			}
			unset( $segment_data['time_interval'] );
			unset( $segment_data['datetime_anchor'] );
			$segment_id = $segment_data[ $segment_dimension ];
			unset( $segment_data[ $segment_dimension ] );

			if ( ! isset( $result_segments[ $time_interval ]['segments'][ $segment_id ] ) ) {
				$result_segments[ $time_interval ]['segments'][ $segment_id ] = array(
					'segment_id' => $segment_id,
					'subtotals'  => array(),
				);
			}
			$result_segments[ $time_interval ]['segments'][ $segment_id ]['subtotals'] = array_merge( $result_segments[ $time_interval ]['segments'][ $segment_id ]['subtotals'], $segment_data );
		}
		return $result_segments;
	}

	/**
	 * Update row-level db result for segments in 'intervals' section to the format used for output.
	 *
	 * @param array  $segments_db_result Results from the SQL db query for segmenting.
	 * @param string $segment_dimension Name of column used for grouping the result.
	 *
	 * @return array Reformatted array.
	 */
	protected function reformat_intervals_segments( $segments_db_result, $segment_dimension ) {
		$aggregated_segment_result = array();

		if ( strpos( $segment_dimension, '.' ) ) {
			$segment_dimension = substr( strstr( $segment_dimension, '.' ), 1 );
		}

		foreach ( $segments_db_result as $segment_data ) {
			$time_interval = $segment_data['time_interval'];
			if ( ! isset( $aggregated_segment_result[ $time_interval ] ) ) {
				$aggregated_segment_result[ $time_interval ]             = array();
				$aggregated_segment_result[ $time_interval ]['segments'] = array();
			}
			unset( $segment_data['time_interval'] );
			unset( $segment_data['datetime_anchor'] );
			$segment_id = $segment_data[ $segment_dimension ];
			unset( $segment_data[ $segment_dimension ] );
			$segment_datum = array(
				'segment_id' => $segment_id,
				'subtotals'  => $segment_data,
			);
			$aggregated_segment_result[ $time_interval ]['segments'][ $segment_id ] = $segment_datum;
		}

		return $aggregated_segment_result;
	}

	/**
	 * Fetches all segment ids from db and stores it for later use.
	 *
	 * @return array
	 */
	protected function set_all_segments() {
		global $wpdb;

		if ( ! isset( $this->query_args['segmentby'] ) || '' === $this->query_args['segmentby'] ) {
			$this->all_segment_ids = array();
		}

		if ( 'product' === $this->query_args['segmentby'] ) {
			$segments = wc_get_products(
				array(
					'return' => 'ids',
					'limit'  => -1,
				)
			);
		} elseif ( 'variation' === $this->query_args['segmentby'] ) {
			// TODO: assuming that this will only be used for one product, check assumption.
			if ( ! isset( $this->query_args['product_includes'] ) || count( $this->query_args['product_includes'] ) !== 1 ) {
				return array();
			}

			$segments = wc_get_products(
				array(
					'return' => 'ids',
					'limit'  => - 1,
					'type'   => 'variation',
					'parent' => $this->query_args['product_includes'][0],
				)
			);
		} elseif ( 'category' === $this->query_args['segmentby'] ) {
			$categories = get_categories(
				array(
					'taxonomy' => 'product_cat',
				)
			);
			$segments   = wp_list_pluck( $categories, 'cat_ID' );
		} elseif ( 'coupon' === $this->query_args['segmentby'] ) {
			// TODO: switch to a non-direct-SQL way to get all coupons?
			$coupon_ids = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type='shop_coupon' AND post_status='publish'", ARRAY_A ); // WPCS: cache ok, DB call ok, unprepared SQL ok.
			$segments   = wp_list_pluck( $coupon_ids, 'ID' );
		} elseif ( 'customer_type' === $this->query_args['segmentby'] ) {
			// 0 -- new customer
			// 1 -- returning customer
			$segments = array( 0, 1 );
		} else {
			// Catch all default.
			$segments = array();
		}

		$this->all_segment_ids = $segments;
	}

	/**
	 * Return all segment ids for given segmentby query parameter.
	 *
	 * @return array
	 */
	protected function get_all_segments() {
		if ( ! is_array( $this->all_segment_ids ) ) {
			$this->set_all_segments();
		}

		return $this->all_segment_ids;
	}

	/**
	 * Compares two report data objects by pre-defined object property and ASC/DESC ordering.
	 *
	 * @param stdClass $a Object a.
	 * @param stdClass $b Object b.
	 * @return string
	 */
	private function segment_cmp( $a, $b ) {
		if ( $a['segment_id'] === $b['segment_id'] ) {
			return 0;
		} elseif ( $a['segment_id'] > $b['segment_id'] ) {
			return 1;
		} elseif ( $a['segment_id'] < $b['segment_id'] ) {
			return - 1;
		}
	}

	/**
	 * Adds zeroes for segments not present in the data selection.
	 *
	 * @param array $segments Array of segments from the database for given data points.
	 *
	 * @return array
	 */
	protected function fill_in_missing_segments( $segments ) {

		$segment_subtotals = array();
		if ( isset( $this->query_args['fields'] ) && is_array( $this->query_args['fields'] ) ) {
			foreach ( $this->query_args['fields'] as $field ) {
				if ( isset( $this->report_columns[ $field ] ) ) {
					$segment_subtotals[ $field ] = 0;
				}
			}
		} else {
			foreach ( $this->report_columns as $field => $sql_clause ) {
				$segment_subtotals[ $field ] = 0;
			}
		}
		if ( ! is_array( $segments ) ) {
			$segments = array();
		}
		$all_segment_ids = $this->get_all_segments();
		foreach ( $all_segment_ids as $segment_id ) {
			if ( ! isset( $segments[ $segment_id ] ) ) {
				$segments[ $segment_id ] = array(
					'segment_id' => $segment_id,
					'subtotals'  => $segment_subtotals,
				);
			}
		}

		// Using array_values to remove custom keys, so that it gets later converted to JSON as an array.
		$segments_no_keys = array_values( $segments );
		usort( $segments_no_keys, array( $this, 'segment_cmp' ) );
		return $segments_no_keys;
	}

	/**
	 * Adds missing segments to intervals, modifies $data.
	 *
	 * @param stdClass $data Response data.
	 */
	protected function fill_in_missing_interval_segments( &$data ) {
		foreach ( $data->intervals as $order_id => $interval_data ) {
			$data->intervals[ $order_id ]['segments'] = $this->fill_in_missing_segments( $data->intervals[ $order_id ]['segments'] );
		}
	}

	/**
	 * Calculate segments for segmenting property bound to product (e.g. category, product_id, variation_id).
	 *
	 * @param string $type Type of segments to return--'totals' or 'intervals'.
	 * @param array  $segmenting_selections SELECT part of segmenting SQL query--one for 'product_level' and one for 'order_level'.
	 * @param string $segmenting_from FROM part of segmenting SQL query.
	 * @param string $segmenting_where WHERE part of segmenting SQL query.
	 * @param string $segmenting_groupby GROUP BY part of segmenting SQL query.
	 * @param string $segmenting_dimension_name Name of the segmenting dimension.
	 * @param string $table_name Name of SQL table which is the stats table for orders.
	 * @param array  $query_params Array of SQL clauses for intervals/totals query.
	 * @param string $unique_orders_table Name of temporary SQL table that holds unique orders.
	 *
	 * @return array
	 */
	protected function get_product_related_segments( $type, $segmenting_selections, $segmenting_from, $segmenting_where, $segmenting_groupby, $segmenting_dimension_name, $table_name, $query_params, $unique_orders_table ) {
		if ( 'totals' === $type ) {
			return $this->get_product_related_totals_segments( $segmenting_selections, $segmenting_from, $segmenting_where, $segmenting_groupby, $segmenting_dimension_name, $table_name, $query_params, $unique_orders_table );
		} elseif ( 'intervals' === $type ) {
			return $this->get_product_related_intervals_segments( $segmenting_selections, $segmenting_from, $segmenting_where, $segmenting_groupby, $segmenting_dimension_name, $table_name, $query_params, $unique_orders_table );
		}
	}

	/**
	 * Calculate segments for segmenting property bound to order (e.g. coupon or customer type).
	 *
	 * @param string $type Type of segments to return--'totals' or 'intervals'.
	 * @param string $segmenting_select SELECT part of segmenting SQL query.
	 * @param string $segmenting_from FROM part of segmenting SQL query.
	 * @param string $segmenting_where WHERE part of segmenting SQL query.
	 * @param string $segmenting_groupby GROUP BY part of segmenting SQL query.
	 * @param string $table_name Name of SQL table which is the stats table for orders.
	 * @param array  $query_params Array of SQL clauses for intervals/totals query.
	 *
	 * @return array
	 */
	protected function get_order_related_segments( $type, $segmenting_select, $segmenting_from, $segmenting_where, $segmenting_groupby, $table_name, $query_params ) {
		if ( 'totals' === $type ) {
			return $this->get_order_related_totals_segments( $segmenting_select, $segmenting_from, $segmenting_where, $segmenting_groupby, $table_name, $query_params );
		} elseif ( 'intervals' === $type ) {
			return $this->get_order_related_intervals_segments( $segmenting_select, $segmenting_from, $segmenting_where, $segmenting_groupby, $table_name, $query_params );
		}
	}

	/**
	 * Assign segments to time intervals by updating original $intervals array.
	 *
	 * @param array $intervals Result array from intervals SQL query.
	 * @param array $intervals_segments Result array from interval segments SQL query.
	 */
	protected function assign_segments_to_intervals( &$intervals, $intervals_segments ) {
		$old_keys = array_keys( $intervals );
		foreach ( $intervals as $interval ) {
			$intervals[ $interval['time_interval'] ]             = $interval;
			$intervals[ $interval['time_interval'] ]['segments'] = array();
		}
		foreach ( $old_keys as $key ) {
			unset( $intervals[ $key ] );
		}

		foreach ( $intervals_segments as $time_interval => $segment ) {
			if ( ! isset( $intervals[ $time_interval ] ) ) {
				$intervals[ $time_interval ]['segments'] = array();
			}
			$intervals[ $time_interval ]['segments'] = $segment['segments'];
		}

		// To remove time interval keys (so that REST response is formatted correctly).
		$intervals = array_values( $intervals );
	}

	/**
	 * Returns an array of segments for totals part of REST response.
	 *
	 * @param array  $query_params Totals SQL query parameters.
	 * @param string $table_name Name of the SQL table that is the main order stats table.
	 *
	 * @return array
	 */
	public function get_totals_segments( $query_params, $table_name ) {
		$segments = $this->get_segments( 'totals', $query_params, $table_name );
		return $this->fill_in_missing_segments( $segments );
	}

	/**
	 * Adds an array of segments to data->intervals object.
	 *
	 * @param stdClass $data Data object representing the REST response.
	 * @param array    $intervals_query Intervals SQL query parameters.
	 * @param string   $table_name Name of the SQL table that is the main order stats table.
	 */
	public function add_intervals_segments( &$data, $intervals_query, $table_name ) {
		$intervals_segments = $this->get_segments( 'intervals', $intervals_query, $table_name );
		$this->assign_segments_to_intervals( $data->intervals, $intervals_segments );
		$this->fill_in_missing_interval_segments( $data );
	}
}
