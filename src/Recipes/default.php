<?php
/**
 * Recipes for: PayPal Payments for WooCommerce
 */

declare( strict_types = 1 );

use Whiskey\Registry\RecipeRegistry;
use Whiskey\Ingredients\SetHomepageIngredient;
use Whiskey\Ingredients\CreatePagesIngredient;

add_action( 'whiskey:register_recipe', static function ( RecipeRegistry $registry ) {
	$registry->add(
		'default-shop',
		[
			CreatePagesIngredient::NAME => [
				'shop',
				'classic-cart',
				'classic-checkout',
			],
			SetHomepageIngredient::NAME     => 'shop',
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
