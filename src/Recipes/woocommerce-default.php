<?php
/**
 * Recipes for: WooCommerce
 */

declare( strict_types = 1 );

use Whiskey\RecipeRegistry;

add_action( 'whiskey:register_recipe', static function ( RecipeRegistry $registry ) {
	$registry->add(
		'woocommerce',
		'us_seller',
		[
			'store_country' => 'US',
			'store_state'   => 'CA',
			'currency'      => 'USD',
			'shipping'      => [
				[ 'method' => 'free_shipping', 'title' => 'Free Shipping' ],
				[ 'method' => 'flat_rate', 'title' => 'Standard', 'cost' => 10 ],
			],
		] );
} );
