<?php
/**
 * Example: Register custom recipes from external code
 *
 * Place this in your theme's functions.php or a custom plugin.
 *
 * @package Whiskey\Examples
 */

declare( strict_types = 1 );

use Whiskey\Registry\RecipeRegistry;

/**
 * Register a simple recipe
 */
add_action(
	'whiskey:register_recipe',
	static function ( RecipeRegistry $registry ) {
		$registry->add(
			'my-shop-setup',
			[
				'set_homepage'      => 'shop',
				'create_shop_pages' => [ 'shop', 'cart', 'checkout' ],
				'update_permalinks' => 'shop',
			]
		);
	}
);

/**
 * Register multiple recipes in one callback
 */
add_action(
	'whiskey:register_recipe',
	static function ( RecipeRegistry $registry ) {
		// US Store Setup
		$registry->add(
			'us-store',
			[
				'set_homepage'      => 'shop',
				'create_shop_pages' => [ 'shop', 'cart', 'checkout' ],
			]
		);

		// EU Store Setup
		$registry->add(
			'eu-store',
			[
				'set_homepage'      => 'shop',
				'create_shop_pages' => [ 'shop', 'cart', 'checkout' ],
			]
		);
	}
);

/**
 * Use arrow function syntax (PHP 7.4+)
 */
add_action(
	'whiskey:register_recipe',
	static fn( RecipeRegistry $r ) => $r->add(
		'minimal-setup',
		[ 'set_homepage' => 'home' ]
	)
);
