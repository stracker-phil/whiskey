<?php
/**
 * @covers \Whiskey\Tools\ListIngredientsTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Tools\ListIngredientsTool;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\RecipeExecutor;
use ReflectionClass;

class ListIngredientsToolTest extends WhiskeyTest {
	private ListIngredientsTool $tool;
	private RecipeRegistry $recipes;
	private IngredientRegistry $ingredients;
	private RecipeExecutor $executor;

	protected function setUp(): void {
		parent::setUp();
		$this->recipes     = $this->createStub( RecipeRegistry::class );
		$this->ingredients = $this->createStub( IngredientRegistry::class );
		$this->executor    = $this->createStub( RecipeExecutor::class );
		$this->tool        = new ListIngredientsTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function testGetRestConfigReturnsConfiguration(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_rest_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'GET', $config['method'] );
		$this->assertSame( '/ingredients', $config['path'] );
	}

	public function testGetCliConfigReturnsConfiguration(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_cli_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

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

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, [] );

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

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, [] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'ingredients', $result );
		$this->assertSame( [], $result['ingredients'] );
	}

	public function testFormatCliOutputWithIngredients(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		// Test with ingredients - should not throw exception
		$method->invoke( $this->tool, [
			'ingredients' => [
				'ingredient1',
				'ingredient2',
			],
		] );

		$this->assertTrue( true );
	}

	public function testFormatCliOutputWithEmptyIngredients(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		// Test with empty ingredients - should not throw exception
		$method->invoke( $this->tool, [ 'ingredients' => [] ] );

		$this->assertTrue( true );
	}
}
