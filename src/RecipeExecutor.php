<?php
declare( strict_types = 1 );

namespace Whiskey;

use Whiskey\Registry\IngredientRegistry;
use Whiskey\Registry\RecipeRegistry;

class RecipeExecutor {
	private const EXTENDS = 'extends';

	private IngredientRegistry $ingredients;
	private RecipeRegistry $recipes;
	private array $execution_stack = [];

	public function __construct( IngredientRegistry $ingredients, RecipeRegistry $recipes ) {
		$this->ingredients = $ingredients;
		$this->recipes     = $recipes;
	}

	public function validate( array $config ): ValidationResult {
		if ( empty( $config ) ) {
			return ValidationResult::invalid_value( 'no configuration found' );
		}

		foreach ( $config as $key => $value ) {
			if ( $key === self::EXTENDS ) {
				continue;
			}

			$ingredient = $this->ingredients->get( $key );

			// Unknown ingredients are silently ignored (WordPress pattern).
			if ( ! $ingredient ) {
				continue;
			}

			$result = $ingredient->validate( $value );
			if ( ! $result->is_valid() ) {
				return $result;
			}
		}

		return ValidationResult::valid();
	}

	public function execute( array $config ): ExecutionResult {
		$extends = $config[ self::EXTENDS ] ?? null;
		unset( $config[ self::EXTENDS ] );

		if ( $extends ) {
			$parent_recipes = is_array( $extends ) ? $extends : [ $extends ];

			foreach ( $parent_recipes as $parent_name ) {
				$parent_result = $this->execute_recipe( $parent_name );

				if ( ! $parent_result->is_success() ) {
					return $parent_result;
				}
			}
		}

		// Execute ingredients in this recipe
		$results     = [];
		$has_failure = false;

		foreach ( $config as $key => $value ) {
			$ingredient = $this->ingredients->get( $key );

			if ( ! $ingredient ) {
				continue;
			}

			$result          = $ingredient->execute( $value );
			$results[ $key ] = $result->to_array();

			if ( ! $result->is_success() ) {
				$has_failure = true;
			}
		}

		if ( $has_failure ) {
			return new ExecutionResult(
				false,
				'Recipe execution failed',
				$results
			);
		}

		return new ExecutionResult(
			true,
			'Recipe executed successfully',
			$results
		);
	}

	/**
	 * Execute a recipe by name with circular dependency detection.
	 */
	private function execute_recipe( string $recipe_name ): ExecutionResult {
		// Check for circular dependency
		if ( in_array( $recipe_name, $this->execution_stack, true ) ) {
			$chain = implode( ' -> ', [ ...$this->execution_stack, $recipe_name ] );

			return new ExecutionResult(
				false,
				"Circular recipe dependency detected: {$chain}"
			);
		}

		// Get recipe config
		$config = $this->recipes->get( $recipe_name );

		if ( ! $config ) {
			return new ExecutionResult(
				false,
				"Recipe not found: {$recipe_name}"
			);
		}

		// Track execution to detect loops
		$this->execution_stack[] = $recipe_name;

		// Execute recipe (may include extends)
		$result = $this->execute( $config );

		// Remove from stack after execution
		array_pop( $this->execution_stack );

		return $result;
	}
}
