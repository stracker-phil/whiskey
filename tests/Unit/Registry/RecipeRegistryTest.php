<?php
/**
 * @covers \Whiskey\Registry\RecipeRegistry
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Registry;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Registry\RecipeRegistry;

class RecipeRegistryTest extends WhiskeyTest {

	private RecipeRegistry $registry;

	protected function setUp(): void {
		parent::setUp();
		$this->registry = new RecipeRegistry();
	}

	public function test_init_fires_register_hook(): void {
		$hookFired = false;
		add_action( 'whiskey:register_recipe', function ( $registry ) use ( &$hookFired ) {
			$hookFired = true;
			$this->assertInstanceOf( RecipeRegistry::class, $registry );
		} );

		$this->registry->init();

		$this->assertTrue( $hookFired );
	}

	public function test_init_only_runs_once(): void {
		$callCount = 0;
		add_action( 'whiskey:register_recipe', static function () use ( &$callCount ) {
			$callCount ++;
		} );

		$this->registry->init();
		$this->registry->init();
		$this->registry->init();

		$this->assertSame( 1, $callCount );
	}

	public function test_add_stores_recipe(): void {
		$this->registry->add( 'test-recipe', [ 'ingredient1' => 'value1' ] );

		$this->assertTrue( $this->registry->has( 'test-recipe' ) );
	}

	public function test_add_replaces_existing_recipe(): void {
		$this->registry->add( 'test-recipe', [ 'ingredient1' => 'value1' ] );
		$this->registry->add( 'test-recipe', [ 'ingredient2' => 'value2' ] );

		$recipe = $this->registry->get( 'test-recipe' );

		$this->assertSame( [ 'ingredient2' => 'value2' ], $recipe );
	}

	public function test_add_skips_empty_name(): void {
		$this->registry->add( '', [ 'ingredient1' => 'value1' ] );

		$this->assertEmpty( $this->registry->all() );
	}

	public function test_add_skips_empty_ingredients(): void {
		$this->registry->add( 'test-recipe', [] );

		$this->assertEmpty( $this->registry->all() );
	}

	public function test_get_returns_recipe(): void {
		$this->registry->add( 'test-recipe', [ 'ingredient1' => 'value1' ] );

		$recipe = $this->registry->get( 'test-recipe' );

		$this->assertSame( [ 'ingredient1' => 'value1' ], $recipe );
	}

	public function test_get_returns_null_for_non_existent(): void {
		$recipe = $this->registry->get( 'non-existent' );

		$this->assertNull( $recipe );
	}

	public function test_get_calls_init(): void {
		$hookFired = false;
		add_action( 'whiskey:register_recipe', static function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->get( 'anything' );

		$this->assertTrue( $hookFired );
	}

	public function test_all_returns_all_recipes(): void {
		$this->registry->add( 'recipe1', [ 'ingredient1' => 'value1' ] );
		$this->registry->add( 'recipe2', [ 'ingredient2' => 'value2' ] );

		$recipes = $this->registry->all();

		$this->assertCount( 2, $recipes );
		$this->assertArrayHasKey( 'recipe1', $recipes );
		$this->assertArrayHasKey( 'recipe2', $recipes );
	}

	public function test_all_calls_init(): void {
		$hookFired = false;
		add_action( 'whiskey:register_recipe', static function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->all();

		$this->assertTrue( $hookFired );
	}

	public function test_has_returns_true_for_existing_recipe(): void {
		$this->registry->add( 'test-recipe', [ 'ingredient1' => 'value1' ] );

		$this->assertTrue( $this->registry->has( 'test-recipe' ) );
	}

	public function test_has_returns_false_for_non_existent(): void {
		$this->assertFalse( $this->registry->has( 'non-existent' ) );
	}

	public function test_has_calls_init(): void {
		$hookFired = false;
		add_action( 'whiskey:register_recipe', static function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->has( 'anything' );

		$this->assertTrue( $hookFired );
	}
}
