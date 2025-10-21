<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;

/**
 * Completely removes all PayPal plugin settings from the database.
 * Group: PayPal
 */
class PayPalStartOverIngredient extends Ingredient {
	public const NAME        = 'paypal_start_over';
	public const CATEGORY    = 'paypal';
	public const DESCRIPTION = 'Deletes all PayPal plugin settings, resetting to pre-installation state';

	private const OPTION_PREFIXES = [
		'woocommerce-ppcp-',
		'woocommerce_ppcp-',
	];

	private const SPECIFIC_OPTIONS = [
		'ppcp-settings',
		'woocommerce_payments_nox_profile',
		'ppcp-webhook-simulation',
		'ppcp-webhook',
	];

	public function validate( $value ): bool {
		// This ingredient doesn't require any input value
		return true;
	}

	public function execute( $value ): ExecutionResult {
		$deleted_options = [];

		// Delete options by prefix
		foreach ( self::OPTION_PREFIXES as $prefix ) {
			$options = $this->get_options_by_prefix( $prefix );
			foreach ( $options as $option_name ) {
				if ( delete_option( $option_name ) ) {
					$deleted_options[] = $option_name;
				}
			}
		}

		// Delete specific options
		foreach ( self::SPECIFIC_OPTIONS as $option_name ) {
			if ( delete_option( $option_name ) ) {
				$deleted_options[] = $option_name;
			}
		}

		if ( count( $deleted_options ) === 0 ) {
			return new ExecutionResult(
				true,
				'No PayPal settings found to delete.',
				[ 'deleted' => [] ]
			);
		}

		return new ExecutionResult(
			true,
			'PayPal settings deleted successfully.',
			[
				'deleted' => $deleted_options,
				'count'   => count( $deleted_options ),
			]
		);
	}

	/**
	 * Get all option names that start with the given prefix.
	 *
	 * @param string $prefix The option name prefix to search for.
	 * @return array List of option names.
	 */
	private function get_options_by_prefix( string $prefix ): array {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( $prefix ) . '%'
		);

		$results = $wpdb->get_col( $sql );

		return is_array( $results ) ? $results : [];
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, PayPalStartOverIngredient::class ]
);
