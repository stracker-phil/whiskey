<?php
/**
 * Recipes for: PayPal Payments for WooCommerce
 */

declare( strict_types = 1 );

use Whiskey\RecipeRegistry;

add_action( 'whiskey:register_recipe', static function ( RecipeRegistry $registry ) {
	$registry->add(
		'paypal',
		'us_merchant',
		[
			// TODO
		]
	);

	$registry->add(
		'paypal',
		'german_merchant',
		[
			// TODO
		]
	);

	$registry->add(
		'paypal',
		'mexico_merchant',
		[
			// TODO
		]
	);
} );
