<?php
/**
 * Show plugin status
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Tools;

use WP_CLI;
use Override;

/**
 * Tool to show plugin status and information
 */
class StatusTool extends WhiskeyTool {

	protected function get_rest_config(): ?array {
		return [
			'method' => 'GET',
			'path'   => '/status',
		];
	}

	protected function get_cli_config(): ?array {
		return [
			'command'  => 'whiskey status',
			'synopsis' => 'Show plugin status and PHP version.',
		];
	}

	protected function handle_logic( array $args ): array {
		return [
			'php_version' => PHP_VERSION,
			'recipes'     => count( $this->recipes->all() ),
			'ingredients' => count( $this->ingredients->all() ),
		];
	}

	#[Override]
	protected function format_cli_output( array $data ): void {
		WP_CLI::log( 'Whiskey Plugin Status:' );
		WP_CLI::log( sprintf( '  PHP Version: %s', $data['php_version'] ?? 'Unknown' ) );
		WP_CLI::log( sprintf( '  Recipes: %d', $data['recipes'] ?? 0 ) );
		WP_CLI::log( sprintf( '  Ingredients: %d', $data['ingredients'] ?? 0 ) );
		WP_CLI::success( 'Plugin is active.' );
	}
}
