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

		do_action( 'whiskey:register_recipe', $this );

		$this->initialized = true;
	}

	/**
	 * @explain Called by plugins/themes via the 'whiskey:register_recipe' hook.
	 *          Registry instance is passed as hook parameter for dependency injection.
	 */
	public function add( string $type, string $name, array $config ): void {
		if ( empty( $name ) || empty( $type ) || empty( $config ) ) {
			return;
		}

		$this->recipes[ $name ] = [ 'type' => $type, 'config' => $config ];
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
