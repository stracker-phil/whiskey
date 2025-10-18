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

// Assuming you've created: namespace YourPlugin\Ingredients\MyCustomIngredient

/**
 * Register a single ingredient
 */
add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, \YourPlugin\Ingredients\MyCustomIngredient::class ]
);

/**
 * Register multiple ingredients at once
 */
add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [
		...$items,
		\YourPlugin\Ingredients\CustomIngredient1::class,
		\YourPlugin\Ingredients\CustomIngredient2::class,
		\YourPlugin\Ingredients\CustomIngredient3::class,
	]
);

