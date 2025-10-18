<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\Registry\IngredientRegistry;

/**
 * Activates a WordPress theme by its name.
 * Group: WordPress core
 */
class SetActiveThemeIngredient extends Ingredient {
	public const NAME        = 'set_active_theme';
	public const CATEGORY    = 'wordpress';
	public const DESCRIPTION = 'Activates a theme; specify the theme directory name (e.g., "twentytwentyfour")';

	public function validate( $value ): bool {
		return is_string( $value ) && $value !== '';
	}

	public function execute( $value ): ExecutionResult {
		$theme = wp_get_theme( $value );

		if ( ! $theme->exists() ) {
			return new ExecutionResult(
				false,
				"Theme '{$value}' does not exist.",
				[ 'theme' => $value ]
			);
		}

		$previous_theme = wp_get_theme();
		$previous_name  = $previous_theme->get_stylesheet();

		switch_theme( $value );

		$current_theme = wp_get_theme();

		if ( $current_theme->get_stylesheet() !== $value ) {
			return new ExecutionResult(
				false,
				"Failed to activate theme '{$value}'.",
				[
					'requested' => $value,
					'previous'  => $previous_name,
					'current'   => $current_theme->get_stylesheet(),
				]
			);
		}

		return new ExecutionResult(
			true,
			"Theme '{$value}' activated successfully.",
			[
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
