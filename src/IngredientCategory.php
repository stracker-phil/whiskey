<?php
declare( strict_types = 1 );

namespace Whiskey;

/**
 * Ingredient category constants and helpers.
 */
class IngredientCategory {
	public const GENERAL     = 'general';
	public const WORDPRESS   = 'wordpress';
	public const WOOCOMMERCE = 'woocommerce';
	public const PAYPAL      = 'paypal';

	/**
	 * Get the display name for a category.
	 *
	 * @param string $category One of the category constants.
	 * @return string Display name.
	 */
	public static function get_display_name( string $category ): string {
		return match ( $category ) {
			self::GENERAL => 'Generic',
			self::WORDPRESS => 'WordPress Core',
			self::WOOCOMMERCE => 'WooCommerce',
			self::PAYPAL => 'PayPal Integration',
			default => $category,
		};
	}

	/**
	 * Get the CLI color code for a category.
	 *
	 * @param string $category One of the category constants.
	 * @return string ANSI color code.
	 */
	public static function get_color( string $category ): string {
		return match ( $category ) {
			self::WORDPRESS => "\033[94m", // Blue
			self::WOOCOMMERCE => "\033[95m", // Magenta
			self::PAYPAL => "\033[96m", // Cyan
			default => "\033[0m", // Reset
		};
	}

	/**
	 * Get all available categories.
	 *
	 * @return array Array of category constants.
	 */
	public static function all(): array {
		return [
			self::GENERAL,
			self::WORDPRESS,
			self::WOOCOMMERCE,
			self::PAYPAL,
		];
	}

	/**
	 * Check if a string is a valid category.
	 *
	 * @param string $category Category to validate.
	 * @return bool True if valid.
	 */
	public static function is_valid( string $category ): bool {
		return in_array( $category, self::all(), true );
	}
}
