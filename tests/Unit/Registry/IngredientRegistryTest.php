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

	public function testInitFiresRegisterHook(): void {
		$hookFired = false;
		add_action( 'whiskey:register_ingredient', function ( $registry ) use ( &$hookFired ) {
			$hookFired = true;
			$this->assertInstanceOf( IngredientRegistry::class, $registry );
		} );

		$this->registry->init();

		$this->assertTrue( $hookFired );
	}

	public function testInitOnlyRunsOnce(): void {
		$callCount = 0;
		add_action( 'whiskey:register_ingredient', static function () use ( &$callCount ) {
			$callCount ++;
		} );

		$this->registry->init();
		$this->registry->init();
		$this->registry->init();

		$this->assertSame( 1, $callCount );
	}

	public function testAddStoresIngredientClass(): void {
		$this->registry->add( TestIngredient::class );

		$this->assertTrue( $this->registry->has( 'test_ingredient' ) );
	}

	public function testAddSkipsNonExistentClass(): void {
		$this->registry->add( 'NonExistentClass' );

		$this->assertEmpty( $this->registry->all() );
	}

	public function testAddSkipsIngredientWithEmptyName(): void {
		$this->registry->add( TestIngredientEmptyName::class );

		$this->assertEmpty( $this->registry->all() );
	}

	public function testGetReturnsInstantiatedIngredient(): void {
		$this->registry->add( TestIngredient::class );

		$ingredient = $this->registry->get( 'test_ingredient' );

		$this->assertInstanceOf( TestIngredient::class, $ingredient );
	}

	public function testGetReturnsNullForNonExistent(): void {
		$ingredient = $this->registry->get( 'non-existent' );

		$this->assertNull( $ingredient );
	}

	public function testGetCallsInit(): void {
		$hookFired = false;
		add_action( 'whiskey:register_ingredient', static function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->get( 'anything' );

		$this->assertTrue( $hookFired );
	}

	public function testAllReturnsAllIngredientClasses(): void {
		$this->registry->add( TestIngredient::class );
		$this->registry->add( TestIngredient2::class );

		$ingredients = $this->registry->all();

		$this->assertCount( 2, $ingredients );
		$this->assertArrayHasKey( 'test_ingredient', $ingredients );
		$this->assertArrayHasKey( 'test_ingredient2', $ingredients );
		$this->assertSame( TestIngredient::class, $ingredients['test_ingredient'] );
		$this->assertSame( TestIngredient2::class, $ingredients['test_ingredient2'] );
	}

	public function testAllCallsInit(): void {
		$hookFired = false;
		add_action( 'whiskey:register_ingredient', static function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->all();

		$this->assertTrue( $hookFired );
	}

	public function testHasReturnsTrueForExistingIngredient(): void {
		$this->registry->add( TestIngredient::class );

		$this->assertTrue( $this->registry->has( 'test_ingredient' ) );
	}

	public function testHasReturnsFalseForNonExistent(): void {
		$this->assertFalse( $this->registry->has( 'non-existent' ) );
	}

	public function testHasCallsInit(): void {
		$hookFired = false;
		add_action( 'whiskey:register_ingredient', static function () use ( &$hookFired ) {
			$hookFired = true;
		} );

		$this->registry->has( 'anything' );

		$this->assertTrue( $hookFired );
	}

	public function testGetMetadataReturnsIngredientMetadata(): void {
		$this->registry->add( TestIngredient::class );

		$metadata = $this->registry->get_metadata( 'test_ingredient' );

		$this->assertSame( 'test_ingredient', $metadata['name'] );
		$this->assertSame( 'test', $metadata['category'] );
		$this->assertSame( 'Test ingredient for testing', $metadata['description'] );
	}

	public function testAllMetadataReturnsAllMetadata(): void {
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
