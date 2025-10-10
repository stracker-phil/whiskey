<?php
/**
 * Example: Register a PayPal configuration recipe
 *
 * @package Whiskey\Examples
 */

declare(strict_types=1);

use Whiskey\RecipeRegistry;

// Register a PayPal sandbox configuration recipe.
add_action(
	'whiskey:register_recipe',
	function( RecipeRegistry $registry ) {
		$registry->register(
			[
				'name'   => 'paypal-sandbox-setup',
				'type'   => 'paypal',
				'config' => [
					'mode'               => 'sandbox',
					'merchant_id'        => 'YOUR_MERCHANT_ID',
					'clear_transients'   => true,
					'verify_connection'  => true,
				],
			]
		);
	}
);

// Arrow function version (also PHP 7.4):
// add_action(
//     'whiskey:register_recipe',
//     fn( RecipeRegistry $r ) => $r->register([
//         'name' => 'paypal-sandbox-setup',
//         'type' => 'paypal',
//         'config' => [...]
//     ])
// );
