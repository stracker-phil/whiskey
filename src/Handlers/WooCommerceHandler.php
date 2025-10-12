<?php
/**
 * WooCommerce Recipe Handler
 *
 * @package Whiskey\Handlers
 */

declare( strict_types = 1 );

namespace Whiskey\Handlers;

use Whiskey\ExecutionResult;

/**
 * Handles WooCommerce configuration recipes
 */
class WooCommerceHandler extends RecipeHandler {
	protected const TYPE = 'woocommerce';

	protected function do_validate( array $config ): bool {
		// Not implemented yet.
		return true;
	}

	protected function do_execute( array $config ): ExecutionResult {
		$data = [];

		// Not implemented yet.

		return new ExecutionResult(
			true,
			'WooCommerce recipe executed successfully',
			$data
		);
	}
}
