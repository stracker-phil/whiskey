<?php
/**
 * @covers \Whiskey\Tools\StatusTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tools\StatusTool;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;

class StatusToolTest extends ToolTest {
	protected function setUp(): void {
		parent::setUp();
		$this->tool = new StatusTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function test_get_rest_config_returns_configuration(): void {
		$this->assertRestConfig( 'GET', '/status' );
	}

	public function test_get_cli_config_returns_configuration(): void {
		$this->assertCliConfig( 'whiskey status', 'status' );
	}

	public function test_handle_logic_returns_php_version(): void {
		$result = $this->invoke_protected_method( $this->tool, 'handle_logic', [ [] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'php_version', $result );
		$this->assertSame( PHP_VERSION, $result['php_version'] );
	}

	public function test_handle_logic_returns_recipe_count(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'all' )->willReturn( [
			'recipe1' => [],
			'recipe2' => [],
		] );

		$tool = new StatusTool( $recipes, $this->ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [] ] );

		$this->assertArrayHasKey( 'recipes', $result );
		$this->assertSame( 2, $result['recipes'] );
	}

	public function test_handle_logic_returns_ingredient_count(): void {
		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'all' )->willReturn( [
			'ing1' => 'Class1',
			'ing2' => 'Class2',
			'ing3' => 'Class3',
		] );

		$tool = new StatusTool( $this->recipes, $ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [] ] );

		$this->assertArrayHasKey( 'ingredients', $result );
		$this->assertSame( 3, $result['ingredients'] );
	}

	public function test_format_cli_output(): void {
		// Test with data - should not throw exception
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[
				[
					'php_version' => '7.4.0',
					'recipes'     => 5,
					'ingredients' => 10,
				],
			]
		);

		$this->assertTrue( true );
	}
}
