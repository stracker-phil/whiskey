<?php
/**
 * List all registered ingredients
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Tools;

use WP_CLI;
use Whiskey\IngredientCategory;

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
		WP_CLI::log( '' );

		$categories = [];

		foreach ( $ingredients as $ingredient ) {
			$meta = $this->ingredients->get_metadata( $ingredient );

			$category = $meta['category'] ?? 'default';
			if ( ! isset( $categories[ $category ] ) ) {
				$categories[ $category ] = [];
			}
			$categories[ $category ][] = [
				'name'        => $ingredient,
				'description' => $meta['description'] ?? '',
			];
		}

		foreach ( $categories as $category => $items ) {
			$color         = IngredientCategory::get_color( $category );
			$display_name  = IngredientCategory::get_display_name( $category );
			$reset         = "\033[0m";

			WP_CLI::log( $color . $display_name . $reset );

			foreach ( $items as $item ) {
				WP_CLI::log( sprintf( '  - %s: %s', $item['name'], $item['description'] ) );
			}

			WP_CLI::log( '' );
		}

		WP_CLI::success( sprintf( 'Found %d ingredient(s).', count( $ingredients ) ) );
	}
}
