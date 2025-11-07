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

	// ===== Configuration Tests =====

	/**
	 * GIVEN ApplyRecipeTool
	 * WHEN getting REST configuration
	 * THEN should return correct endpoint and method
	 */
	public function test_get_rest_config_returns_configuration(): void {
		$this->assertRestConfig( 'POST', '/recipe/' );
	}

	/**
	 * GIVEN ApplyRecipeTool
	 * WHEN getting CLI configuration
	 * THEN should return correct command and argument
	 */
	public function test_get_cli_config_returns_configuration(): void {
		$this->assertCliConfig( 'whiskey apply', 'recipe' );
	}

	// ===== handle_logic() Error Cases =====

	/**
	 * GIVEN various invalid arguments
	 * WHEN handling logic
	 * THEN should throw exception with appropriate message
	 *
	 * @dataProvider handle_logic_error_provider
	 */
	public function test_handle_logic_throws_exception_for_errors(
		array $args,
		?array $recipe_config,
		?ValidationResult $validation_result,
		?ExecutionResult $execution_result,
		string $expected_exception_message
	): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$executor = $this->createStub( RecipeExecutor::class );
		if ( $validation_result ) {
			$executor->method( 'validate' )->willReturn( $validation_result );
		}

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( $expected_exception_message );

		$this->invoke_protected_method( $tool, 'handle_logic', [ $args ] );
	}

	public function handle_logic_error_provider(): array {
		return [
			'missing recipe name'   => [
				[],
				null,
				null,
				null,
				'Recipe name is required',
			],
			'recipe not found'      => [
				[ 'name' => 'nonexistent' ],
				null,
				null,
				null,
				'Recipe not found: nonexistent',
			],
			'invalid recipe config' => [
				[ 'name' => 'test-recipe' ],
				[ 'invalid' => 'config' ],
				ValidationResult::invalid_type( 'test' ),
				null,
				'Invalid type: expected test',
			],
			'execution failure'     => [
				[ 'name' => 'test-recipe' ],
				[ 'ingredient1' => 'value1' ],
				ValidationResult::valid(
					fn() => new ExecutionResult( false, 'Ingredient failed' )
				),
				new ExecutionResult( false, 'Ingredient failed' ),
				'Recipe execution failed: Ingredient failed',
			],
		];
	}

	// ===== handle_logic() Success Cases =====

	/**
	 * GIVEN valid recipe configuration and dry-run flag
	 * WHEN handling logic
	 * THEN should return validation result without executing
	 */
	public function test_handle_logic_returns_dry_run_result_when_flag_set(): void {
		$recipe_config = [ 'ingredient1' => 'value1' ];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$validation_result = ValidationResult::valid(
			fn() => new ExecutionResult( true, 'Success' )
		);

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( $validation_result );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [
			[
				'name'    => 'test-recipe',
				'dry-run' => true,
			],
		] );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'dry_run', $result );
		$this->assertTrue( $result['dry_run'] );
		$this->assertTrue( $result['valid'] );
		$this->assertSame( 'test-recipe', $result['name'] );
	}

	/**
	 * GIVEN valid recipe configuration
	 * WHEN executing recipe successfully
	 * THEN should return execution result with success data
	 */
	public function test_handle_logic_executes_recipe_successfully(): void {
		$recipe_config = [ 'ingredient1' => 'value1' ];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$execution_result = new ExecutionResult(
			true,
			'Recipe executed successfully',
			[ 'ingredient1' => [ 'success' => true, 'message' => 'Done' ] ]
		);

		$validation_result = ValidationResult::valid( fn() => $execution_result );

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( $validation_result );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'test-recipe' ] ] );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'test-recipe', $result['name'] );
		$this->assertSame( 'Recipe executed successfully', $result['message'] );
	}

	/**
	 * GIVEN positional argument for recipe name
	 * WHEN handling logic
	 * THEN should extract name from index 0
	 */
	public function test_handle_logic_extracts_name_from_positional_arg(): void {
		$recipe_config = [ 'ingredient1' => 'value1' ];

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$execution_result = new ExecutionResult( true, 'Success' );

		$validation_result = ValidationResult::valid( fn() => $execution_result );

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( $validation_result );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 0 => 'my-recipe' ] ] );

		$this->assertSame( 'my-recipe', $result['name'] );
	}

	// ===== format_rest_success() Tests =====

	/**
	 * GIVEN execution result data
	 * WHEN formatting REST success response
	 * THEN should return WP_REST_Response with correct structure
	 *
	 * @dataProvider format_rest_success_provider
	 */
	public function test_format_rest_success(
		array $data,
		bool $expected_success,
		string $expected_message,
		array $expected_data
	): void {
		$response = $this->invoke_protected_method( $this->tool, 'format_rest_success', [ $data ] );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertSame( $expected_success, $response_data['success'] );
		$this->assertSame( $expected_message, $response_data['message'] );
		$this->assertSame( $expected_data, $response_data['data'] );
	}

	public function format_rest_success_provider(): array {
		return [
			'complete data'           => [
				[
					'name'    => 'test-recipe',
					'success' => true,
					'message' => 'Recipe applied successfully',
					'data'    => [ 'key' => 'value' ],
				],
				true,
				'Recipe applied successfully',
				[ 'key' => 'value' ],
			],
			'missing optional fields' => [
				[ 'name' => 'test' ],
				true,
				'',
				[],
			],
		];
	}

	// ===== format_cli_output() Tests =====

	/**
	 * GIVEN execution result data
	 * WHEN formatting CLI output
	 * THEN should output appropriate messages
	 *
	 * @dataProvider format_cli_output_provider
	 */
	public function test_format_cli_output( array $data, array $expected_message_fragments ): void {
		$this->invoke_protected_method( $this->tool, 'format_cli_output', [ $data ] );

		$messages = \WP_CLI::get_log_messages();

		foreach ( $expected_message_fragments as $fragment ) {
			$this->assertContains( $fragment, $messages );
		}
	}

	/**
	 * GIVEN execution result with failed ingredient
	 * WHEN formatting CLI output
	 * THEN should display failure marker and message
	 */
	public function test_format_cli_output_displays_failed_ingredients(): void {
		$data = [
			'name'    => 'test-recipe',
			'message' => 'Recipe execution completed with errors',
			'data'    => [
				'successful_ingredient' => [
					'success' => true,
					'message' => 'Success message',
					'data'    => [],
				],
				'failed_ingredient'     => [
					'success' => false,
					'message' => 'Something went wrong',
					'data'    => [],
				],
			],
		];

		$this->invoke_protected_method( $this->tool, 'format_cli_output', [ $data ] );

		$messages = \WP_CLI::get_log_messages();

		// Should contain both success and failure markers
		$this->assertContains( '  ✓ successful_ingredient: Success message', $messages );
		$this->assertContains( '  ✗ failed_ingredient: Something went wrong', $messages );
	}

	public function format_cli_output_provider(): array {
		return [
			'dry run mode'         => [
				[
					'name'    => 'test-recipe',
					'dry_run' => true,
					'valid'   => true,
				],
				[ 'Recipe: test-recipe' ],
			],
			'successful execution' => [
				[
					'name'    => 'test-recipe',
					'message' => 'Recipe applied successfully',
					'data'    => [
						'ingredient1' => [
							'success' => true,
							'message' => 'Ingredient 1 executed',
							'data'    => [ 'key' => 'value' ],
						],
					],
				],
				[ 'Recipe: test-recipe', 'Executing recipe...' ],
			],
		];
	}

	// ===== format_ingredient_data() Tests =====

	/**
	 * GIVEN various data structures
	 * WHEN formatting ingredient data
	 * THEN should handle different structures without throwing
	 *
	 * @dataProvider format_ingredient_data_provider
	 */
	public function test_format_ingredient_data( array $data ): void {
		// Should not throw exception
		$this->invoke_protected_method( $this->tool, 'format_ingredient_data', [ $data, 2 ] );

		// Verify something was logged (basic smoke test)
		$messages = \WP_CLI::get_log_messages();
		$this->assertNotEmpty( $messages );
	}

	public function format_ingredient_data_provider(): array {
		return [
			'simple list'      => [
				[ 'items' => [ 'item1', 'item2', 'item3' ] ],
			],
			'nested structure' => [
				[
					'config' => [
						'setting1' => 'value1',
						'setting2' => 'value2',
					],
				],
			],
		];
	}

	// ===== is_simple_list() Tests =====

	/**
	 * GIVEN various array structures
	 * WHEN checking if array is simple list
	 * THEN should correctly identify simple vs complex arrays
	 *
	 * @dataProvider is_simple_list_provider
	 */
	public function test_is_simple_list( array $input, bool $expected ): void {
		$result = $this->invoke_protected_method( $this->tool, 'is_simple_list', [ $input ] );

		$this->assertSame( $expected, $result );
	}

	public function is_simple_list_provider(): array {
		return [
			'empty array is simple'           => [ [], true ],
			'scalar array is simple'          => [ [ 'a', 'b', 'c' ], true ],
			'associative array is not simple' => [ [ 'key' => 'value' ], false ],
			'nested array is not simple'      => [ [ [ 'nested' ] ], false ],
			'array with object is not simple' => [ [ new \stdClass() ], false ],
		];
	}
}
