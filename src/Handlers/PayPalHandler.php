<?php
/**
 * PayPal Recipe Handler
 *
 * @package Whiskey\Handlers
 */

declare( strict_types = 1 );

namespace Whiskey\Handlers;

use Whiskey\ExecutionResult;

/**
 * Handles PayPal configuration recipes
 */
class PayPalHandler extends RecipeHandler {
	protected const TYPE = 'paypal';

	protected function do_validate( array $config ): bool {
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

	protected function do_execute( array $config ): ExecutionResult {
		$data = [];

		return new ExecutionResult(
			true,
			'PayPal recipe executed successfully',
			$data
		);
	}
}
