<?php
/**
 * @covers \Whiskey\Tools\StatusTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Tools\StatusTool;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\RecipeExecutor;
use ReflectionClass;

class StatusToolTest extends WhiskeyTest {
	private StatusTool $tool;
	private RecipeRegistry $recipes;
	private IngredientRegistry $ingredients;
	private RecipeExecutor $executor;

	protected function setUp(): void {
		parent::setUp();
		$this->recipes = $this->createStub( RecipeRegistry::class );
		$this->ingredients = $this->createStub( IngredientRegistry::class );
		$this->executor = $this->createStub( RecipeExecutor::class );
		$this->tool = new StatusTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function testGetRestConfigReturnsConfiguration(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_rest_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'GET', $config['method'] );
		$this->assertSame( '/status', $config['path'] );
	}

	public function testGetCliConfigReturnsConfiguration(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_cli_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'whiskey status', $config['command'] );
		$this->assertStringContainsString( 'status', $config['synopsis'] );
	}

	public function testHandleLogicReturnsPhpVersion(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->tool, array() );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'php_version', $result );
		$this->assertSame( PHP_VERSION, $result['php_version'] );
	}

	public function testHandleLogicReturnsRecipeCount(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'all' )->willReturn( array(
			'recipe1' => array(),
			'recipe2' => array(),
		) );

		$tool = new StatusTool( $recipes, $this->ingredients, $this->executor );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array() );

		$this->assertArrayHasKey( 'recipes', $result );
		$this->assertSame( 2, $result['recipes'] );
	}

	public function testHandleLogicReturnsIngredientCount(): void {
		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'all' )->willReturn( array(
			'ing1' => 'Class1',
			'ing2' => 'Class2',
			'ing3' => 'Class3',
		) );

		$tool = new StatusTool( $this->recipes, $ingredients, $this->executor );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array() );

		$this->assertArrayHasKey( 'ingredients', $result );
		$this->assertSame( 3, $result['ingredients'] );
	}

	public function testFormatCliOutput(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		// Test with data - should not throw exception
		$method->invoke(
			$this->tool,
			array(
				'php_version' => '7.4.0',
				'recipes'     => 5,
				'ingredients' => 10,
			)
		);

		$this->assertTrue( true );
	}
}
