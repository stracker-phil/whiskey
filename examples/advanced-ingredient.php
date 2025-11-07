<?php
/**
 * Example: Advanced ingredient with complex validation
 *
 * This demonstrates handling array inputs, WordPress object lookups,
 * and multiple validation scenarios.
 *
 * @package Whiskey\Examples
 */

declare( strict_types = 1 );

namespace YourPlugin\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\ValidationResult;

/**
 * Assigns a custom menu to a theme location
 */
class AssignMenuToLocationIngredient extends Ingredient {
	public const NAME        = 'assign_menu_to_location';
	public const CATEGORY    = 'wordpress';
	public const DESCRIPTION = 'Assign a menu to a theme location';

	/**
	 * Validate input format
	 *
	 * Expects: [ 'menu' => 'menu-slug-or-id', 'location' => 'primary' ]
	 */
	public function validate( $value ): ValidationResult {
		if ( ! is_array( $value ) ) {
			return ValidationResult::invalid_type( 'array' );
		}

		// Check required keys
		if ( ! isset( $value['menu'] ) ) {
			return ValidationResult::missing_key( 'menu' );
		}

		if ( ! isset( $value['location'] ) ) {
			return ValidationResult::missing_key( 'location' );
		}

		// Menu can be string or int
		if ( ! is_string( $value['menu'] ) && ! is_int( $value['menu'] ) ) {
			return ValidationResult::invalid_type( 'string or int for menu' );
		}

		// Location must be string
		if ( ! is_string( $value['location'] ) ) {
			return ValidationResult::invalid_type( 'string for location' );
		}

		// Return valid result with execution callback
		return ValidationResult::valid( fn() => $this->execute( $value ) );
	}

	/**
	 * Execute the menu assignment (private - only called via ValidationResult::execute())
	 */
	private function execute( $value ): ExecutionResult {
		$menu     = $value['menu'];
		$location = $value['location'];

		// Resolve menu to ID
		$menu_id = $this->resolve_menu_id( $menu );

		if ( ! $menu_id ) {
			return new ExecutionResult(
				false,
				'Menu not found',
				[
					'menu_input' => $menu,
					'location'   => $location,
				]
			);
		}

		// Check if location exists
		$locations = get_registered_nav_menus();
		if ( ! isset( $locations[ $location ] ) ) {
			return new ExecutionResult(
				false,
				sprintf( 'Theme location "%s" does not exist', $location ),
				[
					'location'            => $location,
					'available_locations' => array_keys( $locations ),
				]
			);
		}

		// Assign the menu
		$theme_locations              = get_nav_menu_locations();
		$theme_locations[ $location ] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $theme_locations );

		return new ExecutionResult(
			true,
			sprintf( 'Menu assigned to location "%s"', $location ),
			[
				'menu_id'  => $menu_id,
				'location' => $location,
			]
		);
	}

	/**
	 * Resolve menu to ID from slug or ID
	 *
	 * @param string|int $menu Menu slug or ID
	 * @return int Menu ID or 0 if not found
	 */
	private function resolve_menu_id( $menu ): int {
		// Already an ID
		if ( is_int( $menu ) ) {
			$menu_object = wp_get_nav_menu_object( $menu );

			return $menu_object ? $menu_object->term_id : 0;
		}

		// Try as slug
		$menu_object = wp_get_nav_menu_object( $menu );

		return $menu_object ? $menu_object->term_id : 0;
	}
}

// Self-register
add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, AssignMenuToLocationIngredient::class ]
);
