<?php
/**
 * Ingredient Registry - Collects and manages ingredients
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Registry;

use Whiskey\Ingredient;

class IngredientRegistry {

	private array $ingredients = [];

	private bool $initialized = false;

	/**
	 * @explain Pass registry instance to hook callbacks for dependency injection.
	 *          This enables testing and provides cleaner API than global singleton access.
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		do_action( 'whiskey:register_ingredient', $this );

		$this->initialized = true;
	}

	/**
	 * @param string $name       Unique name of the recipe. If a recipe with the
	 *                           same name exists, it is replaced
	 * @param string $ingredient Class name of the ingredient implementation.
	 */
	public function add( string $name, string $ingredient ): void {
		if ( empty( $name ) || ! class_exists( $ingredient ) ) {
			return;
		}

		$this->ingredients[ $name ] = $ingredient;
	}

	public function get( string $name ): ?Ingredient {
		$this->init();

		if ( ! isset( $this->ingredients[ $name ] ) ) {
			return null;
		}

		$class = $this->ingredients[ $name ];

		return new $class();
	}

	public function all(): array {
		$this->init();

		return $this->ingredients;
	}

	public function has( string $name ): bool {
		$this->init();

		return isset( $this->ingredients[ $name ] );
	}

	public function all_metadata(): array {
		$this->init();

		$result = [];
		foreach ( $this->ingredients as $key => $class ) {
			$ingredient = new $class();

			$result[ $key ] = [
				'key'      => $key,
				'category' => $ingredient::CATEGORY,
			];
		}

		return $result;
	}
}
