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

	private RecipeRegistry $recipes;
	private IngredientRegistry $ingredients;
	private RestController $rest;
	private CliController $cli;
	private Main $main;

	protected function setUp(): void {
		parent::setUp();

		$this->recipes     = $this->createMock( RecipeRegistry::class );
		$this->ingredients = $this->createMock( IngredientRegistry::class );
		$this->rest        = $this->createMock( RestController::class );
		$this->cli         = $this->createMock( CliController::class );

		$this->main = new Main(
			$this->recipes,
			$this->ingredients,
			$this->rest,
			$this->cli
		);
	}

	public function testConstructorCreatesInstance(): void {
		$this->assertInstanceOf( Main::class, $this->main );
	}

	public function testInitHookInitializesRegistries(): void {
		$this->ingredients->expects( $this->once() )->method( 'init' );
		$this->recipes->expects( $this->once() )->method( 'init' );

		do_action( 'init' );
	}

	public function testRestApiInitHookRegistersRoutes(): void {
		$this->rest->expects( $this->once() )->method( 'register_routes' );

		do_action( 'rest_api_init' );
	}

	public function testCliInitHookRegistersCommands(): void {
		$this->cli->expects( $this->once() )->method( 'register_commands' );

		do_action( 'cli_init' );
	}
}

