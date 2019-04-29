<?php
/**
 * Report table sync related functions and actions.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Admin_Reports_Sync Class.
 */
class WC_Admin_Reports_Sync {
	/**
	 * Action hook for reducing a range of batches down to single actions.
	 */
	const QUEUE_BATCH_ACTION = 'wc-admin_queue_batches';

	/**
	 * Action hook for queuing an action after another is complete.
	 */
	const QUEUE_DEPEDENT_ACTION = 'wc-admin_queue_dependent_action';

	/**
	 * Action hook for processing a batch of customers.
	 */
	const CUSTOMERS_BATCH_ACTION = 'wc-admin_process_customers_batch';

	/**
	 * Action hook for processing a batch of orders.
	 */
	const ORDERS_BATCH_ACTION = 'wc-admin_process_orders_batch';

	/**
	 * Action hook for initializing the orders lookup batch creation.
	 */
	const ORDERS_LOOKUP_BATCH_INIT = 'wc-admin_orders_lookup_batch_init';

	/**
	 * Action hook for processing a batch of orders.
	 */
	const SINGLE_ORDER_ACTION = 'wc-admin_process_order';

	/**
	 * Action scheduler group.
	 */
	const QUEUE_GROUP = 'wc-admin-data';

	/**
	 * Queue instance.
	 *
	 * @var WC_Queue_Interface
	 */
	protected static $queue = null;

	/**
	 * Get queue instance.
	 *
	 * @return WC_Queue_Interface
	 */
	public static function queue() {
		if ( is_null( self::$queue ) ) {
			self::$queue = WC()->queue();
		}

		return self::$queue;
	}

	/**
	 * Set queue instance.
	 *
	 * @param WC_Queue_Interface $queue Queue instance.
	 */
	public static function set_queue( $queue ) {
		self::$queue = $queue;
	}

	/**
	 * Hook in sync methods.
	 */
	public static function init() {
		// Initialize syncing hooks.
		add_action( 'wp_loaded', array( __CLASS__, 'orders_lookup_update_init' ) );

		// Initialize scheduled action handlers.
		add_action( self::QUEUE_BATCH_ACTION, array( __CLASS__, 'queue_batches' ), 10, 4 );
		add_action( self::QUEUE_DEPEDENT_ACTION, array( __CLASS__, 'queue_dependent_action' ), 10, 3 );
		add_action( self::CUSTOMERS_BATCH_ACTION, array( __CLASS__, 'customer_lookup_process_batch' ), 10, 3 );
		add_action( self::ORDERS_BATCH_ACTION, array( __CLASS__, 'orders_lookup_process_batch' ), 10, 3 );
		add_action( self::ORDERS_LOOKUP_BATCH_INIT, array( __CLASS__, 'orders_lookup_batch_init' ), 10, 2 );
		add_action( self::SINGLE_ORDER_ACTION, array( __CLASS__, 'orders_lookup_process_order' ) );
	}

	/**
	 * Regenerate data for reports.
	 *
	 * @param int|bool $days Number of days to process.
	 * @param bool     $skip_existing Skip exisiting records.
	 * @return string
	 */
	public static function regenerate_report_data( $days, $skip_existing ) {
		self::customer_lookup_batch_init( $days, $skip_existing );
		self::queue_dependent_action( self::ORDERS_LOOKUP_BATCH_INIT, array( $days, $skip_existing ), self::CUSTOMERS_BATCH_ACTION );

		return __( 'Report table data is being rebuilt.  Please allow some time for data to fully populate.', 'woocommerce-admin' );
	}

	/**
	 * Clears all queued actions.
	 */
	public static function clear_queued_actions() {
		$store = ActionScheduler::store();

		if ( is_a( $store, 'WC_Admin_ActionScheduler_WPPostStore' ) ) {
			// If we're using our data store, call our bespoke deletion method.
			$store->clear_pending_wcadmin_actions();
		} else {
			self::queue()->cancel_all( null, array(), self::QUEUE_GROUP );
		}
	}

	/**
	 * Schedule an action to process a single Order.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function schedule_single_order_process( $order_id ) {
		if ( 'shop_order' !== get_post_type( $order_id ) ) {
			return;
		}

		if ( apply_filters( 'woocommerce_disable_order_scheduling', false ) ) {
			self::orders_lookup_process_order( $order_id );
			return;
		}

		// This can get called multiple times for a single order, so we look
		// for existing pending jobs for the same order to avoid duplicating efforts.
		$existing_jobs = self::queue()->search(
			array(
				'status'   => 'pending',
				'per_page' => 1,
				'claimed'  => false,
				'search'   => "[{$order_id}]",
				'group'    => self::QUEUE_GROUP,
			)
		);

		if ( $existing_jobs ) {
			$existing_job = current( $existing_jobs );

			// Bail out if there's a pending single order action, or a pending dependent action.
			if (
				( self::SINGLE_ORDER_ACTION === $existing_job->get_hook() ) ||
				(
					self::QUEUE_DEPEDENT_ACTION === $existing_job->get_hook() &&
					in_array( self::SINGLE_ORDER_ACTION, $existing_job->get_args() )
				)
			) {
				return;
			}
		}

		// We want to ensure that customer lookup updates are scheduled before order updates.
		self::queue_dependent_action( self::SINGLE_ORDER_ACTION, array( $order_id ), self::CUSTOMERS_BATCH_ACTION );
	}

	/**
	 * Attach order lookup update hooks.
	 */
	public static function orders_lookup_update_init() {
		// Activate WC_Order extension.
		WC_Admin_Order::add_filters();

		add_action( 'save_post', array( __CLASS__, 'schedule_single_order_process' ) );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'schedule_single_order_process' ) );

		WC_Admin_Reports_Orders_Stats_Data_Store::init();
		WC_Admin_Reports_Customers_Data_Store::init();
		WC_Admin_Reports_Coupons_Data_Store::init();
		WC_Admin_Reports_Products_Data_Store::init();
		WC_Admin_Reports_Taxes_Data_Store::init();
	}

	/**
	 * Init order/product lookup tables update (in batches).
	 *
	 * @param integer|boolean $days Number of days to process.
	 * @param boolean         $skip_existing Skip exisiting records.
	 */
	public static function orders_lookup_batch_init( $days, $skip_existing ) {
		$batch_size = self::get_batch_size( self::ORDERS_BATCH_ACTION );
		$orders     = self::get_orders( 1, 1, $days, $skip_existing );

		if ( 0 === $orders->total ) {
			return;
		}

		$num_batches = ceil( $orders->total / $batch_size );

		self::queue_batches( 1, $num_batches, self::ORDERS_BATCH_ACTION, array( $days, $skip_existing ) );
	}

	/**
	 * Get the order IDs and total count that need to be synced.
	 *
	 * @param int      $limit Number of records to retrieve.
	 * @param int      $page  Page number.
	 * @param int|bool $days Number of days prior to current date to limit search results.
	 * @param bool     $skip_existing Skip already imported orders.
	 */
	public static function get_orders( $limit = 10, $page = 1, $days = false, $skip_existing = false ) {
		global $wpdb;
		$where_clause = '';
		$offset       = $page > 1 ? $page * $limit : 0;

		if ( $days ) {
			$days_ago      = date( 'Y-m-d 00:00:00', time() - ( DAY_IN_SECONDS * $days ) );
			$where_clause .= " AND post_date >= '{$days_ago}'";
		}

		if ( $skip_existing ) {
			$where_clause .= " AND NOT EXISTS (
				SELECT 1 FROM {$wpdb->prefix}wc_order_stats
				WHERE {$wpdb->prefix}wc_order_stats.order_id = {$wpdb->posts}.ID
			)";
		}

		$count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
			WHERE post_type = 'shop_order'
			{$where_clause}"
		); // WPCS: unprepared SQL ok.

		$order_ids = absint( $count ) > 0 ? $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts}
				WHERE post_type = 'shop_order'
				{$where_clause}
				ORDER BY post_date ASC
				LIMIT %d
				OFFSET %d",
				$limit,
				$offset
			)
		) : array(); // WPCS: unprepared SQL ok.

		return (object) array(
			'total'     => absint( $count ),
			'order_ids' => $order_ids,
		);
	}

	/**
	 * Process a batch of orders to update (stats and products).
	 *
	 * @param int      $batch_number Batch number to process (essentially a query page number).
	 * @param int|bool $days Number of days to process.
	 * @param bool     $skip_existing Skip exisiting records.
	 * @return void
	 */
	public static function orders_lookup_process_batch( $batch_number, $days, $skip_existing ) {
		$batch_size = self::get_batch_size( self::ORDERS_BATCH_ACTION );
		$orders     = self::get_orders( $batch_size, $batch_number, $days, $skip_existing );

		foreach ( $orders->order_ids as $order_id ) {
			self::orders_lookup_process_order( $order_id );
		}
	}

	/**
	 * Process a single order to update lookup tables for.
	 * If an error is encountered in one of the updates, a retry action is scheduled.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public static function orders_lookup_process_order( $order_id ) {
		$result = array_sum(
			array(
				WC_Admin_Reports_Orders_Stats_Data_Store::sync_order( $order_id ),
				WC_Admin_Reports_Products_Data_Store::sync_order_products( $order_id ),
				WC_Admin_Reports_Coupons_Data_Store::sync_order_coupons( $order_id ),
				WC_Admin_Reports_Taxes_Data_Store::sync_order_taxes( $order_id ),
			)
		);

		// If all updates were either skipped or successful, we're done.
		// The update methods return -1 for skip, or a boolean success indicator.
		if ( 4 === absint( $result ) ) {
			return;
		}

		// Otherwise assume an error occurred and reschedule.
		self::schedule_single_order_process( $order_id );
	}

	/**
	 * Returns the batch size for regenerating reports.
	 * Note: can differ per batch action.
	 *
	 * @param string $action Single batch action name.
	 * @return int Batch size.
	 */
	public static function get_batch_size( $action ) {
		$batch_sizes = array(
			self::QUEUE_BATCH_ACTION     => 100,
			self::CUSTOMERS_BATCH_ACTION => 25,
			self::ORDERS_BATCH_ACTION    => 10,
		);
		$batch_size  = isset( $batch_sizes[ $action ] ) ? $batch_sizes[ $action ] : 25;

		/**
		 * Filter the batch size for regenerating a report table.
		 *
		 * @param int    $batch_size Batch size.
		 * @param string $action Batch action name.
		 */
		return apply_filters( 'wc_admin_report_regenerate_batch_size', $batch_size, $action );
	}

	/**
	 * Queue a large number of batch jobs, respecting the batch size limit.
	 * Reduces a range of batches down to "single batch" jobs.
	 *
	 * @param int    $range_start Starting batch number.
	 * @param int    $range_end Ending batch number.
	 * @param string $single_batch_action Action to schedule for a single batch.
	 * @param array  $action_args Action arguments.
	 * @return void
	 */
	public static function queue_batches( $range_start, $range_end, $single_batch_action, $action_args = array() ) {
		$batch_size       = self::get_batch_size( self::QUEUE_BATCH_ACTION );
		$range_size       = 1 + ( $range_end - $range_start );
		$action_timestamp = time() + 5;

		if ( $range_size > $batch_size ) {
			// If the current batch range is larger than a single batch,
			// split the range into $queue_batch_size chunks.
			$chunk_size = ceil( $range_size / $batch_size );

			for ( $i = 0; $i < $batch_size; $i++ ) {
				$batch_start = $range_start + ( $i * $chunk_size );
				$batch_end   = min( $range_end, $range_start + ( $chunk_size * ( $i + 1 ) ) - 1 );

				self::queue()->schedule_single(
					$action_timestamp,
					self::QUEUE_BATCH_ACTION,
					array( $batch_start, $batch_end, $single_batch_action, $action_args ),
					self::QUEUE_GROUP
				);
			}
		} else {
			// Otherwise, queue the single batches.
			for ( $i = $range_start; $i <= $range_end; $i++ ) {
				$batch_action_args = array_merge( array( $i ), $action_args );
				self::queue()->schedule_single( $action_timestamp, $single_batch_action, $batch_action_args, self::QUEUE_GROUP );
			}
		}
	}

	/**
	 * Queue an action to run after another.
	 *
	 * @param string $action Action to run after prerequisite.
	 * @param array  $action_args Action arguments.
	 * @param string $prerequisite_action Prerequisite action.
	 */
	public static function queue_dependent_action( $action, $action_args, $prerequisite_action ) {
		$blocking_jobs = self::queue()->search(
			array(
				'status'   => 'pending',
				'orderby'  => 'date',
				'order'    => 'DESC',
				'per_page' => 1,
				'claimed'  => false,
				'search'   => $prerequisite_action, // search is used instead of hook to find queued batch creation.
				'group'    => self::QUEUE_GROUP,
			)
		);

		$next_job_schedule = null;
		$blocking_job_hook = null;

		if ( $blocking_jobs ) {
			$blocking_job      = current( $blocking_jobs );
			$blocking_job_hook = $blocking_job->get_hook();
			$next_job_schedule = $blocking_job->get_schedule()->next();
		}

		// Eliminate the false positive scenario where the blocking job is
		// actually another queued dependent action awaiting the same prerequisite.
		// Also, ensure that the next schedule is a DateTime (it can be null).
		if (
			is_a( $next_job_schedule, 'DateTime' ) &&
			( self::QUEUE_DEPEDENT_ACTION !== $blocking_job_hook )
		) {
			self::queue()->schedule_single(
				$next_job_schedule->getTimestamp() + 5,
				self::QUEUE_DEPEDENT_ACTION,
				array( $action, $action_args, $prerequisite_action ),
				self::QUEUE_GROUP
			);
		} else {
			self::queue()->schedule_single( time() + 5, $action, $action_args, self::QUEUE_GROUP );
		}
	}

	/**
	 * Exclude users that already exist in our customer lookup table.
	 *
	 * Meant to be hooked into 'pre_user_query' action.
	 *
	 * @param WP_User_Query $wp_user_query WP_User_Query to modify.
	 */
	public static function exclude_existing_customers_from_query( $wp_user_query ) {
		global $wpdb;

		$wp_user_query->query_where .= " AND NOT EXISTS (
			SELECT ID FROM {$wpdb->prefix}wc_customer_lookup
			WHERE {$wpdb->prefix}wc_customer_lookup.user_id = {$wpdb->users}.ID
		)";
	}

	/**
	 * Retrieve user IDs given import criteria.
	 *
	 * @param int|bool $days Number of days to process.
	 * @param bool     $skip_existing Skip exisiting records.
	 * @param array    $query_args Optional. WP_User_Query args.
	 * @return WP_User_Query
	 */
	public static function get_user_ids_for_batch( $days, $skip_existing, $query_args = array() ) {
		if ( ! is_array( $query_args ) ) {
			$query_args = array();
		}

		if ( $days ) {
			$query_args['date_query'] = array(
				'after' => date( 'Y-m-d 00:00:00', time() - ( DAY_IN_SECONDS * $days ) ),
			);
		}

		if ( $skip_existing ) {
			add_action( 'pre_user_query', array( __CLASS__, 'exclude_existing_customers_from_query' ) );
		}

		$customer_query = new WP_User_Query( $query_args );

		remove_action( 'pre_user_query', array( __CLASS__, 'exclude_existing_customers_from_query' ) );

		return $customer_query;
	}



	/**
	 * Init customer lookup table update (in batches).
	 *
	 * @param int|bool $days Number of days to process.
	 * @param bool     $skip_existing Skip exisiting records.
	 */
	public static function customer_lookup_batch_init( $days, $skip_existing ) {
		$batch_size      = self::get_batch_size( self::CUSTOMERS_BATCH_ACTION );
		$customer_query  = self::get_user_ids_for_batch(
			$days,
			$skip_existing,
			array(
				'fields' => 'ID',
				'number' => 1,
			)
		);
		$total_customers = $customer_query->get_total();

		if ( 0 === $total_customers ) {
			return;
		}

		$num_batches = ceil( $total_customers / $batch_size );

		self::queue_batches( 1, $num_batches, self::CUSTOMERS_BATCH_ACTION, array( $days, $skip_existing ) );
	}

	/**
	 * Process a batch of customers to update.
	 *
	 * @param int      $batch_number Batch number to process (essentially a query page number).
	 * @param int|bool $days Number of days to process.
	 * @param bool     $skip_existing Skip exisiting records.
	 * @return void
	 */
	public static function customer_lookup_process_batch( $batch_number, $days, $skip_existing ) {
		$batch_size     = self::get_batch_size( self::CUSTOMERS_BATCH_ACTION );
		$customer_query = self::get_user_ids_for_batch(
			$days,
			$skip_existing,
			array(
				'fields'  => 'ID',
				'orderby' => 'ID',
				'order'   => 'ASC',
				'number'  => $batch_size,
				'paged'   => $batch_number,
			)
		);

		$customer_ids = $customer_query->get_results();

		foreach ( $customer_ids as $customer_id ) {
			// @todo Schedule single customer update if this fails?
			WC_Admin_Reports_Customers_Data_Store::update_registered_customer( $customer_id );
		}
	}
}

WC_Admin_Reports_Sync::init();
