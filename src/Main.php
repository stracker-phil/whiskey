<?php
/**
 * Main Plugin Bootstrap Class
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

use Whiskey\Controllers\RestController;

/**
 * Main plugin bootstrap - manages plugin lifecycle and dependencies
 */
class Main {
	private RecipeRegistry $registry;
	private RestController $rest_controller;

	public function __construct(
		RecipeRegistry $registry,
		RestController $rest_controller
	) {
		$this->registry        = $registry;
		$this->rest_controller = $rest_controller;
	}

	public function init(): void {
		add_action( 'init', [ $this, 'register_components' ], 11 );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function register_components(): void {
		$this->load_builtin_recipes();
		$this->registry->init();
	}

	public function register_rest_routes(): void {
		$this->rest_controller->register_routes();
	}

	/**
	 * Load the built-in recipes from the "Recipes"-folder.
	 */
	private function load_builtin_recipes(): void {
		$recipes_dir = __DIR__ . '/Recipes';

		foreach ( glob( $recipes_dir . '/*.php' ) as $file ) {
			include_once $file;
		}
	}
}
