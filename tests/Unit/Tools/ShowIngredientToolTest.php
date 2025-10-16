<?php
/**
 * @covers \Whiskey\Tools\ShowIngredientTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Tools\ShowIngredientTool;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\RecipeExecutor;
use Exception;

class ShowIngredientToolTest extends WhiskeyTest {
	private ShowIngredientTool $tool;
	private RecipeRegistry $recipes;
	private IngredientRegistry $ingredients;
	private RecipeExecutor $executor;

	protected function setUp(): void {
		parent::setUp();
		$this->recipes     = $this->createStub( RecipeRegistry::class );
		$this->ingredients = $this->createStub( IngredientRegistry::class );
		$this->executor    = $this->createStub( RecipeExecutor::class );
		$this->tool        = new ShowIngredientTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function testGetRestConfigReturnsConfiguration(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_rest_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'GET', $config['method'] );
		$this->assertStringContainsString( '/ingredient/', $config['path'] );
	}

	public function testGetCliConfigReturnsConfiguration(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_cli_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'whiskey ingredient', $config['command'] );
		$this->assertStringContainsString( 'ingredient', $config['synopsis'] );
	}

	public function testHandleLogicReturnsIngredientMetadata(): void {
		$metadata = array(
			'category'    => 'wordpress',
			'description' => 'Test ingredient description',
		);

		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'get_metadata' )->willReturn( $metadata );

		$tool = new ShowIngredientTool( $this->recipes, $ingredients, $this->executor );

		$reflection = new \ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array( 'name' => 'test-ingredient' ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'category', $result );
		$this->assertArrayHasKey( 'description', $result );
		$this->assertSame( 'test-ingredient', $result['name'] );
		$this->assertSame( 'wordpress', $result['category'] );
		$this->assertSame( 'Test ingredient description', $result['description'] );
	}

	public function testHandleLogicExtractsNameFromPositionalArg(): void {
		$metadata = array(
			'category'    => 'wordpress',
			'description' => 'Test',
		);

		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'get_metadata' )->willReturn( $metadata );

		$tool = new ShowIngredientTool( $this->recipes, $ingredients, $this->executor );

		$reflection = new \ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array( 0 => 'my-ingredient' ) );

		$this->assertSame( 'my-ingredient', $result['name'] );
	}

	public function testHandleLogicThrowsExceptionWhenNameMissing(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Ingredient name is required' );

		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$method->invoke( $this->tool, array() );
	}

	public function testHandleLogicThrowsExceptionWhenIngredientNotFound(): void {
		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'get_metadata' )->willReturn( array() );

		$tool = new ShowIngredientTool( $this->recipes, $ingredients, $this->executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Ingredient not found: nonexistent' );

		$reflection = new \ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$method->invoke( $tool, array( 'name' => 'nonexistent' ) );
	}

	public function testExtractCliArgsIncludesFormatParameter(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'extract_cli_args' );
		$method->setAccessible( true );

		$result = $method->invoke(
			$this->tool,
			array( 'test-ingredient' ),
			array( 'format' => 'json' )
		);

		$this->assertArrayHasKey( 'format', $result );
		$this->assertSame( 'json', $result['format'] );
	}

	public function testExtractCliArgsDefaultsToTableFormat(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'extract_cli_args' );
		$method->setAccessible( true );

		$result = $method->invoke(
			$this->tool,
			array( 'test-ingredient' ),
			array()
		);

		$this->assertArrayHasKey( 'format', $result );
		$this->assertSame( 'table', $result['format'] );
	}

	public function testFormatCliOutputWithTableFormat(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		// Test with table format - should not throw exception
		$method->invoke(
			$this->tool,
			array(
				'format'      => 'table',
				'name'        => 'test-ingredient',
				'category'    => 'wordpress',
				'description' => 'Test description',
			)
		);

		$this->assertTrue( true );
	}

	public function testFormatCliOutputWithJsonFormat(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		// Test with json format - should call WP_CLI\Utils\format_items
		$method->invoke(
			$this->tool,
			array(
				'format'      => 'json',
				'name'        => 'test-ingredient',
				'category'    => 'wordpress',
				'description' => 'Test description',
			)
		);

		$this->assertTrue( true );
	}

	public function testFormatCliOutputWithYamlFormat(): void {
		$reflection = new \ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		// Test with yaml format - should call WP_CLI\Utils\format_items
		$method->invoke(
			$this->tool,
			array(
				'format'      => 'yaml',
				'name'        => 'test-ingredient',
				'category'    => 'wordpress',
				'description' => 'Test description',
			)
		);

		$this->assertTrue( true );
	}
}
