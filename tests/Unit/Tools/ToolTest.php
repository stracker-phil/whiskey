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
	 * The tool that is tested.
	 *
	 * @var WhiskeyTool
	 */
	protected WhiskeyTool $tool;

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
	 *     $result = $this->invoke_protected_method($this->tool, 'handle_logic', [['name' =>
	 *     'test']]);
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

	/**
	 * Assert that a tool's REST config matches expected values.
	 *
	 * Tests the get_rest_config() method and verifies the method and path.
	 * Uses assertStringContainsString for path to handle regex patterns flexibly.
	 *
	 * Example:
	 *     $this->assert_rest_config('GET', '/recipes');
	 *     $this->assert_rest_config('POST', '/recipe/'); // matches paths with regex
	 *
	 * @param string $expected_method        Expected HTTP method (GET, POST, etc.).
	 * @param string $expected_path_fragment Expected path or path fragment.
	 *
	 * @return void
	 */
	protected function assertRestConfig(
		string $expected_method,
		string $expected_path_fragment
	): void {
		$config = $this->invoke_protected_method( $this->tool, 'get_rest_config' );

		$this->assertIsArray( $config );
		$this->assertSame( $expected_method, $config['method'] );
		$this->assertStringContainsString( $expected_path_fragment, $config['path'] );
	}

	/**
	 * Assert that a tool's CLI config matches expected values.
	 *
	 * Tests the get_cli_config() method and verifies the command and synopsis.
	 *
	 * Example:
	 *     $this->assert_cli_config('whiskey recipes', 'recipes');
	 *     $this->assert_cli_config('whiskey apply', 'recipe');
	 *
	 * @param string $expected_command           Expected CLI command string.
	 * @param string $expected_synopsis_fragment Expected word/phrase in synopsis.
	 *
	 * @return void
	 */
	protected function assertCliConfig(
		string $expected_command,
		string $expected_synopsis_fragment
	): void {
		$config = $this->invoke_protected_method( $this->tool, 'get_cli_config' );

		$this->assertIsArray( $config );
		$this->assertSame( $expected_command, $config['command'] );
		$this->assertStringContainsString( $expected_synopsis_fragment, $config['synopsis'] );
	}
}
