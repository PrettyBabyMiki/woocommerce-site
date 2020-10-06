<?php
/**
 * A facade to allow deprecating an entire class. Calling instance or static
 * functions on the facade triggers a deprecation notice before calling the
 * underlying function.
 *
 * Use it by extending DeprecatedClassFacade in your facade class, setting the
 * static $facade_over_classname string to the name of the class to build
 * a facade over, and setting the static $deprecated_in_version to the version
 * that the class was deprecated in. Eg.:
 *
 * class DeprecatedGoose extends DeprecatedClassFacade {
 *     static $facade_over_classname = 'Goose';
 *     static $deprecated_in_version = '1.6.0';
 * }
 */

namespace Automattic\WooCommerce\Admin;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * A facade to allow deprecating an entire class.
 */
class DeprecatedClassFacade {
	/**
	 * The instance that this facade covers over.
	 *
	 * @var object
	 */
	protected $instance;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->instance = new static::$facade_over_classname();
	}

	/**
	 * Executes when calling any function on an instance of this class.
	 *
	 * @param string $name      The name of the function being called.
	 * @param array  $arguments An array of the arguments to the function call.
	 */
	public function __call( $name, $arguments ) {
		_deprecated_function(
			static::$facade_over_classname . '::' . $name,
			static::$deprecated_in_version
		);
		return call_user_func_array(
			array(
				$this->instance,
				$name,
			),
			$arguments
		);
	}

	/**
	 * Executes when calling any static function on this class.
	 *
	 * @param string $name      The name of the function being called.
	 * @param array  $arguments An array of the arguments to the function call.
	 */
	public static function __callStatic( $name, $arguments ) {
		_deprecated_function(
			static::$facade_over_classname . '::' . $name,
			static::$deprecated_in_version
		);
		return call_user_func_array(
			array(
				static::$facade_over_classname,
				$name,
			),
			$arguments
		);
	}
}
