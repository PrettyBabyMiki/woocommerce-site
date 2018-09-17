<?php
/**
 * Order stats background process.
 *
 * @package WooCommerce Admin/Classes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Background_Process', false ) ) {
	include_once WC_ABSPATH . '/includes/abstracts/class-wc-background-process.php';
}

/**
 * WC_Admin_Order_Stats_Background_Process class.
 *
 * @todo use Action Scheduler instead of this.
 */
class WC_Admin_Order_Stats_Background_Process extends WC_Background_Process {

	/**
	 * Initiate new background process.
	 */
	public function __construct() {
		// Uses unique prefix per blog so each blog has separate queue.
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'wc_order_stats';
		parent::__construct();
	}

	/**
	 * Push to queue without scheduling duplicate recalculation events.
	 * Overrides WC_Background_Process::push_to_queue.
	 *
	 * @param integer $data Timestamp of hour to generate stats.
	 */
	public function push_to_queue( $data ) {
		$data = absint( $data );
		if ( ! in_array( $data, $this->data, true ) ) {
			$this->data[] = $data;
		}

		return $this;
	}

	/**
	 * Dispatch but only if there is data to update.
	 * Overrides WC_Background_Process::dispatch.
	 */
	public function dispatch() {
		if ( ! $this->data ) {
			return false;
		}

		return parent::dispatch();
	}

	/**
	 * Code to execute for each item in the queue
	 *
	 * @param string $item Queue item to iterate over.
	 * @return bool
	 */
	protected function task( $item ) {
		if ( ! $item ) {
			return false;
		}

		$order = wc_get_order( $item );
		if ( ! $order ) {
			return false;
		}

		WC_Admin_Reports_Orders_Data_Store::update( $order );
		return false;
	}
}
