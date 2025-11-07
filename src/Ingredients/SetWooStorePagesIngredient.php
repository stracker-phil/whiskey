<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use WP_Post;
use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\IngredientCategory;
use Whiskey\ValidationResult;

/**
 * Sets WooCommerce store pages (cart, checkout, my-account).
 * Group: WooCommerce
 */
class SetWooStorePagesIngredient extends Ingredient {
	public const NAME        = 'set_woo_store_pages';
	public const CATEGORY    = IngredientCategory::WOOCOMMERCE;
	public const DESCRIPTION = 'Configures WooCommerce store pages; accepts array with keys: cart, checkout, my-account';

	private const ALLOWED_KEYS = [ 'cart', 'checkout', 'my-account' ];
	private const OPTION_MAP   = [
		'cart'       => 'woocommerce_cart_page_id',
		'checkout'   => 'woocommerce_checkout_page_id',
		'my-account' => 'woocommerce_myaccount_page_id',
	];

	public function validate( $value ): ValidationResult {
		if ( ! is_array( $value ) ) {
			return ValidationResult::invalid_type( 'array' );
		}

		if ( count( $value ) === 0 ) {
			return ValidationResult::invalid_array_structure();
		}

		foreach ( $value as $key => $slug ) {
			if ( ! in_array( $key, self::ALLOWED_KEYS, true ) ) {
				return ValidationResult::invalid_value( 'allowed keys: cart, checkout, my-account' );
			}

			if ( ! is_string( $slug ) ) {
				return ValidationResult::invalid_type( 'string values for page slugs' );
			}
		}

		return ValidationResult::valid( fn() => $this->execute( $value ) );
	}

	private function execute( $value ): ExecutionResult {
		$results = [];
		$errors  = [];

		foreach ( $value as $key => $slug ) {
			$page = get_page_by_path( $slug );

			if ( ! $page instanceof WP_Post ) {
				$errors[ $key ] = "Page '{$slug}' not found";
				continue;
			}

			$option_name    = self::OPTION_MAP[ $key ];
			$previous_value = get_option( $option_name, 0 );

			$updated = update_option( $option_name, $page->ID );

			if ( ! $updated && (int) $previous_value !== $page->ID ) {
				$errors[ $key ] = "Failed to update option '{$option_name}'";
				continue;
			}

			$results[ $key ] = [
				'page_id'  => $page->ID,
				'slug'     => $slug,
				'previous' => (int) $previous_value,
			];
		}

		if ( count( $errors ) > 0 ) {
			return new ExecutionResult(
				false,
				'Failed to update some WooCommerce store pages.',
				[
					'updated' => $results,
					'errors'  => $errors,
				]
			);
		}

		return new ExecutionResult(
			true,
			'WooCommerce store pages updated successfully.',
			[ 'pages' => $results ]
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, SetWooStorePagesIngredient::class ]
);
