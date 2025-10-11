<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Mockery;
use Mockery\MockInterface;
use WP_REST_Request;
use WP_REST_Response;
use Whiskey\HandlerFactory;
use Whiskey\RecipeRegistry;
use Whiskey\RestController;
use Whiskey\RecipeHandlerInterface;
use Whiskey\ExecutionResult;

/**
 * @covers RestController
 */
final class RestControllerTest extends WhiskeyTest {
	private ?RestController $controller = null;

	/** @var MockInterface&RecipeRegistry */
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
	 * GIVEN a registered recipe with valid configuration
	 * WHEN the recipe is applied via REST API
	 * THEN the handler should execute successfully
	 * AND return the execution result
	 */
	public function testApplyRecipeExecutesHandlerSuccessfully(): void {
		$recipeName = 'test-recipe';
		$recipeData = [
			'type'   => 'plugin',
			'config' => [ 'plugin' => 'test-plugin' ],
		];

		$executionResult = Mockery::mock( ExecutionResult::class );
		$executionResult->allows( 'to_array' )->andReturn( [
			'success' => true,
			'message' => 'Recipe applied successfully',
		] );

		$handler = Mockery::mock( RecipeHandlerInterface::class );
		$handler->expects( 'validate' )
			->with( $recipeData['config'] )
			->andReturnTrue();
		$handler->expects( 'execute' )
			->with( $recipeData['config'] )
			->andReturn( $executionResult );

		$this->registry->expects( 'get' )
			->with( $recipeName )
			->andReturn( $recipeData );

		$this->factory->expects( 'get_handler' )
			->with( 'plugin' )
			->andReturn( $handler );

		$request = Mockery::mock( WP_REST_Request::class );
		$request->expects( 'get_param' )
			->with( 'name' )
			->andReturn( $recipeName );

		$response = $this->controller->apply_recipe( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( 'Recipe applied successfully', $data['message'] );
	}
}
