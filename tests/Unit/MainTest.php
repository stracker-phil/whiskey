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
}
