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
use Override;

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
		$name     = $args['name'] ?? $args[0] ?? null;
		$dry_run  = isset( $args['dry-run'] );
		$continue = isset( $args['continue'] );
		$strategy = ExecutionStrategy::from_cli_args( $dry_run, $continue );

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

		$execution_result = $validation_result->execute( $strategy );

		// For continue-on-error, return results even on failure for proper reporting
		$result = [
			'name'     => $name,
			'success'  => $execution_result->is_success(),
			'message'  => $execution_result->get_message(),
			'data'     => $execution_result->get_data(),
			'strategy' => $strategy,
		];

		// Only throw exception for stop-on-failure strategy
		if ( ! $execution_result->is_success() && ExecutionStrategy::should_stop_on_failure( $strategy ) ) {
			throw new RuntimeException( sprintf( 'Recipe execution failed: %s', $execution_result->get_message() ) );
		}

		return $result;
	}

	#[Override]
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

	#[Override]
	protected function format_cli_output( array $data ): void {
		$name     = $data['name'] ?? 'Unknown';
		$dry_run  = $data['dry_run'] ?? false;
		$success  = $data['success'] ?? true;
		$strategy = $data['strategy'] ?? ExecutionStrategy::SEQUENTIAL;

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
		$failure_count   = 0;
		$success_count   = 0;

		if ( ! empty( $ingredient_data ) ) {
			foreach ( $ingredient_data as $ingredient => $ingredient_result ) {
				$ingredient_success = $ingredient_result['success'] ?? false;
				$message            = $ingredient_result['message'] ?? '';
				$ingredient_info    = $ingredient_result['data'] ?? [];

				if ( $ingredient_success ) {
					WP_CLI::log( sprintf( '  ✓ %s: %s', $ingredient, $message ) );
					++$success_count;
				} else {
					WP_CLI::log( sprintf( '  ✗ %s: %s', $ingredient, $message ) );
					++$failure_count;
				}

				// Display data details if present
				if ( ! empty( $ingredient_info ) ) {
					$this->format_ingredient_data( $ingredient_info, 4 );
				}
			}
		}

		WP_CLI::log( '' );

		// Show summary based on results and strategy
		if ( $success ) {
			WP_CLI::success( $data['message'] ?? 'Recipe applied successfully.' );
		} elseif ( ExecutionStrategy::CONTINUE_ON_ERROR === $strategy ) {
			// For continue-on-error, show summary and exit with error
			$summary = sprintf(
				'Recipe completed with failures: %d succeeded, %d failed',
				$success_count,
				$failure_count
			);
			WP_CLI::warning( $summary );
			WP_CLI::error( $data['message'] ?? 'Recipe execution had failures.', false );
		} else {
			// For sequential (stop on first failure)
			WP_CLI::error( $data['message'] ?? 'Recipe execution failed.' );
		}
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
		if ( ! array_is_list( $array ) ) {
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
