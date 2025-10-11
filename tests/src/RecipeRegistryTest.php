<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\RecipeRegistry;
use function Brain\Monkey\Functions\when;

/**
 * @covers RecipeRegistry
 */
final class RecipeRegistryTest extends WhiskeyTest {
	private ?RecipeRegistry $registry = null;

	protected function setUp(): void {
		parent::setUp();

		$this->registry = new RecipeRegistry();
	}

	/**
	 * GIVEN the registry is not initialized
	 * WHEN init() is called
	 * THEN the registration hook should fire
	 * AND the registry instance should be passed for dependency injection
	 */
	public function testInitFiresRegistrationHook(): void {
		$this->expectDone( 'whiskey:register_recipe' )
			->once()
			->with( $this->registry );

		$this->registry->init();
	}

	/**
	 * GIVEN the registry is already initialized
	 * WHEN init() is called again
	 * THEN the registration hook should NOT fire again
	 */
	public function testInitOnlyFiresOnce(): void {
		$this->expectDone( 'whiskey:register_recipe' )
			->once()
			->with( $this->registry );

		$this->registry->init();
		$this->registry->init(); // Second call
		$this->registry->init(); // Third call
	}

	/**
	 * GIVEN a recipe with all required fields
	 * WHEN register() is called
	 * THEN the recipe should be stored by name
	 */
	public function testRegisterStoresValidRecipe(): void {
		$recipe = [
			'name'   => 'test-recipe',
			'type'   => 'plugin',
			'config' => [ 'plugin' => 'my-plugin' ],
		];

		when( 'do_action' )->justReturn( null );

		$this->registry->register( $recipe );

		$this->assertTrue( $this->registry->has( 'test-recipe' ) );
		$this->assertSame( $recipe, $this->registry->get( 'test-recipe' ) );
	}

	/**
	 * GIVEN a recipe missing required fields
	 * WHEN register() is called
	 * THEN the recipe should NOT be stored
	 *
	 * @dataProvider invalidRecipeProvider
	 */
	public function testRegisterIgnoresInvalidRecipe( array $invalidRecipe ): void {
		when( 'do_action' )->justReturn( null );

		$this->registry->register( $invalidRecipe );

		$this->assertEmpty( $this->registry->all() );
	}

	/**
	 * GIVEN recipes are registered
	 * WHEN get() is called with an existing name
	 * THEN the recipe should be returned
	 */
	public function testGetReturnsExistingRecipe(): void {
		when( 'do_action' )->justReturn( null );

		$recipe = [
			'name'   => 'my-recipe',
			'type'   => 'theme',
			'config' => [ 'theme' => 'my-theme' ],
		];

		$this->registry->register( $recipe );

		$result = $this->registry->get( 'my-recipe' );

		$this->assertSame( $recipe, $result );
	}

	/**
	 * GIVEN recipes are registered
	 * WHEN get() is called with a non-existing name
	 * THEN null should be returned
	 */
	public function testGetReturnsNullForMissingRecipe(): void {
		when( 'do_action' )->justReturn( null );

		$result = $this->registry->get( 'non-existing' );

		$this->assertNull( $result );
	}

	/**
	 * GIVEN multiple recipes are registered
	 * WHEN all() is called
	 * THEN all recipes should be returned indexed by name
	 */
	public function testAllReturnsAllRecipes(): void {
		when( 'do_action' )->justReturn( null );

		$recipe1 = [
			'name'   => 'recipe-one',
			'type'   => 'plugin',
			'config' => [ 'plugin' => 'plugin-one' ],
		];

		$recipe2 = [
			'name'   => 'recipe-two',
			'type'   => 'plugin',
			'config' => [ 'plugin' => 'plugin-two' ],
		];

		$this->registry->register( $recipe1 );
		$this->registry->register( $recipe2 );

		$all = $this->registry->all();

		$this->assertCount( 2, $all );
		$this->assertSame( $recipe1, $all['recipe-one'] );
		$this->assertSame( $recipe2, $all['recipe-two'] );
	}

	/**
	 * GIVEN recipes are registered
	 * WHEN has() is called with an existing name
	 * THEN true should be returned
	 */
	public function testHasReturnsTrueForExistingRecipe(): void {
		when( 'do_action' )->justReturn( null );

		$recipe = [
			'name'   => 'existing-recipe',
			'type'   => 'plugin',
			'config' => [ 'plugin' => 'test' ],
		];

		$this->registry->register( $recipe );

		$this->assertTrue( $this->registry->has( 'existing-recipe' ) );
	}

	/**
	 * GIVEN recipes are registered
	 * WHEN has() is called with a non-existing name
	 * THEN false should be returned
	 */
	public function testHasReturnsFalseForMissingRecipe(): void {
		when( 'do_action' )->justReturn( null );

		$this->assertFalse( $this->registry->has( 'missing-recipe' ) );
	}

	/**
	 * @return array<string, array<string, array>>
	 */
	public function invalidRecipeProvider(): array {
		return [
			'missing name'   => [
				'recipe' => [
					'type'   => 'plugin',
					'config' => [ 'plugin' => 'test' ],
				],
			],
			'missing type'   => [
				'recipe' => [
					'name'   => 'test',
					'config' => [ 'plugin' => 'test' ],
				],
			],
			'missing config' => [
				'recipe' => [
					'name' => 'test',
					'type' => 'plugin',
				],
			],
			'empty array'    => [
				'recipe' => [],
			],
		];
	}

	/**
	 * GIVEN recipes are registered via the hook
	 * WHEN get() is called WITHOUT explicit init()
	 * THEN the registry should auto-initialize
	 * AND the hooked recipe should be available
	 */
	public function testGetTriggersInitAutomatically(): void {
		// Simulate a plugin hooking in to register a recipe
		when( 'do_action' )->alias( function ( $hook, $registry ) {
			if ( $hook === 'whiskey:register_recipe' ) {
				$registry->register( [
					'name'   => 'auto-recipe',
					'type'   => 'plugin',
					'config' => [ 'plugin' => 'my-plugin' ],
				] );
			}
		} );

		// Call get() WITHOUT calling init() first
		$recipe = $this->registry->get( 'auto-recipe' );

		$this->assertNotNull( $recipe );
		$this->assertSame( 'auto-recipe', $recipe['name'] );
		$this->assertSame( 'plugin', $recipe['type'] );
	}

	/**
	 * GIVEN recipes are registered via the hook
	 * WHEN all() is called WITHOUT explicit init()
	 * THEN the registry should auto-initialize
	 * AND return all hooked recipes
	 */
	public function testAllTriggersInitAutomatically(): void {
		when( 'do_action' )->alias( function ( $hook, $registry ) {
			if ( $hook === 'whiskey:register_recipe' ) {
				$registry->register( [
					'name'   => 'auto-recipe',
					'type'   => 'plugin',
					'config' => [ 'plugin' => 'test' ],
				] );
			}
		} );

		$recipes = $this->registry->all();

		$this->assertCount( 1, $recipes );
		$this->assertArrayHasKey( 'auto-recipe', $recipes );
	}

	/**
	 * GIVEN recipes are registered via the hook
	 * WHEN has() is called WITHOUT explicit init()
	 * THEN the registry should auto-initialize
	 * AND correctly report recipe existence
	 */
	public function testHasTriggersInitAutomatically(): void {
		when( 'do_action' )->alias( function ( $hook, $registry ) {
			if ( $hook === 'whiskey:register_recipe' ) {
				$registry->register( [
					'name'   => 'auto-recipe',
					'type'   => 'plugin',
					'config' => [ 'plugin' => 'test' ],
				] );
			}
		} );

		$this->assertTrue( $this->registry->has( 'auto-recipe' ) );
	}
}
