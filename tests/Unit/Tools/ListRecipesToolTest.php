<?php
/**
 * @covers \Whiskey\Tools\ListRecipesTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Tools\ListRecipesTool;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\RecipeExecutor;

class ListRecipesToolTest extends WhiskeyTest {
	private ListRecipesTool $tool;
	private RecipeRegistry $recipes;
	private IngredientRegistry $ingredients;
	private RecipeExecutor $executor;

	protected function setUp(): void {
		parent::setUp();
		$this->recipes     = $this->createStub( RecipeRegistry::class );
		$this->ingredients = $this->createStub( IngredientRegistry::class );
		$this->executor    = $this->createStub( RecipeExecutor::class );
		$this->tool        = new ListRecipesTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function testGetRestConfigReturnsConfiguration(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_rest_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'GET', $config['method'] );
		$this->assertSame( '/recipes', $config['path'] );
	}

	public function testGetCliConfigReturnsConfiguration(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_cli_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'whiskey recipes', $config['command'] );
		$this->assertStringContainsString( 'recipes', $config['synopsis'] );
	}

	public function testHandleLogicReturnsRecipeList(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'all' )->willReturn(
			array(
				'recipe1' => array( 'ingredient1' => 'value1' ),
				'recipe2' => array( 'ingredient2' => 'value2' ),
				'recipe3' => array( 'ingredient3' => 'value3' ),
			)
		);

		$tool = new ListRecipesTool( $recipes, $this->ingredients, $this->executor );

		$reflection = new \ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'recipes', $result );
		$this->assertSame( array( 'recipe1', 'recipe2', 'recipe3' ), $result['recipes'] );
	}

	public function testHandleLogicReturnsEmptyArrayWhenNoRecipes(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'all' )->willReturn( array() );

		$tool = new ListRecipesTool( $recipes, $this->ingredients, $this->executor );

		$reflection = new \ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'recipes', $result );
		$this->assertSame( array(), $result['recipes'] );
	}
}
