<?php
/**
 * @covers \Whiskey\Tools\ShowRecipeTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tools\ShowRecipeTool;
use Whiskey\Registry\RecipeRegistry;
use Exception;

class ShowRecipeToolTest extends ToolTest {
	protected function setUp(): void {
		parent::setUp();
		$this->tool = new ShowRecipeTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function test_get_rest_config_returns_configuration(): void {
		$this->assertRestConfig( 'GET', '/recipe/' );
	}

	public function test_get_cli_config_returns_configuration(): void {
		$this->assertCliConfig( 'whiskey recipe', 'recipe' );
	}

	public function test_handle_logic_returns_recipe_data(): void {
		$recipe_config = [
			'ingredient1' => 'value1',
			'ingredient2' => 'value2',
		];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$tool = new ShowRecipeTool( $recipes, $this->ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'test-recipe' ] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'config', $result );
		$this->assertSame( 'test-recipe', $result['name'] );
		$this->assertSame( $recipe_config, $result['config'] );
	}

	public function test_handle_logic_extracts_name_from_positional_arg(): void {
		$recipe_config = [ 'ingredient1' => 'value1' ];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$tool = new ShowRecipeTool( $recipes, $this->ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 0 => 'my-recipe' ] ] );

		$this->assertSame( 'my-recipe', $result['name'] );
	}

	public function test_handle_logic_throws_exception_when_name_missing(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe name is required' );

		$this->invoke_protected_method( $this->tool, 'handle_logic', [ [] ] );
	}

	public function test_handle_logic_throws_exception_when_recipe_not_found(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( null );

		$tool = new ShowRecipeTool( $recipes, $this->ingredients, $this->executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe not found: nonexistent' );

		$this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'nonexistent' ] ] );
	}

	public function test_format_cli_output(): void {
		// Test with data - should not throw exception
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[
				[
					'name'   => 'test-recipe',
					'config' => [
						'ingredient1' => 'value1',
						'ingredient2' => [ 'nested' => 'value' ],
					],
				],
			]
		);

		$this->assertTrue( true );
	}
}
