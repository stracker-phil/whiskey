<?php
/**
 * @covers \Whiskey\Tools\ShowRecipeTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Tools\ShowRecipeTool;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\RecipeExecutor;
use Exception;

class ShowRecipeToolTest extends WhiskeyTest {
	private ShowRecipeTool $tool;
	private RecipeRegistry $recipes;
	private IngredientRegistry $ingredients;
	private RecipeExecutor $executor;

	protected function setUp(): void {
		parent::setUp();
		$this->recipes     = $this->createStub( RecipeRegistry::class );
		$this->ingredients = $this->createStub( IngredientRegistry::class );
		$this->executor    = $this->createStub( RecipeExecutor::class );
		$this->tool        = new ShowRecipeTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function testGetRestConfigReturnsConfiguration(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_rest_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'GET', $config['method'] );
		$this->assertSame( '/recipe/(?P<name>[a-zA-Z0-9-_]+)', $config['path'] );
	}

	public function testGetCliConfigReturnsConfiguration(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_cli_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'whiskey recipe', $config['command'] );
		$this->assertStringContainsString( 'recipe', $config['synopsis'] );
	}

	public function testHandleLogicReturnsRecipeData(): void {
		$recipe_config = array(
			'ingredient1' => 'value1',
			'ingredient2' => 'value2',
		);

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$tool = new ShowRecipeTool( $recipes, $this->ingredients, $this->executor );

		$reflection = new \ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array( 'name' => 'test-recipe' ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'config', $result );
		$this->assertSame( 'test-recipe', $result['name'] );
		$this->assertSame( $recipe_config, $result['config'] );
	}

	public function testHandleLogicExtractsNameFromPositionalArg(): void {
		$recipe_config = array( 'ingredient1' => 'value1' );

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$tool = new ShowRecipeTool( $recipes, $this->ingredients, $this->executor );

		$reflection = new \ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array( 0 => 'my-recipe' ) );

		$this->assertSame( 'my-recipe', $result['name'] );
	}

	public function testHandleLogicThrowsExceptionWhenNameMissing(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe name is required' );

		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$method->invoke( $this->tool, array() );
	}

	public function testHandleLogicThrowsExceptionWhenRecipeNotFound(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( null );

		$tool = new ShowRecipeTool( $recipes, $this->ingredients, $this->executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe not found: nonexistent' );

		$reflection = new \ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$method->invoke( $tool, array( 'name' => 'nonexistent' ) );
	}

	public function testFormatCliOutput(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		// Test with data - should not throw exception
		$method->invoke(
			$this->tool,
			array(
				'name'   => 'test-recipe',
				'config' => array(
					'ingredient1' => 'value1',
					'ingredient2' => array( 'nested' => 'value' ),
				),
			)
		);

		$this->assertTrue( true );
	}
}
