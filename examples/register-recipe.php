<?php
/**
 * Example: Register custom recipes from external code
 *
 * Place this in your theme's functions.php or a custom plugin.
 *
 * @package Whiskey\Examples
 */

declare( strict_types = 1 );

/**
 * Register a simple recipe
 */
add_filter(
	'whiskey:register_recipes',
	static fn( array $items ) => array_merge( $items, [
		'my-shop-setup' => [
			'set_homepage'      => 'shop',
			'create_shop_pages' => [ 'shop', 'cart', 'checkout' ],
			'update_permalinks' => 'shop',
		],
	] )
);

/**
 * Register multiple recipes in one callback
 */
add_filter(
	'whiskey:register_recipes',
	static fn( array $items ) => array_merge( $items, [
			// US Store Setup
			'us-store' => [
				'set_homepage'      => 'shop',
				'create_shop_pages' => [ 'shop', 'cart', 'checkout' ],
			],
			// EU Store Setup
			'eu-store' => [
				'set_homepage'      => 'shop',
				'create_shop_pages' => [ 'shop', 'cart', 'checkout' ],
			],
		]
	)
);
