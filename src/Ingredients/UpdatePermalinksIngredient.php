<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\IngredientCategory;
use Whiskey\ValidationResult;

/**
 * Updates the permalink structure of the WordPress site.
 * Group: WordPress core
 */
class UpdatePermalinksIngredient extends Ingredient {
	public const NAME        = 'permalink_structure';
	public const CATEGORY    = IngredientCategory::WORDPRESS;
	public const DESCRIPTION = 'Changes the permalink structure; common values: "/%postname%/", "/%year%/%monthnum%/%postname%/", or empty string for default';

	public function validate( mixed $value ): ValidationResult {
		if ( ! is_string( $value ) ) {
			return ValidationResult::invalid_type( 'string' );
		}

		return ValidationResult::valid( fn() => $this->execute( $value ) );
	}

	private function execute( string $value ): ExecutionResult {
		$previous_structure = get_option( 'permalink_structure', '' );

		$updated = update_option( 'permalink_structure', $value );

		if ( ! $updated && $previous_structure !== $value ) {
			return new ExecutionResult(
				success: false,
				message: 'Failed to update permalink structure.',
				data: [ 'previous' => $previous_structure ]
			);
		}

		flush_rewrite_rules();

		return new ExecutionResult(
			success: true,
			message: 'Permalink structure updated and rewrite rules flushed.',
			data: [
				'previous' => $previous_structure,
				'current'  => $value,
			]
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, UpdatePermalinksIngredient::class ]
);
