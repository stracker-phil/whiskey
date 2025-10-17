<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\Registry\IngredientRegistry;

/**
 * Sets the WooCommerce default country/state.
 * Group: WooCommerce
 */
class SetWooCountryIngredient extends Ingredient {
	public const NAME        = 'set_woo_country';
	public const CATEGORY    = 'woocommerce';
	public const DESCRIPTION = 'Sets the default country/state for WooCommerce; accepts "US:CA", "AT", or ["US", "CA"]';

	public function validate( $value ): bool {
		// Accept string like "US:CA" or "AT"
		if ( is_string( $value ) ) {
			// Pattern: 2 letters, optionally followed by colon and 2 more letters
			return (bool) preg_match( '/^\w{2}(:\w{2})?$/', $value );
		}

		// Accept array like ['US', 'CA']
		if ( is_array( $value ) ) {
			return count( $value ) === 2
				&& is_string( $value[0] )
				&& is_string( $value[1] )
				&& preg_match( '/^\w{2}$/', $value[0] )
				&& preg_match( '/^\w{2}$/', $value[1] );
		}

		return false;
	}

	public function execute( $value ): ExecutionResult {
		$previous_value = get_option( 'woocommerce_default_country', '' );

		// Convert array format to string format
		$country_value = $value;
		if ( is_array( $value ) ) {
			$country_value = implode( ':', $value );
		}

		$updated = update_option( 'woocommerce_default_country', $country_value );

		if ( ! $updated && $previous_value !== $country_value ) {
			return new ExecutionResult(
				false,
				'Failed to update WooCommerce default country.',
				[
					'previous'  => $previous_value,
					'requested' => $country_value,
				]
			);
		}

		return new ExecutionResult(
			true,
			"WooCommerce default country updated to '{$country_value}'.",
			[
				'previous' => $previous_value,
				'current'  => $country_value,
			]
		);
	}
}

add_action(
	'whiskey:register_ingredient',
	static fn( IngredientRegistry $registry ) => $registry->add( SetWooCountryIngredient::class )
);
