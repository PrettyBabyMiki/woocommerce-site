<?php

/**
 * Class Log.
 * @package WooCommerce\Tests\Util
 * @since 2.3
 */
class WC_Tests_Log extends WC_Unit_Test_Case {
	public function read_content( $handle ) {
		return file_get_contents( wc_get_log_file_path( $handle ) );
	}

	/**
	 * Test add().
	 *
	 * @since 2.4
	 *
	 * @expectedDeprecated WC_Logger::add
	 */
	public function test_add() {
		$log = wc_get_logger();

		$log->add( 'unit-tests', 'this is a message' );

		$this->assertStringMatchesFormat( '%d-%d-%d @ %d:%d:%d - %s', $this->read_content( 'unit-tests' ) );
		$this->assertStringEndsWith( ' - this is a message' . PHP_EOL, $this->read_content( 'unit-tests' ) );
	}

	/**
	 * Test clear().
	 *
	 * @since 2.4
	 *
	 * @expectedDeprecated WC_Logger::clear
	 */
	public function test_clear() {
		$log = wc_get_logger();

		$log->clear();
	}
}
