<?php
/**
 * Tests for RecipeRegistry
 *
 * @package Whiskey\Tests
 */

declare( strict_types = 1 );

namespace Whiskey\Tests;

use PHPUnit\Framework\TestCase;
use Whiskey\RecipeRegistry;

/**
 * RecipeRegistry test case
 */
final class RecipeRegistryTest extends TestCase {

	private RecipeRegistry $registry;

	protected function setUp(): void {
		parent::setUp();
		$this->registry = new RecipeRegistry();

		// Reset global hooks between tests.
		global $wp_filter;
		$wp_filter = [];
	}

	public function test_register_stores_valid_recipe(): void {
		$recipe = [
			'name'   => 'test-recipe',
			'type'   => 'paypal',
			'config' => [ 'mode' => 'sandbox' ],
		];

		$this->registry->register( $recipe );

		$this->assertTrue( $this->registry->has( 'test-recipe' ) );
		$this->assertEquals( $recipe, $this->registry->get( 'test-recipe' ) );
	}

	/**
	 * @dataProvider invalid_recipe_provider
	 */
	public function test_register_ignores_invalid_recipe( array $recipe ): void {
		$this->registry->register( $recipe );

		$this->assertEmpty( $this->registry->all() );
	}

	/**
	 * Data provider for invalid recipes
	 *
	 * @return array<string, array<array>>
	 */
	public function invalid_recipe_provider(): array {
		return [
			'missing name'   => [
				[
					'type'   => 'paypal',
					'config' => [ 'mode' => 'sandbox' ],
				],
			],
			'missing type'   => [
				[
					'name'   => 'test-recipe',
					'config' => [ 'mode' => 'sandbox' ],
				],
			],
			'missing config' => [
				[
					'name' => 'test-recipe',
					'type' => 'paypal',
				],
			],
		];
	}

	public function test_get_returns_null_for_nonexistent_recipe(): void {
		$this->assertNull( $this->registry->get( 'nonexistent' ) );
	}

	public function test_has_returns_false_for_nonexistent_recipe(): void {
		$this->assertFalse( $this->registry->has( 'nonexistent' ) );
	}

	public function test_all_returns_all_registered_recipes(): void {
		$recipe1 = [
			'name'   => 'recipe-1',
			'type'   => 'paypal',
			'config' => [ 'mode' => 'sandbox' ],
		];

		$recipe2 = [
			'name'   => 'recipe-2',
			'type'   => 'woocommerce',
			'config' => [ 'currency' => 'USD' ],
		];

		$this->registry->register( $recipe1 );
		$this->registry->register( $recipe2 );

		$all = $this->registry->all();

		$this->assertCount( 2, $all );
		$this->assertEquals( $recipe1, $all['recipe-1'] );
		$this->assertEquals( $recipe2, $all['recipe-2'] );
	}

	public function test_init_fires_registration_hook(): void {
		$hook_fired = false;

		add_action(
			'whiskey:register_recipe',
			function ( RecipeRegistry $registry ) use ( &$hook_fired ) {
				$hook_fired = true;
				$registry->register(
					[
						'name'   => 'hook-recipe',
						'type'   => 'paypal',
						'config' => [ 'mode' => 'live' ],
					]
				);
			}
		);

		$this->registry->init();

		$this->assertTrue( $hook_fired );
		$this->assertTrue( $this->registry->has( 'hook-recipe' ) );
	}

	public function test_init_only_runs_once(): void {
		$call_count = 0;

		add_action(
			'whiskey:register_recipe',
			function () use ( &$call_count ) {
				$call_count ++;
			}
		);

		$this->registry->init();
		$this->registry->init();
		$this->registry->init();

		$this->assertEquals( 1, $call_count );
	}

	public function test_get_triggers_init_automatically(): void {
		add_action(
			'whiskey:register_recipe',
			function ( RecipeRegistry $registry ) {
				$registry->register(
					[
						'name'   => 'auto-recipe',
						'type'   => 'paypal',
						'config' => [ 'mode' => 'sandbox' ],
					]
				);
			}
		);

		// Call get without calling init first.
		$recipe = $this->registry->get( 'auto-recipe' );

		$this->assertNotNull( $recipe );
		$this->assertEquals( 'auto-recipe', $recipe['name'] );
	}
}
