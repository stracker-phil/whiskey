<?php
/**
 * Plugin name: Whiskey - my configurations
 * Description: Provides custom ingredients and recipes for Whiskey
 */

namespace MyPlugin\Whiskey;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\ValidationResult;
use Whiskey\Registry\IngredientRegistry;

/**
 * First: Define (or load) custom ingredient classes.
 */
class MySettingIngredient extends Ingredient {
	public const NAME        = 'my_setting';
	public const CATEGORY    = 'myplugin';
	public const DESCRIPTION = 'Configure my plugin';

	public function validate( $value ): ValidationResult {
		if ( ! is_string( $value ) ) {
			return ValidationResult::invalid_type( 'string' );
		}

		// Return valid result with execution callback
		return ValidationResult::valid( fn() => $this->execute( $value ) );
	}

	private function execute( $value ): ExecutionResult {
		update_option( 'my_plugin_setting', $value );

		return new ExecutionResult( true, 'Setting updated', [ 'value' => $value ] );
	}
}

/**
 * Second: Register all custom ingredients
 */
add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, MySettingIngredient::class ]
);

/**
 * Third Create a recipe using custom or pre-defined ingredients
 */
add_filter(
	'whiskey:register_recipes',
	static fn( array $items ) => array_merge( $items, [
		'my-plugin-setup' => [
			MySettingIngredient::NAME => 'production',
			// Other ingredients...
		],
	] )
);

/**
 * Use your recipe via REST or CLI:
 *
 * - POST /wp-json/whiskey/v1/recipe/my-plugin-setup/apply
 * - wp whiskey apply my-plugin-setup
 */
