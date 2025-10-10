<?php
/**
 * Handler Factory - Creates handler instances based on recipe type
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

use Whiskey\Handlers\PayPalHandler;

/**
 * Factory for creating recipe handlers
 *
 * @explain Maps recipe types to their corresponding handler implementations.
 *          Uses switch statement in PHP 7.4, will be refactored to match in PHP 8.0.
 */
final class HandlerFactory {

	/**
	 * Get handler instance for given recipe type
	 */
	public function get_handler( string $type ): ?RecipeHandlerInterface {
		switch ( $type ) {
			case 'paypal':
				return new PayPalHandler();
			default:
				return null;
		}
	}
}
