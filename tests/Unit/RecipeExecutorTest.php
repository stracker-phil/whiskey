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
use Mockery;
use Mockery\MockInterface;

class RecipeExecutorTest extends WhiskeyTest {

	/** @var MockInterface&IngredientRegistry */
	private MockInterface $ingredients;

	private RecipeExecutor $executor;

	protected function setUp(): void {
		parent::setUp();

		$this->ingredients = Mockery::mock( IngredientRegistry::class );
		$this->executor    = new RecipeExecutor( $this->ingredients );
	}

	public function testValidateReturnsFalseForEmptyConfig(): void {
		$result = $this->executor->validate( array() );

		$this->assertFalse( $result );
	}

	public function testValidateReturnsTrueForValidConfig(): void {
		$ingredient = Mockery::mock( Ingredient::class );
		$ingredient->shouldReceive( 'validate' )->with( 'value' )->andReturn( true );

		$this->ingredients->shouldReceive( 'get' )->with( 'ingredient_name' )->andReturn( $ingredient );

		$result = $this->executor->validate( array( 'ingredient_name' => 'value' ) );

		$this->assertTrue( $result );
	}

	public function testValidateSkipsUnknownIngredients(): void {
		$ingredient = Mockery::mock( Ingredient::class );
		$ingredient->shouldReceive( 'validate' )->with( 'valid_value' )->andReturn( true );

		$this->ingredients->shouldReceive( 'get' )->with( 'unknown' )->andReturn( null );
		$this->ingredients->shouldReceive( 'get' )->with( 'known' )->andReturn( $ingredient );

		$result = $this->executor->validate(
			array(
				'unknown' => 'value',
				'known'   => 'valid_value',
			)
		);

		$this->assertTrue( $result );
	}

	public function testValidateReturnsFalseWhenIngredientValidationFails(): void {
		$ingredient = Mockery::mock( Ingredient::class );
		$ingredient->shouldReceive( 'validate' )->with( 'invalid_value' )->andReturn( false );

		$this->ingredients->shouldReceive( 'get' )->with( 'ingredient_name' )->andReturn( $ingredient );

		$result = $this->executor->validate( array( 'ingredient_name' => 'invalid_value' ) );

		$this->assertFalse( $result );
	}

	public function testValidateReturnsTrueWhenOnlyUnknownIngredientsPresent(): void {
		$this->ingredients->shouldReceive( 'get' )->with( 'unknown1' )->andReturn( null );
		$this->ingredients->shouldReceive( 'get' )->with( 'unknown2' )->andReturn( null );

		$result = $this->executor->validate(
			array(
				'unknown1' => 'value1',
				'unknown2' => 'value2',
			)
		);

		$this->assertTrue( $result );
	}

	public function testExecuteSkipsUnknownIngredients(): void {
		$ingredient = Mockery::mock( Ingredient::class );
		$ingredient->shouldReceive( 'execute' )->with( 'value' )->andReturn(
			new ExecutionResult( true, 'Success', array( 'data' => 'result' ) )
		);

		$this->ingredients->shouldReceive( 'get' )->with( 'unknown' )->andReturn( null );
		$this->ingredients->shouldReceive( 'get' )->with( 'known' )->andReturn( $ingredient );

		$result = $this->executor->execute(
			array(
				'unknown' => 'ignored',
				'known'   => 'value',
			)
		);

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'known', $data );
		$this->assertArrayNotHasKey( 'unknown', $data );
	}

	public function testExecuteCollectsAllIngredientResults(): void {
		$ingredient1 = Mockery::mock( Ingredient::class );
		$ingredient1->shouldReceive( 'execute' )->with( 'value1' )->andReturn(
			new ExecutionResult( true, 'Success 1', array( 'data' => 'result1' ) )
		);

		$ingredient2 = Mockery::mock( Ingredient::class );
		$ingredient2->shouldReceive( 'execute' )->with( 'value2' )->andReturn(
			new ExecutionResult( true, 'Success 2', array( 'data' => 'result2' ) )
		);

		$this->ingredients->shouldReceive( 'get' )->with( 'ingredient1' )->andReturn( $ingredient1 );
		$this->ingredients->shouldReceive( 'get' )->with( 'ingredient2' )->andReturn( $ingredient2 );

		$result = $this->executor->execute(
			array(
				'ingredient1' => 'value1',
				'ingredient2' => 'value2',
			)
		);

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertCount( 2, $data );
		$this->assertArrayHasKey( 'ingredient1', $data );
		$this->assertArrayHasKey( 'ingredient2', $data );
	}

	public function testExecuteReturnsSuccessResult(): void {
		$ingredient = Mockery::mock( Ingredient::class );
		$ingredient->shouldReceive( 'execute' )->andReturn(
			new ExecutionResult( true, 'Success', array() )
		);

		$this->ingredients->shouldReceive( 'get' )->andReturn( $ingredient );

		$result = $this->executor->execute( array( 'test' => 'value' ) );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'Recipe executed successfully', $result->get_message() );
	}

	public function testExecuteStoresResultsAsArrays(): void {
		$ingredient = Mockery::mock( Ingredient::class );
		$ingredient->shouldReceive( 'execute' )->with( 'value' )->andReturn(
			new ExecutionResult( false, 'Failed', array( 'error' => 'details' ) )
		);

		$this->ingredients->shouldReceive( 'get' )->with( 'ingredient_name' )->andReturn( $ingredient );

		$result = $this->executor->execute( array( 'ingredient_name' => 'value' ) );

		$data = $result->get_data();
		$this->assertIsArray( $data['ingredient_name'] );
		$this->assertSame( false, $data['ingredient_name']['success'] );
		$this->assertSame( 'Failed', $data['ingredient_name']['message'] );
	}
}
