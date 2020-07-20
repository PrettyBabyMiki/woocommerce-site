<?php
/**
 * AbstractServiceProviderTests class file.
 *
 * @package Automattic\WooCommerce\Tests\Internal\DependencyManagement
 */

namespace Automattic\WooCommerce\Tests\Internal\DependencyManagement;

use Automattic\WooCommerce\Internal\DependencyManagement\AbstractServiceProvider;
use Automattic\WooCommerce\Internal\DependencyManagement\ContainerException;
use Automattic\WooCommerce\Internal\DependencyManagement\ExtendedContainer;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithConstructorArgumentWithoutTypeHint;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithDependencies;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithPrivateConstructor;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\ClassWithScalarConstructorArgument;
use Automattic\WooCommerce\Tests\Internal\DependencyManagement\ExampleClasses\DependencyClass;
use League\Container\Definition\DefinitionInterface;

/**
 * Tests for AbstractServiceProvider.
 */
class AbstractServiceProviderTest extends \WC_Unit_Test_Case {

	/**
	 * The system under test.
	 *
	 * @var AbstractServiceProvider
	 */
	private $sut;

	/**
	 * The container used for tests.
	 *
	 * @var ExtendedContainer
	 */
	private $container;

	/**
	 * Runs before each test.
	 */
	public function setUp() {
		$this->container = new ExtendedContainer();

		$this->sut = new class() extends AbstractServiceProvider {
			// phpcs:disable

			/**
			 * Public version of add_with_auto_arguments, which is usually protected.
			 */
			public function add_with_auto_arguments( string $class_name, $concrete = null, bool $shared = false ) : DefinitionInterface {
				return parent::add_with_auto_arguments( $class_name, $concrete, $shared );
			}

			/**
			 * The mandatory 'register' method (defined in the base class as abstract).
			 * Not implemented because this class is tested on its own, not as a service provider actually registered on a container.
			 */
			public function register() {}

			// phpcs:enable
		};

		$this->sut->setContainer( $this->container );
	}

	/**
	 * Runs before all the tests of the class.
	 */
	public static function setUpBeforeClass() {
		/**
		 * Return a new instance of DependencyClass.
		 *
		 * @return DependencyClass The new instance.
		 */
		function get_new_dependency_class() {
			return new DependencyClass();
		};
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if an invalid class name is passed as class name.
	 */
	public function test_add_with_auto_arguments_throws_on_non_class_passed_as_class_name() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "AbstractServiceProvider::add_with_auto_arguments: error when reflecting class 'foobar': Class foobar does not exist" );

		$this->sut->add_with_auto_arguments( 'foobar' );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed concrete is neither an existing class name, an existing function, an object, or a callback.
	 *
	 * @testWith ["foobar"]
	 *           [1234]
	 *
	 * @param mixed $concrete The concrete to use to register the class.
	 */
	public function test_add_with_auto_arguments_throws_on_non_class_passed_as_concrete( $concrete ) {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'AbstractServiceProvider::add_with_auto_arguments: concrete must be a valid class name, function name, object, or callable.' );

		$this->sut->add_with_auto_arguments( get_class( $this ), $concrete );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed class has a private constructor.
	 */
	public function test_add_with_auto_arguments_throws_on_class_private_constructor() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "AbstractServiceProvider::add_with_auto_arguments: constructor of class '" . ClassWithPrivateConstructor::class . "' isn't public, instances can't be created." );

		$this->sut->add_with_auto_arguments( ClassWithPrivateConstructor::class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed concrete is a class with a private constructor.
	 */
	public function test_add_with_auto_arguments_throws_on_concrete_private_constructor() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "AbstractServiceProvider::add_with_auto_arguments: constructor of class '" . ClassWithPrivateConstructor::class . "' isn't public, instances can't be created." );

		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, ClassWithPrivateConstructor::class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed class has a constructor argument without type hint.
	 */
	public function test_add_with_auto_arguments_throws_on_constructor_argument_without_type_hint() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "AbstractServiceProvider::add_with_auto_arguments: constructor argument 'argument_without_type_hint' of class '" . ClassWithConstructorArgumentWithoutTypeHint::class . "' doesn't have a type hint or has one that doesn't specify a class." );

		$this->sut->add_with_auto_arguments( ClassWithConstructorArgumentWithoutTypeHint::class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should throw an exception if the passed class has a constructor argument with a scalar type hint.
	 */
	public function test_add_with_auto_arguments_throws_on_constructor_argument_with_scalar_type_hint() {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( "AbstractServiceProvider::add_with_auto_arguments: constructor argument 'scalar_argument_without_default_value' of class '" . ClassWithScalarConstructorArgument::class . "' doesn't have a type hint or has one that doesn't specify a class." );

		$this->sut->add_with_auto_arguments( ClassWithScalarConstructorArgument::class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should properly register the supplied class when no concrete is passed.
	 *
	 * @testWith [true, 1]
	 *           [false, 2]
	 *
	 * @param bool $shared Whether to register the test class as shared or not.
	 * @param int  $expected_constructions_count Expected number of times that the test class will have been instantiated.
	 */
	public function test_add_with_auto_arguments_works_as_expected_with_no_concrete( bool $shared, int $expected_constructions_count ) {
		ClassWithDependencies::$instances_count = 0;

		$this->container->share( DependencyClass::class );
		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, null, $shared );

		$this->container->get( ClassWithDependencies::class );
		$resolved = $this->container->get( ClassWithDependencies::class );

		// A new instance is created for each resolution or not, depending on $shared.
		$this->assertEquals( $expected_constructions_count, ClassWithDependencies::$instances_count );

		// Arguments with default values are honored.
		$this->assertEquals( ClassWithDependencies::SOME_NUMBER, $resolved->some_number );

		// Constructor arguments are filled as expected.
		$this->assertSame( $this->container->get( DependencyClass::class ), $resolved->dependency_class );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should properly register the supplied class when a concrete representing a class name is passed.
	 */
	public function test_add_with_auto_arguments_works_as_expected_when_concrete_is_class_name() {
		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, DependencyClass::class );

		$resolved = $this->container->get( ClassWithDependencies::class );

		$this->assertInstanceOf( DependencyClass::class, $resolved );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should properly register the supplied class when a concrete that is an object is passed.
	 */
	public function test_add_with_auto_arguments_works_as_expected_when_concrete_is_object() {
		$object = new DependencyClass();

		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, $object );

		$resolved = $this->container->get( ClassWithDependencies::class );

		$this->assertSame( $object, $resolved );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should properly register the supplied class when a concrete that is a closure is passed.
	 */
	public function test_add_with_auto_arguments_works_as_expected_when_concrete_is_a_closure() {
		$callable = function() {
			return new DependencyClass();
		};

		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, $callable );

		$resolved = $this->container->get( ClassWithDependencies::class );

		$this->assertInstanceOf( DependencyClass::class, $resolved );
	}

	/**
	 * @testdox 'add_with_auto_arguments' should properly register the supplied class when a concrete that is a function name is passed.
	 */
	public function test_add_with_auto_arguments_works_as_expected_when_concrete_is_a_function_name() {
		$this->sut->add_with_auto_arguments( ClassWithDependencies::class, __NAMESPACE__ . '\get_new_dependency_class' );

		$resolved = $this->container->get( ClassWithDependencies::class );

		$this->assertInstanceOf( DependencyClass::class, $resolved );
	}
}

