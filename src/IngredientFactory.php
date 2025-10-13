<?php
declare( strict_types = 1 );

namespace Whiskey;

use Whiskey\Ingredients\SetHomepageIngredient;

/**
 * @todo untested
 */
class IngredientFactory {
	/**
	 * @var array<string, string> Map ingredient keys to class names
	 */
	private array $ingredients = [
		'set_homepage'        => SetHomepageIngredient::class,
	];

	public function get( string $key ): ?Ingredient {
		if ( ! isset( $this->ingredients[ $key ] ) ) {
			return null;
		}

		$class = $this->ingredients[ $key ];

		return new $class();
	}

	/**
	 * Get all registered ingredient keys
	 */
	public function get_all_keys(): array {
		return array_keys( $this->ingredients );
	}

	/**
	 * Get ingredient metadata for REST endpoint
	 */
	public function get_metadata( string $key ): ?array {
		$ingredient = $this->get( $key );
		if ( ! $ingredient ) {
			return null;
		}

		return [
			'key'      => $key,
			'category' => $ingredient::CATEGORY ?? 'general',
		];
	}
}
