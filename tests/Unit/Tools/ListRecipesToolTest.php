<?php
/**
 * @covers \Whiskey\Tools\ListRecipesTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tools\ListRecipesTool;
use Whiskey\Registry\RecipeRegistry;

class ListRecipesToolTest extends ToolTest {
	protected function setUp(): void {
		parent::setUp();
		$this->tool = new ListRecipesTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function test_get_rest_config_returns_configuration(): void {
		$this->assertRestConfig( 'GET', '/recipes' );
	}

	public function test_get_cli_config_returns_configuration(): void {
		$this->assertCliConfig( 'whiskey recipes', 'recipes' );
	}

	public function test_handle_logic_returns_recipe_list(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'all' )->willReturn(
			[
				'recipe1' => [ 'ingredient1' => 'value1' ],
				'recipe2' => [ 'ingredient2' => 'value2' ],
				'recipe3' => [ 'ingredient3' => 'value3' ],
			]
		);

		$tool = new ListRecipesTool( $recipes, $this->ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'recipes', $result );
		$this->assertSame( [ 'recipe1', 'recipe2', 'recipe3' ], $result['recipes'] );
	}

	public function test_handle_logic_returns_empty_array_when_no_recipes(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'all' )->willReturn( [] );

		$tool = new ListRecipesTool( $recipes, $this->ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'recipes', $result );
		$this->assertSame( [], $result['recipes'] );
	}

	public function test_format_cli_output_with_recipes(): void {
		// Test with recipes - should not throw exception
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[ [ 'recipes' => [ 'recipe1', 'recipe2' ] ] ]
		);

		$this->assertTrue( true );
	}

	public function test_format_cli_output_with_empty_recipes(): void {
		// Test with empty recipes - should not throw exception
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[ [ 'recipes' => [] ] ]
		);

		$this->assertTrue( true );
	}
}
