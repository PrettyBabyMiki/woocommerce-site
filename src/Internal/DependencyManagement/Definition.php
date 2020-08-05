<?php
/**
 * An extension to the Definition class to prevent constructor injection from being possible.
 *
 * @package Automattic\WooCommerce\Internal\DependencyManagement
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement;

use \League\Container\Definition\Definition as BaseDefinition;

/**
 * An extension of the definition class that replaces constructor injection with method injection.
 */
class Definition extends BaseDefinition {

	/**
	 * The standard method that we use for dependency injection.
	 */
	const INJECTION_METHOD = 'set_internal_dependencies';

	/**
	 * Resolve a class using method injection instead of constructor injection.
	 *
	 * @param string $concrete The concrete to instantiate.
	 *
	 * @return object
	 */
	protected function resolveClass( string $concrete ) {
		$resolved = $this->resolveArguments( $this->arguments );
		$concrete = new $concrete();

		// Constructor injection causes backwards compatibility problems
		// so we will rely on method injection via an internal method.
		if ( method_exists( $concrete, static::INJECTION_METHOD ) ) {
			call_user_func_array( array( $concrete, static::INJECTION_METHOD ), $resolved );
		}

		return $concrete;
	}
}
