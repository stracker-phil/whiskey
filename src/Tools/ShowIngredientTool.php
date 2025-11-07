<?php
/**
 * Show details of a specific ingredient
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Tools;

use RuntimeException;
use WP_CLI;
use function WP_CLI\Utils\format_items;

/**
 * Tool to show details of a specific ingredient
 */
class ShowIngredientTool extends WhiskeyTool {

	protected function get_rest_config(): ?array {
		return [
			'method' => 'GET',
			'path'   => '/ingredient/(?P<n>[a-zA-Z0-9-_]+)',
		];
	}

	protected function get_cli_config(): ?array {
		return [
			'command'  => 'whiskey ingredient',
			'synopsis' => 'Show details of a specific ingredient.',
		];
	}

	protected function handle_logic( array $args ): array {
		$name = $args['name'] ?? $args[0] ?? null;

		if ( ! $name ) {
			throw new RuntimeException( 'Ingredient name is required.' );
		}

		$metadata = $this->ingredients->get_metadata( $name );

		if ( ! $metadata ) {
			throw new RuntimeException( sprintf( 'Ingredient not found: %s', $name ) );
		}

		return [
			'name'        => $name,
			'category'    => $metadata['category'] ?? '',
			'description' => $metadata['description'] ?? '',
		];
	}

	protected function format_cli_output( array $data ): void {
		$format = $data['format'] ?? 'table';
		$name   = $data['name'] ?? 'Unknown';

		if ( 'table' === $format ) {
			WP_CLI::log( sprintf( 'Ingredient: %s', $name ) );
			WP_CLI::log( '' );
			WP_CLI::log( sprintf( '  Category: %s', $data['category'] ?? '' ) );
			WP_CLI::log( sprintf( '  Description: %s', $data['description'] ?? '' ) );
		} else {
			// For json/yaml format, use WP_CLI formatter
			$output = [
				'name'        => $name,
				'category'    => $data['category'] ?? '',
				'description' => $data['description'] ?? '',
			];
			format_items( $format, [ $output ], array_keys( $output ) );
		}
	}

	protected function extract_cli_args( array $args, array $assoc_args ): array {
		// Include format parameter from assoc_args
		return array_merge(
			[ 0 => $args[0] ?? null ],
			[ 'format' => $assoc_args['format'] ?? 'table' ]
		);
	}
}
