<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\IngredientCategory;
use Whiskey\ValidationResult;

/**
 * Sets the WooCommerce default country/state.
 * Group: WooCommerce
 */
class SetWooCountryIngredient extends Ingredient {
	public const string             NAME        = 'set_woo_country';
	public const IngredientCategory CATEGORY    = IngredientCategory::WooCommerce;
	public const string             DESCRIPTION = 'Sets the default country/state for WooCommerce; accepts "US:CA", "AT", or ["US", "CA"]';

	public function validate( mixed $value ): ValidationResult {
		// Accept string like "US:CA" or "AT"
		if ( is_string( $value ) ) {
			if ( ! preg_match( '/^\w{2}(:\w{2})?$/', $value ) ) {
				return ValidationResult::invalid_format( 'country code format (e.g., "US:CA" or "AT")' );
			}

			return ValidationResult::valid( fn() => $this->execute( $value ) );
		}

		// Accept array like ['US', 'CA']
		if ( is_array( $value ) ) {
			if ( count( $value ) !== 2 ) {
				return ValidationResult::invalid_array_structure();
			}

			if ( ! is_string( $value[0] ) || ! is_string( $value[1] ) ) {
				return ValidationResult::invalid_type( 'array with two strings' );
			}

			if ( ! preg_match( '/^\w{2}$/', $value[0] ) || ! preg_match( '/^\w{2}$/', $value[1] ) ) {
				return ValidationResult::invalid_format( 'two-letter country and state codes' );
			}

			return ValidationResult::valid( fn() => $this->execute( $value ) );
		}

		return ValidationResult::invalid_type( 'string or array' );
	}

	private function execute( string|array $value ): ExecutionResult {
		$previous_value = get_option( 'woocommerce_default_country', '' );

		// Convert array format to string format
		$country_value = $value;
		if ( is_array( $value ) ) {
			$country_value = implode( ':', $value );
		}

		$updated = update_option( 'woocommerce_default_country', $country_value );

		if ( ! $updated && $previous_value !== $country_value ) {
			return new ExecutionResult(
				success: false,
				message: 'Failed to update WooCommerce default country.',
				data: [
					'previous'  => $previous_value,
					'requested' => $country_value,
				]
			);
		}

		return new ExecutionResult(
			success: true,
			message: "WooCommerce default country updated to '{$country_value}'.",
			data: [
				'previous' => $previous_value,
				'current'  => $country_value,
			]
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, SetWooCountryIngredient::class ]
);
