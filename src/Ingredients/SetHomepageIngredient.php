<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use WP_Post;
use Whiskey\Ingredient;
use Whiskey\ExecutionResult;

/**
 * Updates the "home_page" setting.
 * Group: WordPress core
 */
class SetHomepageIngredient extends Ingredient {
	public const NAME        = 'set_homepage';
	public const CATEGORY    = 'wordpress';
	public const DESCRIPTION = 'Changes the home-page to a static WP page; specify either a post_id or post_name';

	public function validate( $value ): bool {
		return is_string( $value ) || is_int( $value );
	}

	public function execute( $value ): ExecutionResult {
		$page_id = $this->page_id_from_value( $value );

		if ( ! $page_id ) {
			return new ExecutionResult(
				false,
				"Did not find a page with id or slug '{$value}'."
			);

		}
		$this->set_home_page( $page_id );


		return new ExecutionResult(
			true,
			"New home page set to post_id {$page_id}."
		);
	}

	private function page_id_from_value( $value ): int {
		if ( is_string( $value ) ) {
			$page = get_page_by_path( $value );

			if ( $page instanceof WP_Post ) {
				return $page->ID;
			}
		}

		if ( is_int( $value ) ) {
			$page = get_post( $value );

			if ( $page instanceof WP_Post && 'page' === $page->post_type ) {
				return $page->ID;
			}
		}

		return 0;
	}

	private function set_home_page( int $post_id ): void {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $post_id );
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, SetHomepageIngredient::class ]
);
