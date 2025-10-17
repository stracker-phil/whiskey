<?php
/**
 * @covers \Whiskey\RecipeExecutor
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\RecipeExecutor;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\Ingredient;
use Whiskey\ExecutionResult;

class RecipeExecutorTest extends WhiskeyTest {

	private IngredientRegistry $ingredients;
	private RecipeExecutor $executor;

	protected function setUp(): void {
		parent::setUp();

		$this->ingredients = $this->createMock( IngredientRegistry::class );
		$this->executor    = new RecipeExecutor( $this->ingredients );
	}

	public function test_validate_returns_false_for_empty_config(): void {
		$result = $this->executor->validate( [] );

		$this->assertFalse( $result );
	}

	public function test_validate_returns_true_for_valid_config(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'validate' )
			->with( 'value' )
			->willReturn( true );

		$this->ingredients->expects( $this->once() )
			->method( 'get' )
			->with( 'ingredient_name' )
			->willReturn( $ingredient );

		$result = $this->executor->validate( [ 'ingredient_name' => 'value' ] );

		$this->assertTrue( $result );
	}

	public function test_validate_skips_unknown_ingredients(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'validate' )
			->with( 'valid_value' )
			->willReturn( true );

		$this->ingredients->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'unknown', null ],
					[ 'known', $ingredient ],
				]
			);

		$result = $this->executor->validate(
			[
				'unknown' => 'value',
				'known'   => 'valid_value',
			]
		);

		$this->assertTrue( $result );
	}

	public function test_validate_returns_false_when_ingredient_validation_fails(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'validate' )
			->with( 'invalid_value' )
			->willReturn( false );

		$this->ingredients->expects( $this->once() )
			->method( 'get' )
			->with( 'ingredient_name' )
			->willReturn( $ingredient );

		$result = $this->executor->validate( [ 'ingredient_name' => 'invalid_value' ] );

		$this->assertFalse( $result );
	}

	public function test_validate_returns_true_when_only_unknown_ingredients_present(): void {
		$this->ingredients->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'unknown1', null ],
					[ 'unknown2', null ],
				]
			);

		$result = $this->executor->validate(
			[
				'unknown1' => 'value1',
				'unknown2' => 'value2',
			]
		);

		$this->assertTrue( $result );
	}

	public function test_execute_skips_unknown_ingredients(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'execute' )
			->with( 'value' )
			->willReturn( new ExecutionResult( true, 'Success', [ 'data' => 'result' ] ) );

		$this->ingredients->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'unknown', null ],
					[ 'known', $ingredient ],
				]
			);

		$result = $this->executor->execute(
			[
				'unknown' => 'ignored',
				'known'   => 'value',
			]
		);

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'known', $data );
		$this->assertArrayNotHasKey( 'unknown', $data );
	}

	public function test_execute_collects_all_ingredient_results(): void {
		$ingredient1 = $this->createMock( Ingredient::class );
		$ingredient1->expects( $this->once() )
			->method( 'execute' )
			->with( 'value1' )
			->willReturn( new ExecutionResult( true, 'Success 1', [ 'data' => 'result1' ] ) );

		$ingredient2 = $this->createMock( Ingredient::class );
		$ingredient2->expects( $this->once() )
			->method( 'execute' )
			->with( 'value2' )
			->willReturn( new ExecutionResult( true, 'Success 2', [ 'data' => 'result2' ] ) );

		$this->ingredients->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'ingredient1', $ingredient1 ],
					[ 'ingredient2', $ingredient2 ],
				]
			);

		$result = $this->executor->execute(
			[
				'ingredient1' => 'value1',
				'ingredient2' => 'value2',
			]
		);

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 2, $data );
		$this->assertArrayHasKey( 'ingredient1', $data );
		$this->assertArrayHasKey( 'ingredient2', $data );
	}

	public function test_execute_returns_success_result(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'execute' )
			->willReturn( new ExecutionResult( true, 'Success', [] ) );

		$this->ingredients->expects( $this->once() )
			->method( 'get' )
			->willReturn( $ingredient );

		$result = $this->executor->execute( [ 'test' => 'value' ] );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'Recipe executed successfully', $result->get_message() );
	}

	public function test_execute_stores_results_as_arrays(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'execute' )
			->with( 'value' )
			->willReturn( new ExecutionResult( false, 'Failed', [ 'error' => 'details' ] ) );

		$this->ingredients->expects( $this->once() )
			->method( 'get' )
			->with( 'ingredient_name' )
			->willReturn( $ingredient );

		$result = $this->executor->execute( [ 'ingredient_name' => 'value' ] );

		$data = $result->get_data();
		$this->assertIsArray( $data['ingredient_name'] );
		$this->assertSame( false, $data['ingredient_name']['success'] );
		$this->assertSame( 'Failed', $data['ingredient_name']['message'] );
	}
}
