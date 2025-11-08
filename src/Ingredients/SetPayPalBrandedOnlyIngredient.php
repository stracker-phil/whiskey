<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\IngredientCategory;
use Whiskey\ValidationResult;

/**
 * Configures PayPal installation path (branded-only vs white-label).
 * Group: PayPal
 */
class SetPayPalBrandedOnlyIngredient extends Ingredient {
	public const string             NAME        = 'set_paypal_branded_only';
	public const IngredientCategory CATEGORY    = IngredientCategory::PayPal;
	public const string             DESCRIPTION = 'Sets PayPal installation mode; true = branded-only (core-profiler), false = white-label (direct)';

	private const string OPTION_NOX_PROFILE = 'woocommerce_payments_nox_profile';
	private const string OPTION_DATA_COMMON = 'woocommerce-ppcp-data-common';
	private const string PATH_BRANDED       = 'core-profiler';
	private const string PATH_DIRECT        = 'direct';

	public function validate( mixed $value ): ValidationResult {
		if ( ! is_bool( $value ) ) {
			return ValidationResult::invalid_type( 'boolean' );
		}

		return ValidationResult::valid( fn() => $this->execute( $value ) );
	}

	private function execute( bool $value ): ExecutionResult {
		// Always delete nox_profile option
		$deleted_nox = delete_option( self::OPTION_NOX_PROFILE );

		// Load and prepare data-common option
		$data_common = get_option( self::OPTION_DATA_COMMON, [] );

		if ( ! is_array( $data_common ) ) {
			$data_common = [];
		}

		$previous_path = $data_common['wc_installation_path'] ?? null;

		// Set installation path based on mode
		$data_common['wc_installation_path'] = $value ? self::PATH_BRANDED : self::PATH_DIRECT;

		$updated = update_option( self::OPTION_DATA_COMMON, $data_common );

		if ( ! $updated && $previous_path !== $data_common['wc_installation_path'] ) {
			return new ExecutionResult(
				success: false,
				message: 'Failed to update PayPal installation path.',
				data: [
					'deleted_nox_profile' => $deleted_nox,
					'previous_path'       => $previous_path,
					'requested_path'      => $data_common['wc_installation_path'],
				]
			);
		}

		$mode = $value ? 'branded-only (core-profiler)' : 'white-label (direct)';

		return new ExecutionResult(
			success: true,
			message: "PayPal configured to {$mode} mode.",
			data: [
				'deleted_nox_profile' => $deleted_nox,
				'previous_path'       => $previous_path,
				'current_path'        => $data_common['wc_installation_path'],
			]
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, SetPayPalBrandedOnlyIngredient::class ]
);
