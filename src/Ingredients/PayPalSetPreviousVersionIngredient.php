<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\IngredientCategory;
use Whiskey\ValidationResult;

/**
 * Sets the PayPal plugin installed version to trigger update logic.
 * Group: PayPal
 */
class PayPalSetPreviousVersionIngredient extends Ingredient {
	public const NAME        = 'paypal_set_previous_version';
	public const CATEGORY    = IngredientCategory::PAYPAL;
	public const DESCRIPTION = 'Sets the stored PayPal plugin version to trigger update logic; accepts version string (e.g., "2.0.0") or empty string to delete';

	private const OPTION_NAME = 'woocommerce-ppcp-version';

	public function validate( mixed $value ): ValidationResult {
		if ( ! is_string( $value ) ) {
			return ValidationResult::invalid_type( 'string' );
		}

		// Allow empty string (for deletion)
		if ( $value === '' ) {
			return ValidationResult::valid( fn() => $this->execute( '' ) );
		}

		// Validate semantic version format (e.g., "2.0.0", "1.2.3-beta")
		if ( preg_match( '/^\d+\.\d+\.\d+/', $value ) !== 1 ) {
			return ValidationResult::invalid_format( 'semantic version (e.g., "2.0.0", "1.2.3-beta")' );
		}

		return ValidationResult::valid( fn() => $this->execute( $value ) );
	}

	private function execute( string $value ): ExecutionResult {
		$previous_value = get_option( self::OPTION_NAME, '' );

		// Empty string means delete the option
		if ( $value === '' ) {
			$deleted = delete_option( self::OPTION_NAME );

			if ( ! $deleted && $previous_value !== '' ) {
				return new ExecutionResult(
					success: false,
					message: 'Failed to delete PayPal version option.',
					data: [
						'previous' => $previous_value,
						'option'   => self::OPTION_NAME,
					]
				);
			}

			return new ExecutionResult(
				success: true,
				message: 'PayPal version option deleted successfully.',
				data: [
					'previous' => $previous_value,
					'action'   => 'deleted',
					'option'   => self::OPTION_NAME,
				]
			);
		}

		// Set the version
		$updated = update_option( self::OPTION_NAME, $value );

		if ( ! $updated && $previous_value !== $value ) {
			return new ExecutionResult(
				success: false,
				message: 'Failed to update PayPal version.',
				data: [
					'previous'  => $previous_value,
					'requested' => $value,
					'option'    => self::OPTION_NAME,
				]
			);
		}

		return new ExecutionResult(
			success: true,
			message: "PayPal version set to '{$value}'.",
			data: [
				'previous' => $previous_value,
				'current'  => $value,
				'option'   => self::OPTION_NAME,
			]
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, PayPalSetPreviousVersionIngredient::class ]
);
