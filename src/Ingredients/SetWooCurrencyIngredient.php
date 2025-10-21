<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;

/**
 * Sets the WooCommerce currency.
 * Group: WooCommerce
 */
class SetWooCurrencyIngredient extends Ingredient {
	public const NAME        = 'set_woo_currency';
	public const CATEGORY    = 'woocommerce';
	public const DESCRIPTION = 'Sets the currency for WooCommerce; accepts 3-letter currency code (e.g., "USD", "EUR", "GBP")';

	public function validate( $value ): bool {
		return is_string( $value ) && preg_match( '/^[A-Z]{3}$/', $value ) === 1;
	}

	public function execute( $value ): ExecutionResult {
		$previous_value = get_option( 'woocommerce_currency', '' );

		$updated = update_option( 'woocommerce_currency', $value );

		if ( ! $updated && $previous_value !== $value ) {
			return new ExecutionResult(
				false,
				'Failed to update WooCommerce currency.',
				[
					'previous'  => $previous_value,
					'requested' => $value,
				]
			);
		}

		return new ExecutionResult(
			true,
			"WooCommerce currency updated to '{$value}'.",
			[
				'previous' => $previous_value,
				'current'  => $value,
			]
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, SetWooCurrencyIngredient::class ]
);
