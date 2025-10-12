<?php
/**
 * WordPress Recipe Handler
 *
 * @package Whiskey\Handlers
 */

declare( strict_types = 1 );

namespace Whiskey\Handlers;

use Whiskey\ExecutionResult;
use Whiskey\RecipeHandlerInterface;

/**
 * Handles WordPress configuration recipes
 */
class WordPressHandler implements RecipeHandlerInterface {
	public function validate( array $config ): bool {
		// Not implemented yet.
		return true;
	}

	public function execute( array $config ): ExecutionResult {
		$data = [];

		// Not implemented yet.

		return new ExecutionResult(
			true,
			'WordPress recipe executed successfully',
			$data
		);
	}
}
