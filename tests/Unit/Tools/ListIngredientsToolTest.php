<?php
/**
 * @covers \Whiskey\Tools\ListIngredientsTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tools\ListIngredientsTool;
use Whiskey\Registry\IngredientRegistry;

class ListIngredientsToolTest extends ToolTest {
	private ListIngredientsTool $tool;

	protected function setUp(): void {
		parent::setUp();
		$this->tool = new ListIngredientsTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function testGetRestConfigReturnsConfiguration(): void {
		$config = $this->invoke_protected_method( $this->tool, 'get_rest_config' );

		$this->assertIsArray( $config );
		$this->assertSame( 'GET', $config['method'] );
		$this->assertSame( '/ingredients', $config['path'] );
	}

	public function testGetCliConfigReturnsConfiguration(): void {
		$config = $this->invoke_protected_method( $this->tool, 'get_cli_config' );

		$this->assertIsArray( $config );
		$this->assertSame( 'whiskey ingredients', $config['command'] );
		$this->assertStringContainsString( 'ingredients', $config['synopsis'] );
	}

	public function testHandleLogicReturnsIngredientList(): void {
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

	public function testHandleLogicReturnsEmptyArrayWhenNoIngredients(): void {
		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'all' )->willReturn( [] );

		$tool = new ListIngredientsTool( $this->recipes, $ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'ingredients', $result );
		$this->assertSame( [], $result['ingredients'] );
	}

	public function testFormatCliOutputWithIngredients(): void {
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

	public function testFormatCliOutputWithEmptyIngredients(): void {
		// Test with empty ingredients - should not throw exception
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[ [ 'ingredients' => [] ] ]
		);

		$this->assertTrue( true );
	}
}
