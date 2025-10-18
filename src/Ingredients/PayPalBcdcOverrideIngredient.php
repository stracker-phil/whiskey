<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;

/**
 * Manages the PayPal BCDC migration override setting.
 * Group: PayPal
 */
class PayPalBcdcOverrideIngredient extends Ingredient {
	public const NAME        = 'paypal_bcdc_override';
	public const CATEGORY    = 'paypal';
	public const DESCRIPTION = 'Controls PayPal BCDC migration override: true to enable, false to delete, array to set custom data';

	private const OPTION_KEY = 'woocommerce_paypal_payments_bcdc_migration_override';

	public function validate( $value ): bool {
		return is_bool( $value ) || is_array( $value );
	}

	public function execute( $value ): ExecutionResult {
		$previous_value = get_option( self::OPTION_KEY, null );

		// Enable override.
		if ( true === $value ) {
			$updated = update_option( self::OPTION_KEY, true );

			if ( ! $updated && $previous_value !== true ) {
				return new ExecutionResult(
					false,
					'Failed to enable BCDC override flag.',
					[
						'previous'  => $previous_value,
						'requested' => true,
					]
				);
			}

			return new ExecutionResult(
				true,
				'BCDC override flag enabled.',
				[
					'previous' => $previous_value,
					'current'  => true,
				]
			);
		}

		// Delete the option (unset)
		if ( false === $value ) {
			$deleted = delete_option( self::OPTION_KEY );

			if ( ! $deleted && $previous_value !== null ) {
				return new ExecutionResult(
					false,
					'Failed to delete BCDC override flag.',
					[ 'previous' => $previous_value ]
				);
			}

			return new ExecutionResult(
				true,
				'BCDC override flag deleted.',
				[
					'previous' => $previous_value,
					'current'  => null,
				]
			);
		}

		// Handle array: save custom data
		if ( is_array( $value ) ) {
			$updated = update_option( self::OPTION_KEY, $value );

			if ( ! $updated && $previous_value !== $value ) {
				return new ExecutionResult(
					false,
					'Failed to update BCDC override data.',
					[
						'previous'  => $previous_value,
						'requested' => $value,
					]
				);
			}

			return new ExecutionResult(
				true,
				'BCDC override data updated.',
				[
					'previous' => $previous_value,
					'current'  => $value,
				]
			);
		}

		// Should never reach here due to validation
		return new ExecutionResult(
			false,
			'Invalid value type.',
			[ 'value' => $value ]
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, PayPalBcdcOverrideIngredient::class ]
);
