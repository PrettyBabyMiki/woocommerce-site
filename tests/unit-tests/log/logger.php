<?php

/**
 * Class WC_Tests_Logger
 * @package WooCommerce\Tests\Log
 * @since 2.3
 *
 * @todo isolate tests from file handler
 */
class WC_Tests_Logger extends WC_Unit_Test_Case {

	public function tearDown() {
		$log_files = array(
			wc_get_log_file_path( 'unit-tests' ),
			wc_get_log_file_path( 'log' ),
		);

		foreach ( $log_files as $file ) {
			if ( file_exists( $file ) && is_writable( $file ) ) {
				unlink( $file );
			}
		}
		parent::tearDown();
	}

	public function read_content( $handle ) {
		return file_get_contents( wc_get_log_file_path( $handle ) );
	}

	/**
	 * Test add().
	 *
	 * @since 2.4
	 */
	public function test_add() {
		$log = new WC_Logger( array( new WC_Log_Handler_File() ), 'debug' );

		$log->add( 'unit-tests', 'this is a message' );

		$this->assertStringMatchesFormat( '%d-%d-%d @ %d:%d:%d - %s', $this->read_content( 'unit-tests' ) );
		$this->assertStringEndsWith( ' - this is a message' . PHP_EOL, $this->read_content( 'unit-tests' ) );
		$this->setExpectedDeprecated( 'WC_Logger::add' );
	}

	/**
	 * Test clear().
	 *
	 * @since 2.4
	 */
	public function test_clear() {
		$log = new WC_Logger( null, 'debug' );
		$log->clear( 'log' );
		$this->setExpectedDeprecated( 'WC_Logger::clear' );
	}

	/**
	 * Test log() complains for bad levels.
	 *
	 * @since 2.8
	 */
	public function test_bad_level() {
		$log = new WC_Logger( null, 'debug' );
		$log->log( 'this-is-an-invalid-level', '' );
		$this->setExpectedIncorrectUsage( 'WC_Logger::log' );
	}

	/**
	 * Test log().
	 *
	 * @since 2.8
	 */
	public function test_log() {
		$log = new WC_Logger( array( new WC_Log_Handler_File() ), 'debug' );
		$log->log( 'debug', 'debug' );
		$log->log( 'info', 'info' );
		$log->log( 'notice', 'notice' );
		$log->log( 'warning', 'warning' );
		$log->log( 'error', 'error' );
		$log->log( 'critical', 'critical' );
		$log->log( 'alert', 'alert' );
		$log->log( 'emergency', 'emergency' );

		$log_content = $this->read_content( 'log' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}

	/**
	 * Test logs passed to all handlers.
	 *
	 * @since 2.8
	 */
	public function test_log_handlers() {
		$false_handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$false_handler->expects( $this->exactly( 8 ) )->method( 'handle' )->will( $this->returnValue( false ) );

		$true_handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$false_handler->expects( $this->exactly( 8 ) )->method( 'handle' )->will( $this->returnValue( true ) );

		$final_handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$final_handler->expects( $this->exactly( 8 ) )->method( 'handle' );

		$log = new WC_Logger( array( $false_handler, $true_handler, $final_handler ), 'debug' );

		$log->debug( 'debug' );
		$log->info( 'info' );
		$log->notice( 'notice' );
		$log->warning( 'warning' );
		$log->error( 'error' );
		$log->critical( 'critical' );
		$log->alert( 'alert' );
		$log->emergency( 'emergency' );
	}

	/**
	 * Test WC_Logger->[debug..emergency] methods
	 *
	 * @since 2.8
	 */
	public function test_level_methods() {
		$log = new WC_Logger( array( new WC_Log_Handler_File() ), 'debug' );

		$log->debug( 'debug' );
		$log->info( 'info' );
		$log->notice( 'notice' );
		$log->warning( 'warning' );
		$log->error( 'error' );
		$log->critical( 'critical' );
		$log->alert( 'alert' );
		$log->emergency( 'emergency' );

		$log_content = $this->read_content( 'log' );
		$this->assertStringMatchesFormatFile( dirname( __FILE__ ) . '/test_log_expected.txt', $log_content );
	}

	/**
	 * Test 'woocommerce_register_log_handlers' filter.
	 *
	 * @since 2.8
	 */
	public function test_woocommerce_register_log_handlers_filter() {
		add_filter( 'woocommerce_register_log_handlers', array( $this, 'return_assertion_handlers' ) );
		$log = new WC_Logger( null, 'debug' );
		$log->debug( 'debug' );
		$log->info( 'info' );
		$log->notice( 'notice' );
		$log->warning( 'warning' );
		$log->error( 'error' );
		$log->critical( 'critical' );
		$log->alert( 'alert' );
		$log->emergency( 'emergency' );
		remove_filter( 'woocommerce_register_log_handlers', array( $this, 'return_assertion_handlers' ) );
	}

	/**
	 * Helper for 'woocommerce_register_log_handlers' filter test.
	 *
	 * Returns an array of 1 mocked handler.
	 * The handler expects to receive exactly 8 messages (1 for each level).
	 *
	 * @since 2.8
	 *
	 * @return WC_Log_Handler[] array of mocked handlers.
	 */
	public function return_assertion_handlers() {
		$handler = $this
			->getMockBuilder( 'WC_Log_Handler' )
			->setMethods( array( 'handle' ) )
			->getMock();
		$handler->expects( $this->exactly( 8 ) )->method( 'handle' );

		return array( $handler );
	}

}
