<?php
/**
 * Recipes for: PayPal Payments for WooCommerce
 */

declare( strict_types = 1 );

use Whiskey\Registry\RecipeRegistry;

add_action( 'whiskey:register_recipe', static function ( RecipeRegistry $registry ) {
	$registry->add(
		'us_merchant',
		[
			// TODO
		]
	);

	$registry->add(
		'german_merchant',
		[
			// TODO
		]
	);

	$registry->add(
		'mexico_merchant',
		[
			// TODO
		]
	);
} );
