<?php
/**
 * @covers \Whiskey\Registry\IngredientRegistry
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Registry;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\Ingredient;
use Whiskey\ExecutionResult;

class IngredientRegistryTest extends WhiskeyTest {

	private IngredientRegistry $registry;

	protected function setUp(): void {
		parent::setUp();
		$this->registry = new IngredientRegistry();
	}

	public function test_init_fires_register_hook(): void {
		$hookFired = false;
		add_action( 'whiskey:register_ingredient', function ( $registry ) use ( &$hookFired ) {
			$hookFired = true;
			$this->assertInstanceOf( IngredientRegistry::class, $registry );
		} );

		$this->registry->init();

		$this->assertTrue( $hookFired );
	}

	public function test_init_only_runs_once(): void {
		$callCount = 0;
		add_action( 'whiskey:register_ingredient', static function () use ( &$callCount ) {
			$callCount ++;
		} );

		$this->registry->init();
		$this->registry->init();
		$this->registry->init();

		$this->assertSame( 1, $callCount );
	}

	public function test_add_stores_ingredient_class(): void {
		$this->registry->add( TestIngredient::class );

		$this->assertTrue( $this->registry->has( 'test_ingredient' ) );
	}

	public function test_add_skips_non_existent_class(): void {
		$this->registry->add( 'NonExistentClass' );

		$this->assertEmpty( $this->registry->all() );
	}

	public function test_add_skips_ingredient_with_empty_name(): void {
		$this->registry->add( TestIngredientEmptyName::class );

		$this->assertEmpty( $this->registry->all() );
	}

	public function test_get_returns_instantiated_ingredient(): void {
		$this->registry->add( TestIngredient::class );

		$ingredient = $this->registry->get( 'test_ingredient' );

		$this->assertInstanceOf( TestIngredient::class, $ingredient );
	}

	public function test_get_returns_null_for_non_existent(): void {
		$ingredient = $this->registry->get( 'non-existent' );

		$this->assertNull( $ingredient );
	}

	public function test_get_calls_init(): void {
		$hookFired = false;
		add_action( 'whiskey:register_ingredient', static function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->get( 'anything' );

		$this->assertTrue( $hookFired );
	}

	public function test_all_returns_all_ingredient_classes(): void {
		$this->registry->add( TestIngredient::class );
		$this->registry->add( TestIngredient2::class );

		$ingredients = $this->registry->all();

		$this->assertCount( 2, $ingredients );
		$this->assertArrayHasKey( 'test_ingredient', $ingredients );
		$this->assertArrayHasKey( 'test_ingredient2', $ingredients );
		$this->assertSame( TestIngredient::class, $ingredients['test_ingredient'] );
		$this->assertSame( TestIngredient2::class, $ingredients['test_ingredient2'] );
	}

	public function test_all_calls_init(): void {
		$hookFired = false;
		add_action( 'whiskey:register_ingredient', static function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->all();

		$this->assertTrue( $hookFired );
	}

	public function test_has_returns_true_for_existing_ingredient(): void {
		$this->registry->add( TestIngredient::class );

		$this->assertTrue( $this->registry->has( 'test_ingredient' ) );
	}

	public function test_has_returns_false_for_non_existent(): void {
		$this->assertFalse( $this->registry->has( 'non-existent' ) );
	}

	public function test_has_calls_init(): void {
		$hookFired = false;
		add_action( 'whiskey:register_ingredient', static function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->has( 'anything' );

		$this->assertTrue( $hookFired );
	}

	public function test_get_metadata_returns_ingredient_metadata(): void {
		$this->registry->add( TestIngredient::class );

		$metadata = $this->registry->get_metadata( 'test_ingredient' );

		$this->assertSame( 'test_ingredient', $metadata['name'] );
		$this->assertSame( 'test', $metadata['category'] );
		$this->assertSame( 'Test ingredient for testing', $metadata['description'] );
	}

	public function test_all_metadata_returns_all_metadata(): void {
		$this->registry->add( TestIngredient::class );
		$this->registry->add( TestIngredient2::class );

		$metadata = $this->registry->all_metadata();

		$this->assertCount( 2, $metadata );
		$this->assertArrayHasKey( 'test_ingredient', $metadata );
		$this->assertArrayHasKey( 'test_ingredient2', $metadata );
		$this->assertSame( 'test', $metadata['test_ingredient']['category'] );
		$this->assertSame( 'test', $metadata['test_ingredient2']['category'] );
	}
}

/**
 * Test ingredient for unit tests
 */
class TestIngredient extends Ingredient {
	public const NAME        = 'test_ingredient';
	public const CATEGORY    = 'test';
	public const DESCRIPTION = 'Test ingredient for testing';

	public function validate( $value ): bool {
		return true;
	}

	public function execute( $value ): ExecutionResult {
		return new ExecutionResult( true, 'Success', [] );
	}
}

/**
 * Second test ingredient
 */
class TestIngredient2 extends Ingredient {
	public const NAME        = 'test_ingredient2';
	public const CATEGORY    = 'test';
	public const DESCRIPTION = 'Second test ingredient';

	public function validate( $value ): bool {
		return true;
	}

	public function execute( $value ): ExecutionResult {
		return new ExecutionResult( true, 'Success', [] );
	}
}

/**
 * Test ingredient with empty name
 */
class TestIngredientEmptyName extends Ingredient {
	public const NAME        = '';
	public const CATEGORY    = 'test';
	public const DESCRIPTION = 'Should be skipped';

	public function validate( $value ): bool {
		return true;
	}

	public function execute( $value ): ExecutionResult {
		return new ExecutionResult( true, 'Success', [] );
	}
}
