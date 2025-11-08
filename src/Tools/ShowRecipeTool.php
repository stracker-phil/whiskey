<?php
/**
 * Show details of a specific recipe
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Tools;

use RuntimeException;
use WP_CLI;
use Override;

/**
 * Tool to show details of a specific recipe
 */
class ShowRecipeTool extends WhiskeyTool {

	protected function get_rest_config(): ?array {
		return [
			'method' => 'GET',
			'path'   => '/recipe/(?P<name>[a-zA-Z0-9-_]+)',
		];
	}

	protected function get_cli_config(): ?array {
		return [
			'command'  => 'whiskey recipe',
			'synopsis' => 'Show details of a specific recipe.',
		];
	}

	protected function handle_logic( array $args ): array {
		$name = $args['name'] ?? $args[0] ?? null;

		if ( ! $name ) {
			throw new RuntimeException( 'Recipe name is required.' );
		}

		$recipe = $this->recipes->get( $name );

		if ( ! $recipe ) {
			throw new RuntimeException( sprintf( 'Recipe not found: %s', $name ) );
		}

		return [
			'name'   => $name,
			'config' => $recipe,
		];
	}

	#[Override]
	protected function format_cli_output( array $data ): void {
		$name   = $data['name'] ?? 'Unknown';
		$config = $data['config'] ?? [];

		WP_CLI::log( sprintf( 'Recipe: %s', $name ) );
		WP_CLI::log( '' );
		WP_CLI::log( 'Configuration:' );

		foreach ( $config as $ingredient => $value ) {
			$formatted_value = is_array( $value ) ? wp_json_encode( $value ) : $value;
			WP_CLI::log( sprintf( '  %s: %s', $ingredient, $formatted_value ) );
		}
	}
}
