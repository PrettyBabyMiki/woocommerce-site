<?php
/**
 * FakeQueue class file.
 *
 * @package WooCommerce\Testing\Tools
 */

namespace Automattic\WooCommerce\Testing\Tools;

/**
 * Fake scheduled actions queue for unit tests, it just records all the method calls
 * in a publicly accessible $methods_called property.
 *
 * To use, add this to the setUp method of the unit tests class:
 *
 * add_filter( 'woocommerce_queue_class', function() { return FakeQueue::class; } );
 *
 * then WC->queue() will return an instance of this class.
 */
class FakeQueue implements \WC_Queue_Interface {

	/**
	 * Records all the method calls to this instance.
	 *
	 * @var array
	 */
	public $methods_called = array();

	// phpcs:disable Squiz.Commenting.FunctionComment.Missing

	public function add( $hook, $args = array(), $group = '' ) {
		// TODO: Implement add() method.
	}

	public function schedule_single( $timestamp, $hook, $args = array(), $group = '' ) {
		$this->add_to_methods_called(
			'schedule_single',
			$args,
			$group,
			array(
				'timestamp' => $timestamp,
				'hook'      => $hook,
			)
		);
	}

	public function schedule_recurring( $timestamp, $interval_in_seconds, $hook, $args = array(), $group = '' ) {
		// TODO: Implement schedule_recurring() method.
	}

	public function schedule_cron( $timestamp, $cron_schedule, $hook, $args = array(), $group = '' ) {
		// TODO: Implement schedule_cron() method.
	}

	public function cancel( $hook, $args = array(), $group = '' ) {
		// TODO: Implement cancel() method.
	}

	public function cancel_all( $hook, $args = array(), $group = '' ) {
		// TODO: Implement cancel_all() method.
	}

	public function get_next( $hook, $args = null, $group = '' ) {
		// TODO: Implement get_next() method.
	}

	public function search( $args = array(), $return_format = OBJECT ) {
		// TODO: Implement search() method.
	}

	// phpcs:enable Squiz.Commenting.FunctionComment.Missing

	/**
	 * Registers a method call for this instance.
	 *
	 * @param string $method Name of the invoked method.
	 * @param array  $args Arguments passed in '$args' to the method call.
	 * @param string $group Group name passed in '$group' to the method call.
	 * @param array  $extra_args Any extra information to store about the method call.
	 */
	private function add_to_methods_called( $method, $args, $group, $extra_args = array() ) {
		$value = array(
			'method' => $method,
			'args'   => $args,
			'group'  => $group,
		);

		$this->methods_called[] = array_merge( $value, $extra_args );
	}
}
