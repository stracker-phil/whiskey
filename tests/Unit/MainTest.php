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
use Mockery;

class MainTest extends WhiskeyTest {

	public function testRegisterRestRoutesCallsController(): void {
		$recipes     = Mockery::mock( RecipeRegistry::class );
		$ingredients = Mockery::mock( IngredientRegistry::class );
		$rest        = Mockery::mock( RestController::class );
		$cli         = Mockery::mock( CliController::class );

		$rest->shouldReceive( 'register_routes' )->once();

		$main = new Main( $recipes, $ingredients, $rest, $cli );
		$main->register_rest_routes();

		$this->assertedByMockery();
	}

	public function testRegisterCliCommandsCallsController(): void {
		$recipes     = Mockery::mock( RecipeRegistry::class );
		$ingredients = Mockery::mock( IngredientRegistry::class );
		$rest        = Mockery::mock( RestController::class );
		$cli         = Mockery::mock( CliController::class );

		$cli->shouldReceive( 'register_commands' )->once();

		$main = new Main( $recipes, $ingredients, $rest, $cli );
		$main->register_cli_commands();

		$this->assertedByMockery();
	}

	public function testRegisterComponentsInitializesIngredientRegistry(): void {
		$recipes     = Mockery::mock( RecipeRegistry::class );
		$ingredients = Mockery::mock( IngredientRegistry::class );
		$rest        = Mockery::mock( RestController::class );
		$cli         = Mockery::mock( CliController::class );

		$ingredients->shouldReceive( 'init' )->once();
		$recipes->shouldReceive( 'init' )->once();

		$main = new Main( $recipes, $ingredients, $rest, $cli );
		$main->register_components();

		$this->assertedByMockery();
	}

	public function testRegisterComponentsInitializesRecipeRegistry(): void {
		$recipes     = Mockery::mock( RecipeRegistry::class );
		$ingredients = Mockery::mock( IngredientRegistry::class );
		$rest        = Mockery::mock( RestController::class );
		$cli         = Mockery::mock( CliController::class );

		$ingredients->shouldReceive( 'init' )->once();
		$recipes->shouldReceive( 'init' )->once();

		$main = new Main( $recipes, $ingredients, $rest, $cli );
		$main->register_components();

		$this->assertedByMockery();
	}

	public function testRegisterComponentsInitializesRegistriesInCorrectOrder(): void {
		$recipes     = Mockery::mock( RecipeRegistry::class );
		$ingredients = Mockery::mock( IngredientRegistry::class );
		$rest        = Mockery::mock( RestController::class );
		$cli         = Mockery::mock( CliController::class );

		$ingredients->shouldReceive( 'init' )->once()->globally()->ordered();
		$recipes->shouldReceive( 'init' )->once()->globally()->ordered();

		$main = new Main( $recipes, $ingredients, $rest, $cli );
		$main->register_components();

		$this->assertedByMockery();
	}
}
