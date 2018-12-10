<?php
/**
 * WC_Admin_Reports_Data_Store class file.
 *
 * @package WooCommerce Admin/Classes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Reports_Data_Store: Common parent for custom report data stores.
 */
class WC_Admin_Reports_Data_Store {

	/**
	 * Cache group for the reports.
	 *
	 * @var string
	 */
	protected $cache_group = 'reports';

	/**
	 * Time out for the cache.
	 *
	 * @var int
	 */
	protected $cache_timeout = 3600;

	/**
	 * Table used as a data store for this report.
	 *
	 * @var string
	 */
	const TABLE_NAME = '';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array();

	/**
	 * SQL columns to select in the db query.
	 *
	 * @var array
	 */
	protected $report_columns = array();

	// TODO: this does not really belong here, maybe factor out the comparison as separate class?
	/**
	 * Order by property, used in the cmp function.
	 *
	 * @var string
	 */
	private $order_by = '';
	/**
	 * Order property, used in the cmp function.
	 *
	 * @var string
	 */
	private $order = '';

	/**
	 * Compares two report data objects by pre-defined object property and ASC/DESC ordering.
	 *
	 * @param stdClass $a Object a.
	 * @param stdClass $b Object b.
	 * @return string
	 */
	private function interval_cmp( $a, $b ) {
		if ( '' === $this->order_by || '' === $this->order ) {
			return 0;
			// TODO: should return WP_Error here perhaps?
		}
		if ( $a[ $this->order_by ] === $b[ $this->order_by ] ) {
			return 0;
		} elseif ( $a[ $this->order_by ] > $b[ $this->order_by ] ) {
			return strtolower( $this->order ) === 'desc' ? -1 : 1;
		} elseif ( $a[ $this->order_by ] < $b[ $this->order_by ] ) {
			return strtolower( $this->order ) === 'desc' ? 1 : -1;
		}
	}

	/**
	 * Sorts intervals according to user's request.
	 *
	 * They are pre-sorted in SQL, but after adding gaps, they need to be sorted including the added ones.
	 *
	 * @param stdClass $data      Data object, must contain an array under $data->intervals.
	 * @param string   $sort_by   Ordering property.
	 * @param string   $direction DESC/ASC.
	 */
	protected function sort_intervals( &$data, $sort_by, $direction ) {
		$this->order_by = $this->normalize_order_by( $sort_by );
		$this->order    = $direction;
		usort( $data->intervals, array( $this, 'interval_cmp' ) );
	}

	/**
	 * Fills in interval gaps from DB with 0-filled objects.
	 *
	 * @param array    $db_intervals   Array of all intervals present in the db.
	 * @param DateTime $datetime_start Start date.
	 * @param DateTime $datetime_end   End date.
	 * @param string   $time_interval  Time interval, e.g. day, week, month.
	 * @param stdClass $data           Data with SQL extracted intervals.
	 * @return stdClass
	 */
	protected function fill_in_missing_intervals( $db_intervals, $datetime_start, $datetime_end, $time_interval, &$data ) {
		// TODO: this is ugly and messy.
		// At this point, we don't know when we can stop iterating, as the ordering can be based on any value.
		$end_datetime = new DateTime( $datetime_end );
		$time_ids     = array_flip( wp_list_pluck( $data->intervals, 'time_interval' ) );
		$db_intervals = array_flip( $db_intervals );
		$datetime     = new DateTime( $datetime_start );
		// Totals object used to get all needed properties.
		$totals_arr = get_object_vars( $data->totals );
		foreach ( $totals_arr as $key => $val ) {
			$totals_arr[ $key ] = 0;
		}
		// TODO: should 'products' be in intervals?
		unset( $totals_arr['products'] );
		while ( $datetime <= $end_datetime ) {
			$next_start = WC_Admin_Reports_Interval::iterate( $datetime, $time_interval );
			$time_id    = WC_Admin_Reports_Interval::time_interval_id( $time_interval, $datetime );
			// Either create fill-zero interval or use data from db.
			if ( $next_start > $end_datetime ) {
				$interval_end = $end_datetime->format( 'Y-m-d H:i:s' );
			} else {
				$prev_end_timestamp = (int) $next_start->format( 'U' ) - 1;
				$prev_end           = new DateTime();
				$prev_end->setTimestamp( $prev_end_timestamp );
				$interval_end = $prev_end->format( 'Y-m-d H:i:s' );
			}
			if ( array_key_exists( $time_id, $time_ids ) ) {
				// For interval present in the db for this time frame, just fill in dates.
				$record               = &$data->intervals[ $time_ids[ $time_id ] ];
				$record['date_start'] = $datetime->format( 'Y-m-d H:i:s' );
				$record['date_end']   = $interval_end;
			} elseif ( ! array_key_exists( $time_id, $db_intervals ) ) {
				// For intervals present in the db outside of this time frame, do nothing.
				// For intervals not present in the db, fabricate it.
				$record_arr                  = array();
				$record_arr['time_interval'] = $time_id;
				$record_arr['date_start']    = $datetime->format( 'Y-m-d H:i:s' );
				$record_arr['date_end']      = $interval_end;
				$data->intervals[]           = array_merge( $record_arr, $totals_arr );
			}
			$datetime = $next_start;
		}
		return $data;
	}

	/**
	 * Removes extra records from intervals so that only requested number of records get returned.
	 *
	 * @param stdClass $data           Data from whose intervals the records get removed.
	 * @param int      $page_no        Offset requested by the user.
	 * @param int      $items_per_page Number of records requested by the user.
	 * @param int      $db_interval_count Database interval count.
	 * @param int      $expected_interval_count Expected interval count on the output.
	 * @param string   $order_by Order by field.
	 */
	protected function remove_extra_records( &$data, $page_no, $items_per_page, $db_interval_count, $expected_interval_count, $order_by ) {
		if ( 'date' === strtolower( $order_by ) ) {
			$offset = 0;
		} else {
			$offset = ( $page_no - 1 ) * $items_per_page - $db_interval_count;
			$offset = $offset < 0 ? 0 : $offset;
		}
		$count = $expected_interval_count - ( $page_no - 1 ) * $items_per_page;
		if ( $count < 0 ) {
			$count = 0;
		} elseif ( $count > $items_per_page ) {
			$count = $items_per_page;
		}
		$data->intervals = array_slice( $data->intervals, $offset, $count );
	}

	/**
	 * Updates the LIMIT query part for Intervals query of the report.
	 *
	 * If there are less records in the database than time intervals, then we need to remap offset in SQL query
	 * to fetch correct records.
	 *
	 * @param array $intervals_query Array with clauses for the Intervals SQL query.
	 * @param array $query_args Query arguements.
	 * @param int   $db_interval_count Database interval count.
	 * @param int   $expected_interval_count Expected interval count on the output.
	 */
	protected function update_intervals_sql_params( &$intervals_query, &$query_args, $db_interval_count, $expected_interval_count ) {
		if ( $db_interval_count === $expected_interval_count ) {
			return;
		}
		if ( 'date' === strtolower( $query_args['orderby'] ) ) {
			// page X in request translates to slightly different dates in the db, in case some
			// records are missing from the db.
			$start_iteration = 0;
			$end_iteration   = 0;
			if ( 'asc' === strtolower( $query_args['order'] ) ) {
				// ORDER BY date ASC.
				$new_start_date    = new DateTime( $query_args['after'] );
				$intervals_to_skip = ( $query_args['page'] - 1 ) * $intervals_query['per_page'];
				$latest_end_date   = new DateTime( $query_args['before'] );
				for ( $i = 0; $i < $intervals_to_skip; $i++ ) {
					if ( $new_start_date > $latest_end_date ) {
						$new_start_date  = $latest_end_date;
						$start_iteration = 0;
						break;
					}
					$new_start_date = WC_Admin_Reports_Interval::iterate( $new_start_date, $query_args['interval'] );
					$start_iteration ++;
				}

				$new_end_date = clone $new_start_date;
				for ( $i = 0; $i < $intervals_query['per_page']; $i++ ) {
					if ( $new_end_date > $latest_end_date ) {
						$new_end_date  = $latest_end_date;
						$end_iteration = 0;
						break;
					}
					$new_end_date = WC_Admin_Reports_Interval::iterate( $new_end_date, $query_args['interval'] );
					$end_iteration ++;
				}
				if ( $end_iteration ) {
					$new_end_date_timestamp = (int) $new_end_date->format( 'U' ) - 1;
					$new_end_date->setTimestamp( $new_end_date_timestamp );
				}
			} else {
				// ORDER BY date DESC.
				$new_end_date        = new DateTime( $query_args['before'] );
				$intervals_to_skip   = ( $query_args['page'] - 1 ) * $intervals_query['per_page'];
				$earliest_start_date = new DateTime( $query_args['after'] );
				for ( $i = 0; $i < $intervals_to_skip; $i++ ) {
					if ( $new_end_date < $earliest_start_date ) {
						$new_end_date  = $earliest_start_date;
						$end_iteration = 0;
						break;
					}
					$new_end_date = WC_Admin_Reports_Interval::iterate( $new_end_date, $query_args['interval'], true );
					$end_iteration ++;
				}

				$new_start_date = clone $new_end_date;
				for ( $i = 0; $i < $intervals_query['per_page']; $i++ ) {
					if ( $new_start_date < $earliest_start_date ) {
						$new_start_date  = $earliest_start_date;
						$start_iteration = 0;
						break;
					}
					$new_start_date = WC_Admin_Reports_Interval::iterate( $new_start_date, $query_args['interval'], true );
					$start_iteration ++;
				}
				if ( $start_iteration ) {
					// TODO: is this correct? should it only be added if iterate runs? other two iterate instances, too?
					$new_start_date_timestamp = (int) $new_start_date->format( 'U' ) + 1;
					$new_start_date->setTimestamp( $new_start_date_timestamp );
				}
			}
			$query_args['adj_after']               = $new_start_date->format( WC_Admin_Reports_Interval::$iso_datetime_format );
			$query_args['adj_before']              = $new_end_date->format( WC_Admin_Reports_Interval::$iso_datetime_format );
			$intervals_query['where_time_clause']  = '';
			$intervals_query['where_time_clause'] .= " AND date_created <= '{$query_args['adj_before']}'";
			$intervals_query['where_time_clause'] .= " AND date_created >= '{$query_args['adj_after']}'";
			$intervals_query['limit']              = 'LIMIT 0,' . $intervals_query['per_page'];
		} else {
			if ( 'asc' === $query_args['order'] ) {
				$offset = ( ( $query_args['page'] - 1 ) * $intervals_query['per_page'] ) - ( $expected_interval_count - $db_interval_count );
				$offset = $offset < 0 ? 0 : $offset;
				$count  = $query_args['page'] * $intervals_query['per_page'] - ( $expected_interval_count - $db_interval_count );
				if ( $count < 0 ) {
					$count = 0;
				} elseif ( $count > $intervals_query['per_page'] ) {
					$count = $intervals_query['per_page'];
				}
				$intervals_query['limit'] = 'LIMIT ' . $offset . ',' . $count;
			}
			// Otherwise no change in limit clause.
			$query_args['adj_after']  = $query_args['after'];
			$query_args['adj_before'] = $query_args['before'];
		}
	}

	/**
	 * Casts strings returned from the database to appropriate data types for output.
	 *
	 * @param array $array Associative array of values extracted from the database.
	 * @return array|WP_Error
	 */
	protected function cast_numbers( $array ) {
		$retyped_array = array();
		$column_types  = apply_filters( 'woocommerce_rest_reports_column_types', $this->column_types, $array );
		foreach ( $array as $column_name => $value ) {
			if ( is_array( $value ) ) {
				$value = $this->cast_numbers( $value );
			}

			if ( isset( $column_types[ $column_name ] ) ) {
				$retyped_array[ $column_name ] = $column_types[ $column_name ]( $value );
			} else {
				$retyped_array[ $column_name ] = $value;
			}
		}
		return $retyped_array;
	}

	/**
	 * Returns a list of columns selected by the query_args formatted as a comma separated string.
	 *
	 * @param array $query_args User-supplied options.
	 * @return string
	 */
	protected function selected_columns( $query_args ) {
		$selections = $this->report_columns;

		if ( isset( $query_args['fields'] ) && is_array( $query_args['fields'] ) ) {
			$keep = array();
			foreach ( $query_args['fields'] as $field ) {
				if ( isset( $selections[ $field ] ) ) {
					$keep[ $field ] = $selections[ $field ];
				}
			}
			$selections = implode( ', ', $keep );
		} else {
			$selections = implode( ', ', $selections );
		}
		return $selections;
	}

	/**
	 * Get the order statuses used when calculating reports.
	 *
	 * @return array
	 */
	protected static function get_report_order_statuses() {
		return apply_filters( 'woocommerce_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) );
	}

	/**
	 * Maps order status provided by the user to the one used in the database.
	 *
	 * @param string $status Order status.
	 * @return string
	 */
	protected function normalize_order_status( $status ) {
		$status = trim( $status );
		return 'wc-' . $status;
	}

	/**
	 * Normalizes order_by clause to match to SQL query.
	 *
	 * @param string $order_by Order by option requeste by user.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		if ( 'date' === $order_by ) {
			return 'time_interval';
		}

		return $order_by;
	}

	/**
	 * Updates start and end dates for intervals so that they represent intervals' borders, not times when data in db were recorded.
	 *
	 * E.g. if there are db records for only Tuesday and Thursday this week, the actual week interval is [Mon, Sun], not [Tue, Thu].
	 *
	 * @param DateTime $datetime_start Start date.
	 * @param DateTime $datetime_end End date.
	 * @param string   $time_interval Time interval, e.g. day, week, month.
	 * @param array    $intervals Array of intervals extracted from SQL db.
	 */
	protected function update_interval_boundary_dates( $datetime_start, $datetime_end, $time_interval, &$intervals ) {
		foreach ( $intervals as $key => $interval ) {
			$datetime = new DateTime( $interval['datetime_anchor'] );

			$prev_start = WC_Admin_Reports_Interval::iterate( $datetime, $time_interval, true );
			// TODO: not sure if the +1/-1 here are correct, especially as they are applied before the ?: below.
			$prev_start_timestamp = (int) $prev_start->format( 'U' ) + 1;
			$prev_start->setTimestamp( $prev_start_timestamp );
			if ( $datetime_start ) {
				$start_datetime                  = new DateTime( $datetime_start );
				$date_start                      = $prev_start < $start_datetime ? $start_datetime : $prev_start;
				$intervals[ $key ]['date_start'] = $date_start->format( 'Y-m-d H:i:s' );
			} else {
				$intervals[ $key ]['date_start'] = $prev_start->format( 'Y-m-d H:i:s' );
			}

			$next_end           = WC_Admin_Reports_Interval::iterate( $datetime, $time_interval );
			$next_end_timestamp = (int) $next_end->format( 'U' ) - 1;
			$next_end->setTimestamp( $next_end_timestamp );
			if ( $datetime_end ) {
				$end_datetime                  = new DateTime( $datetime_end );
				$date_end                      = $next_end > $end_datetime ? $end_datetime : $next_end;
				$intervals[ $key ]['date_end'] = $date_end->format( 'Y-m-d H:i:s' );
			} else {
				$intervals[ $key ]['date_end'] = $next_end->format( 'Y-m-d H:i:s' );
			}

			$intervals[ $key ]['interval'] = $time_interval;
		}
	}

	/**
	 * Change structure of intervals to form a correct response.
	 *
	 * @param array $intervals Time interval, e.g. day, week, month.
	 */
	protected function create_interval_subtotals( &$intervals ) {
		foreach ( $intervals as $key => $interval ) {
			// Move intervals result to subtotals object.
			$intervals[ $key ] = array(
				'interval'       => $interval['time_interval'],
				'date_start'     => $interval['date_start'],
				'date_start_gmt' => $interval['date_start'],
				'date_end'       => $interval['date_end'],
				'date_end_gmt'   => $interval['date_end'],
			);

			unset( $interval['interval'] );
			unset( $interval['date_start'] );
			unset( $interval['date_end'] );
			unset( $interval['datetime_anchor'] );
			unset( $interval['time_interval'] );
			$intervals[ $key ]['subtotals'] = (object) $this->cast_numbers( $interval );
		}
	}

	/**
	 * Fills WHERE clause of SQL request with date-related constraints.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $table_name Name of the db table relevant for the date constraint.
	 * @return array
	 */
	protected function get_time_period_sql_params( $query_args, $table_name ) {
		$sql_query = array(
			'from_clause'       => '',
			'where_time_clause' => '',
			'where_clause'      => '',
		);

		if ( isset( $query_args['before'] ) && '' !== $query_args['before'] ) {
			$datetime                        = new DateTime( $query_args['before'] );
			$datetime_str                    = $datetime->format( WC_Admin_Reports_Interval::$sql_datetime_format );
			$sql_query['where_time_clause'] .= " AND {$table_name}.date_created <= '$datetime_str'";

		}

		if ( isset( $query_args['after'] ) && '' !== $query_args['after'] ) {
			$datetime                        = new DateTime( $query_args['after'] );
			$datetime_str                    = $datetime->format( WC_Admin_Reports_Interval::$sql_datetime_format );
			$sql_query['where_time_clause'] .= " AND {$table_name}.date_created >= '$datetime_str'";
		}

		return $sql_query;
	}

	/**
	 * Fills LIMIT clause of SQL request based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return array
	 */
	protected function get_limit_sql_params( $query_args ) {
		$sql_query['per_page'] = get_option( 'posts_per_page' );
		if ( isset( $query_args['per_page'] ) && is_numeric( $query_args['per_page'] ) ) {
			$sql_query['per_page'] = (int) $query_args['per_page'];
		}

		$sql_query['offset'] = 0;
		if ( isset( $query_args['page'] ) ) {
			$sql_query['offset'] = ( (int) $query_args['page'] - 1 ) * $sql_query['per_page'];
		}

		$sql_query['limit'] = "LIMIT {$sql_query['offset']}, {$sql_query['per_page']}";
		return $sql_query;
	}

	/**
	 * Fills ORDER BY clause of SQL request based on user supplied parameters.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return array
	 */
	protected function get_order_by_sql_params( $query_args ) {
		$sql_query['order_by_clause'] = '';
		if ( isset( $query_args['orderby'] ) ) {
			$sql_query['order_by_clause'] = $this->normalize_order_by( $query_args['orderby'] );
		}

		if ( isset( $query_args['order'] ) ) {
			$sql_query['order_by_clause'] .= ' ' . $query_args['order'];
		} else {
			$sql_query['order_by_clause'] .= ' DESC';
		}

		return $sql_query;
	}

	/**
	 * Fills FROM and WHERE clauses of SQL request for 'Intervals' section of data response based on user supplied parameters.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $table_name Name of the db table relevant for the date constraint.
	 * @return array
	 */
	protected function get_intervals_sql_params( $query_args, $table_name ) {
		$intervals_query = array(
			'from_clause'       => '',
			'where_time_clause' => '',
			'where_clause'      => '',
		);

		$intervals_query = array_merge( $intervals_query, $this->get_time_period_sql_params( $query_args, $table_name ) );

		if ( isset( $query_args['interval'] ) && '' !== $query_args['interval'] ) {
			$interval                         = $query_args['interval'];
			$intervals_query['select_clause'] = WC_Admin_Reports_Interval::db_datetime_format( $interval );
		}

		$intervals_query = array_merge( $intervals_query, $this->get_limit_sql_params( $query_args ) );

		$intervals_query = array_merge( $intervals_query, $this->get_order_by_sql_params( $query_args ) );

		return $intervals_query;
	}

	/**
	 * Returns an array of products belonging to given categories.
	 *
	 * @param array $categories List of categories IDs.
	 * @return array|stdClass
	 */
	protected function get_products_by_cat_ids( $categories ) {
		$product_categories = get_categories(
			array(
				'hide_empty' => 0,
				'taxonomy'   => 'product_cat',
			)
		);
		$cat_slugs          = array();
		$categories         = array_flip( $categories );
		foreach ( $product_categories as $product_cat ) {
			if ( key_exists( $product_cat->cat_ID, $categories ) ) {
				$cat_slugs[] = $product_cat->slug;
			}
		}
		$args = array(
			'category' => $cat_slugs,
			'limit'    => -1,
		);
		return wc_get_products( $args );
	}

	/**
	 * Returns comma separated ids of allowed products, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_included_products( $query_args ) {
		$included_products = array();
		$operator          = $this->get_match_operator( $query_args );

		if ( isset( $query_args['categories'] ) && is_array( $query_args['categories'] ) && count( $query_args['categories'] ) > 0 ) {
			$included_products = $this->get_products_by_cat_ids( $query_args['categories'] );
			$included_products = wc_list_pluck( $included_products, 'get_id' );
		}

		if ( isset( $query_args['product_includes'] ) && is_array( $query_args['product_includes'] ) && count( $query_args['product_includes'] ) > 0 ) {
			if ( count( $included_products ) > 0 ) {
				if ( 'AND' === $operator ) {
					$included_products = array_intersect( $included_products, $query_args['product_includes'] );
				} elseif ( 'OR' === $operator ) {
					// Union of products from selected categories and manually included products.
					$included_products = array_unique( array_merge( $included_products, $query_args['product_includes'] ) );
				}
			} else {
				$included_products = $query_args['product_includes'];
			}
		}

		$included_products_str = implode( ',', $included_products );
		return $included_products_str;
	}

	/**
	 * Returns comma separated ids of excluded products, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_excluded_products( $query_args ) {
		$excluded_products_str = '';

		if ( isset( $query_args['product_excludes'] ) && is_array( $query_args['product_excludes'] ) && count( $query_args['product_excludes'] ) > 0 ) {
			$excluded_products_str = implode( ',', $query_args['product_excludes'] );
		}
		return $excluded_products_str;
	}

	/**
	 * Returns comma separated ids of included coupons, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_included_coupons( $query_args ) {
		$included_coupons_str = '';

		if ( isset( $query_args['coupon_includes'] ) && is_array( $query_args['coupon_includes'] ) && count( $query_args['coupon_includes'] ) > 0 ) {
			$included_coupons_str = implode( ',', $query_args['coupon_includes'] );
		}
		return $included_coupons_str;
	}

	/**
	 * Returns comma separated ids of excluded coupons, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_excluded_coupons( $query_args ) {
		$excluded_coupons_str = '';

		if ( isset( $query_args['coupon_excludes'] ) && is_array( $query_args['coupon_excludes'] ) && count( $query_args['coupon_excludes'] ) > 0 ) {
			$excluded_coupons_str = implode( ',', $query_args['coupon_excludes'] );
		}
		return $excluded_coupons_str;
	}


	/**
	 * Returns order status subquery to be used in WHERE SQL query, based on query arguments from the user.
	 *
	 * @param array  $query_args Parameters supplied by the user.
	 * @param string $operator   AND or OR, based on match query argument.
	 * @return string
	 */
	protected function get_status_subquery( $query_args, $operator = 'AND' ) {
		global $wpdb;

		$subqueries = array();
		if ( isset( $query_args['status_is'] ) && is_array( $query_args['status_is'] ) && count( $query_args['status_is'] ) > 0 ) {
			$allowed_statuses = array_map( array( $this, 'normalize_order_status' ), $query_args['status_is'] );
			if ( $allowed_statuses ) {
				$subqueries[] = "{$wpdb->prefix}posts.post_status IN ( '" . implode( "','", $allowed_statuses ) . "' )";
			}
		}

		if ( isset( $query_args['status_is_not'] ) && is_array( $query_args['status_is_not'] ) && count( $query_args['status_is_not'] ) > 0 ) {
			$forbidden_statuses = array_map( array( $this, 'normalize_order_status' ), $query_args['status_is_not'] );
			if ( $forbidden_statuses ) {
				$subqueries[] = "{$wpdb->prefix}posts.post_status NOT IN ( '" . implode( "','", $forbidden_statuses ) . "' )";
			}
		}

		return implode( " $operator ", $subqueries );
	}

	/**
	 * Returns customer subquery to be used in WHERE SQL query, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_customer_subquery( $query_args ) {
		global $wpdb;

		$customer_filter = '';
		if ( isset( $query_args['customer'] ) ) {
			if ( 'new' === strtolower( $query_args['customer'] ) ) {
				$customer_filter = " {$wpdb->prefix}wc_order_stats.returning_customer = 0";
			} elseif ( 'returning' === strtolower( $query_args['customer'] ) ) {
				$customer_filter = " {$wpdb->prefix}wc_order_stats.returning_customer = 1";
			}
		}

		return $customer_filter;
	}

	/**
	 * Returns logic operator for WHERE subclause based on 'match' query argument.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_match_operator( $query_args ) {
		$operator = 'AND';

		if ( ! isset( $query_args['match'] ) ) {
			return $operator;
		}

		if ( 'all' === strtolower( $query_args['match'] ) ) {
			$operator = 'AND';
		} elseif ( 'any' === strtolower( $query_args['match'] ) ) {
			$operator = 'OR';
		}
		return $operator;
	}

}
