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
		add_action( 'init', [ $this, 'register_components' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	public function register_components(): void {
		$this->load_builtin_recipes();
		$this->registry->init();
	}

	public function register_rest_routes(): void {
		$this->rest_controller->register_routes();
	}

	private function load_builtin_recipes(): void {
		if ( ! is_dir( WHISKEY_RECIPES_DIR ) ) {
			return;
		}

		foreach ( glob( WHISKEY_RECIPES_DIR . '/*.php' ) as $file ) {
			require_once $file;
		}
	}
}
