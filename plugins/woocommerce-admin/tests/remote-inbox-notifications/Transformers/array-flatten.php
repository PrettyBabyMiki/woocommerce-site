<?php
/**
 * ArrayKeys tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers\ArrayFlatten;

/**
 * class WC_Tests_RemoteInboxNotifications_Transformers_ArrayKeys
 */
class WC_Tests_RemoteInboxNotifications_Transformers_ArrayFlatten extends WC_Unit_Test_Case {
	/**
	 * Test it returns flatten array
	 */
	public function test_it_returns_flatten_array() {
		$items = array(
			array(
				'member1',
			),
			array(
				'member2',
			),
			array(
				'member3',
			),
		);

		$array_keys = new ArrayFlatten();
		$result     = $array_keys->transform( $items );
		$this->assertEquals( array( 'member1', 'member2', 'member3' ), $result );
	}
}
