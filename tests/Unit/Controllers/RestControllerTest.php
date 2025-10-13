<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Controllers;

use Mockery;
use Mockery\MockInterface;
use WP_REST_Request;
use WP_REST_Response;
use Whiskey\HandlerFactory;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Controllers\RestController;
use Whiskey\Handlers\RecipeHandler;
use Whiskey\ExecutionResult;
use function Brain\Monkey\Functions\when;
use Whiskey\Tests\Unit\WhiskeyTest;

/**
 * @covers \Whiskey\Controllers\RestController
 */
final class RestControllerTest extends WhiskeyTest {
	private ?RestController $controller = null;

	/** @var MockInterface&\Whiskey\Registry\RecipeRegistry */
	private MockInterface $registry;

	/** @var MockInterface&HandlerFactory */
	private MockInterface $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->registry = Mockery::mock( RecipeRegistry::class );
		$this->factory  = Mockery::mock( HandlerFactory::class );

		$this->controller = new RestController(
			$this->registry,
			$this->factory
		);
	}

	/**
	 * GIVEN recipes exist in the system
	 * WHEN get_recipes endpoint is called
	 * THEN a successful response with all recipes should be returned
	 */
	public function testGetRecipesReturnsAllRecipes(): void {
		$recipes = [
			'recipe-one' => [ 'name' => 'recipe-one', 'type' => 'plugin' ],
			'recipe-two' => [ 'name' => 'recipe-two', 'type' => 'theme' ],
		];

		// Setup: allow registry to return data (don't verify it was called)
		$this->registry->allows( 'all' )->andReturn( $recipes );

		$response = $this->controller->get_recipes();

		// Assert ONLY on observable behavior (the response)
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( $recipes, $data['data'] );
	}

	/**
	 * GIVEN a recipe exists in the registry
	 * WHEN get_recipe is called with the recipe name
	 * THEN the recipe should be returned
	 * AND response status should be 200
	 */
	public function testGetRecipeReturnsExistingRecipe(): void {
		$recipe = [
			'name'   => 'my-recipe',
			'type'   => 'plugin',
			'config' => [ 'plugin' => 'test' ],
		];

		$this->registry->expects( 'get' )
			->with( 'my-recipe' )
			->andReturn( $recipe );

		$request = Mockery::mock( WP_REST_Request::class );
		$request->expects( 'get_param' )
			->with( 'name' )
			->andReturn( 'my-recipe' );

		$response = $this->controller->get_recipe( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( $recipe, $data['data'] );
	}

	/**
	 * GIVEN a recipe does not exist
	 * WHEN get_recipe is called with the recipe name
	 * THEN a 404 response should be returned
	 * AND contain an error message
	 */
	public function testGetRecipeReturns404ForMissingRecipe(): void {
		$this->registry->expects( 'get' )
			->with( 'missing-recipe' )
			->andReturnNull();

		$request = Mockery::mock( WP_REST_Request::class );
		$request->expects( 'get_param' )
			->with( 'name' )
			->andReturn( 'missing-recipe' );

		$response = $this->controller->get_recipe( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertStringContainsString( 'Recipe not found: missing-recipe', $data['message'] );
	}

	/**
	 * GIVEN a recipe exists with valid configuration
	 * WHEN apply_recipe is called
	 * THEN the recipe should execute successfully
	 * AND return a success response
	 */
	public function testApplyRecipeExecutesHandlerSuccessfully(): void {
		$recipeData = [
			'type'   => 'plugin',
			'config' => [ 'plugin' => 'test-plugin' ],
		];

		$executionResult = Mockery::mock( ExecutionResult::class );
		$executionResult->allows( 'to_array' )->andReturn( [
			'success' => true,
			'message' => 'Recipe applied successfully',
		] );

		$handler = Mockery::mock( RecipeHandler::class );
		// Use allows() - we don't care about the orchestration
		$handler->allows( 'validate' )->andReturnTrue();
		$handler->allows( 'execute' )->andReturn( $executionResult );

		$this->registry->allows( 'get' )->andReturn( $recipeData );
		$this->factory->allows( 'get_handler' )->andReturn( $handler );

		$request = Mockery::mock( WP_REST_Request::class );
		$request->allows( 'get_param' )->with( 'name' )->andReturn( 'test-recipe' );

		$response = $this->controller->apply_recipe( $request );

		// Test ONLY the response (observable behavior)
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'Recipe applied successfully', $data['message'] );
	}

	/**
	 * GIVEN a recipe does not exist
	 * WHEN apply_recipe is called
	 * THEN a 404 response should be returned
	 */
	public function testApplyRecipeReturns404WhenRecipeNotFound(): void {
		$this->registry->expects( 'get' )
			->with( 'missing-recipe' )
			->andReturnNull();

		$request = Mockery::mock( WP_REST_Request::class );
		$request->expects( 'get_param' )
			->with( 'name' )
			->andReturn( 'missing-recipe' );

		$response = $this->controller->apply_recipe( $request );

		$this->assertSame( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertStringContainsString( 'Recipe not found', $data['message'] );
	}

	/**
	 * GIVEN a recipe with an unknown type
	 * WHEN apply_recipe is called
	 * THEN a 400 response should be returned
	 */
	public function testApplyRecipeReturns400WhenHandlerNotFound(): void {
		$recipeData = [
			'type'   => 'unknown-type',
			'config' => [ 'test' => 'value' ],
		];

		$this->registry->expects( 'get' )
			->with( 'test-recipe' )
			->andReturn( $recipeData );

		$this->factory->expects( 'get_handler' )
			->with( 'unknown-type' )
			->andReturnNull();

		$request = Mockery::mock( WP_REST_Request::class );
		$request->expects( 'get_param' )
			->with( 'name' )
			->andReturn( 'test-recipe' );

		$response = $this->controller->apply_recipe( $request );

		$this->assertSame( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertStringContainsString( 'Unknown recipe type', $data['message'] );
	}

	/**
	 * GIVEN a recipe with invalid configuration
	 * WHEN apply_recipe is called
	 * THEN a 400 response should be returned
	 */
	public function testApplyRecipeReturns400WhenValidationFails(): void {
		$recipeData = [
			'type'   => 'plugin',
			'config' => [ 'invalid' => 'config' ],
		];

		$handler = Mockery::mock( RecipeHandler::class );
		$handler->expects( 'validate' )
			->with( $recipeData['config'] )
			->andReturnFalse();

		$this->registry->expects( 'get' )
			->with( 'test-recipe' )
			->andReturn( $recipeData );

		$this->factory->expects( 'get_handler' )
			->with( 'plugin' )
			->andReturn( $handler );

		$request = Mockery::mock( WP_REST_Request::class );
		$request->expects( 'get_param' )
			->with( 'name' )
			->andReturn( 'test-recipe' );

		$response = $this->controller->apply_recipe( $request );

		$this->assertSame( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertSame( 'Invalid recipe configuration', $data['message'] );
	}

	/**
	 * GIVEN the controller is initialized
	 * WHEN get_status is called
	 * THEN plugin information should be returned
	 */
	public function testGetStatusReturnsPluginInfo(): void {
		$response = $this->controller->get_status();

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'Whiskey', $data['plugin'] );
		$this->assertSame( '1.0.0', $data['version'] );
		$this->assertSame( PHP_VERSION, $data['php_version'] );
	}

	/**
	 * GIVEN a user has manage_options capability
	 * WHEN permission_callback is called
	 * THEN it should return true
	 */
	public function testPermissionCallbackReturnsTrueForAdmin(): void {
		when( 'current_user_can' )->justReturn( true );

		$result = $this->controller->permission_callback();

		$this->assertTrue( $result );
	}

	/**
	 * GIVEN a user does NOT have manage_options capability
	 * WHEN permission_callback is called
	 * THEN it should return false
	 */
	public function testPermissionCallbackReturnsFalseForNonAdmin(): void {
		when( 'current_user_can' )->justReturn( false );

		$result = $this->controller->permission_callback();

		$this->assertFalse( $result );
	}
}
