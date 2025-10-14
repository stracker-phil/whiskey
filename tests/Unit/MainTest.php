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
use Mockery\MockInterface;

class MainTest extends WhiskeyTest {

	/** @var MockInterface&RecipeRegistry */
	private MockInterface $recipes;

	/** @var MockInterface&IngredientRegistry */
	private MockInterface $ingredients;

	/** @var MockInterface&RestController */
	private MockInterface $rest;

	/** @var MockInterface&CliController */
	private MockInterface $cli;

	private Main $main;

	protected function setUp(): void {
		parent::setUp();

		$this->recipes     = Mockery::mock( RecipeRegistry::class );
		$this->ingredients = Mockery::mock( IngredientRegistry::class );
		$this->rest        = Mockery::mock( RestController::class );
		$this->cli         = Mockery::mock( CliController::class );

		$this->main = new Main(
			$this->recipes,
			$this->ingredients,
			$this->rest,
			$this->cli
		);
	}

	public function testRegisterRestRoutesCallsController(): void {
		$this->rest->shouldReceive( 'register_routes' )->once();

		$this->main->register_rest_routes();

		$this->assertedByMockery();
	}

	public function testRegisterCliCommandsCallsController(): void {
		$this->cli->shouldReceive( 'register_commands' )->once();

		$this->main->register_cli_commands();

		$this->assertedByMockery();
	}

	public function testRegisterComponentsInitializesIngredientRegistry(): void {
		$this->ingredients->shouldReceive( 'init' )->once();
		$this->recipes->shouldReceive( 'init' )->once();

		$this->main->register_components();

		$this->assertedByMockery();
	}

	public function testRegisterComponentsInitializesRecipeRegistry(): void {
		$this->ingredients->shouldReceive( 'init' )->once();
		$this->recipes->shouldReceive( 'init' )->once();

		$this->main->register_components();

		$this->assertedByMockery();
	}

	public function testRegisterComponentsInitializesRegistriesInCorrectOrder(): void {
		$this->ingredients->shouldReceive( 'init' )->once()->globally()->ordered();
		$this->recipes->shouldReceive( 'init' )->once()->globally()->ordered();

		$this->main->register_components();

		$this->assertedByMockery();
	}
}
