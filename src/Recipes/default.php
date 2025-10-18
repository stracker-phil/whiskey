<?php
/**
 * Default recipes.
 */

declare( strict_types = 1 );

use Whiskey\Registry\RecipeRegistry;
use Whiskey\Ingredients\CreatePagesIngredient;
use Whiskey\Ingredients\SetHomepageIngredient;
use Whiskey\Ingredients\SetActiveThemeIngredient;
use Whiskey\Ingredients\SetMenuItemsIngredient;
use Whiskey\Ingredients\UpdatePermalinksIngredient;
use Whiskey\Ingredients\SetWooCountryIngredient;
use Whiskey\Ingredients\SetWooCurrencyIngredient;

add_filter(
	'whiskey:register_recipes',
	static fn( array $items ) => array_merge( $items, [
		'site-setup' => [
			CreatePagesIngredient::NAME      => [
				'shop',
				'classic-cart',
				'block-cart',
				'classic-checkout',
				'block-checkout',
				'my-account',
			],
			SetActiveThemeIngredient::NAME   => 'storefront',
			SetHomepageIngredient::NAME      => 'shop',
			SetMenuItemsIngredient::NAME     => [
				'shop',
				'classic-cart',
				'block-cart',
				'classic-checkout',
				'block-checkout',
				'my-account',
			],
			UpdatePermalinksIngredient::NAME => '/%postname%/',
		],
	] )
);

add_filter(
	'whiskey:register_recipes',
	static fn( array $items ) => array_merge( $items, [
		'us-shop' => [
			SetWooCountryIngredient::NAME  => 'US:CA',
			SetWooCurrencyIngredient::NAME => 'USD',
		],
	] )
);

add_filter(
	'whiskey:register_recipes',
	static fn( array $items ) => array_merge( $items, [
		'eu-shop' => [
			SetWooCountryIngredient::NAME  => 'AT',
			SetWooCurrencyIngredient::NAME => 'EUR',
		],
	] )
);
