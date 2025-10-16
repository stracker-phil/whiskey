<?php
/**
 * @covers \Whiskey\Tools\ApplyRecipeTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Tools\ApplyRecipeTool;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\RecipeExecutor;
use Whiskey\ExecutionResult;
use Exception;
use ReflectionClass;

class ApplyRecipeToolTest extends WhiskeyTest {
	private ApplyRecipeTool $tool;
	private RecipeRegistry $recipes;
	private IngredientRegistry $ingredients;
	private RecipeExecutor $executor;

	protected function setUp(): void {
		parent::setUp();
		$this->recipes     = $this->createStub( RecipeRegistry::class );
		$this->ingredients = $this->createStub( IngredientRegistry::class );
		$this->executor    = $this->createStub( RecipeExecutor::class );
		$this->tool        = new ApplyRecipeTool( $this->recipes, $this->ingredients, $this->executor );
	}

	public function testGetRestConfigReturnsConfiguration(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_rest_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'POST', $config['method'] );
		$this->assertStringContainsString( '/recipe/', $config['path'] );
		$this->assertStringContainsString( '/apply', $config['path'] );
	}

	public function testGetCliConfigReturnsConfiguration(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'get_cli_config' );
		$method->setAccessible( true );

		$config = $method->invoke( $this->tool );

		$this->assertIsArray( $config );
		$this->assertSame( 'whiskey apply', $config['command'] );
		$this->assertStringContainsString( 'recipe', $config['synopsis'] );
	}

	public function testHandleLogicThrowsExceptionWhenNameMissing(): void {
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe name is required' );

		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$method->invoke( $this->tool, array() );
	}

	public function testHandleLogicThrowsExceptionWhenRecipeNotFound(): void {
		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( null );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $this->executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe not found: nonexistent' );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$method->invoke( $tool, array( 'name' => 'nonexistent' ) );
	}

	public function testHandleLogicThrowsExceptionWhenConfigInvalid(): void {
		$recipe_config = array( 'invalid' => 'config' );

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( false );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Invalid recipe configuration' );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$method->invoke( $tool, array( 'name' => 'test-recipe' ) );
	}

	public function testHandleLogicReturnsDryRunResultWhenFlagSet(): void {
		$recipe_config = array( 'ingredient1' => 'value1' );

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( true );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array( 'name' => 'test-recipe', 'dry-run' => true ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'dry_run', $result );
		$this->assertTrue( $result['dry_run'] );
		$this->assertTrue( $result['valid'] );
		$this->assertSame( 'test-recipe', $result['name'] );
	}

	public function testHandleLogicExecutesRecipeSuccessfully(): void {
		$recipe_config = array( 'ingredient1' => 'value1' );

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$execution_result = new ExecutionResult(
			true,
			'Recipe executed successfully',
			array( 'ingredient1' => array( 'success' => true, 'message' => 'Done' ) )
		);

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( true );
		$executor->method( 'execute' )->willReturn( $execution_result );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array( 'name' => 'test-recipe' ) );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['success'] );
		$this->assertSame( 'test-recipe', $result['name'] );
		$this->assertSame( 'Recipe executed successfully', $result['message'] );
	}

	public function testHandleLogicThrowsExceptionOnExecutionFailure(): void {
		$recipe_config = array( 'ingredient1' => 'value1' );

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$execution_result = new ExecutionResult( false, 'Ingredient failed' );

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( true );
		$executor->method( 'execute' )->willReturn( $execution_result );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Recipe execution failed: Ingredient failed' );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$method->invoke( $tool, array( 'name' => 'test-recipe' ) );
	}

	public function testHandleLogicExtractsNameFromPositionalArg(): void {
		$recipe_config = array( 'ingredient1' => 'value1' );

		$recipes = $this->createStub( RecipeRegistry::class );
		$recipes->method( 'get' )->willReturn( $recipe_config );

		$execution_result = new ExecutionResult( true, 'Success' );

		$executor = $this->createStub( RecipeExecutor::class );
		$executor->method( 'validate' )->willReturn( true );
		$executor->method( 'execute' )->willReturn( $execution_result );

		$tool = new ApplyRecipeTool( $recipes, $this->ingredients, $executor );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'handle_logic' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, array( 0 => 'my-recipe' ) );

		$this->assertSame( 'my-recipe', $result['name'] );
	}

	public function testFormatRestSuccessReturnsCustomFormat(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_rest_success' );
		$method->setAccessible( true );

		$data = array(
			'name'    => 'test-recipe',
			'success' => true,
			'message' => 'Recipe applied successfully',
			'data'    => array( 'key' => 'value' ),
		);

		$response = $method->invoke( $this->tool, $data );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$response_data = $response->get_data();
		$this->assertTrue( $response_data['success'] );
		$this->assertSame( 'Recipe applied successfully', $response_data['message'] );
		$this->assertArrayHasKey( 'data', $response_data );
	}

	public function testFormatRestSuccessHandlesMissingOptionalFields(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_rest_success' );
		$method->setAccessible( true );

		$data = array( 'name' => 'test' );

		$response      = $method->invoke( $this->tool, $data );
		$response_data = $response->get_data();

		$this->assertTrue( $response_data['success'] );
		$this->assertSame( '', $response_data['message'] );
		$this->assertSame( array(), $response_data['data'] );
	}

	public function testFormatCliOutputWithDryRunMode(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		$data = array(
			'name'    => 'test-recipe',
			'dry_run' => true,
			'valid'   => true,
		);

		// Should not throw exception
		$method->invoke( $this->tool, $data );

		$messages = \WP_CLI::get_log_messages();
		$this->assertContains( 'Recipe: test-recipe', $messages );
	}

	public function testFormatCliOutputWithSuccessfulExecution(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		$data = array(
			'name'    => 'test-recipe',
			'message' => 'Recipe applied successfully',
			'data'    => array(
				'ingredient1' => array(
					'success' => true,
					'message' => 'Ingredient 1 executed',
					'data'    => array( 'key' => 'value' ),
				),
				'ingredient2' => array(
					'success' => false,
					'message' => 'Ingredient 2 failed',
				),
			),
		);

		$method->invoke( $this->tool, $data );

		$messages = \WP_CLI::get_log_messages();
		$this->assertContains( 'Recipe: test-recipe', $messages );
		$this->assertContains( 'Executing recipe...', $messages );
	}

	public function testFormatIngredientDataWithSimpleList(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_ingredient_data' );
		$method->setAccessible( true );

		$data = array(
			'items' => array( 'item1', 'item2', 'item3' ),
		);

		// Should not throw exception
		$method->invoke( $this->tool, $data, 2 );

		$messages = \WP_CLI::get_log_messages();
		$this->assertNotEmpty( $messages );
	}

	public function testFormatIngredientDataWithNestedStructure(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'format_ingredient_data' );
		$method->setAccessible( true );

		$data = array(
			'config' => array(
				'setting1' => 'value1',
				'setting2' => 'value2',
			),
		);

		// Should not throw exception
		$method->invoke( $this->tool, $data, 2 );

		$this->assertTrue( true );
	}

	public function testIsSimpleListReturnsTrueForEmptyArray(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'is_simple_list' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->tool, array() );

		$this->assertTrue( $result );
	}

	public function testIsSimpleListReturnsTrueForScalarArray(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'is_simple_list' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->tool, array( 'a', 'b', 'c' ) );

		$this->assertTrue( $result );
	}

	public function testIsSimpleListReturnsFalseForAssociativeArray(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'is_simple_list' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->tool, array( 'key' => 'value' ) );

		$this->assertFalse( $result );
	}

	public function testIsSimpleListReturnsFalseForNestedArray(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'is_simple_list' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->tool, array( array( 'nested' ) ) );

		$this->assertFalse( $result );
	}

	public function testIsSimpleListReturnsFalseForObjectInArray(): void {
		$reflection = new ReflectionClass( $this->tool );
		$method     = $reflection->getMethod( 'is_simple_list' );
		$method->setAccessible( true );

		$result = $method->invoke( $this->tool, array( new \stdClass() ) );

		$this->assertFalse( $result );
	}
}
