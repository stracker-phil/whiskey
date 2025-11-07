<?php
/**
 * Apply/execute a recipe
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Tools;

use RuntimeException;
use WP_CLI;
use WP_REST_Response;
use Whiskey\ExecutionStrategy;

/**
 * Tool to apply/execute a recipe
 */
class ApplyRecipeTool extends WhiskeyTool {

	protected function get_rest_config(): ?array {
		return [
			'method' => 'POST',
			'path'   => '/recipe/(?P<n>[a-zA-Z0-9-_]+)/apply',
			'args'   => [
				'strategy' => [
					'required'          => false,
					'default'           => ExecutionStrategy::SEQUENTIAL,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		];
	}

	protected function get_cli_config(): ?array {
		return [
			'command'  => 'whiskey apply',
			'synopsis' => 'Apply a recipe to configure the site.',
		];
	}

	protected function handle_logic( array $args ): array {
		$name    = $args['name'] ?? $args[0] ?? null;
		$dry_run = isset( $args['dry-run'] );

		if ( ! $name ) {
			throw new RuntimeException( 'Recipe name is required.' );
		}

		$config = $this->recipes->get( $name );

		if ( ! $config ) {
			throw new RuntimeException( sprintf( 'Recipe not found: %s', $name ) );
		}

		$validation_result = $this->executor->validate( $config );
		if ( ! $validation_result->is_valid() ) {
			throw new RuntimeException( $validation_result->get_message() );
		}

		if ( $dry_run ) {
			return [
				'name'    => $name,
				'dry_run' => true,
				'valid'   => true,
				'message' => 'Recipe configuration is valid.',
			];
		}

		$execution_result = $validation_result->execute();

		if ( ! $execution_result->is_success() ) {
			throw new RuntimeException( sprintf( 'Recipe execution failed: %s', $execution_result->get_message() ) );
		}

		return [
			'name'    => $name,
			'success' => $execution_result->is_success(),
			'message' => $execution_result->get_message(),
			'data'    => $execution_result->get_data(),
		];
	}

	protected function format_rest_success( array $data ): WP_REST_Response {
		// For REST, return ExecutionResult format directly
		return new WP_REST_Response(
			[
				'success' => $data['success'] ?? true,
				'message' => $data['message'] ?? '',
				'data'    => $data['data'] ?? [],
			],
			200
		);
	}

	protected function format_cli_output( array $data ): void {
		$name    = $data['name'] ?? 'Unknown';
		$dry_run = $data['dry_run'] ?? false;

		WP_CLI::log( sprintf( 'Recipe: %s', $name ) );
		WP_CLI::log( '' );

		if ( $dry_run ) {
			WP_CLI::success( 'Recipe configuration is valid.' );
			WP_CLI::log( '' );
			WP_CLI::warning( 'Dry-run mode: Recipe was not executed.' );

			return;
		}

		WP_CLI::log( 'Executing recipe...' );
		WP_CLI::log( '' );

		// Display ingredient results
		$ingredient_data = $data['data'] ?? [];
		if ( ! empty( $ingredient_data ) ) {
			foreach ( $ingredient_data as $ingredient => $ingredient_result ) {
				$success         = $ingredient_result['success'] ?? false;
				$message         = $ingredient_result['message'] ?? '';
				$ingredient_info = $ingredient_result['data'] ?? [];

				if ( $success ) {
					WP_CLI::log( sprintf( '  ✓ %s: %s', $ingredient, $message ) );
				} else {
					WP_CLI::log( sprintf( '  ✗ %s: %s', $ingredient, $message ) );
				}

				// Display data details if present
				if ( ! empty( $ingredient_info ) ) {
					$this->format_ingredient_data( $ingredient_info, 4 );
				}
			}
		}

		WP_CLI::log( '' );
		WP_CLI::success( $data['message'] ?? 'Recipe applied successfully.' );
	}

	/**
	 * Format data array for CLI output in a readable way
	 *
	 * @param array $data   Data to format
	 * @param int   $indent Number of spaces to indent
	 */
	private function format_ingredient_data( array $data, int $indent ): void {
		$prefix = str_repeat( ' ', $indent );

		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				// Check if it's a simple list of scalars
				if ( $this->is_simple_list( $value ) ) {
					$formatted = implode( ', ', $value );
					WP_CLI::log( sprintf( '%s%s: %s', $prefix, $key, $formatted ) );
				} else {
					// Nested structure
					WP_CLI::log( sprintf( '%s%s:', $prefix, $key ) );
					$this->format_ingredient_data( $value, $indent + 2 );
				}
			} else {
				WP_CLI::log( sprintf( '%s%s: %s', $prefix, $key, $value ) );
			}
		}
	}

	/**
	 * Check if array is a simple list of scalar values
	 *
	 * @param array $array Array to check
	 * @return bool
	 */
	private function is_simple_list( array $array ): bool {
		if ( empty( $array ) ) {
			return true;
		}

		// Check if it's an indexed array (not associative)
		if ( array_keys( $array ) !== range( 0, count( $array ) - 1 ) ) {
			return false;
		}

		// Check if all values are scalars
		foreach ( $array as $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				return false;
			}
		}

		return true;
	}
}
