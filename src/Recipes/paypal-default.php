<?php
/**
 * PayPal Recipe Configurations
 *
 * Registers default PayPal setups for common scenarios.
 */

declare( strict_types = 1 );

use Whiskey\RecipeRegistry;

add_action( 'whiskey:register_recipe', static function ( RecipeRegistry $registry ) {
	$registry->register(
		'paypal',
		'us_merchant',
		[
			// TODO
		]
	);

	$registry->register(
		'paypal',
		'german_merchant',
		[
			// TODO
		]
	);

	$registry->register(
		'paypal',
		'mexico_merchant',
		[
			// TODO
		]
	);
} );

throw new \Exception('TST');
