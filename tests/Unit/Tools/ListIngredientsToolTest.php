<?php
/**
 * @covers \Whiskey\Tools\ListIngredientsTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tools\ListIngredientsTool;
use Whiskey\Registry\IngredientRegistry;

class ListIngredientsToolTest extends ToolTest {
	protected function setUp(): void {
		parent::setUp();
		$this->tool = new ListIngredientsTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function test_get_rest_config_returns_configuration(): void {
		$this->assertRestConfig( 'GET', '/ingredients' );
	}

	public function test_get_cli_config_returns_configuration(): void {
		$this->assertCliConfig( 'whiskey ingredients', 'ingredients' );
	}

	public function test_handle_logic_returns_ingredient_list(): void {
		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'all' )->willReturn(
			[
				'ingredient1' => 'Class1',
				'ingredient2' => 'Class2',
				'ingredient3' => 'Class3',
			]
		);

		$tool = new ListIngredientsTool( $this->recipes, $ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'ingredients', $result );
		$this->assertSame( [
			'ingredient1',
			'ingredient2',
			'ingredient3',
		], $result['ingredients'] );
	}

	public function test_handle_logic_returns_empty_array_when_no_ingredients(): void {
		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'all' )->willReturn( [] );

		$tool = new ListIngredientsTool( $this->recipes, $ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'ingredients', $result );
		$this->assertSame( [], $result['ingredients'] );
	}

	public function test_format_cli_output_with_ingredients(): void {
		// Test with ingredients - should not throw exception
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[
				[
					'ingredients' => [
						'ingredient1',
						'ingredient2',
					],
				],
			]
		);

		$this->assertTrue( true );
	}

	public function test_format_cli_output_with_empty_ingredients(): void {
		// Test with empty ingredients - should not throw exception
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[ [ 'ingredients' => [] ] ]
		);

		$this->assertTrue( true );
	}
}
