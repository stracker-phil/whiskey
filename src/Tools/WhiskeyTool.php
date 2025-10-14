<?php
/**
 * Base class for Whiskey tools
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Tools;

use WP_CLI;
use WP_REST_Request;
use WP_REST_Response;
use Exception;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\RecipeExecutor;

/**
 * Base class for all Whiskey tools.
 *
 * Tools define their REST/CLI configuration and business logic.
 * The base class handles registration, error handling, and response formatting.
 */
abstract class WhiskeyTool {
	protected RecipeRegistry $recipes;
	protected IngredientRegistry $ingredients;
	protected RecipeExecutor $executor;

	public function __construct(
		RecipeRegistry $recipes,
		IngredientRegistry $ingredients,
		RecipeExecutor $executor
	) {
		$this->recipes     = $recipes;
		$this->ingredients = $ingredients;
		$this->executor    = $executor;
	}

	/**
	 * Return REST configuration or null to skip REST registration.
	 *
	 * @return array{method: string, path: string, args?: array}|null
	 */
	abstract protected function get_rest_config(): ?array;

	/**
	 * Return CLI configuration or null to skip CLI registration.
	 *
	 * @return array{command: string, synopsis: string, when?: string}|null
	 */
	abstract protected function get_cli_config(): ?array;

	/**
	 * Execute the tool's business logic.
	 *
	 * Should return data array on success or throw exception on failure.
	 *
	 * @param array $args Normalized arguments from REST or CLI
	 * @return array Result data
	 * @throws Exception On validation or execution errors
	 */
	abstract protected function handle_logic( array $args ): array;

	/**
	 * Register REST endpoint if configuration is provided.
	 */
	final public function init_rest( string $namespace, callable $permission_callback ): void {
		$config = $this->get_rest_config();
		if ( ! $config ) {
			return;
		}

		register_rest_route(
			$namespace,
			$config['path'],
			[
				'methods'             => $config['method'],
				'callback'            => [ $this, 'handle_rest' ],
				'permission_callback' => $permission_callback,
				'args'                => $config['args'] ?? [],
			]
		);
	}

	/**
	 * Register CLI command if configuration is provided.
	 */
	final public function init_cli(): void {
		if ( ! class_exists( 'WP_CLI' ) ) {
			return;
		}

		$config = $this->get_cli_config();
		if ( ! $config ) {
			return;
		}

		WP_CLI::add_command(
			$config['command'],
			[ $this, 'handle_cli' ],
			[
				'shortdesc' => $config['synopsis'] ?? '',
				'when'      => $config['when'] ?? 'after_wp_load',
			]
		);
	}

	/**
	 * Handle REST API request.
	 */
	final public function handle_rest( WP_REST_Request $request ): WP_REST_Response {
		try {
			$args = $this->extract_rest_args( $request );
			$data = $this->handle_logic( $args );

			return $this->format_rest_success( $data );
		} catch ( Exception $e ) {
			return $this->format_rest_error( $e->getMessage(), $this->get_http_code( $e ) );
		}
	}

	/**
	 * Handle CLI command.
	 */
	final public function handle_cli( array $args, array $assoc_args ): void {
		try {
			$normalized = $this->extract_cli_args( $args, $assoc_args );
			$data       = $this->handle_logic( $normalized );

			$this->format_cli_output( $data );
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Extract arguments from REST request.
	 * Override in child class if custom extraction needed.
	 */
	protected function extract_rest_args( WP_REST_Request $request ): array {
		return $request->get_params();
	}

	/**
	 * Extract and normalize arguments from CLI.
	 * Override in child class if custom extraction needed.
	 */
	protected function extract_cli_args( array $args, array $assoc_args ): array {
		return array_merge( $args, $assoc_args );
	}

	/**
	 * Format successful REST response.
	 */
	protected function format_rest_success( array $data ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'success' => true,
				'data'    => $data,
			],
			200
		);
	}

	/**
	 * Format error REST response.
	 */
	protected function format_rest_error( string $message, int $code = 400 ): WP_REST_Response {
		return new WP_REST_Response(
			[
				'success' => false,
				'message' => $message,
			],
			$code
		);
	}

	/**
	 * Format CLI output.
	 * Override in child class for custom formatting.
	 */
	protected function format_cli_output( array $data ): void {
		// Default: just dump the data as formatted output
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				WP_CLI::log( sprintf( '%s:', $key ) );
				foreach ( $value as $item ) {
					WP_CLI::log( sprintf( '  - %s', $item ) );
				}
			} else {
				WP_CLI::log( sprintf( '%s: %s', $key, $value ) );
			}
		}
	}

	/**
	 * Map exception to HTTP status code.
	 * Override to customize error codes.
	 */
	protected function get_http_code( Exception $e ): int {
		// Common pattern: "not found" exceptions map to 404
		if ( strpos( $e->getMessage(), 'not found' ) !== false ) {
			return 404;
		}

		return 400;
	}
}
