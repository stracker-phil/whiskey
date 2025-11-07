<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\IngredientCategory;
use Whiskey\ExecutionResult;
use Whiskey\ValidationResult;

/**
 * Activates a WordPress theme by its name.
 * Group: WordPress core
 */
class SetActiveThemeIngredient extends Ingredient {
	public const NAME        = 'set_active_theme';
	public const CATEGORY    = IngredientCategory::WORDPRESS;
	public const DESCRIPTION = 'Activates a theme; specify the theme directory name (e.g., "twentytwentyfour")';

	public function validate( mixed $value ): ValidationResult {
		if ( ! is_string( $value ) ) {
			return ValidationResult::invalid_type( 'string' );
		}

		if ( $value === '' ) {
			return ValidationResult::invalid_value( 'non-empty string' );
		}

		return ValidationResult::valid( fn() => $this->execute( $value ) );
	}

	private function execute( mixed $value ): ExecutionResult {
		$theme = wp_get_theme( $value );

		if ( ! $theme->exists() ) {
			return new ExecutionResult(
				success: false,
				message: "Theme '{$value}' does not exist.",
				data: [ 'theme' => $value ]
			);
		}

		$previous_theme = wp_get_theme();
		$previous_name  = $previous_theme->get_stylesheet();

		switch_theme( $value );

		$current_theme = wp_get_theme();

		if ( $current_theme->get_stylesheet() !== $value ) {
			return new ExecutionResult(
				success: false,
				message: "Failed to activate theme '{$value}'.",
				data: [
					'requested' => $value,
					'previous'  => $previous_name,
					'current'   => $current_theme->get_stylesheet(),
				]
			);
		}

		return new ExecutionResult(
			success: true,
			message: "Theme '{$value}' activated successfully.",
			data: [
				'previous' => $previous_name,
				'current'  => $value,
			]
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, SetActiveThemeIngredient::class ]
);
