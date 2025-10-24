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
	public const NAME        = 'set_paypal_legacy_mode';
	public const CATEGORY    = IngredientCategory::PAYPAL;
	public const DESCRIPTION = 'Switches PayPal between legacy and modern UI; true = legacy mode, false = modern UI';

	private const OPTION_NEW_MERCHANT = 'woocommerce-ppcp-is-new-merchant';
	private const OPTION_OLD_UI       = 'woocommerce_ppcp-settings-should-use-old-ui';

	public function validate( $value ): ValidationResult {
		if ( ! is_bool( $value ) ) {
			return ValidationResult::invalid_type( 'boolean' );
		}

		return ValidationResult::valid();
	}

	public function execute( $value ): ExecutionResult {
		$changes = [];

		if ( $value ) {
			// Legacy mode: delete new-merchant flag, enable old UI
			$deleted_new_merchant = delete_option( self::OPTION_NEW_MERCHANT );
			$updated_old_ui       = update_option( self::OPTION_OLD_UI, 'yes' );

			$changes['deleted_new_merchant'] = $deleted_new_merchant;
			$changes['enabled_old_ui']       = $updated_old_ui;

			if ( ! $updated_old_ui ) {
				return new ExecutionResult(
					false,
					'Failed to enable legacy UI mode.',
					$changes
				);
			}

			return new ExecutionResult(
				true,
				'PayPal configured to use legacy UI.',
				$changes
			);
		}

		// Modern UI: set new-merchant flag, delete old UI option
		$updated_new_merchant = update_option( self::OPTION_NEW_MERCHANT, '1' );
		$deleted_old_ui       = delete_option( self::OPTION_OLD_UI );

		$changes['enabled_new_merchant'] = $updated_new_merchant;
		$changes['deleted_old_ui']       = $deleted_old_ui;

		if ( ! $updated_new_merchant ) {
			return new ExecutionResult(
				false,
				'Failed to enable modern UI mode.',
				$changes
			);
		}

		return new ExecutionResult(
			true,
			'PayPal configured to use modern UI.',
			$changes
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, SetPayPalLegacyModeIngredient::class ]
);
