<?php
/**
 * REST API Controller
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

use WP_REST_Request;
use WP_REST_Response;

/**
 * REST API controller for recipe endpoints
 */
class RestController {

	private const NAMESPACE = 'whiskey/v1';

	private RecipeRegistry $registry;
	private HandlerFactory $factory;

	public function __construct( RecipeRegistry $registry, HandlerFactory $factory ) {
		$this->registry = $registry;
		$this->factory  = $factory;
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
		$recipes = $this->registry->all();

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
		$recipe = $this->registry->get( $name );

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
		$recipe = $this->registry->get( $name );

		if ( ! $recipe ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => sprintf( 'Recipe not found: %s', $name ),
				],
				404
			);
		}

		$handler = $this->factory->get_handler( $recipe['type'] );

		if ( ! $handler ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => sprintf( 'Unknown recipe type: %s', $recipe['type'] ),
				],
				400
			);
		}

		if ( ! $handler->validate( $recipe['config'] ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'message' => 'Invalid recipe configuration',
				],
				400
			);
		}

		$result = $handler->execute( $recipe['config'] );

		return new WP_REST_Response(
			$result->to_array(),
			200
		);
	}

	/**
	 * Get plugin status
	 */
	public function get_status(): WP_REST_Response {
		return new WP_REST_Response(
			[
				'success'     => true,
				'plugin'      => 'Whiskey',
				'version'     => '1.0.0',
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
