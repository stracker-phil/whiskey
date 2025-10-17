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

add_action( 'whiskey:register_recipe', static function ( RecipeRegistry $registry ) {
	$registry->add(
		'site-setup',
		[
			// Create all 6 WooCommerce pages
			CreatePagesIngredient::NAME      => [
				'shop',
				'classic-cart',
				'block-cart',
				'classic-checkout',
				'block-checkout',
				'my-account',
			],

			// Set Storefront as active theme
			SetActiveThemeIngredient::NAME   => 'storefront',

			// Set shop page as homepage
			SetHomepageIngredient::NAME      => 'shop',

			// Add pages to primary menu
			SetMenuItemsIngredient::NAME     => [
				'shop',
				'classic-cart',
				'block-cart',
				'classic-checkout',
				'block-checkout',
				'my-account',
			],

			// Set pretty permalinks
			UpdatePermalinksIngredient::NAME => '/%postname%/',
		]
	);

	$registry->add(
		'us-shop',
		[
			// Set store location to California, US
			SetWooCountryIngredient::NAME  => 'US:CA',

			// Set currency to USD
			SetWooCurrencyIngredient::NAME => 'USD',
		]
	);

	$registry->add(
		'eu-shop',
		[
			// Set store location to Austria
			SetWooCountryIngredient::NAME  => 'AT',

			// Set currency to EUR
			SetWooCurrencyIngredient::NAME => 'EUR',
		]
	);
} );
