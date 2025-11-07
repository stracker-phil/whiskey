<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use WP_Post;
use Whiskey\Ingredient;
use Whiskey\IngredientCategory;
use Whiskey\ExecutionResult;
use Whiskey\ValidationResult;

/**
 * Creates or updates pre-defined pages.
 * Group: WordPress core
 */
class CreatePagesIngredient extends Ingredient {
	public const NAME        = 'create_pages';
	public const CATEGORY    = IngredientCategory::WORDPRESS;
	public const DESCRIPTION = 'Creates or updates WordPress pages: shop, block-cart, block-checkout, classic-cart, classic-checkout, my-account';

	public function validate( $value ): ValidationResult {
		if ( ! is_array( $value ) ) {
			return ValidationResult::invalid_type( 'array' );
		}

		foreach ( $value as $slug ) {
			if ( ! is_string( $slug ) ) {
				return ValidationResult::invalid_type( 'array of strings' );
			}
		}

		return ValidationResult::valid( fn() => $this->execute( $value ) );
	}

	private function execute( $value ): ExecutionResult {
		$pages = [];

		foreach ( $value as $slug ) {
			$template = $this->load_template( $slug );

			if ( ! $template ) {
				$pages[ $slug ] = 0;
				continue;
			}

			$pages[ $slug ] = $this->create_or_update_page(
				$slug,
				$template['post_type'],
				$template['title'],
				$template['content']
			);
		}

		$failed_count = count( array_filter( $pages, fn( $id ) => $id === 0 ) );

		if ( $failed_count > 0 ) {
			return new ExecutionResult(
				false,
				"Failed to create or update {$failed_count} page(s).",
				[ 'pages' => $pages ]
			);
		}

		return new ExecutionResult(
			true,
			'Shop pages created or updated successfully.',
			[ 'pages' => $pages ]
		);
	}

	private function load_template( string $slug ): ?array {
		$template_path = $this->get_template_path( $slug );

		if ( ! file_exists( $template_path ) ) {
			return null;
		}

		$template = require $template_path;

		if ( ! is_array( $template ) || ! isset( $template['title'], $template['content'] ) ) {
			return null;
		}

		// Set defaults for optional fields
		if ( ! isset( $template['post_type'] ) ) {
			$template['post_type'] = 'page';
		}

		if ( ! isset( $template['post_meta'] ) ) {
			$template['post_meta'] = [];
		}

		return $template;
	}

	private function get_template_path( string $slug ): string {
		return __DIR__ . '/PageTemplates/' . $slug . '.php';
	}

	private function create_or_update_page( string $slug, string $post_type = 'page', string $title = '', string $content = '' ): int {
		$existing_page = get_page_by_path( $slug, 'OBJECT', $post_type );

		$post_data = [
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_type'    => $post_type,
		];

		if ( $existing_page instanceof WP_Post ) {
			// Update existing page
			$post_data['ID'] = $existing_page->ID;
			$result          = wp_insert_post( $post_data, true );

			if ( is_wp_error( $result ) ) {
				return 0;
			}

			return $result;
		}

		// Create new page
		$result = wp_insert_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return 0;
		}

		return $result;
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, CreatePagesIngredient::class ]
);
