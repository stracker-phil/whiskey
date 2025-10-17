<?php
/**
 * @covers \Whiskey\Tools\ShowIngredientTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tools\ShowIngredientTool;
use Whiskey\Registry\IngredientRegistry;
use Exception;

class ShowIngredientToolTest extends ToolTest {
	protected function setUp(): void {
		parent::setUp();
		$this->tool = new ShowIngredientTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function test_get_rest_config_returns_configuration(): void {
		$this->assertRestConfig( 'GET', '/ingredient/' );
	}

	public function test_get_cli_config_returns_configuration(): void {
		$this->assertCliConfig( 'whiskey ingredient', 'ingredient' );
	}

	public function test_handle_logic_returns_ingredient_metadata(): void {
		$metadata = [
			'category'    => 'wordpress',
			'description' => 'Test ingredient description',
		];

		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'get_metadata' )->willReturn( $metadata );

		$tool = new ShowIngredientTool( $this->recipes, $ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'test-ingredient' ] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'name', $result );
		$this->assertArrayHasKey( 'category', $result );
		$this->assertArrayHasKey( 'description', $result );
		$this->assertSame( 'test-ingredient', $result['name'] );
		$this->assertSame( 'wordpress', $result['category'] );
		$this->assertSame( 'Test ingredient description', $result['description'] );
	}

	public function test_handle_logic_extracts_name_from_positional_arg(): void {
		$metadata = [
			'category'    => 'wordpress',
			'description' => 'Test',
		];

		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'get_metadata' )->willReturn( $metadata );

		$tool = new ShowIngredientTool( $this->recipes, $ingredients, $this->executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 0 => 'my-ingredient' ] ] );

		$this->assertSame( 'my-ingredient', $result['name'] );
	}

	public function test_handle_logic_throws_exception_when_name_missing(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Ingredient name is required' );

		$this->invoke_protected_method( $this->tool, 'handle_logic', [ [] ] );
	}

	public function test_handle_logic_throws_exception_when_ingredient_not_found(): void {
		$ingredients = $this->createStub( IngredientRegistry::class );
		$ingredients->method( 'get_metadata' )->willReturn( [] );

		$tool = new ShowIngredientTool( $this->recipes, $ingredients, $this->executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Ingredient not found: nonexistent' );

		$this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'nonexistent' ] ] );
	}

	public function test_extract_cli_args_includes_format_parameter(): void {
		$result = $this->invoke_protected_method(
			$this->tool,
			'extract_cli_args',
			[
				[ 'test-ingredient' ],
				[ 'format' => 'json' ],
			]
		);

		$this->assertArrayHasKey( 'format', $result );
		$this->assertSame( 'json', $result['format'] );
	}

	public function test_extract_cli_args_defaults_to_table_format(): void {
		$result = $this->invoke_protected_method(
			$this->tool,
			'extract_cli_args',
			[
				[ 'test-ingredient' ],
				[],
			]
		);

		$this->assertArrayHasKey( 'format', $result );
		$this->assertSame( 'table', $result['format'] );
	}

	public function test_format_cli_output_with_table_format(): void {
		// Test with table format - should not throw exception
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[
				[
					'format'      => 'table',
					'name'        => 'test-ingredient',
					'category'    => 'wordpress',
					'description' => 'Test description',
				],
			]
		);

		$this->assertTrue( true );
	}

	public function test_format_cli_output_with_json_format(): void {
		// Test with json format - should call WP_CLI\Utils\format_items
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[
				[
					'format'      => 'json',
					'name'        => 'test-ingredient',
					'category'    => 'wordpress',
					'description' => 'Test description',
				],
			]
		);

		$this->assertTrue( true );
	}

	public function test_format_cli_output_with_yaml_format(): void {
		// Test with yaml format - should call WP_CLI\Utils\format_items
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[
				[
					'format'      => 'yaml',
					'name'        => 'test-ingredient',
					'category'    => 'wordpress',
					'description' => 'Test description',
				],
			]
		);

		$this->assertTrue( true );
	}
}
