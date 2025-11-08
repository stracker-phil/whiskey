<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\IngredientCategory;
use Whiskey\ValidationResult;

/**
 * Configures PayPal UI mode (legacy vs modern).
 * Group: PayPal
 */
class SetPayPalLegacyModeIngredient extends Ingredient {
	public const string             NAME        = 'set_paypal_legacy_mode';
	public const IngredientCategory CATEGORY    = IngredientCategory::PayPal;
	public const string             DESCRIPTION = 'Switches PayPal between legacy and modern UI; true = legacy mode, false = modern UI';

	private const string OPTION_NEW_MERCHANT = 'woocommerce-ppcp-is-new-merchant';
	private const string OPTION_OLD_UI       = 'woocommerce_ppcp-settings-should-use-old-ui';

	public function validate( mixed $value ): ValidationResult {
		if ( ! is_bool( $value ) ) {
			return ValidationResult::invalid_type( 'boolean' );
		}

		return ValidationResult::valid( fn() => $this->execute( $value ) );
	}

	private function execute( bool $value ): ExecutionResult {
		$changes = [];

		if ( $value ) {
			// Legacy mode: delete new-merchant flag, enable old UI
			$deleted_new_merchant = delete_option( self::OPTION_NEW_MERCHANT );
			$updated_old_ui       = update_option( self::OPTION_OLD_UI, 'yes' );

			$changes['deleted_new_merchant'] = $deleted_new_merchant;
			$changes['enabled_old_ui']       = $updated_old_ui;

			if ( ! $updated_old_ui ) {
				return new ExecutionResult(
					success: false,
					message: 'Failed to enable legacy UI mode.',
					data: $changes
				);
			}

			return new ExecutionResult(
				success: true,
				message: 'PayPal configured to use legacy UI.',
				data: $changes
			);
		}

		// Modern UI: set new-merchant flag, delete old UI option
		$updated_new_merchant = update_option( self::OPTION_NEW_MERCHANT, '1' );
		$deleted_old_ui       = delete_option( self::OPTION_OLD_UI );

		$changes['enabled_new_merchant'] = $updated_new_merchant;
		$changes['deleted_old_ui']       = $deleted_old_ui;

		if ( ! $updated_new_merchant ) {
			return new ExecutionResult(
				success: false,
				message: 'Failed to enable modern UI mode.',
				data: $changes
			);
		}

		return new ExecutionResult(
			success: true,
			message: 'PayPal configured to use modern UI.',
			data: $changes
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, SetPayPalLegacyModeIngredient::class ]
);
