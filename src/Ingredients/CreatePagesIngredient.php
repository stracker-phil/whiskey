<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use WP_Post;
use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\Registry\IngredientRegistry;

/**
 * Creates or updates pre-defined pages.
 * Group: WordPress core
 */
class CreatePagesIngredient extends Ingredient {
	public const NAME        = 'create_pages';
	public const CATEGORY    = 'wordpress';
	public const DESCRIPTION = 'Creates or updates WordPress pages: shop, block-cart, block-checkout, classic-cart, classic-checkout, my-account';

	public function validate( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $slug ) {
			if ( ! is_string( $slug ) ) {
				return false;
			}
		}

		return true;
	}

	public function execute( $value ): ExecutionResult {
		$pages = [];

		foreach ( $value as $slug ) {
			$template = $this->load_template( $slug );

			if ( ! $template ) {
				$pages[ $slug ] = 0;
				continue;
			}

			$post_id        = $this->create_or_update_page( $slug, $template );
			$pages[ $slug ] = $post_id;
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

	private function create_or_update_page( string $slug, array $template ): int {
		$existing_page = get_page_by_path( $slug );

		$post_data = [
			'post_title'   => $template['title'],
			'post_content' => $template['content'],
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_type'    => $template['post_type'],
		];

		if ( $existing_page instanceof WP_Post ) {
			// Update existing page
			$post_data['ID'] = $existing_page->ID;
			$result          = wp_insert_post( $post_data, true );

			if ( is_wp_error( $result ) ) {
				return 0;
			}

			$this->update_post_meta( $result, $template['post_meta'] );

			return $result;
		}

		// Create new page
		$result = wp_insert_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return 0;
		}

		$this->update_post_meta( $result, $template['post_meta'] );

		return $result;
	}

	private function update_post_meta( int $post_id, array $post_meta ): void {
		foreach ( $post_meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, CreatePagesIngredient::class ]
);
