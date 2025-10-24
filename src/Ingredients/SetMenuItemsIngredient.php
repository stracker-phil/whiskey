<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use WP_Post;
use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\IngredientCategory;
use Whiskey\ValidationResult;

/**
 * Creates a primary navigation menu with specified pages.
 * Replaces any existing primary menu completely.
 * Group: WordPress core
 */
class SetMenuItemsIngredient extends Ingredient {
	public const NAME        = 'set_menu_items';
	public const CATEGORY    = IngredientCategory::WORDPRESS;
	public const DESCRIPTION = 'Creates or replaces the primary navigation menu with the specified page slugs';

	private const MENU_LOCATION = 'primary';
	private const MENU_NAME     = 'Primary Navigation';

	public function validate( $value ): ValidationResult {
		if ( ! is_array( $value ) ) {
			return ValidationResult::invalid_type( 'array' );
		}

		foreach ( $value as $slug ) {
			if ( ! is_string( $slug ) ) {
				return ValidationResult::invalid_type( 'array of strings' );
			}
		}

		return ValidationResult::valid();
	}

	public function execute( $value ): ExecutionResult {
		$menu_id = $this->get_or_create_menu();

		if ( ! $menu_id ) {
			return new ExecutionResult(
				false,
				'Failed to create or retrieve primary menu.',
				[]
			);
		}

		// Clear existing menu items
		$this->clear_menu_items( $menu_id );

		// Add new menu items
		$items = [];
		foreach ( $value as $slug ) {
			$page = get_page_by_path( $slug );

			if ( ! $page instanceof WP_Post ) {
				$items[ $slug ] = 0;
				continue;
			}

			$menu_item_id   = $this->add_menu_item( $menu_id, $page->ID );
			$items[ $slug ] = $menu_item_id;
		}

		// Assign menu to primary location
		$this->set_menu_location( $menu_id );

		$failed_count = count( array_filter( $items, fn( $id ) => $id === 0 ) );

		if ( $failed_count > 0 ) {
			return new ExecutionResult(
				false,
				"Failed to add {$failed_count} menu item(s).",
				[
					'menu_id' => $menu_id,
					'items'   => $items,
				]
			);
		}

		return new ExecutionResult(
			true,
			'Primary menu updated successfully.',
			[
				'menu_id' => $menu_id,
				'items'   => $items,
			]
		);
	}

	private function get_or_create_menu(): int {
		$menu = wp_get_nav_menu_object( self::MENU_NAME );

		if ( $menu ) {
			return $menu->term_id;
		}

		$menu_id = wp_create_nav_menu( self::MENU_NAME );

		if ( is_wp_error( $menu_id ) ) {
			return 0;
		}

		return $menu_id;
	}

	private function clear_menu_items( int $menu_id ): void {
		$menu_items = wp_get_nav_menu_items( $menu_id );

		if ( ! $menu_items ) {
			return;
		}

		foreach ( $menu_items as $item ) {
			wp_delete_post( $item->ID, true );
		}
	}

	private function add_menu_item( int $menu_id, int $page_id ): int {
		$menu_item_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			[
				'menu-item-object-id' => $page_id,
				'menu-item-object'    => 'page',
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
			]
		);

		if ( is_wp_error( $menu_item_id ) ) {
			return 0;
		}

		return $menu_item_id;
	}

	private function set_menu_location( int $menu_id ): void {
		$locations = get_theme_mod( 'nav_menu_locations', [] );

		$locations[ self::MENU_LOCATION ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, SetMenuItemsIngredient::class ]
);
