<?php
declare( strict_types = 1 );

namespace Whiskey;

/**
 * Ingredient category enum.
 */
enum IngredientCategory: string {
	case General = 'general';
	case WordPress = 'wordpress';
	case WooCommerce = 'woocommerce';
	case PayPal = 'paypal';

	/**
	 * Get the display name for this category.
	 *
	 * @return string Display name.
	 */
	public function get_display_name(): string {
		return match ( $this ) {
			self::General => 'Generic',
			self::WordPress => 'WordPress Core',
			self::WooCommerce => 'WooCommerce',
			self::PayPal => 'PayPal Integration',
		};
	}

	/**
	 * Get the CLI color code for this category.
	 *
	 * @return string ANSI color code.
	 */
	public function get_color(): string {
		return match ( $this ) {
			self::WordPress => "\033[94m",   // Blue
			self::WooCommerce => "\033[95m", // Magenta
			self::PayPal => "\033[96m",      // Cyan
			default => "\033[0m",            // Reset
		};
	}
}
