<?php
/**
 * Example: Register custom ingredients from external code
 *
 * Place this in your theme's functions.php or a custom plugin.
 * First create your ingredient class, then register it.
 *
 * @package Whiskey\Examples
 */

declare( strict_types = 1 );

use Whiskey\Registry\IngredientRegistry;

// Assuming you've created: namespace YourPlugin\Ingredients\MyCustomIngredient

/**
 * Register a single ingredient
 */
add_action(
	'whiskey:register_ingredient',
	static function ( IngredientRegistry $registry ) {
		$registry->add( \YourPlugin\Ingredients\MyCustomIngredient::class );
	}
);

/**
 * Register multiple ingredients at once
 */
add_action(
	'whiskey:register_ingredient',
	static function ( IngredientRegistry $registry ) {
		$registry->add( \YourPlugin\Ingredients\CustomIngredient1::class );
		$registry->add( \YourPlugin\Ingredients\CustomIngredient2::class );
		$registry->add( \YourPlugin\Ingredients\CustomIngredient3::class );
	}
);

/**
 * Arrow function syntax (PHP 7.4+)
 */
add_action(
	'whiskey:register_ingredient',
	static fn( IngredientRegistry $r ) => $r->add( \YourPlugin\Ingredients\SimpleIngredient::class )
);
