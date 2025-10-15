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

	public function testConstructorCreatesInstance(): void {
		$main = new Main( $this->recipes, $this->ingredients, $this->rest, $this->cli );

		$this->assertInstanceOf( Main::class, $main );
	}
}
