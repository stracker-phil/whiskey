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

	public function test_constructor_creates_instance(): void {
		$this->assertInstanceOf( Main::class, $this->main );
	}

	public function test_init_hook_initializes_registries(): void {
		$this->ingredients->expects( $this->once() )->method( 'init' );
		$this->recipes->expects( $this->once() )->method( 'init' );

		do_action( 'init' );
	}

	public function test_rest_api_init_hook_registers_routes(): void {
		$this->rest->expects( $this->once() )->method( 'register_routes' );

		do_action( 'rest_api_init' );
	}

	public function test_cli_init_hook_registers_commands(): void {
		$this->cli->expects( $this->once() )->method( 'register_commands' );

		do_action( 'cli_init' );
	}
}

