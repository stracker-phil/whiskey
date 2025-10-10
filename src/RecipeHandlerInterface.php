<?php
/**
 * Recipe Handler Interface
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

/**
 * Interface for recipe handlers
 *
 * @explain Defines the contract for all recipe handlers (PayPal, WooCommerce, WordPress).
 *          Each handler validates and executes its specific recipe configuration.
 */
interface RecipeHandlerInterface {

	/**
	 * Validate recipe configuration
	 *
	 * @explain Checks if the provided configuration is valid for this handler.
	 *          Returns true if valid, false otherwise. Does not throw exceptions.
	 *
	 * @param array $config Recipe configuration to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public function validate( array $config ): bool;

	/**
	 * Execute recipe operations
	 *
	 * @explain Performs the actual recipe operations (e.g., configure PayPal, setup WooCommerce).
	 *          Returns ExecutionResult object with success status and optional data.
	 *
	 * @param array $config Recipe configuration to execute.
	 * @return ExecutionResult Execution result.
	 */
	public function execute( array $config ): ExecutionResult;
}
