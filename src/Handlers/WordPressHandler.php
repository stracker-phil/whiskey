<?php
/**
 * WordPress Recipe Handler
 *
 * @package Whiskey\Handlers
 */

declare( strict_types = 1 );

namespace Whiskey\Handlers;

use Whiskey\ExecutionResult;

/**
 * Handles WordPress configuration recipes
 */
class WordPressHandler extends RecipeHandler {
	protected function do_validate( array $config ): bool {
		// Not implemented yet.
		return true;
	}

	protected function do_execute( array $config ): ExecutionResult {
		$data = [];

		// Not implemented yet.

		return new ExecutionResult(
			true,
			'WordPress recipe executed successfully',
			$data
		);
	}
}
