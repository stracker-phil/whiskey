<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Mockery;
use Mockery\MockInterface;
use Whiskey\Main;
use Whiskey\RecipeRegistry;
use Whiskey\Controllers\RestController;

/**
 * @covers Main
 */
final class MainTest extends WhiskeyTest {
	/** @var MockInterface&RecipeRegistry */
	private MockInterface $registry;

	/** @var MockInterface&RestController */
	private MockInterface $restController;

	private ?Main $main = null;

	protected function setUp(): void {
		parent::setUp();

		$this->registry       = Mockery::mock( RecipeRegistry::class );
		$this->restController = Mockery::mock( RestController::class );
		defined( 'WHISKEY_RECIPES_DIR' ) || define( 'WHISKEY_RECIPES_DIR', '' );

		$this->main = new Main( $this->registry, $this->restController );
	}

	/**
	 * GIVEN Main is initialized
	 * WHEN init() is called
	 * THEN WordPress hooks should be registered
	 * AND components should initialize on 'init'
	 * AND REST routes should register on 'rest_api_init'
	 */
	public function testInitRegistersWordPressHooks(): void {
		$this->expectAdded( 'init' )
			->once()
			->with( [ $this->main, 'register_components' ] );

		$this->expectAdded( 'rest_api_init' )
			->once()
			->with( [ $this->main, 'register_rest_routes' ] );

		$this->main->init();
	}

	/**
	 * GIVEN the recipe registry is configured
	 * WHEN register_components is called
	 * THEN the registry should initialize
	 */
	public function testRegisterComponentsDelegatesToRegistry(): void {
		$this->registry->expects( 'init' )
			->once();

		$this->main->register_components();
		$this->assertedByMockery();
	}

	/**
	 * GIVEN the REST controller is configured
	 * WHEN register_rest_routes is called
	 * THEN the controller should register its routes
	 */
	public function testRegisterRestRoutesDelegatesToController(): void {
		$this->restController->expects( 'register_routes' )
			->once();

		$this->main->register_rest_routes();
		$this->assertedByMockery();
	}
}
