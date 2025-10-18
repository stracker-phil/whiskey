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
		add_filter( 'whiskey:register_recipes', function ( array $items ) use ( &$hookFired ) {
			$hookFired = true;
			$this->assertIsArray( $items );

			return $items;
		} );

		$this->registry->init();

		$this->assertTrue( $hookFired );
	}

	public function test_init_only_runs_once(): void {
		$callCount = 0;
		add_filter( 'whiskey:register_recipes', static function ( array $items ) use ( &$callCount ) {
			$callCount ++;

			return $items;
		} );

		$this->registry->init();
		$this->registry->init();
		$this->registry->init();

		$this->assertSame( 1, $callCount );
	}

	public function test_init_collects_recipes_from_filter(): void {
		add_filter( 'whiskey:register_recipes', static function ( array $items ) {
			return array_merge( $items, [
				'test-recipe' => [ 'ingredient1' => 'value1' ],
			] );
		} );

		$this->registry->init();

		$this->assertTrue( $this->registry->has( 'test-recipe' ) );
	}

	public function test_add_stores_recipe(): void {
		$config = [
			'set_homepage' => 'shop',
			'create_pages' => [ 'cart', 'checkout' ],
		];

		$this->registry->add( 'test-recipe', $config );

		$this->assertTrue( $this->registry->has( 'test-recipe' ) );
		$this->assertSame( $config, $this->registry->get( 'test-recipe' ) );
	}

	public function test_add_skips_empty_name(): void {
		$this->registry->add( '', [ 'ingredient' => 'value' ] );

		$this->assertEmpty( $this->registry->all() );
	}

	public function test_add_skips_empty_ingredients(): void {
		$this->registry->add( 'test-recipe', [] );

		$this->assertEmpty( $this->registry->all() );
	}

	public function test_add_replaces_existing_recipe(): void {
		$config1 = [ 'ingredient1' => 'value1' ];
		$config2 = [ 'ingredient2' => 'value2' ];

		$this->registry->add( 'test-recipe', $config1 );
		$this->registry->add( 'test-recipe', $config2 );

		$this->assertSame( $config2, $this->registry->get( 'test-recipe' ) );
	}

	public function test_get_returns_recipe_config(): void {
		$config = [ 'ingredient' => 'value' ];
		$this->registry->add( 'test-recipe', $config );

		$result = $this->registry->get( 'test-recipe' );

		$this->assertSame( $config, $result );
	}

	public function test_get_returns_null_for_non_existent(): void {
		$result = $this->registry->get( 'non-existent' );

		$this->assertNull( $result );
	}

	public function test_get_calls_init(): void {
		$hookFired = false;
		add_filter( 'whiskey:register_recipes', static function ( array $items ) use ( &$hookFired ) {
			$hookFired = true;

			return $items;
		} );

		$this->registry->get( 'anything' );

		$this->assertTrue( $hookFired );
	}

	public function test_all_returns_all_recipes(): void {
		$config1 = [ 'ingredient1' => 'value1' ];
		$config2 = [ 'ingredient2' => 'value2' ];

		$this->registry->add( 'recipe1', $config1 );
		$this->registry->add( 'recipe2', $config2 );

		$recipes = $this->registry->all();

		$this->assertCount( 2, $recipes );
		$this->assertArrayHasKey( 'recipe1', $recipes );
		$this->assertArrayHasKey( 'recipe2', $recipes );
		$this->assertSame( $config1, $recipes['recipe1'] );
		$this->assertSame( $config2, $recipes['recipe2'] );
	}

	public function test_all_calls_init(): void {
		$hookFired = false;
		add_filter( 'whiskey:register_recipes', static function ( array $items ) use ( &$hookFired ) {
			$hookFired = true;

			return $items;
		} );

		$this->registry->all();

		$this->assertTrue( $hookFired );
	}

	public function test_has_returns_true_for_existing_recipe(): void {
		$this->registry->add( 'test-recipe', [ 'ingredient' => 'value' ] );

		$this->assertTrue( $this->registry->has( 'test-recipe' ) );
	}

	public function test_has_returns_false_for_non_existent(): void {
		$this->assertFalse( $this->registry->has( 'non-existent' ) );
	}

	public function test_has_calls_init(): void {
		$hookFired = false;
		add_filter( 'whiskey:register_recipes', static function ( array $items ) use ( &$hookFired ) {
			$hookFired = true;

			return $items;
		} );

		$this->registry->has( 'anything' );

		$this->assertTrue( $hookFired );
	}

	public function test_init_continues_after_individual_recipe_error(): void {
		// Register multiple recipes, one will fail
		add_filter( 'whiskey:register_recipes', static function ( array $items ) {
			return array_merge( $items, [
				'recipe1'    => [ 'ingredient1' => 'value1' ],
				'bad-recipe' => [], // Empty ingredients, will fail
				'recipe2'    => [ 'ingredient2' => 'value2' ],
			] );
		} );

		// Init should complete without throwing
		$this->registry->init();

		// Verify valid recipes were added
		$this->assertTrue( $this->registry->has( 'recipe1' ) );
		$this->assertTrue( $this->registry->has( 'recipe2' ) );
		$this->assertFalse( $this->registry->has( 'bad-recipe' ) );
	}

	public function test_multiple_filters_accumulate_recipes(): void {
		// First filter adds recipe 1
		add_filter( 'whiskey:register_recipes', static function ( array $items ) {
			return array_merge( $items, [
				'recipe1' => [ 'ingredient1' => 'value1' ],
			] );
		}, 10 );

		// Second filter adds recipe 2
		add_filter( 'whiskey:register_recipes', static function ( array $items ) {
			return array_merge( $items, [
				'recipe2' => [ 'ingredient2' => 'value2' ],
			] );
		}, 20 );

		$this->registry->init();

		// Both recipes should be registered
		$this->assertTrue( $this->registry->has( 'recipe1' ) );
		$this->assertTrue( $this->registry->has( 'recipe2' ) );
		$this->assertCount( 2, $this->registry->all() );
	}

	public function test_filter_can_override_recipes_from_previous_filters(): void {
		$original_config = [ 'ingredient1' => 'original' ];
		$override_config = [ 'ingredient1' => 'override' ];

		// First filter adds recipe
		add_filter( 'whiskey:register_recipes', static function ( array $items ) use ( $original_config ) {
			return array_merge( $items, [
				'test-recipe' => $original_config,
			] );
		}, 10 );

		// Second filter overrides same recipe
		add_filter( 'whiskey:register_recipes', static function ( array $items ) use ( $override_config ) {
			return array_merge( $items, [
				'test-recipe' => $override_config,
			] );
		}, 20 );

		$this->registry->init();

		// Recipe should have override config
		$this->assertSame( $override_config, $this->registry->get( 'test-recipe' ) );
	}

	public function test_init_handles_non_string_items_gracefully(): void {
		// Simulate a broken filter that returns invalid types
		add_filter( 'whiskey:register_ingredients', static function ( $items ) {
			return [ 123, 'invalid', null, TestIngredient::class ];
		} );

		// Should not throw, should skip invalid items
		$this->registry->init();

		// Only valid ingredient should be registered
		$this->assertTrue( $this->registry->has( 'test_ingredient' ) );
	}
}
