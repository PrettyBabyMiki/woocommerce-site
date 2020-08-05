<?php
/**
 * ExtendedContainer class file.
 *
 * @package WooCommerce\Internal\DependencyManagement
 */

namespace Automattic\WooCommerce\Internal\DependencyManagement;

use Automattic\WooCommerce\Container;
use League\Container\Definition\DefinitionInterface;

/**
 * This class extends the original League's Container object by adding some functionality
 * that we need for WooCommerce.
 */
class ExtendedContainer extends \League\Container\Container {

	/**
	 * Whitelist of classes that we can register using the container
	 * despite not belonging to the WooCommerce root namespace.
	 *
	 * In general we allow only the registration of classes in the
	 * WooCommerce root namespace to prevent registering 3rd party code
	 * (which doesn't really belong to this container) or old classes
	 * (which may be eventually deprecated, also the LegacyProxy
	 * should be used for those).
	 *
	 * @var string[]
	 */
	private $registration_whitelist = array(
		\Psr\Container\ContainerInterface::class,
	);

	/**
	 * Register a class in the container.
	 *
	 * @param string    $class_name Class name.
	 * @param mixed     $concrete How to resolve the class with `get`: a factory callback, a concrete instance, another class name, or null to just create an instance of the class.
	 * @param bool|null $shared Whether the resolution should be performed only once and cached.
	 *
	 * @return DefinitionInterface The generated definition for the container.
	 * @throws ContainerException Invalid parameters.
	 */
	public function add( string $class_name, $concrete = null, bool $shared = null ) : DefinitionInterface {
		if ( ! $this->class_is_in_root_namespace( $class_name ) && ! in_array( $class_name, $this->registration_whitelist, true ) ) {
			throw new ContainerException( "Can't use the container to register '$class_name', only objects in the " . Container::WOOCOMMERCE_ROOT_NAMESPACE . ' namespace are allowed for registration.' );
		}

		return parent::add( $class_name, $concrete, $shared );
	}

	/**
	 * Does a class belong to the WooCommerce root namespace?
	 *
	 * @param string $class_name The class name to check.
	 *
	 * @return bool True if the class belongs to the WooCommerce root namespace.
	 */
	private function class_is_in_root_namespace( $class_name ) {
		return substr( $class_name, 0, strlen( Container::WOOCOMMERCE_ROOT_NAMESPACE ) + 1 ) === Container::WOOCOMMERCE_ROOT_NAMESPACE . '\\';
	}

	/**
	 * Replace an existing registration with a different concrete.
	 *
	 * @param string $class_name The class name whose definition will be replaced.
	 * @param mixed  $concrete The new concrete (same as "add").
	 *
	 * @return DefinitionInterface The modified definition.
	 * @throws ContainerException Invalid parameters.
	 */
	public function replace( string $class_name, $concrete ) {
		if ( ! $this->has( $class_name ) ) {
			throw new ContainerException( "ExtendedContainer::replace: The container doesn't have '$class_name' registered, please use 'add' instead of 'replace'." );
		}

		return $this->extend( $class_name )->setConcrete( $concrete );
	}

	/**
	 * Reset all the cached resolutions, so any further "get" for shared definitions will generate the instance again.
	 */
	public function reset_all_resolved() {
		foreach ( $this->definitions->getIterator() as $definition ) {
			// setConcrete causes the cached resolved value to be forgotten.
			$concrete = $definition->getConcrete();
			$definition->setConcrete( $concrete );
		}
	}

	/**
	 * Get an instance of a registered class.
	 *
	 * @param string $id The class name.
	 * @param bool   $new True to generate a new instance even if the class was registered as shared.
	 *
	 * @return object An instance of the requested class.
	 * @throws ContainerException Attempt to get an instance of a non-namespaced class.
	 */
	public function get( $id, bool $new = false ) {
		if ( false === strpos( $id, '\\' ) ) {
			throw new ContainerException( "Attempt to get an instance of the non-namespaced class '$id' from the container, did you forget to add a namespace import?" );
		}

		return parent::get( $id, $new );
	}
}
