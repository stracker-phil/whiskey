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
	 * GIVEN valid recipe parameters
	 * WHEN register() is called
	 * THEN the recipe should be stored by name
	 */
	public function testRegisterStoresValidRecipe(): void {
		when( 'do_action' )->justReturn( null );

		$this->registry->add( 'plugin', 'test-recipe', [ 'plugin' => 'my-plugin' ] );

		$this->assertTrue( $this->registry->has( 'test-recipe' ) );

		$stored = $this->registry->get( 'test-recipe' );
		$this->assertSame( 'plugin', $stored['type'] );
		$this->assertSame( [ 'plugin' => 'my-plugin' ], $stored['config'] );
	}

	/**
	 * GIVEN invalid recipe parameters
	 * WHEN register() is called
	 * THEN the recipe should NOT be stored
	 *
	 * @dataProvider invalidRecipeProvider
	 */
	public function testRegisterIgnoresInvalidRecipe( string $type, string $name, array $config ): void {
		when( 'do_action' )->justReturn( null );

		$this->registry->add( $type, $name, $config );

		$this->assertEmpty( $this->registry->all() );
	}

	/**
	 * GIVEN a recipe is registered
	 * WHEN get() is called with an existing name
	 * THEN the recipe should be returned with type and config
	 */
	public function testGetReturnsExistingRecipe(): void {
		when( 'do_action' )->justReturn( null );

		$this->registry->add( 'theme', 'my-recipe', [ 'theme' => 'my-theme' ] );

		$result = $this->registry->get( 'my-recipe' );

		$this->assertSame( 'theme', $result['type'] );
		$this->assertSame( [ 'theme' => 'my-theme' ], $result['config'] );
	}

	/**
	 * GIVEN no recipes are registered
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

		$this->registry->add( 'plugin', 'recipe-one', [ 'plugin' => 'plugin-one' ] );
		$this->registry->add( 'plugin', 'recipe-two', [ 'plugin' => 'plugin-two' ] );

		$all = $this->registry->all();

		$this->assertCount( 2, $all );
		$this->assertArrayHasKey( 'recipe-one', $all );
		$this->assertArrayHasKey( 'recipe-two', $all );
		$this->assertSame( 'plugin', $all['recipe-one']['type'] );
		$this->assertSame( [ 'plugin' => 'plugin-one' ], $all['recipe-one']['config'] );
		$this->assertSame( 'plugin', $all['recipe-two']['type'] );
		$this->assertSame( [ 'plugin' => 'plugin-two' ], $all['recipe-two']['config'] );
	}

	/**
	 * GIVEN a recipe is registered
	 * WHEN has() is called with an existing name
	 * THEN true should be returned
	 */
	public function testHasReturnsTrueForExistingRecipe(): void {
		when( 'do_action' )->justReturn( null );

		$this->registry->add( 'plugin', 'existing-recipe', [ 'plugin' => 'test' ] );

		$this->assertTrue( $this->registry->has( 'existing-recipe' ) );
	}

	/**
	 * GIVEN no recipes are registered
	 * WHEN has() is called with a non-existing name
	 * THEN false should be returned
	 */
	public function testHasReturnsFalseForMissingRecipe(): void {
		when( 'do_action' )->justReturn( null );

		$this->assertFalse( $this->registry->has( 'missing-recipe' ) );
	}

	/**
	 * GIVEN recipes are registered via the hook
	 * WHEN get() is called WITHOUT explicit init()
	 * THEN the registry should auto-initialize
	 * AND the hooked recipe should be available
	 */
	public function testGetTriggersInitAutomatically(): void {
		when( 'do_action' )->alias( function ( $hook, $registry ) {
			if ( $hook === 'whiskey:register_recipe' ) {
				$registry->add( 'plugin', 'auto-recipe', [ 'plugin' => 'my-plugin' ] );
			}
		} );

		$recipe = $this->registry->get( 'auto-recipe' );

		$this->assertNotNull( $recipe );
		$this->assertSame( 'plugin', $recipe['type'] );
		$this->assertSame( [ 'plugin' => 'my-plugin' ], $recipe['config'] );
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
				$registry->add( 'plugin', 'auto-recipe', [ 'plugin' => 'test' ] );
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
				$registry->add( 'plugin', 'auto-recipe', [ 'plugin' => 'test' ] );
			}
		} );

		$this->assertTrue( $this->registry->has( 'auto-recipe' ) );
	}

	/**
	 * @return array<string, array{type: string, name: string, config: array}>
	 */
	public function invalidRecipeProvider(): array {
		return [
			'empty name'   => [
				'type'   => 'plugin',
				'name'   => '',
				'config' => [ 'plugin' => 'test' ],
			],
			'empty type'   => [
				'type'   => '',
				'name'   => 'test-recipe',
				'config' => [ 'plugin' => 'test' ],
			],
			'empty config' => [
				'type'   => 'plugin',
				'name'   => 'test-recipe',
				'config' => [],
			],
		];
	}
}
