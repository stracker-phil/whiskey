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

	public function testInitFiresRegisterHook(): void {
		$hookFired = false;
		add_action( 'whiskey:register_recipe', function ( $registry ) use ( &$hookFired ) {
			$hookFired = true;
			$this->assertInstanceOf( RecipeRegistry::class, $registry );
		} );

		$this->registry->init();

		$this->assertTrue( $hookFired );
	}

	public function testInitOnlyRunsOnce(): void {
		$callCount = 0;
		add_action( 'whiskey:register_recipe', function () use ( &$callCount ) {
			$callCount++;
		} );

		$this->registry->init();
		$this->registry->init();
		$this->registry->init();

		$this->assertSame( 1, $callCount );
	}

	public function testAddStoresRecipe(): void {
		$this->registry->add( 'test-recipe', array( 'ingredient1' => 'value1' ) );

		$this->assertTrue( $this->registry->has( 'test-recipe' ) );
	}

	public function testAddReplacesExistingRecipe(): void {
		$this->registry->add( 'test-recipe', array( 'ingredient1' => 'value1' ) );
		$this->registry->add( 'test-recipe', array( 'ingredient2' => 'value2' ) );

		$recipe = $this->registry->get( 'test-recipe' );

		$this->assertSame( array( 'ingredient2' => 'value2' ), $recipe );
	}

	public function testAddSkipsEmptyName(): void {
		$this->registry->add( '', array( 'ingredient1' => 'value1' ) );

		$this->assertEmpty( $this->registry->all() );
	}

	public function testAddSkipsEmptyIngredients(): void {
		$this->registry->add( 'test-recipe', array() );

		$this->assertEmpty( $this->registry->all() );
	}

	public function testGetReturnsRecipe(): void {
		$this->registry->add( 'test-recipe', array( 'ingredient1' => 'value1' ) );

		$recipe = $this->registry->get( 'test-recipe' );

		$this->assertSame( array( 'ingredient1' => 'value1' ), $recipe );
	}

	public function testGetReturnsNullForNonExistent(): void {
		$recipe = $this->registry->get( 'non-existent' );

		$this->assertNull( $recipe );
	}

	public function testGetCallsInit(): void {
		$hookFired = false;
		add_action( 'whiskey:register_recipe', function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->get( 'anything' );

		$this->assertTrue( $hookFired );
	}

	public function testAllReturnsAllRecipes(): void {
		$this->registry->add( 'recipe1', array( 'ingredient1' => 'value1' ) );
		$this->registry->add( 'recipe2', array( 'ingredient2' => 'value2' ) );

		$recipes = $this->registry->all();

		$this->assertCount( 2, $recipes );
		$this->assertArrayHasKey( 'recipe1', $recipes );
		$this->assertArrayHasKey( 'recipe2', $recipes );
	}

	public function testAllCallsInit(): void {
		$hookFired = false;
		add_action( 'whiskey:register_recipe', function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->all();

		$this->assertTrue( $hookFired );
	}

	public function testHasReturnsTrueForExistingRecipe(): void {
		$this->registry->add( 'test-recipe', array( 'ingredient1' => 'value1' ) );

		$this->assertTrue( $this->registry->has( 'test-recipe' ) );
	}

	public function testHasReturnsFalseForNonExistent(): void {
		$this->assertFalse( $this->registry->has( 'non-existent' ) );
	}

	public function testHasCallsInit(): void {
		$hookFired = false;
		add_action( 'whiskey:register_recipe', function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->has( 'anything' );

		$this->assertTrue( $hookFired );
	}
}
