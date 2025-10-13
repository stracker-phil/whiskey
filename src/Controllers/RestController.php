<?php
/**
 * REST API Controller
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\RecipeExecutor;
use Whiskey\Registry\IngredientRegistry;

/**
 * REST API controller for recipe endpoints
 */
class RestController {

	private const NAMESPACE = 'whiskey/v1';

	private RecipeRegistry $recipes;
	private RecipeExecutor $executor;
	private IngredientRegistry $ingredients;

	public function __construct( RecipeRegistry $recipes, IngredientRegistry $ingredients, Recipeexecutor $executor ) {
		$this->recipes     = $recipes;
		$this->ingredients = $ingredients;
		$this->executor    = $executor;
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/recipes',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_recipes' ],
				'permission_callback' => [ $this, 'permission_callback' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/recipe/(?P<name>[a-zA-Z0-9-]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_recipe' ],
				'permission_callback' => [ $this, 'permission_callback' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/recipe/(?P<name>[a-zA-Z0-9-]+)/apply',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'apply_recipe' ],
				'permission_callback' => [ $this, 'permission_callback' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/ingredients',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_ingredients' ],
				'permission_callback' => [ $this, 'permission_callback' ],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/status',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_status' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	/**
	 * Get all registered recipes
	 */
	public function get_recipes(): WP_REST_Response {
		$recipes = $this->recipes->all();

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $recipes,
			],
			200
		);
	}

	/**
	 * Get specific recipe by name
	 */
	public function get_recipe( WP_REST_Request $request ): WP_REST_Response {
		$name   = $request->get_param( 'name' );
		$recipe = $this->recipes->get( $name );

		if ( ! $recipe ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => sprintf( 'Recipe not found: %s', $name ),
				],
				404
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $recipe,
			],
			200
		);
	}

	/**
	 * Execute a recipe
	 */
	public function apply_recipe( WP_REST_Request $request ): WP_REST_Response {
		$name   = $request->get_param( 'name' );
		$config = $this->recipes->get( $name );

		if ( ! $config ) {
			return new WP_REST_Response(
				[ 'success' => false, 'message' => "Recipe not found: $name" ],
				404
			);
		}

		if ( ! $this->executor->validate( $config ) ) {
			return new WP_REST_Response(
				[ 'success' => false, 'message' => 'Invalid recipe configuration' ],
				400
			);
		}

		$result = $this->executor->execute( $config );

		return new WP_REST_Response( $result->to_array(), 200 );
	}

	/**
	 * Get all available ingredients
	 */
	public function get_ingredients(): WP_REST_Response {
		$ingredients = $this->ingredients->all_metadata();

		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $ingredients,
				200,
			]
		);
	}

	/**
	 * Get plugin status
	 */
	public function get_status(): WP_REST_Response {
		return new WP_REST_Response(
			[
				'success'     => true,
				'php_version' => PHP_VERSION,
			],
			200
		);
	}

	/**
	 * Permission callback for protected endpoints
	 */
	public function permission_callback(): bool {
		return current_user_can( 'manage_options' );
	}
}
