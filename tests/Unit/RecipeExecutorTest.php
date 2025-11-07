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
use Whiskey\ValidationResult;
use Whiskey\ExecutionStrategy;

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

	// ===== validate() Tests =====

	/**
	 * GIVEN empty configuration
	 * WHEN validating
	 * THEN should return invalid result
	 */
	public function test_validate_returns_false_for_empty_config(): void {
		$result = $this->executor->validate( [] );

		$this->assertFalse( $result->is_valid() );
	}

	/**
	 * GIVEN valid ingredient configuration
	 * WHEN validating
	 * THEN should return valid result
	 */
	public function test_validate_returns_true_for_valid_config(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'validate' )
			->with( 'value' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success' ) ) );

		$this->ingredients->expects( $this->once() )
			->method( 'get' )
			->with( 'ingredient_name' )
			->willReturn( $ingredient );

		$result = $this->executor->validate( [ 'ingredient_name' => 'value' ] );

		$this->assertTrue( $result->is_valid() );
	}

	/**
	 * GIVEN configuration with unknown ingredients
	 * WHEN validating
	 * THEN should skip unknown ingredients and validate known ones
	 *
	 * @dataProvider unknown_ingredients_provider
	 */
	public function test_validate_skips_unknown_ingredients(
		array $config,
		array $ingredient_map,
		bool $expected_valid
	): void {
		$this->ingredients->expects( $this->exactly( count( $config ) ) )
			->method( 'get' )
			->willReturnMap( $ingredient_map );

		$result = $this->executor->validate( $config );

		$this->assertSame( $expected_valid, $result->is_valid() );
	}

	public function unknown_ingredients_provider(): array {
		$valid_ingredient = $this->createMock( Ingredient::class );
		$valid_ingredient->method( 'validate' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success' ) ) );

		return [
			'mixed known and unknown'  => [
				[ 'unknown' => 'value', 'known' => 'valid_value' ],
				[
					[ 'unknown', null ],
					[ 'known', $valid_ingredient ],
				],
				true,
			],
			'only unknown ingredients' => [
				[ 'unknown1' => 'value1', 'unknown2' => 'value2' ],
				[
					[ 'unknown1', null ],
					[ 'unknown2', null ],
				],
				true,
			],
		];
	}

	/**
	 * GIVEN configuration with invalid ingredient value
	 * WHEN validating
	 * THEN should return invalid result
	 */
	public function test_validate_returns_false_when_ingredient_validation_fails(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'validate' )
			->with( 'invalid_value' )
			->willReturn( ValidationResult::invalid_type( 'string' ) );

		$this->ingredients->expects( $this->once() )
			->method( 'get' )
			->with( 'ingredient_name' )
			->willReturn( $ingredient );

		$result = $this->executor->validate( [ 'ingredient_name' => 'invalid_value' ] );

		$this->assertFalse( $result->is_valid() );
	}

	/**
	 * GIVEN configuration with 'extends' keyword
	 * WHEN validating
	 * THEN should flatten parent recipe and validate all ingredients
	 */
	public function test_validate_ignores_extends_keyword(): void {
		// Parent recipe with a dummy ingredient that will be ignored (no ingredient registered)
		$parent_config = [ 'unknown_parent_ingredient' => 'parent_value' ];

		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->method( 'validate' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success' ) ) );

		$recipes = $this->createMock( RecipeRegistry::class );
		$recipes->method( 'get' )
			->willReturnCallback( function ( $name ) use ( $parent_config ) {
				if ( $name === 'parent-recipe' ) {
					return $parent_config;
				}
				return null;
			} );

		$ingredients = $this->createMock( IngredientRegistry::class );
		$ingredients->method( 'get' )
			->willReturnCallback( function ( $name ) use ( $ingredient ) {
				// Only return ingredient for child ingredient, not parent
				if ( $name === 'ingredient_name' ) {
					return $ingredient;
				}
				return null;
			} );

		$executor = new RecipeExecutor( $ingredients, $recipes );

		$result = $executor->validate(
			[
				'extends'         => 'parent-recipe',
				'ingredient_name' => 'value',
			]
		);

		$this->assertTrue( $result->is_valid() );
	}

	// ===== execute() Basic Tests =====

	/**
	 * GIVEN valid ingredient configuration
	 * WHEN executing
	 * THEN should return success result with appropriate message
	 */
	public function test_execute_returns_success_result(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'validate' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success', [] ) ) );

		$this->ingredients->expects( $this->once() )
			->method( 'get' )
			->willReturn( $ingredient );

		$validation_result = $this->executor->validate( [ 'test' => 'value' ] );
		$result            = $validation_result->execute();

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'Recipe executed successfully', $result->get_message() );
	}

	/**
	 * GIVEN configuration with unknown ingredients
	 * WHEN executing
	 * THEN should skip unknown ingredients and execute known ones
	 */
	public function test_execute_skips_unknown_ingredients(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'validate' )
			->with( 'value' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success', [ 'data' => 'result' ] ) ) );

		$this->ingredients->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'unknown', null ],
					[ 'known', $ingredient ],
				]
			);

		$validation_result = $this->executor->validate(
			[
				'unknown' => 'ignored',
				'known'   => 'value',
			]
		);
		$result            = $validation_result->execute();

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'known', $data );
		$this->assertArrayNotHasKey( 'unknown', $data );
	}

	/**
	 * GIVEN multiple ingredients
	 * WHEN executing
	 * THEN should collect all ingredient results in data array
	 */
	public function test_execute_collects_all_ingredient_results(): void {
		$ingredient1 = $this->createMock( Ingredient::class );
		$ingredient1->expects( $this->once() )
			->method( 'validate' )
			->with( 'value1' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success 1', [ 'data' => 'result1' ] ) ) );

		$ingredient2 = $this->createMock( Ingredient::class );
		$ingredient2->expects( $this->once() )
			->method( 'validate' )
			->with( 'value2' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success 2', [ 'data' => 'result2' ] ) ) );

		$this->ingredients->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'ingredient1', $ingredient1 ],
					[ 'ingredient2', $ingredient2 ],
				]
			);

		$validation_result = $this->executor->validate(
			[
				'ingredient1' => 'value1',
				'ingredient2' => 'value2',
			]
		);
		$result            = $validation_result->execute();

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 2, $data );
		$this->assertArrayHasKey( 'ingredient1', $data );
		$this->assertArrayHasKey( 'ingredient2', $data );
	}

	/**
	 * GIVEN ingredient execution result
	 * WHEN storing results
	 * THEN should store as array with success/message/data keys
	 */
	public function test_execute_stores_results_as_arrays(): void {
		$ingredient = $this->createMock( Ingredient::class );
		$ingredient->expects( $this->once() )
			->method( 'validate' )
			->with( 'value' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( false, 'Failed', [ 'error' => 'details' ] ) ) );

		$this->ingredients->expects( $this->once() )
			->method( 'get' )
			->with( 'ingredient_name' )
			->willReturn( $ingredient );

		$validation_result = $this->executor->validate( [ 'ingredient_name' => 'value' ] );
		$result            = $validation_result->execute();

		$data = $result->get_data();
		$this->assertIsArray( $data['ingredient_name'] );
		$this->assertSame( false, $data['ingredient_name']['success'] );
		$this->assertSame( 'Failed', $data['ingredient_name']['message'] );
	}

	/**
	 * GIVEN configuration with dry-run strategy
	 * WHEN executing recipe
	 * THEN should validate ingredients without executing them
	 */
	public function test_execute_validates_ingredients_in_dry_run_mode(): void {
		$ingredient1 = $this->createMock( Ingredient::class );
		$ingredient1->expects( $this->once() )
			->method( 'validate' )
			->with( 'value1' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success 1', [] ) ) );

		$ingredient2 = $this->createMock( Ingredient::class );
		$ingredient2->expects( $this->once() )
			->method( 'validate' )
			->with( 'value2' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success 2', [] ) ) );

		$this->ingredients->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'ingredient1', $ingredient1 ],
					[ 'ingredient2', $ingredient2 ],
				]
			);

		$validation_result = $this->executor->validate(
			[
				'ingredient1' => 'value1',
				'ingredient2' => 'value2',
			]
		);
		$result            = $validation_result->execute( ExecutionStrategy::DRY_RUN );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'Recipe validated successfully (dry-run mode)', $result->get_message() );

		$data = $result->get_data();
		$this->assertCount( 2, $data );

		// Verify both ingredients have dry-run results
		$this->assertArrayHasKey( 'ingredient1', $data );
		$this->assertTrue( $data['ingredient1']['success'] );
		$this->assertSame( 'Validated (not executed)', $data['ingredient1']['message'] );
		$this->assertSame( [], $data['ingredient1']['data'] );

		$this->assertArrayHasKey( 'ingredient2', $data );
		$this->assertTrue( $data['ingredient2']['success'] );
		$this->assertSame( 'Validated (not executed)', $data['ingredient2']['message'] );
		$this->assertSame( [], $data['ingredient2']['data'] );
	}

	// ===== execute() with 'extends' Tests =====

	/**
	 * GIVEN configuration extending single parent recipe
	 * WHEN executing
	 * THEN should execute parent ingredients then child ingredients
	 */
	public function test_execute_handles_single_extends(): void {
		$parent_config = [ 'parent_ingredient' => 'parent_value' ];

		$parent_ingredient = $this->createMock( Ingredient::class );
		$parent_ingredient->expects( $this->once() )
			->method( 'validate' )
			->with( 'parent_value' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Parent success', [] ) ) );

		$child_ingredient = $this->createMock( Ingredient::class );
		$child_ingredient->expects( $this->once() )
			->method( 'validate' )
			->with( 'child_value' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Child success', [] ) ) );

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

		$validation_result = $this->executor->validate(
			[
				'extends'          => 'parent-recipe',
				'child_ingredient' => 'child_value',
			]
		);
		$result            = $validation_result->execute();

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'child_ingredient', $data );
	}

	/**
	 * GIVEN configuration extending multiple parent recipes
	 * WHEN executing
	 * THEN should execute all parent ingredients then child ingredients
	 */
	public function test_execute_handles_multiple_extends(): void {
		$parent1_config = [ 'ingredient1' => 'value1' ];
		$parent2_config = [ 'ingredient2' => 'value2' ];

		$ingredient1 = $this->createMock( Ingredient::class );
		$ingredient1->expects( $this->once() )
			->method( 'validate' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success 1', [] ) ) );

		$ingredient2 = $this->createMock( Ingredient::class );
		$ingredient2->expects( $this->once() )
			->method( 'validate' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success 2', [] ) ) );

		$ingredient3 = $this->createMock( Ingredient::class );
		$ingredient3->expects( $this->once() )
			->method( 'validate' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Success 3', [] ) ) );

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

		$validation_result = $this->executor->validate(
			[
				'extends'     => [ 'parent1', 'parent2' ],
				'ingredient3' => 'value3',
			]
		);
		$result            = $validation_result->execute();

		$this->assertTrue( $result->is_success() );
	}

	/**
	 * GIVEN configuration with circular recipe dependency
	 * WHEN validating
	 * THEN should detect cycle and return error with dependency chain
	 *
	 * @dataProvider circular_dependency_provider
	 */
	public function test_execute_detects_circular_dependencies(
		array $config,
		array $recipe_map,
		string $expected_chain
	): void {
		$this->recipes->expects( $this->atLeastOnce() )
			->method( 'get' )
			->willReturnMap( $recipe_map );

		$result = $this->executor->validate( $config );

		$this->assertFalse( $result->is_valid() );
		$this->assertStringContainsString( 'Circular recipe dependency', $result->get_message() );
		$this->assertStringContainsString( $expected_chain, $result->get_message() );
	}

	public function circular_dependency_provider(): array {
		return [
			'mutual dependency' => [
				[ 'extends' => 'recipe-a' ],
				[
					[ 'recipe-a', [ 'extends' => 'recipe-b' ] ],
					[ 'recipe-b', [ 'extends' => 'recipe-a' ] ],
				],
				'recipe-a -> recipe-b -> recipe-a',
			],
			'self reference'    => [
				[ 'extends' => 'self-recipe' ],
				[
					[ 'self-recipe', [ 'extends' => 'self-recipe' ] ],
				],
				'self-recipe -> self-recipe',
			],
		];
	}

	/**
	 * GIVEN configuration extending non-existent recipe
	 * WHEN validating
	 * THEN should return error indicating recipe not found
	 */
	public function test_execute_returns_error_when_parent_recipe_not_found(): void {
		$this->recipes->expects( $this->once() )
			->method( 'get' )
			->with( 'non-existent' )
			->willReturn( null );

		$result = $this->executor->validate( [ 'extends' => 'non-existent' ] );

		$this->assertFalse( $result->is_valid() );
		$this->assertStringContainsString( 'Recipe not found: non-existent', $result->get_message() );
	}

	/**
	 * GIVEN parent recipe that fails during execution
	 * WHEN executing child recipe
	 * THEN should stop execution and not execute child ingredients
	 */
	public function test_execute_stops_on_parent_failure(): void {
		$parent_config = [ 'failing_ingredient' => 'value' ];

		$failing_ingredient = $this->createMock( Ingredient::class );
		$failing_ingredient->expects( $this->once() )
			->method( 'validate' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( false, 'Parent failed', [] ) ) );

		$child_ingredient = $this->createMock( Ingredient::class );
		$child_ingredient->expects( $this->once() )
			->method( 'validate' )
			->willReturn( ValidationResult::valid( fn() => new ExecutionResult( true, 'Child success', [] ) ) );

		$this->recipes->expects( $this->once() )
			->method( 'get' )
			->with( 'failing-parent' )
			->willReturn( $parent_config );

		$this->ingredients->expects( $this->exactly( 2 ) )
			->method( 'get' )
			->willReturnMap(
				[
					[ 'failing_ingredient', $failing_ingredient ],
					[ 'child_ingredient', $child_ingredient ],
				]
			);

		$validation_result = $this->executor->validate(
			[
				'extends'          => 'failing-parent',
				'child_ingredient' => 'value',
			]
		);
		$result            = $validation_result->execute();

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Recipe execution failed', $result->get_message() );

		// Verify child ingredient was not executed (stops on first failure)
		$data = $result->get_data();
		$this->assertArrayNotHasKey( 'child_ingredient', $data );
	}
}
