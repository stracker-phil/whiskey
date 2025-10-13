<?php
declare( strict_types = 1 );

namespace Whiskey;

use Whiskey\Registry\IngredientRegistry;

class RecipeExecutor {
	private IngredientRegistry $ingredients;

	public function __construct( IngredientRegistry $ingredients ) {
		$this->ingredients = $ingredients;
	}

	public function validate( array $config ): bool {
		if ( empty( $config ) ) {
			return false;
		}

		foreach ( $config as $key => $value ) {
			$ingredient = $this->ingredients->get( $key );

			// Unknown ingredients are silently ignored (WordPress pattern).
			if ( ! $ingredient ) {
				continue;
			}

			if ( ! $ingredient->validate( $value ) ) {
				return false;
			}
		}

		return true;
	}

	public function execute( array $config ): ExecutionResult {
		$results = [];

		foreach ( $config as $key => $value ) {
			$ingredient = $this->ingredients->get( $key );

			if ( ! $ingredient ) {
				continue;
			}

			$results[ $key ] = $ingredient->execute( $value );
		}

		return new ExecutionResult(
			true,
			'Recipe executed successfully',
			$results
		);
	}
}
