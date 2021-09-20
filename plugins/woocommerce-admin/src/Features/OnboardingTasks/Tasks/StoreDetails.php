<?php

namespace Automattic\WooCommerce\Admin\Features\OnboardingTasks\Tasks;

use Automattic\WooCommerce\Admin\Features\Onboarding;

/**
 * Store Details Task
 */
class StoreDetails {
	/**
	 * Get the task arguments.
	 *
	 * @return array
	 */
	public static function get_task() {
		$profiler_data = get_option( Onboarding::PROFILE_DATA_OPTION, array() );

		return array(
			'id'          => 'store_details',
			'title'       => __( 'Store details', 'woocommerce-admin' ),
			'content'     => __(
				'Your store address is required to set the origin country for shipping, currencies, and payment options.',
				'woocommerce-admin'
			),
			'actionLabel' => __( "Let's go", 'woocommerce-admin' ),
			'actionUrl'   => '/setup-wizard',
			'isComplete'  => isset( $profiler_data['completed'] ) && true === $profiler_data['completed'],
			'isVisible'   => true,
			'time'        => __( '4 minutes', 'woocommerce-admin' ),
		);
	}
}
