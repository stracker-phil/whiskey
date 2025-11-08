<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\IngredientCategory;
use Whiskey\ValidationResult;

/**
 * Manages the PayPal BCDC migration override setting.
 * Group: PayPal
 */
class PayPalBcdcOverrideIngredient extends Ingredient {
	public const string             NAME        = 'paypal_bcdc_override';
	public const IngredientCategory CATEGORY    = IngredientCategory::PayPal;
	public const string             DESCRIPTION = 'Controls PayPal BCDC migration override: true to enable, false to delete, array to set custom data';

	private const string OPTION_KEY = 'woocommerce_paypal_payments_bcdc_migration_override';

	public function validate( mixed $value ): ValidationResult {
		if ( is_bool( $value ) || is_array( $value ) ) {
			return ValidationResult::valid( fn() => $this->execute( $value ) );
		}

		return ValidationResult::invalid_type( 'boolean or array' );
	}

	private function execute( bool|array $value ): ExecutionResult {
		$previous_value = get_option( self::OPTION_KEY, null );

		// Enable override.
		if ( true === $value ) {
			$updated = update_option( self::OPTION_KEY, true );

			if ( ! $updated && $previous_value !== true ) {
				return new ExecutionResult(
					success: false,
					message: 'Failed to enable BCDC override flag.',
					data: [
						'previous'  => $previous_value,
						'requested' => true,
					]
				);
			}

			return new ExecutionResult(
				success: true,
				message: 'BCDC override flag enabled.',
				data: [
					'previous' => $previous_value,
					'current'  => true,
				]
			);
		}

		// Handle array: save custom data
		if ( is_array( $value ) ) {
			$updated = update_option( self::OPTION_KEY, $value );

			if ( ! $updated && $previous_value !== $value ) {
				return new ExecutionResult(
					success: false,
					message: 'Failed to update BCDC override data.',
					data: [
						'previous'  => $previous_value,
						'requested' => $value,
					]
				);
			}

			return new ExecutionResult(
				success: true,
				message: 'BCDC override data updated.',
				data: [
					'previous' => $previous_value,
					'current'  => $value,
				]
			);
		}

		// Every other case: Delete the option (unset)
		$deleted = delete_option( self::OPTION_KEY );

		if ( ! $deleted && $previous_value !== null ) {
			return new ExecutionResult(
				success: false,
				message: 'Failed to delete BCDC override flag.',
				data: [ 'previous' => $previous_value ]
			);
		}

		return new ExecutionResult(
			success: true,
			message: 'BCDC override flag deleted.',
			data: [
				'previous' => $previous_value,
				'current'  => null,
			]
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, PayPalBcdcOverrideIngredient::class ]
);
