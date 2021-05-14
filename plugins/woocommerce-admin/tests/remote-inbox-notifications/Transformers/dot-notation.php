<?php
/**
 * DotNotation tests.
 *
 * @package WooCommerce\Admin\Tests\RemoteInboxNotifications
 */

use Automattic\WooCommerce\Admin\RemoteInboxNotifications\Transformers\DotNotation;


/**
 * class WC_Tests_RemoteInboxNotifications_Transformers_DotNotation
 */
class WC_Tests_RemoteInboxNotifications_Transformers_DotNotation extends WC_Unit_Test_Case {

	/**
	 * Test validate method returns false when 'path' argument is missing
	 */
	public function test_validate_returns_false_when_path_argument_is_missing() {
		$array_column = new DotNotation();
		$result       = $array_column->validate( (object) array() );
		$this->assertFalse( false, $result );
	}

	/**
	 * Test it can get value by index
	 */
	public function test_it_can_get_value_by_index() {
		$arguments    = (object) array( 'path' => '0' );
		$dot_notation = new DotNotation();
		$item         = array( 'name' => 'test' );
		$items        = array( $item );

		$result = $dot_notation->transform( $items, $arguments );
		$this->assertEquals( $result, $item );
	}

	/**
	 * Test it get getvalue by dot notation.
	 */
	public function test_it_can_get_value_by_dot_notation() {
		$arguments = (object) array( 'path' => 'teams.mothra' );

		$items = array(
			'teams' => array(
				'mothra' => 'nice!',
			),
		);

		$dot_notation = new DotNotation();
		$result       = $dot_notation->transform( $items, $arguments );
		$this->assertEquals( 'nice!', $result );
	}
}
