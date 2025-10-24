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
		if ( self::GENERAL === $category ) {
			return 'Generic';
		}

		if ( self::WORDPRESS === $category ) {
			return 'WordPress Core';
		}

		if ( self::WOOCOMMERCE === $category ) {
			return 'WooCommerce';
		}

		if ( self::PAYPAL === $category ) {
			return 'PayPal Integration';
		}

		return $category;
	}

	/**
	 * Get the CLI color code for a category.
	 *
	 * @param string $category One of the category constants.
	 * @return string ANSI color code.
	 */
	public static function get_color( string $category ): string {
		if ( self::WORDPRESS === $category ) {
			return "\033[94m"; // Blue
		}

		if ( self::WOOCOMMERCE === $category ) {
			return "\033[95m"; // Magenta
		}

		if ( self::PAYPAL === $category ) {
			return "\033[96m"; // Cyan
		}

		return "\033[0m"; // Reset
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
