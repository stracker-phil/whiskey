<?php
/**
 * @covers \Whiskey\Main
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\Main;
use Whiskey\Controllers\RestController;
use Whiskey\Controllers\CliController;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;

class MainTest extends WhiskeyTest {

	public function testConstructorCreatesInstance(): void {
		$recipes     = $this->createStub( RecipeRegistry::class );
		$ingredients = $this->createStub( IngredientRegistry::class );
		$rest        = $this->createStub( RestController::class );
		$cli         = $this->createStub( CliController::class );

		$main = new Main( $recipes, $ingredients, $rest, $cli );

		$this->assertInstanceOf( Main::class, $main );
	}

	public function testInitHookInitializesRegistries(): void {
		$recipes     = $this->createMock( RecipeRegistry::class );
		$ingredients = $this->createMock( IngredientRegistry::class );
		$rest        = $this->createStub( RestController::class );
		$cli         = $this->createStub( CliController::class );

		$ingredients->expects( $this->once() )->method( 'init' );
		$recipes->expects( $this->once() )->method( 'init' );

		new Main( $recipes, $ingredients, $rest, $cli );

		// Fire the init hook - should execute the closure
		do_action( 'init' );
	}

	public function testRestApiInitHookRegistersRoutes(): void {
		$recipes     = $this->createStub( RecipeRegistry::class );
		$ingredients = $this->createStub( IngredientRegistry::class );
		$rest        = $this->createMock( RestController::class );
		$cli         = $this->createStub( CliController::class );

		$rest->expects( $this->once() )->method( 'register_routes' );

		new Main( $recipes, $ingredients, $rest, $cli );

		do_action( 'rest_api_init' );
	}

	public function testCliInitHookRegistersCommands(): void {
		$recipes     = $this->createStub( RecipeRegistry::class );
		$ingredients = $this->createStub( IngredientRegistry::class );
		$rest        = $this->createStub( RestController::class );
		$cli         = $this->createMock( CliController::class );

		$cli->expects( $this->once() )->method( 'register_commands' );

		new Main( $recipes, $ingredients, $rest, $cli );

		do_action( 'cli_init' );
	}
}

