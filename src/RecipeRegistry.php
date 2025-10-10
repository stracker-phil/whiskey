<?php
/**
 * Recipe Registry - Collects and manages configuration recipes
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

/**
 * RecipeRegistry class - Central registry for all configuration recipes
 */
final class RecipeRegistry {

	private static ?RecipeRegistry $instance = null;

	/**
	 * @var array<string, array>
	 */
	private array $recipes = [];

	/**
	 * Whether recipes have been collected
	 */
	private bool $initialized = false;

	private function __construct() {
	}

	public static function instance(): RecipeRegistry {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @explain Pass registry instance to hook callbacks for dependency injection.
	 *          This enables testing and provides cleaner API than global singleton access.
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}

		// Collect recipes via action hook - pass registry for DI.
		do_action( 'whiskey:register_recipe', $this );

		$this->initialized = true;
	}

	/**
	 * @explain Called by plugins/themes via the 'whiskey:register_recipe' hook.
	 *          Registry instance is passed as hook parameter for dependency injection.
	 */
	public function register( array $recipe ): void {
		// Validate required fields.
		if ( ! isset( $recipe['name'], $recipe['type'], $recipe['config'] ) ) {
			return;
		}

		// Store by name.
		$this->recipes[ $recipe['name'] ] = $recipe;
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
