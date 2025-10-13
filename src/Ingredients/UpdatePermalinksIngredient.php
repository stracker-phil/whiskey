<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\Registry\IngredientRegistry;

/**
 * Updates the permalink structure of the WordPress site.
 * Group: WordPress core
 *
 * @todo untested
 */
class UpdatePermalinksIngredient extends Ingredient {
	public const NAME        = 'permalink_structure';
	public const CATEGORY    = 'wordpress';
	public const DESCRIPTION = 'Changes the permalink structure; common values: "/%postname%/", "/%year%/%monthnum%/%postname%/", or empty string for default';

	public function validate( $value ): bool {
		return is_string( $value );
	}

	public function execute( $value ): ExecutionResult {
		$previous_structure = get_option( 'permalink_structure', '' );

		$updated = update_option( 'permalink_structure', $value );

		if ( ! $updated && $previous_structure !== $value ) {
			return new ExecutionResult(
				false,
				'Failed to update permalink structure.',
				[ 'previous' => $previous_structure ]
			);
		}

		flush_rewrite_rules();

		return new ExecutionResult(
			true,
			'Permalink structure updated and rewrite rules flushed.',
			[
				'previous' => $previous_structure,
				'current'  => $value,
			]
		);
	}
}

add_action(
	'whiskey:register_ingredient',
	static fn( IngredientRegistry $registry ) => $registry->add( UpdatePermalinksIngredient::class )
);
