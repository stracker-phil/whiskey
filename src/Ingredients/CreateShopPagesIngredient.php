<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use WP_Post;
use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\Registry\IngredientRegistry;

/**
 * Creates or updates standard WooCommerce pages.
 * Group: WordPress core / WooCommerce
 *
 * @todo untested
 */
class CreateShopPagesIngredient extends Ingredient {
	public const NAME        = 'create_shop_pages';
	public const CATEGORY    = 'woocommerce';
	public const DESCRIPTION = 'Creates or updates WooCommerce pages: shop, block-cart, block-checkout, classic-cart, classic-checkout, my-account';

	public function validate( $value ): bool {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $slug ) {
			if ( ! is_string( $slug ) || ! $this->template_exists( $slug ) ) {
				return false;
			}
		}

		return true;
	}

	public function execute( $value ): ExecutionResult {
		$created = array();
		$updated = array();
		$failed  = array();

		foreach ( $value as $slug ) {
			$template = $this->load_template( $slug );

			if ( ! $template ) {
				$failed[] = $slug;
				continue;
			}

			$result = $this->create_or_update_page( $slug, $template );

			if ( $result['created'] ) {
				$created[] = $slug;
			} elseif ( $result['updated'] ) {
				$updated[] = $slug;
			} else {
				$failed[] = $slug;
			}
		}

		if ( count( $failed ) > 0 ) {
			return new ExecutionResult(
				false,
				'Failed to create or update some pages.',
				array(
					'created' => $created,
					'updated' => $updated,
					'failed'  => $failed,
				)
			);
		}

		return new ExecutionResult(
			true,
			'Shop pages created or updated successfully.',
			array(
				'created' => $created,
				'updated' => $updated,
			)
		);
	}

	private function template_exists( string $slug ): bool {
		$template_path = $this->get_template_path( $slug );

		return file_exists( $template_path );
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
			$template['post_meta'] = array();
		}

		return $template;
	}

	private function get_template_path( string $slug ): string {
		return __DIR__ . '/ShopPages/' . $slug . '.php';
	}

	private function create_or_update_page( string $slug, array $template ): array {
		$existing_page = get_page_by_path( $slug );

		$post_data = array(
			'post_title'   => $template['title'],
			'post_content' => $template['content'],
			'post_status'  => 'publish',
			'post_name'    => $slug,
			'post_type'    => $template['post_type'],
		);

		if ( $existing_page instanceof WP_Post ) {
			// Update existing page
			$post_data['ID'] = $existing_page->ID;
			$result          = wp_insert_post( $post_data, true );

			if ( is_wp_error( $result ) ) {
				return array(
					'created' => false,
					'updated' => false,
				);
			}

			$this->update_post_meta( $result, $template['post_meta'] );

			return array(
				'created' => false,
				'updated' => true,
			);
		}

		// Create new page
		$result = wp_insert_post( $post_data, true );

		if ( is_wp_error( $result ) ) {
			return array(
				'created' => false,
				'updated' => false,
			);
		}

		$this->update_post_meta( $result, $template['post_meta'] );

		return array(
			'created' => true,
			'updated' => false,
		);
	}

	private function update_post_meta( int $post_id, array $post_meta ): void {
		foreach ( $post_meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}
}

add_action(
	'whiskey:register_ingredient',
	static fn( IngredientRegistry $registry ) => $registry->add( CreateShopPagesIngredient::class )
);
