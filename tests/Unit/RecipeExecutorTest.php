<?php
/**
 * @covers \Whiskey\RecipeExecutor
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\RecipeExecutor;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Ingredient;
use Whiskey\ExecutionResult;

class RecipeExecutorTest extends WhiskeyTest {

	private IngredientRegistry $ingredients;
	private RecipeRegistry $recipes;
	private RecipeExecutor $executor;

	protected function setUp(): void {
		parent::setUp();

		$this->ingredients = $this->createMock( IngredientRegistry::class );
		$this->recipes     = $this->createMock( RecipeRegistry::class );
		$this->executor    = new RecipeExecutor( $this->ingredients, $this->recipes );
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

	// ===== Tests for 'extends' functionality =====

	public function test_validate_ignores_extends_keyword(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'validate' )
			->with( 'value' )
			->willReturn( true );

		$this->ingredients->expects( $this->once() )
			->method( 'get' )
			->with( 'ingredient_name' )
			->willReturn( $ingredient );

		$result = $this->executor->validate(
			[
				'extends'         => 'parent-recipe',
				'ingredient_name' => 'value',
			]
		);

		$this->assertTrue( $result );
	}

	public function test_execute_handles_single_extends(): void {
		// Setup parent recipe
		$parent_config = [ 'parent_ingredient' => 'parent_value' ];

		$parent_ingredient = $this->createMock( Ingredient::class );
		$parent_ingredient->expects( $this->once() )
			->method( 'execute' )
			->with( 'parent_value' )
			->willReturn( new ExecutionResult( true, 'Parent success', [] ) );

		$child_ingredient = $this->createMock( Ingredient::class );
		$child_ingredient->expects( $this->once() )
			->method( 'execute' )
			->with( 'child_value' )
			->willReturn( new ExecutionResult( true, 'Child success', [] ) );

		$this->recipes->expects( $this->once() )
			->method( 'get' )
			->with( 'parent-recipe' )
			->willReturn( $parent_config );

		$this->ingredients->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'parent_ingredient', $parent_ingredient ],
					[ 'child_ingredient', $child_ingredient ],
				]
			);

		$result = $this->executor->execute(
			[
				'extends'          => 'parent-recipe',
				'child_ingredient' => 'child_value',
			]
		);

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'child_ingredient', $data );
	}

	public function test_execute_handles_multiple_extends(): void {
		// Setup parent recipes
		$parent1_config = [ 'ingredient1' => 'value1' ];
		$parent2_config = [ 'ingredient2' => 'value2' ];

		$ingredient1 = $this->createMock( Ingredient::class );
		$ingredient1->expects( $this->once() )
			->method( 'execute' )
			->willReturn( new ExecutionResult( true, 'Success 1', [] ) );

		$ingredient2 = $this->createMock( Ingredient::class );
		$ingredient2->expects( $this->once() )
			->method( 'execute' )
			->willReturn( new ExecutionResult( true, 'Success 2', [] ) );

		$ingredient3 = $this->createMock( Ingredient::class );
		$ingredient3->expects( $this->once() )
			->method( 'execute' )
			->willReturn( new ExecutionResult( true, 'Success 3', [] ) );

		$this->recipes->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'parent1', $parent1_config ],
					[ 'parent2', $parent2_config ],
				]
			);

		$this->ingredients->expects( $this->exactly( 3 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'ingredient1', $ingredient1 ],
					[ 'ingredient2', $ingredient2 ],
					[ 'ingredient3', $ingredient3 ],
				]
			);

		$result = $this->executor->execute(
			[
				'extends'     => [ 'parent1', 'parent2' ],
				'ingredient3' => 'value3',
			]
		);

		$this->assertTrue( $result->is_success() );
	}

	public function test_execute_detects_circular_dependency(): void {
		// Recipe A extends B, B extends A
		$recipe_a = [ 'extends' => 'recipe-b' ];
		$recipe_b = [ 'extends' => 'recipe-a' ];

		$this->recipes->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'recipe-a', $recipe_a ],
					[ 'recipe-b', $recipe_b ],
				]
			);

		$result = $this->executor->execute( [ 'extends' => 'recipe-a' ] );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Circular recipe dependency', $result->get_message() );
		$this->assertStringContainsString( 'recipe-a -> recipe-b -> recipe-a', $result->get_message() );
	}

	public function test_execute_detects_self_reference(): void {
		$recipe_config = [ 'extends' => 'self-recipe' ];

		$this->recipes->expects( $this->once() )
			->method( 'get' )
			->with( 'self-recipe' )
			->willReturn( $recipe_config );

		$result = $this->executor->execute( [ 'extends' => 'self-recipe' ] );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Circular recipe dependency', $result->get_message() );
		$this->assertStringContainsString( 'self-recipe -> self-recipe', $result->get_message() );
	}

	public function test_execute_returns_error_when_parent_recipe_not_found(): void {
		$this->recipes->expects( $this->once() )
			->method( 'get' )
			->with( 'non-existent' )
			->willReturn( null );

		$result = $this->executor->execute( [ 'extends' => 'non-existent' ] );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Recipe not found: non-existent', $result->get_message() );
	}

	public function test_execute_stops_on_parent_failure(): void {
		$parent_config = [ 'failing_ingredient' => 'value' ];

		$failing_ingredient = $this->createMock( Ingredient::class );
		$failing_ingredient->expects( $this->once() )
			->method( 'execute' )
			->willReturn( new ExecutionResult( false, 'Parent failed', [] ) );

		$this->recipes->expects( $this->once() )
			->method( 'get' )
			->with( 'failing-parent' )
			->willReturn( $parent_config );

		$this->ingredients->expects( $this->once() )
			->method( 'get' )
			->with( 'failing_ingredient' )
			->willReturn( $failing_ingredient );

		$result = $this->executor->execute(
			[
				'extends'          => 'failing-parent',
				'child_ingredient' => 'value',
			]
		);

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Recipe execution failed', $result->get_message() );
		
		// Verify child ingredient was not executed
		$data = $result->get_data();
		$this->assertArrayNotHasKey( 'child_ingredient', $data );
	}
}
