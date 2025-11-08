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

			// Get category (either as enum or string value), default to General
			$category = $meta['category'] ?? IngredientCategory::General;

			// Use the category enum value as key for grouping
			$category_key = $category instanceof IngredientCategory ? $category->value : $category;

			if ( ! isset( $categories[ $category_key ] ) ) {
				$categories[ $category_key ] = [];
			}
			$categories[ $category_key ][] = [
				'name'        => $ingredient,
				'description' => $meta['description'] ?? '',
			];
		}

		foreach ( $categories as $category => $items ) {
			// Convert string category to enum
			$category_enum = IngredientCategory::from( $category );

			$color        = $category_enum->get_color();
			$display_name = $category_enum->get_display_name();
			$reset        = "\033[0m";

			WP_CLI::log( $color . $display_name . $reset );

			foreach ( $items as $item ) {
				WP_CLI::log( sprintf( '  - %s: %s', $item['name'], $item['description'] ) );
			}

			WP_CLI::log( '' );
		}

		WP_CLI::success( sprintf( 'Found %d ingredient(s).', count( $ingredients ) ) );
	}
}
