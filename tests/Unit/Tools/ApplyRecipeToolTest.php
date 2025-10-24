<?php
/**
 * @covers \Whiskey\Tools\ApplyRecipeTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tools\ApplyRecipeTool;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\RecipeExecutor;
use Whiskey\ExecutionResult;
use Whiskey\ValidationResult;
use Exception;

class ApplyRecipeToolTest extends ToolTest {
	protected function setUp(): void {
		parent::setUp();
		$this->tool = new ApplyRecipeTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function test_get_rest_config_returns_configuration(): void {
		$this->assertRestConfig( 'POST', '/recipe/' );
	}

	public function test_get_cli_config_returns_configuration(): void {
		$this->assertCliConfig( 'whiskey apply', 'recipe' );
	}

	public function test_handle_logic_throws_exception_when_name_missing(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe name is required' );

		$this->invoke_protected_method( $this->tool, 'handle_logic', [ [] ] );
	}

	public function test_handle_logic_throws_exception_when_recipe_not_found(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( null );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $this->executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe not found: nonexistent' );

		$this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'nonexistent' ] ] );
	}

	public function test_handle_logic_throws_exception_when_config_invalid(): void {
		$recipe_config = [ 'invalid' => 'config' ];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( ValidationResult::invalid_type( 'test' ) );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid type: expected test' );

		$this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'test-recipe' ] ] );
	}

	public function test_handle_logic_returns_dry_run_result_when_flag_set(): void {
		$recipe_config = [ 'ingredient1' => 'value1' ];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( ValidationResult::valid() );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'test-recipe', 'dry-run' => true ] ] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'dry_run', $result );
		$this->assertTrue( $result['dry_run'] );
		$this->assertTrue( $result['valid'] );
		$this->assertSame( 'test-recipe', $result['name'] );
	}

	public function test_handle_logic_executes_recipe_successfully(): void {
		$recipe_config = [ 'ingredient1' => 'value1' ];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$execution_result = new ExecutionResult(
			true,
			'Recipe executed successfully',
			[ 'ingredient1' => [ 'success' => true, 'message' => 'Done' ] ]
		);

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( ValidationResult::valid() );
		$executor->method( 'execute' )->willReturn( $execution_result );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'test-recipe' ] ] );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'test-recipe', $result['name'] );
		$this->assertSame( 'Recipe executed successfully', $result['message'] );
	}

	public function test_handle_logic_throws_exception_on_execution_failure(): void {
		$recipe_config = [ 'ingredient1' => 'value1' ];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$execution_result = new ExecutionResult( false, 'Ingredient failed' );

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( ValidationResult::valid() );
		$executor->method( 'execute' )->willReturn( $execution_result );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe execution failed: Ingredient failed' );

		$this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'test-recipe' ] ] );
	}

	public function test_handle_logic_extracts_name_from_positional_arg(): void {
		$recipe_config = [ 'ingredient1' => 'value1' ];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$execution_result = new ExecutionResult( true, 'Success' );

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( ValidationResult::valid() );
		$executor->method( 'execute' )->willReturn( $execution_result );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 0 => 'my-recipe' ] ] );

		$this->assertSame( 'my-recipe', $result['name'] );
	}

	public function test_format_rest_success_returns_custom_format(): void {
		$data = [
			'name'    => 'test-recipe',
			'success' => true,
			'message' => 'Recipe applied successfully',
			'data'    => [ 'key' => 'value' ],
		];

		$response = $this->invoke_protected_method( $this->tool, 'format_rest_success', [ $data ] );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertTrue( $response_data['success'] );
		$this->assertSame( 'Recipe applied successfully', $response_data['message'] );
		$this->assertArrayHasKey( 'data', $response_data );
	}

	public function test_format_rest_success_handles_missing_optional_fields(): void {
		$data = [ 'name' => 'test' ];

		$response      = $this->invoke_protected_method( $this->tool, 'format_rest_success', [ $data ] );
		$response_data = $response->get_data();

		$this->assertTrue( $response_data['success'] );
		$this->assertSame( '', $response_data['message'] );
		$this->assertSame( [], $response_data['data'] );
	}

	public function test_format_cli_output_with_dry_run_mode(): void {
		$data = [
			'name'    => 'test-recipe',
			'dry_run' => true,
			'valid'   => true,
		];

		// Should not throw exception
		$this->invoke_protected_method( $this->tool, 'format_cli_output', [ $data ] );

		$messages = \WP_CLI::get_log_messages();
		$this->assertContains( 'Recipe: test-recipe', $messages );
	}

	public function test_format_cli_output_with_successful_execution(): void {
		$data = [
			'name'    => 'test-recipe',
			'message' => 'Recipe applied successfully',
			'data'    => [
				'ingredient1' => [
					'success' => true,
					'message' => 'Ingredient 1 executed',
					'data'    => [ 'key' => 'value' ],
				],
				'ingredient2' => [
					'success' => false,
					'message' => 'Ingredient 2 failed',
				],
			],
		];

		$this->invoke_protected_method( $this->tool, 'format_cli_output', [ $data ] );

		$messages = \WP_CLI::get_log_messages();
		$this->assertContains( 'Recipe: test-recipe', $messages );
		$this->assertContains( 'Executing recipe...', $messages );
	}

	public function test_format_ingredient_data_with_simple_list(): void {
		$data = [
			'items' => [ 'item1', 'item2', 'item3' ],
		];

		// Should not throw exception
		$this->invoke_protected_method( $this->tool, 'format_ingredient_data', [ $data, 2 ] );

		$messages = \WP_CLI::get_log_messages();
		$this->assertNotEmpty( $messages );
	}

	public function test_format_ingredient_data_with_nested_structure(): void {
		$data = [
			'config' => [
				'setting1' => 'value1',
				'setting2' => 'value2',
			],
		];

		// Should not throw exception
		$this->invoke_protected_method( $this->tool, 'format_ingredient_data', [ $data, 2 ] );

		$this->assertTrue( true );
	}

	public function test_is_simple_list_returns_true_for_empty_array(): void {
		$result = $this->invoke_protected_method( $this->tool, 'is_simple_list', [ [] ] );

		$this->assertTrue( $result );
	}

	public function test_is_simple_list_returns_true_for_scalar_array(): void {
		$result = $this->invoke_protected_method( $this->tool, 'is_simple_list', [ [ 'a', 'b', 'c' ] ] );

		$this->assertTrue( $result );
	}

	public function test_is_simple_list_returns_false_for_associative_array(): void {
		$result = $this->invoke_protected_method( $this->tool, 'is_simple_list', [ [ 'key' => 'value' ] ] );

		$this->assertFalse( $result );
	}

	public function test_is_simple_list_returns_false_for_nested_array(): void {
		$result = $this->invoke_protected_method( $this->tool, 'is_simple_list', [ [ [ 'nested' ] ] ] );

		$this->assertFalse( $result );
	}

	public function test_is_simple_list_returns_false_for_object_in_array(): void {
		$result = $this->invoke_protected_method( $this->tool, 'is_simple_list', [ [ new \stdClass() ] ] );

		$this->assertFalse( $result );
	}
}
