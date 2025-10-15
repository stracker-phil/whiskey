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

	protected function setUp(): void {
		parent::setUp();

		$this->recipes     = Mockery::mock( RecipeRegistry::class );
		$this->ingredients = Mockery::mock( IngredientRegistry::class );
		$this->rest        = Mockery::mock( RestController::class );
		$this->cli         = Mockery::mock( CliController::class );
	}

	public function testRestApiInitHookRegistersRoutes(): void {
		$this->rest->shouldReceive( 'register_routes' )->once();

		new Main( $this->recipes, $this->ingredients, $this->rest, $this->cli );

		do_action( 'rest_api_init' );

		$this->assertedByMockery();
	}

	public function testCliInitHookRegistersCommands(): void {
		$this->cli->shouldReceive( 'register_commands' )->once();

		new Main( $this->recipes, $this->ingredients, $this->rest, $this->cli );

		do_action( 'cli_init' );

		$this->assertedByMockery();
	}

	public function testInitHookInitializesRegistries(): void {
		$this->ingredients->shouldReceive( 'init' )->once();
		$this->recipes->shouldReceive( 'init' )->once();

		new Main( $this->recipes, $this->ingredients, $this->rest, $this->cli );

		do_action( 'init' );

		$this->assertedByMockery();
	}

	public function testInitHookInitializesRegistriesInCorrectOrder(): void {
		$this->ingredients->shouldReceive( 'init' )->once()->globally()->ordered();
		$this->recipes->shouldReceive( 'init' )->once()->globally()->ordered();

		new Main( $this->recipes, $this->ingredients, $this->rest, $this->cli );

		do_action( 'init' );

		$this->assertedByMockery();
	}
}
