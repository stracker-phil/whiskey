<?php
/**
 * Recipe Registry - Collects and manages configuration recipes
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Registry;

use Throwable;

class RecipeRegistry {

	/**
	 * @var array<string, array>
	 */
	private array $recipes = [];

	private bool $initialized = false;

	/**
	 * @explain Pass registry instance to hook callbacks for dependency injection.
	 *          This enables testing and provides cleaner API than global singleton access.
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		$items = apply_filters( 'whiskey:register_recipes', [] );

		foreach ( $items as $name => $ingredients ) {
			try {
				$this->add( $name, $ingredients );
			} catch ( Throwable $e ) {
				// Skip invalid recipes silently
				continue;
			}
		}

		$this->initialized = true;
	}

	/**
	 * @param string $name        Unique name of the recipe. If a recipe with the
	 *                            same name exists, it is replaced
	 * @param array  $ingredients List of ingredients to execute, with configuration.
	 */
	public function add( string $name, array $ingredients ): void {
		if ( empty( $name ) || empty( $ingredients ) ) {
			return;
		}

		$this->recipes[ $name ] = $ingredients;
	}

	public function get( string $name ): ?array {
		$this->init();

		return $this->recipes[ $name ] ?? null;
	}

	/**
	 * @return array<string, array>
	 */
	public function all(): array {
		$this->init();

		return $this->recipes;
	}

	public function has( string $name ): bool {
		$this->init();

		return isset( $this->recipes[ $name ] );
	}
}
