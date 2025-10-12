<?php
/**
 * Recipes for: WordPress core
 */

declare( strict_types = 1 );

use Whiskey\RecipeRegistry;

add_action( 'whiskey:register_recipe', static function ( RecipeRegistry $registry ) {
	$registry->add(
		'wordpress',
		'woo_shop',
		[
			'create_shop_pages'   => [
				'shop',
				'block-cart',
				'block-checkout',
				'classic-cart',
				'classic-checkout',
				'my-account',
			],
			'set_homepage'        => 'shop',
			'permalink_structure' => '/%postname%/',
			'add_menu_items'      => [ 'cart', 'my-account' ],
		] );
} );
