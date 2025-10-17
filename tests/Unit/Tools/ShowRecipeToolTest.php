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

	public function testGetRestConfigReturnsConfiguration(): void {
		$this->assert_rest_config( 'GET', '/recipe/' );
	}

	public function testGetCliConfigReturnsConfiguration(): void {
		$this->assert_cli_config( 'whiskey recipe', 'recipe' );
	}

	public function testHandleLogicReturnsRecipeData(): void {
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

	public function testHandleLogicExtractsNameFromPositionalArg(): void {
		$recipe_config = [ 'ingredient1' => 'value1' ];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$tool = new ShowRecipeTool( $recipes, $this->ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 0 => 'my-recipe' ] ] );

		$this->assertSame( 'my-recipe', $result['name'] );
	}

	public function testHandleLogicThrowsExceptionWhenNameMissing(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe name is required' );

		$this->invoke_protected_method( $this->tool, 'handle_logic', [ [] ] );
	}

	public function testHandleLogicThrowsExceptionWhenRecipeNotFound(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( null );

		$tool = new ShowRecipeTool( $recipes, $this->ingredients, $this->executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe not found: nonexistent' );

		$this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'nonexistent' ] ] );
	}

	public function testFormatCliOutput(): void {
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
