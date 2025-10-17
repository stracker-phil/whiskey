<?php
/**
 * @covers \Whiskey\Tools\ListRecipesTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tools\ListRecipesTool;
use Whiskey\Registry\RecipeRegistry;

class ListRecipesToolTest extends ToolTest {
	private ListRecipesTool $tool;

	protected function setUp(): void {
		parent::setUp();
		$this->tool = new ListRecipesTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function testGetRestConfigReturnsConfiguration(): void {
		$this->assert_rest_config( 'GET', '/recipes' );
	}

	public function testGetCliConfigReturnsConfiguration(): void {
		$this->assert_cli_config( 'whiskey recipes', 'recipes' );
	}

	public function testHandleLogicReturnsRecipeList(): void {
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

	public function testHandleLogicReturnsEmptyArrayWhenNoRecipes(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'all' )->willReturn( [] );

		$tool = new ListRecipesTool( $recipes, $this->ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'recipes', $result );
		$this->assertSame( [], $result['recipes'] );
	}

	public function testFormatCliOutputWithRecipes(): void {
		// Test with recipes - should not throw exception
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[ [ 'recipes' => [ 'recipe1', 'recipe2' ] ] ]
		);

		$this->assertTrue( true );
	}

	public function testFormatCliOutputWithEmptyRecipes(): void {
		// Test with empty recipes - should not throw exception
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[ [ 'recipes' => [] ] ]
		);

		$this->assertTrue( true );
	}
}
