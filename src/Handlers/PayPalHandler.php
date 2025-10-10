<?php
/**
 * PayPal Recipe Handler
 *
 * @package Whiskey\Handlers
 */

declare( strict_types = 1 );

namespace Whiskey\Handlers;

use Whiskey\ExecutionResult;
use Whiskey\RecipeHandlerInterface;

/**
 * Handles PayPal configuration recipes
 */
class PayPalHandler implements RecipeHandlerInterface {

	public function validate( array $config ): bool {
		// Required fields for PayPal recipes.
		if ( ! isset( $config['mode'] ) ) {
			return false;
		}

		// Validate mode value.
		if ( ! in_array( $config['mode'], [ 'sandbox', 'live' ], true ) ) {
			return false;
		}

		return true;
	}

	public function execute( array $config ): ExecutionResult {
		$data = [];

		return new ExecutionResult(
			true,
			'PayPal recipe executed successfully',
			$data
		);
	}
}
