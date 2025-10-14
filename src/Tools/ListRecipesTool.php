<?php
/**
 * List all registered recipes
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Tools;

use WP_CLI;

/**
 * Tool to list all available recipes
 */
class ListRecipesTool extends WhiskeyTool {

	protected function get_rest_config(): ?array {
		return [
			'method' => 'GET',
			'path'   => '/recipes',
		];
	}

	protected function get_cli_config(): ?array {
		return [
			'command'  => 'whiskey recipes',
			'synopsis' => 'List all available recipes.',
		];
	}

	protected function handle_logic( array $args ): array {
		$recipes = array_keys( $this->recipes->all() );

		return [ 'recipes' => $recipes ];
	}

	protected function format_cli_output( array $data ): void {
		$recipes = $data['recipes'] ?? [];

		if ( empty( $recipes ) ) {
			WP_CLI::warning( 'No recipes registered.' );

			return;
		}

		WP_CLI::log( 'Available recipes:' );
		foreach ( $recipes as $recipe ) {
			WP_CLI::log( "  - $recipe" );
		}

		WP_CLI::success( sprintf( 'Found %d recipe(s).', count( $recipes ) ) );
	}
}
