<?php
/**
 * Base class for tool tests providing common setup and helper methods.
 *
 * This abstract class eliminates duplicate dependency setup and provides
 * convenient helpers for reflection-based testing that is common across
 * all tool test classes.
 *
 * @package Whiskey\Tests\Unit\Tools
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Tools\WhiskeyTool;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\RecipeExecutor;
use ReflectionClass;
use ReflectionMethod;

/**
 * Base class for tool tests.
 *
 * Provides common test dependencies and reflection helpers to reduce
 * boilerplate in concrete tool test classes.
 */
abstract class ToolTest extends WhiskeyTest {
	/**
	 * Recipe registry stub.
	 *
	 * @var RecipeRegistry
	 */
	protected RecipeRegistry $recipes;

	/**
	 * Ingredient registry stub.
	 *
	 * @var IngredientRegistry
	 */
	protected IngredientRegistry $ingredients;

	/**
	 * Recipe executor stub.
	 *
	 * @var RecipeExecutor
	 */
	protected RecipeExecutor $executor;

	/**
	 * Set up common test dependencies.
	 *
	 * Creates stubs for RecipeRegistry, IngredientRegistry, and RecipeExecutor
	 * that are used by all tool classes.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->recipes     = $this->createStub( RecipeRegistry::class );
		$this->ingredients = $this->createStub( IngredientRegistry::class );
		$this->executor    = $this->createStub( RecipeExecutor::class );
	}

	/**
	 * Call a protected method on a tool via reflection.
	 *
	 * This helper eliminates the boilerplate of creating ReflectionClass,
	 * getting the method, and making it accessible.
	 *
	 * Example:
	 *     $config = $this->invoke_protected_method($this->tool, 'get_rest_config');
	 *     $result = $this->invoke_protected_method($this->tool, 'handle_logic', [['name' => 'test']]);
	 *
	 * @param WhiskeyTool $tool        The tool instance to call the method on.
	 * @param string      $method_name The name of the protected method to call.
	 * @param array       $args        Arguments to pass to the method (variadic unpacking).
	 *
	 * @return mixed The return value of the invoked method.
	 */
	protected function invoke_protected_method(
		WhiskeyTool $tool,
		string $method_name,
		array $args = []
	) {
		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invoke( $tool, ...$args );
	}

	/**
	 * Get a ReflectionMethod for a protected method.
	 *
	 * Useful when you need to call the same protected method multiple times
	 * or need the method object for other reflection operations.
	 *
	 * Example:
	 *     $method = $this->get_protected_method($this->tool, 'handle_logic');
	 *     $result1 = $method->invoke($this->tool, $args1);
	 *     $result2 = $method->invoke($this->tool, $args2);
	 *
	 * @param WhiskeyTool $tool        The tool instance.
	 * @param string      $method_name The name of the protected method.
	 *
	 * @return ReflectionMethod The accessible ReflectionMethod instance.
	 */
	protected function get_protected_method(
		WhiskeyTool $tool,
		string $method_name
	): ReflectionMethod {
		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method;
	}
}
