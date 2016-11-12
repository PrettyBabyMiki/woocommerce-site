<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Provides logging capabilities for debugging purposes.
 *
 * @class          WC_Logger
 * @version        2.0.0
 * @package        WooCommerce/Classes
 * @category       Class
 * @author         WooThemes
 */
class WC_Logger {

	/**
	 * Log Levels
	 *
	 * @see @link {https://tools.ietf.org/html/rfc5424}
	 */
	const EMERGENCY = 'emergency';
	const ALERT     = 'alert';
	const CRITICAL  = 'critical';
	const ERROR     = 'error';
	const WARNING   = 'warning';
	const NOTICE    = 'notice';
	const INFO      = 'info';
	const DEBUG     = 'debug';

	/**
	 * Stores registered log handlers.
	 *
	 * @var array
	 * @access private
	 */
	private $_handlers;

	/**
	 * Constructor for the logger.
	 */
	public function __construct() {
		$handlers = apply_filters( 'woocommerce_register_log_handlers', array() );
		$this->_handlers = $handlers;
	}

	/**
	 * Add a log entry.
	 *
	 * @deprecated since 2.0.0
	 *
	 * @param string $handle
	 * @param string $message
	 *
	 * @return bool
	 */
	public function add( $handle, $message ) {
		_deprecated_function( 'WC_Logger::add', '2.8', 'WC_Logger::log' );
		$this->log( self::INFO, $message, array( 'tag' => $handle ) );
		wc_do_deprecated_action( 'woocommerce_log_add', $handle, $message, '2.8');
		return true;
	}

	/**
	 * Add a log entry.
	 *
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
	 * @param string $message
	 * @param array $context {
	 *     Optional. Additional information for log handlers.
	 *
	 *     @type string $tag Optional. May be used by log handlers to sort messages.
	 * }
	 */
	public function log( $level, $message, $context=array() ) {

		foreach ( $this->_handlers as $handler ) {
			$continue = $handler->handle( $level, $message, $context );

			if ( false === $continue ) {
				break;
			}
		}

	}

	/**
	 * Adds an emergency level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function emergency( $message, $context=array() ) {
		$this->log( self::EMERGENCY, $message, $context );
	}

	/**
	 * Adds an alert level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function alert( $message, $context=array() ) {
		$this->log( self::ALERT, $message, $context );
	}

	/**
	 * Adds a critical level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function critical( $message, $context=array() ) {
		$this->log( self::CRITICAL, $message, $context );
	}

	/**
	 * Adds an error level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function error( $message, $context=array() ) {
		$this->log( self::ERROR, $message, $context );
	}

	/**
	 * Adds a warning level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function warning( $message, $context=array() ) {
		$this->log( self::WARNING, $message, $context );
	}

	/**
	 * Adds a notice level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function notice( $message, $context=array() ) {
		$this->log( self::NOTICE, $message, $context );
	}

	/**
	 * Adds a info level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function info( $message, $context=array() ) {
		$this->log( self::INFO, $message, $context );
	}

	/**
	 * Adds a debug level message.
	 *
	 * @see WC_Logger::log
	 *
	 */
	public function debug( $message, $context=array() ) {
		$this->log( self::DEBUG, $message, $context );
	}

	/**
	 * @deprecated since 2.0.0
	 */
	public function clear() {
		_deprecated_function( 'WC_Logger::clear', '2.8' );
	}
}
