<?php
/**
 * Class for time interval handling for reports
 *
 * @package  WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Date & time interval handling class for Reporting API.
 */
class WC_Admin_Reports_Interval {

	/**
	 * Format string for ISO DateTime formatter.
	 *
	 * @var string
	 */
	public static $iso_datetime_format = 'Y-m-d\TH:i:s\Z';

	/**
	 * Format string for use in SQL queries.
	 *
	 * @var string
	 */
	public static $sql_datetime_format = 'Y-m-d H:i:s';

	/**
	 * Returns date format to be used as grouping clause in SQL.
	 *
	 * @param string $time_interval Time interval.
	 * @return mixed
	 */
	public static function db_datetime_format( $time_interval ) {
		$first_day_of_week = absint( get_option( 'start_of_week' ) );

		if ( 1 === $first_day_of_week ) {
			// Week begins on Monday, ISO 8601.
			$week_format = "DATE_FORMAT(date_created, '%x-%v')";
		} else {
			// Week begins on day other than specified by ISO 8601, needs to be in sync with function simple_week_number.
			$week_format = "CONCAT(YEAR(date_created), '-', LPAD( FLOOR( ( DAYOFYEAR(date_created) + ( ( DATE_FORMAT(MAKEDATE(YEAR(date_created),1), '%w') - $first_day_of_week + 7 ) % 7 ) - 1 ) / 7  ) + 1 , 2, '0'))";

		}

		// Whenever this is changed, double check method time_interval_id to make sure they are in sync.
		$mysql_date_format_mapping = array(
			'hour'    => "DATE_FORMAT(date_created, '%Y-%m-%d %H')",
			'day'     => "DATE_FORMAT(date_created, '%Y-%m-%d')",
			'week'    => $week_format,
			'month'   => "DATE_FORMAT(date_created, '%Y-%m')",
			'quarter' => "CONCAT(YEAR(date_created), '-', QUARTER(date_created))",
			'year'    => 'YEAR(date_created)',

		);

		return $mysql_date_format_mapping[ $time_interval ];
	}

	/**
	 * Returns quarter for the DateTime.
	 *
	 * @param DateTime $datetime Date & time.
	 * @return int|null
	 */
	public static function quarter( $datetime ) {
		switch ( (int) $datetime->format( 'm' ) ) {
			case 1:
			case 2:
			case 3:
				return 1;
			case 4:
			case 5:
			case 6:
				return 2;
			case 7:
			case 8:
			case 9:
				return 3;
			case 10:
			case 11:
			case 12:
				return 4;

		}
		return null;
	}

	/**
	 * Returns simple week number for the DateTime, for week starting on $first_day_of_week.
	 *
	 * The first week of the year is considered to be the week containing January 1.
	 * The second week starts on the next $first_day_of_week.
	 *
	 * @param DateTime $datetime          Date for which the week number is to be calculated.
	 * @param int      $first_day_of_week 0 for Sunday to 6 for Saturday.
	 * @return int
	 */
	public static function simple_week_number( $datetime, $first_day_of_week ) {
		$beg_of_year_day          = new DateTime( "{$datetime->format('Y')}-01-01" );
		$adj_day_beg_of_year      = ( (int) $beg_of_year_day->format( 'w' ) - $first_day_of_week + 7 ) % 7;
		$days_since_start_of_year = (int) $datetime->format( 'z' ) + 1;

		return (int) floor( ( ( $days_since_start_of_year + $adj_day_beg_of_year - 1 ) / 7 ) ) + 1;
	}

	/**
	 * Returns ISO 8601 week number for the DateTime, if week starts on Monday,
	 * otherwise returns simple week number.
	 *
	 * @see WC_Admin_Reports_Interval::simple_week_number()
	 *
	 * @param DateTime $datetime          Date for which the week number is to be calculated.
	 * @param int      $first_day_of_week 0 for Sunday to 6 for Saturday.
	 * @return int
	 */
	public static function week_number( $datetime, $first_day_of_week ) {
		if ( 1 === $first_day_of_week ) {
			$week_number = (int) $datetime->format( 'W' );
		} else {
			$week_number = self::simple_week_number( $datetime, $first_day_of_week );
		}
		return $week_number;
	}

	/**
	 * Returns time interval id for the DateTime.
	 *
	 * @param string   $time_interval Time interval type (week, day, etc).
	 * @param DateTime $datetime      Date & time.
	 * @return string
	 */
	public static function time_interval_id( $time_interval, $datetime ) {
		// Whenever this is changed, double check method db_datetime_format to make sure they are in sync.
		$php_time_format_for = array(
			'hour'    => 'Y-m-d H',
			'day'     => 'Y-m-d',
			'week'    => 'o-W',
			'month'   => 'Y-m',
			'quarter' => 'Y-' . self::quarter( $datetime ),
			'year'    => 'Y',
		);

		// If the week does not begin on Monday.
		$first_day_of_week = absint( get_option( 'start_of_week' ) );

		if ( 'week' === $time_interval && 1 !== $first_day_of_week ) {
			$week_no = self::simple_week_number( $datetime, $first_day_of_week );
			$week_no = str_pad( $week_no, 2, '0', STR_PAD_LEFT );
			$year_no = $datetime->format( 'Y' );
			return "$year_no-$week_no";
		}

		return $datetime->format( $php_time_format_for[ $time_interval ] );
	}

	/**
	 * Calculates number of time intervals between two dates, closed interval on both sides.
	 *
	 * @param string $start    Start date & time.
	 * @param string $end      End date & time.
	 * @param string $interval Time interval increment, e.g. hour, day, week.
	 * @return int
	 */
	public static function intervals_between( $start, $end, $interval ) {
		$start_datetime = new DateTime( $start );
		$end_datetime   = new DateTime( $end );

		switch ( $interval ) {
			case 'hour':
				$diff_timestamp = (int) $end_datetime->format( 'U' ) - (int) $start_datetime->format( 'U' );
				return (int) floor( ( (int) $diff_timestamp ) / HOUR_IN_SECONDS ) + 1;
			case 'day':
				$diff_timestamp = (int) $end_datetime->format( 'U' ) - (int) $start_datetime->format( 'U' );
				return (int) floor( ( (int) $diff_timestamp ) / DAY_IN_SECONDS ) + 1;
			case 'week':
				// TODO: optimize? approximately day count / 7, but year end is tricky, a week can have fewer days.
				$week_count = 0;
				do {
					$start_datetime = self::next_week_start( $start_datetime );
					$week_count++;
				} while ( $start_datetime <= $end_datetime );
				return $week_count;
			case 'month':
				// Year diff in months: (end_year - start_year - 1) * 12.
				$year_diff_in_months = ( (int) $end_datetime->format( 'Y' ) - (int) $start_datetime->format( 'Y' ) - 1 ) * 12;
				// All the months in end_date year plus months from X to 12 in the start_date year.
				$month_diff = (int) $end_datetime->format( 'n' ) + ( 12 - (int) $start_datetime->format( 'n' ) );
				// Add months for number of years between end_date and start_date.
				$month_diff += $year_diff_in_months + 1;
				return $month_diff;
			case 'quarter':
				// Year diff in quarters: (end_year - start_year - 1) * 4.
				$year_diff_in_quarters = ( (int) $end_datetime->format( 'Y' ) - (int) $start_datetime->format( 'Y' ) - 1 ) * 4;
				// All the quarters in end_date year plus quarters from X to 4 in the start_date year.
				$quarter_diff = self::quarter( $end_datetime ) + ( 4 - self::quarter( $start_datetime ) );
				// Add quarters for number of years between end_date and start_date.
				$quarter_diff += $year_diff_in_quarters + 1;
				return $quarter_diff;
			case 'year':
				$year_diff = (int) $end_datetime->format( 'Y' ) - (int) $start_datetime->format( 'Y' );
				return $year_diff + 1;
		}
		return 0;
	}

	/**
	 * Returns a new DateTime object representing the next hour start/previous hour end if reversed.
	 *
	 * @param DateTime $datetime Date and time.
	 * @param bool     $reversed Going backwards in time instead of forward.
	 * @return DateTime
	 */
	public static function next_hour_start( $datetime, $reversed = false ) {
		$hour_increment         = $reversed ? 0 : 1;
		$timestamp              = (int) $datetime->format( 'U' );
		$hours_offset_timestamp = ( floor( $timestamp / HOUR_IN_SECONDS ) + $hour_increment ) * HOUR_IN_SECONDS;

		if ( $reversed ) {
			$hours_offset_timestamp --;
		}

		$hours_offset_time = new DateTime();
		$hours_offset_time->setTimestamp( $hours_offset_timestamp );
		return $hours_offset_time;
	}

	/**
	 * Returns a new DateTime object representing the next day start, or previous day end if reversed.
	 *
	 * @param DateTime $datetime Date and time.
	 * @param bool     $reversed Going backwards in time instead of forward.
	 * @return DateTime
	 */
	public static function next_day_start( $datetime, $reversed = false ) {
		$day_increment      = $reversed ? -1 : 1;
		$timestamp          = (int) $datetime->format( 'U' );
		$next_day_timestamp = ( floor( $timestamp / DAY_IN_SECONDS ) + $day_increment ) * DAY_IN_SECONDS;
		$next_day           = new DateTime();
		$next_day->setTimestamp( $next_day_timestamp );

		// The day boundary is actually next midnight when going in reverse, so set it to day -1 at 23:59:59.
		if ( $reversed ) {
			$timestamp            = (int) $next_day->format( 'U' );
			$end_of_day_timestamp = floor( $timestamp / DAY_IN_SECONDS ) * DAY_IN_SECONDS + DAY_IN_SECONDS - 1;
			$next_day->setTimestamp( $end_of_day_timestamp );
		}

		return $next_day;
	}

	/**
	 * Returns DateTime object representing the next week start, or previous week end if reversed.
	 *
	 * @param DateTime $datetime Date and time.
	 * @param bool     $reversed Going backwards in time instead of forward.
	 * @return DateTime
	 */
	public static function next_week_start( $datetime, $reversed = false ) {
		$first_day_of_week = absint( get_option( 'start_of_week' ) );
		$initial_week_no   = self::week_number( $datetime, $first_day_of_week );

		do {
			$datetime        = self::next_day_start( $datetime, $reversed );
			$current_week_no = self::week_number( $datetime, $first_day_of_week );
		} while ( $current_week_no === $initial_week_no );

		// The week boundary is actually next midnight when going in reverse, so set it to day -1 at 23:59:59.
		if ( $reversed ) {
			$timestamp            = (int) $datetime->format( 'U' );
			$end_of_day_timestamp = floor( $timestamp / DAY_IN_SECONDS ) * DAY_IN_SECONDS + DAY_IN_SECONDS - 1;
			$datetime->setTimestamp( $end_of_day_timestamp );
		}

		return $datetime;
	}

	/**
	 * Returns a new DateTime object representing the next month start, or previous month end if reversed.
	 *
	 * @param DateTime $datetime Date and time.
	 * @param bool     $reversed Going backwards in time instead of forward.
	 * @return DateTime
	 */
	public static function next_month_start( $datetime, $reversed = false ) {
		$month_increment = 1;
		$year            = $datetime->format( 'Y' );
		$month           = (int) $datetime->format( 'm' );

		if ( $reversed ) {
			$beg_of_month_datetime       = new DateTime( "$year-$month-01 00:00:00" );
			$timestamp                   = (int) $beg_of_month_datetime->format( 'U' );
			$end_of_prev_month_timestamp = $timestamp - 1;
			$datetime->setTimestamp( $end_of_prev_month_timestamp );
		} else {
			$month += $month_increment;
			if ( $month > 12 ) {
				$month = 1;
				$year ++;
			}
			$day      = '01';
			$datetime = new DateTime( "$year-$month-$day 00:00:00" );
		}

		return $datetime;
	}

	/**
	 * Returns a new DateTime object representing the next quarter start, or previous quarter end if reversed.
	 *
	 * @param DateTime $datetime Date and time.
	 * @param bool     $reversed Going backwards in time instead of forward.
	 * @return DateTime
	 */
	public static function next_quarter_start( $datetime, $reversed = false ) {
		$year  = $datetime->format( 'Y' );
		$month = (int) $datetime->format( 'n' );

		switch ( $month ) {
			case 1:
			case 2:
			case 3:
				if ( $reversed ) {
					$month = 1;
				} else {
					$month = 4;
				}
				break;
			case 4:
			case 5:
			case 6:
				if ( $reversed ) {
					$month = 4;
				} else {
					$month = 7;
				}
				break;
			case 7:
			case 8:
			case 9:
				if ( $reversed ) {
					$month = 7;
				} else {
					$month = 10;
				}
				break;
			case 10:
			case 11:
			case 12:
				if ( $reversed ) {
					$month = 10;
				} else {
					$month = 1;
					$year ++;
				}
				break;
		}
		$datetime = new DateTime( "$year-$month-01 00:00:00" );
		if ( $reversed ) {
			$timestamp                   = (int) $datetime->format( 'U' );
			$end_of_prev_month_timestamp = $timestamp - 1;
			$datetime->setTimestamp( $end_of_prev_month_timestamp );
		}

		return $datetime;
	}

	/**
	 * Return a new DateTime object representing the next year start, or previous year end if reversed.
	 *
	 * @param DateTime $datetime Date and time.
	 * @param bool     $reversed Going backwards in time instead of forward.
	 * @return DateTime
	 */
	public static function next_year_start( $datetime, $reversed = false ) {
		$year_increment = 1;
		$year           = (int) $datetime->format( 'Y' );
		$month          = '01';
		$day            = '01';

		if ( $reversed ) {
			$datetime                   = new DateTime( "$year-$month-$day 00:00:00" );
			$timestamp                  = (int) $datetime->format( 'U' );
			$end_of_prev_year_timestamp = $timestamp - 1;
			$datetime->setTimestamp( $end_of_prev_year_timestamp );
		} else {
			$year    += $year_increment;
			$datetime = new DateTime( "$year-$month-$day 00:00:00" );
		}

		return $datetime;
	}

	/**
	 * Returns beginning of next time interval for provided DateTime.
	 *
	 * E.g. for current DateTime, beginning of next day, week, quarter, etc.
	 *
	 * @param DateTime $datetime      Date and time.
	 * @param string   $time_interval Time interval, e.g. week, day, hour.
	 * @param bool     $reversed Going backwards in time instead of forward.
	 * @return DateTime
	 */
	public static function iterate( $datetime, $time_interval, $reversed = false ) {
		// $result_datetime           =
		// $result_timestamp_adjusted = $result_datetime->format( 'U' ) - 1;
		// $result_datetime->setTimestamp( $result_timestamp_adjusted );
		return call_user_func( array( __CLASS__, "next_{$time_interval}_start" ), $datetime, $reversed );
	}

	/**
	 * Returns expected number of items on the page in case of date ordering.
	 *
	 * @param int $expected_interval_count Expected number of intervals in total.
	 * @param int $items_per_page          Number of items per page.
	 * @param int $page_no                 Page number.
	 *
	 * @return float|int
	 */
	public static function expected_intervals_on_page( $expected_interval_count, $items_per_page, $page_no ) {
		$total_pages = (int) ceil( $expected_interval_count / $items_per_page );
		if ( $page_no < $total_pages ) {
			return $items_per_page;
		} elseif ( $page_no === $total_pages ) {
			return $expected_interval_count - ( $page_no - 1 ) * $items_per_page;
		} else {
			return 0;
		}
	}

	/**
	 * Returns true if there are any intervals that need to be filled in the response.
	 *
	 * @param int    $expected_interval_count Expected number of intervals in total.
	 * @param int    $db_records              Total number of records for given period in the database.
	 * @param int    $items_per_page          Number of items per page.
	 * @param int    $page_no                 Page number.
	 * @param string $order                   asc or desc.
	 * @param string $order_by                Column by which the result will be sorted.
	 * @param int    $intervals_count         Number of records for given (possibly shortened) time interval.
	 *
	 * @return bool
	 */
	public static function intervals_missing( $expected_interval_count, $db_records, $items_per_page, $page_no, $order, $order_by, $intervals_count ) {
		if ( $expected_interval_count > $db_records ) {
			if ( 'date' === $order_by ) {
				$expected_intervals_on_page = self::expected_intervals_on_page( $expected_interval_count, $items_per_page, $page_no );
				if ( $intervals_count < $expected_intervals_on_page ) {
					return true;
				} else {
					return false;
				}
			} else {
				if ( 'desc' === $order ) {
					if ( $page_no > floor( $db_records / $items_per_page ) ) {
						return true;
					} else {
						return false;
					}
				} elseif ( 'asc' === $order ) {
					if ( $page_no <= ceil( ( $expected_interval_count - $db_records ) / $items_per_page ) ) {
						return true;
					} else {
						return false;
					}
				} else {
					// Invalid ordering.
					return false;
				}
			}
		} else {
			return false;
		}
	}

}
