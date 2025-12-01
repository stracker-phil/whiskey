<?php
declare( strict_types = 1 );

namespace Whiskey;

use RuntimeException;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\Registry\RecipeRegistry;

class RecipeExecutor {
	private const EXTENDS = 'extends';

	private IngredientRegistry $ingredients;
	private RecipeRegistry $recipes;

	public function __construct( IngredientRegistry $ingredients, RecipeRegistry $recipes ) {
		$this->ingredients = $ingredients;
		$this->recipes     = $recipes;
	}

	/**
	 * Validate a recipe configuration.
	 *
	 * This is the main entry point. It flattens the recipe (resolving all extends),
	 * validates all ingredients, and returns a ValidationResult that can be executed.
	 *
	 * @param array $config Recipe configuration array
	 * @return ValidationResult
	 */
	public function validate( array $config ): ValidationResult {
		if ( empty( $config ) ) {
			return ValidationResult::invalid_value( 'no configuration found' );
		}

		// Step 1: Flatten/expand the recipe (resolve all extends into a flat list).
		try {
			$flattened = $this->flatten_recipe( $config );
		} catch ( RuntimeException $e ) {
			return ValidationResult::invalid_value( $e->getMessage() );
		}

		// Step 2: Validate all ingredients in the flattened recipe.
		$validation_results = [];

		foreach ( $flattened as $key => $value ) {
			$ingredient = $this->ingredients->get( $key );

			// Unknown ingredients are silently ignored (WordPress pattern).
			if ( ! $ingredient ) {
				continue;
			}

			$result = $ingredient->validate( $value );
			if ( ! $result->is_valid() ) {
				return $result;
			}

			$validation_results[ $key ] = $result;
		}

		// Step 3: Return valid result with simple execute callback
		return ValidationResult::valid(
			fn( string $strategy = ExecutionStrategy::SEQUENTIAL ) => $this->execute( $validation_results, $strategy )
		);
	}

	/**
	 * Flatten a recipe by resolving all extends recursively.
	 *
	 * This converts a recipe with extends into a flat array of ingredient => value pairs.
	 * Later ingredients override earlier ones (child overrides parent).
	 *
	 * @param array $config  Recipe configuration
	 * @param array $visited Array tracking visited recipes for circular dependency detection
	 * @return array Flattened ingredient configuration
	 * @throws RuntimeException If circular dependency detected or recipe not found
	 */
	private function flatten_recipe( array $config, array &$visited = [] ): array {
		$extends = $config[ self::EXTENDS ] ?? null;
		unset( $config[ self::EXTENDS ] );

		$result = [];

		// Process parent recipes first (if any)
		if ( $extends ) {
			$parent_recipes = is_array( $extends ) ? $extends : [ $extends ];

			foreach ( $parent_recipes as $parent_name ) {
				// Check for circular dependency
				if ( in_array( $parent_name, $visited, true ) ) {
					$chain = implode( ' -> ', [ ...$visited, $parent_name ] );
					throw new RuntimeException( "Circular recipe dependency detected: {$chain}" );
				}

				// Track this recipe to detect loops
				$visited[] = $parent_name;

				// Get parent config
				$parent_config = $this->recipes->get( $parent_name );
				if ( ! $parent_config ) {
					throw new RuntimeException( "Recipe not found: {$parent_name}" );
				}

				// Recursively flatten parent
				$parent_flattened = $this->flatten_recipe( $parent_config, $visited );

				// Add parent ingredients (earlier parents can be overridden by later ones)
				foreach ( $parent_flattened as $key => $value ) {
					$result[ $key ] = $value;
				}

				// Remove from visited stack
				array_pop( $visited );
			}
		}

		// Add current config (child overrides parent)
		foreach ( $config as $key => $value ) {
			$result[ $key ] = $value;
		}

		return $result;
	}

	/**
	 * Execute validated ingredients.
	 *
	 * This is a simple loop that executes each validated ingredient according to the strategy.
	 *
	 * @param ValidationResult[] $validation_results Array of ValidationResult objects
	 * @param string             $strategy           Execution strategy
	 * @return ExecutionResult
	 */
	private function execute( array $validation_results, string $strategy ): ExecutionResult {
		$results     = [];
		$has_failure = false;
		$is_dry_run  = ! ExecutionStrategy::should_execute( $strategy );
		$failures    = [];

		foreach ( $validation_results as $key => $validation_result ) {
			if ( $is_dry_run ) {
				$results[ $key ] = [
					'success' => true,
					'message' => 'Validated (not executed)',
					'data'    => [],
				];

				continue;
			}

			// Execute via the pre-validated ValidationResult.
			$result = $validation_result->execute();

			$results[ $key ] = $result->to_array();

			if ( ! $result->is_success() ) {
				$has_failure     = true;
				$failures[ $key ] = $result->get_message();

				// Stop on first failure if strategy requires it.
				if ( ExecutionStrategy::should_stop_on_failure( $strategy ) ) {
					break;
				}
			}
		}

		if ( $is_dry_run ) {
			$message = 'Recipe validated successfully (dry-run mode)';
		} elseif ( $has_failure ) {
			// Build detailed failure message with ingredient names and errors
			$failure_details = [];
			foreach ( $failures as $ingredient => $error ) {
				$failure_details[] = sprintf( '%s: %s', $ingredient, $error );
			}
			$message = 'Recipe execution failed. ' . implode( '; ', $failure_details );
		} else {
			$message = 'Recipe executed successfully';
		}

		return new ExecutionResult(
			! $has_failure,
			$message,
			$results
		);
	}
}
