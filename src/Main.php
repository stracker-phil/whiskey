<?php
/**
 * Main Plugin Bootstrap Class
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

use Whiskey\Controllers\RestController;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;

/**
 * Main plugin bootstrap - manages plugin lifecycle and dependencies
 */
class Main {
	private RecipeRegistry $recipes;
	private IngredientRegistry $ingredients;
	private RestController $rest_controller;

	public function __construct(
		RecipeRegistry $recipes,
		IngredientRegistry $ingredients,
		RestController $rest_controller
	) {
		$this->recipes         = $recipes;
		$this->ingredients     = $ingredients;
		$this->rest_controller = $rest_controller;
	}

	public function init(): void {
		add_action( 'init', [ $this, 'register_components' ], 11 );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function register_components(): void {
		$this->load_builtin_ingredients();
		$this->load_builtin_recipes();

		$this->ingredients->init();
		$this->recipes->init();
	}

	public function register_rest_routes(): void {
		$this->rest_controller->register_routes();
	}

	/**
	 * Load the built-in ingredients from the "Ingredients"-folder.
	 */
	private function load_builtin_ingredients(): void {
		$dir = __DIR__ . '/Ingredients';

		foreach ( glob( $dir . '/*.php' ) as $file ) {
			include_once $file;
		}
	}

	/**
	 * Load the built-in recipes from the "Recipes"-folder.
	 */
	private function load_builtin_recipes(): void {
		$dir = __DIR__ . '/Recipes';

		foreach ( glob( $dir . '/*.php' ) as $file ) {
			include_once $file;
		}
	}
}
