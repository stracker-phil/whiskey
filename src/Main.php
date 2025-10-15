<?php
/**
 * Main Plugin Bootstrap Class
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

use Whiskey\Controllers\RestController;
use Whiskey\Controllers\CliController;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;

/**
 * Main plugin bootstrap - manages plugin lifecycle and dependencies
 */
class Main {
	private RecipeRegistry $recipes;
	private IngredientRegistry $ingredients;
	private RestController $rest_controller;
	private CliController $cli_controller;

	public function __construct(
		RecipeRegistry $recipes,
		IngredientRegistry $ingredients,
		RestController $rest_controller,
		CliController $cli_controller
	) {
		$this->recipes         = $recipes;
		$this->ingredients     = $ingredients;
		$this->rest_controller = $rest_controller;
		$this->cli_controller  = $cli_controller;

		$this->init();
	}

	/**
	 * Wire up the app modules.
	 */
	protected function init(): void {
		add_action( 'init', function () {
			$this->load_builtin_ingredients();
			$this->load_builtin_recipes();
			$this->ingredients->init();
			$this->recipes->init();
		}, 11 );

		add_action( 'rest_api_init', function () {
			$this->rest_controller->register_routes();
		} );
		add_action( 'cli_init', function () {
			$this->cli_controller->register_commands();
		} );
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
