<?php
/**
 * List all registered ingredients
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Tools;

use WP_CLI;

/**
 * Tool to list all available ingredients
 */
class ListIngredientsTool extends WhiskeyTool {

	protected function get_rest_config(): ?array {
		return [
			'method' => 'GET',
			'path'   => '/ingredients',
		];
	}

	protected function get_cli_config(): ?array {
		return [
			'command'  => 'whiskey ingredients',
			'synopsis' => 'List all available ingredients.',
		];
	}

	protected function handle_logic( array $args ): array {
		$ingredients = array_keys( $this->ingredients->all() );

		return [ 'ingredients' => $ingredients ];
	}

	protected function format_cli_output( array $data ): void {
		$ingredients = $data['ingredients'] ?? [];

		if ( empty( $ingredients ) ) {
			WP_CLI::warning( 'No ingredients registered.' );

			return;
		}

		WP_CLI::log( 'Available ingredients:' );
		foreach ( $ingredients as $ingredient ) {
			WP_CLI::log( "  - $ingredient" );
		}

		WP_CLI::success( sprintf( 'Found %d ingredient(s).', count( $ingredients ) ) );
	}
}
