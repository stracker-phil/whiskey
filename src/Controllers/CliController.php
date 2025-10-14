<?php
/**
 * WP-CLI Controller
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Controllers;

use WP_CLI;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\RecipeExecutor;

/**
 * WP-CLI commands for Whiskey plugin
 */
class CliController {

	private RecipeRegistry $recipes;
	private IngredientRegistry $ingredients;
	private RecipeExecutor $executor;

	public function __construct( RecipeRegistry $recipes, IngredientRegistry $ingredients, RecipeExecutor $executor ) {
		$this->recipes     = $recipes;
		$this->ingredients = $ingredients;
		$this->executor    = $executor;
	}

	/**
	 * Register WP-CLI commands
	 */
	public function register_commands(): void {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		WP_CLI::add_command( 'whiskey recipes', [ $this, 'list_recipes' ] );
		WP_CLI::add_command( 'whiskey recipe', [ $this, 'show_recipe' ] );
		WP_CLI::add_command( 'whiskey apply', [ $this, 'apply_recipe' ] );
		WP_CLI::add_command( 'whiskey ingredients', [ $this, 'list_ingredients' ] );
		WP_CLI::add_command( 'whiskey status', [ $this, 'show_status' ] );
	}

	/**
	 * List all available recipes.
	 *
	 * ## EXAMPLES
	 *
	 *     wp whiskey recipes
	 *
	 * @when after_wp_load
	 */
	public function list_recipes(): void {
		$recipes = array_keys( $this->recipes->all() );

		if ( empty( $recipes ) ) {
			WP_CLI::warning( 'No recipes registered.' );
			return;
		}

		WP_CLI::log( 'Available recipes:' );
		foreach ( $recipes as $recipe ) {
			WP_CLI::log( "  - {$recipe}" );
		}

		WP_CLI::success( sprintf( 'Found %d recipe(s).', count( $recipes ) ) );
	}

	/**
	 * Show details of a specific recipe.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The recipe name.
	 *
	 * ## EXAMPLES
	 *
	 *     wp whiskey recipe us_merchant
	 *
	 * @when after_wp_load
	 */
	public function show_recipe( array $args ): void {
		$name = $args[0] ?? null;

		if ( ! $name ) {
			WP_CLI::error( 'Recipe name is required.' );
		}

		$recipe = $this->recipes->get( $name );

		if ( ! $recipe ) {
			WP_CLI::error( sprintf( 'Recipe not found: %s', $name ) );
		}

		WP_CLI::log( sprintf( 'Recipe: %s', $name ) );
		WP_CLI::log( '' );
		WP_CLI::log( 'Configuration:' );

		foreach ( $recipe as $ingredient => $value ) {
			$formatted_value = is_array( $value ) ? json_encode( $value ) : $value;
			WP_CLI::log( sprintf( '  %s: %s', $ingredient, $formatted_value ) );
		}
	}

	/**
	 * Apply a recipe to configure the site.
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : The recipe name to apply.
	 *
	 * [--dry-run]
	 * : Validate the recipe without executing it.
	 *
	 * ## EXAMPLES
	 *
	 *     wp whiskey apply us_merchant
	 *     wp whiskey apply us_merchant --dry-run
	 *
	 * @when after_wp_load
	 */
	public function apply_recipe( array $args, array $assoc_args ): void {
		$name    = $args[0] ?? null;
		$dry_run = isset( $assoc_args['dry-run'] );

		if ( ! $name ) {
			WP_CLI::error( 'Recipe name is required.' );
		}

		$config = $this->recipes->get( $name );

		if ( ! $config ) {
			WP_CLI::error( sprintf( 'Recipe not found: %s', $name ) );
		}

		WP_CLI::log( sprintf( 'Recipe: %s', $name ) );
		WP_CLI::log( '' );

		if ( ! $this->executor->validate( $config ) ) {
			WP_CLI::error( 'Invalid recipe configuration.' );
		}

		WP_CLI::success( 'Recipe configuration is valid.' );

		if ( $dry_run ) {
			WP_CLI::log( '' );
			WP_CLI::warning( 'Dry-run mode: Recipe was not executed.' );
			return;
		}

		WP_CLI::log( '' );
		WP_CLI::log( 'Executing recipe...' );
		WP_CLI::log( '' );

		$result = $this->executor->execute( $config );

		if ( ! $result->is_success() ) {
			WP_CLI::log( '' );
			WP_CLI::error( sprintf( 'Recipe execution failed: %s', $result->get_message() ) );
		}

		// Display ingredient results
		$data = $result->get_data();
		if ( ! empty( $data['results'] ) ) {
			foreach ( $data['results'] as $ingredient => $ingredient_result ) {
				$success = $ingredient_result['success'] ?? false;
				$message = $ingredient_result['message'] ?? '';

				if ( $success ) {
					WP_CLI::log( sprintf( '  ✓ %s: %s', $ingredient, $message ) );
				} else {
					WP_CLI::log( sprintf( '  ✗ %s: %s', $ingredient, $message ) );
				}
			}
		}

		WP_CLI::log( '' );
		WP_CLI::success( $result->get_message() );
	}

	/**
	 * List all available ingredients.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp whiskey ingredients
	 *     wp whiskey ingredients --format=json
	 *
	 * @when after_wp_load
	 */
	public function list_ingredients( array $args, array $assoc_args ): void {
		$ingredients = $this->ingredients->all_metadata();
		$format      = $assoc_args['format'] ?? 'table';

		if ( empty( $ingredients ) ) {
			WP_CLI::warning( 'No ingredients registered.' );
			return;
		}

		$items = [];
		foreach ( $ingredients as $name => $metadata ) {
			$items[] = [
				'name'        => $name,
				'category'    => $metadata['category'],
				'description' => $metadata['description'],
			];
		}

		WP_CLI\Utils\format_items( $format, $items, [ 'name', 'category', 'description' ] );

		if ( 'table' === $format ) {
			WP_CLI::log( '' );
			WP_CLI::success( sprintf( 'Found %d ingredient(s).', count( $ingredients ) ) );
		}
	}

	/**
	 * Show plugin status and PHP version.
	 *
	 * ## EXAMPLES
	 *
	 *     wp whiskey status
	 *
	 * @when after_wp_load
	 */
	public function show_status(): void {
		WP_CLI::log( 'Whiskey Plugin Status:' );
		WP_CLI::log( sprintf( '  PHP Version: %s', PHP_VERSION ) );
		WP_CLI::log( sprintf( '  Recipes: %d', count( $this->recipes->all() ) ) );
		WP_CLI::log( sprintf( '  Ingredients: %d', count( $this->ingredients->all_metadata() ) ) );
		WP_CLI::success( 'Plugin is active.' );
	}
}
