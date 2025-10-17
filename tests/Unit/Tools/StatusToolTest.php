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

	public function testGetRestConfigReturnsConfiguration(): void {
		$this->assert_rest_config( 'GET', '/status' );
	}

	public function testGetCliConfigReturnsConfiguration(): void {
		$this->assert_cli_config( 'whiskey status', 'status' );
	}

	public function testHandleLogicReturnsPhpVersion(): void {
		$result = $this->invoke_protected_method( $this->tool, 'handle_logic', [ [] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'php_version', $result );
		$this->assertSame( PHP_VERSION, $result['php_version'] );
	}

	public function testHandleLogicReturnsRecipeCount(): void {
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

	public function testHandleLogicReturnsIngredientCount(): void {
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

	public function testFormatCliOutput(): void {
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
