<?php
/**
 * Example: Complete custom ingredient implementation
 *
 * This shows how to create a custom ingredient for your own plugin settings.
 * Copy this pattern to create your own ingredients.
 *
 * @package Whiskey\Examples
 */

declare( strict_types = 1 );

namespace YourPlugin\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;

/**
 * Sets a custom plugin option
 */
class CustomPluginSettingIngredient extends Ingredient {
	public const NAME        = 'custom_plugin_setting';
	public const CATEGORY    = 'custom';
	public const DESCRIPTION = 'Configure your custom plugin setting';

	/**
	 * Validate input - keep it simple, just type checking
	 */
	public function validate( $value ): bool {
		// Accept string or boolean
		return is_string( $value ) || is_bool( $value );
	}

	/**
	 * Execute the configuration
	 */
	public function execute( $value ): ExecutionResult {
		// Normalize the value
		$normalized = $this->normalize_value( $value );

		// Perform the update
		$success = update_option( 'your_plugin_custom_setting', $normalized );

		// Return appropriate result
		if ( ! $success ) {
			return new ExecutionResult(
				false,
				'Failed to update custom plugin setting',
				[ 'attempted_value' => $value ]
			);
		}

		return new ExecutionResult(
			true,
			sprintf( 'Custom setting updated to: %s', $normalized ),
			[
				'setting' => 'your_plugin_custom_setting',
				'value'   => $normalized,
			]
		);
	}

	/**
	 * Helper method to normalize the value
	 */
	private function normalize_value( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'enabled' : 'disabled';
		}

		return sanitize_text_field( $value );
	}
}

// Self-register when Whiskey loads
add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, CustomPluginSettingIngredient::class ]
);
