<?php
/**
 * Base class for all recipe handlers.
 *
 * @explain Defines the contract for all recipe handlers (PayPal, WooCommerce, WordPress).
 *           Each handler validates and executes its specific recipe configuration.
 */

namespace Whiskey\Handlers;

use Whiskey\ExecutionResult;

abstract class RecipeHandler {

	/**
	 * The handler type, must be defined by the child class!
	 */
	protected const TYPE = '';

	abstract protected function do_validate( array $config ): bool;

	abstract protected function do_execute( array $config ): ExecutionResult;

	/**
	 * Validate recipe configuration
	 *
	 * @explain Checks if the provided configuration is valid for this handler.
	 *          Returns true if valid, false otherwise. Does not throw exceptions.
	 *
	 * @param array $config Recipe configuration to validate.
	 * @return bool True if valid (can be executed), false otherwise.
	 */
	public function validate( array $config ): bool {
		$result = $this->do_validate( $config );

		/**
		 * Filter the validation result. Allowing other developers to
		 * validate custom configuration values.
		 */
		return apply_filters(
			'whiskey:validate_result:' . static::TYPE,
			$result,
			$config
		);
	}

	/**
	 * Execute recipe operations
	 *
	 * @explain Performs the actual recipe operations (e.g., configure PayPal, setup WooCommerce).
	 *          Returns ExecutionResult object with success status and optional data.
	 *
	 * @param array $config Recipe configuration to execute.
	 * @return ExecutionResult Execution result.
	 */
	public function execute( array $config ): ExecutionResult {
		$result = $this->do_execute( $config );

		/**
		 * Action that fires after the recipe was applied, regardless
		 * of success/failure. Allows other developers to extend the
		 * handler with custom behavior.
		 */
		do_action(
			'whiskey:post_execute:' . static::TYPE,
			$config,
			$result
		);

		return $result;
	}
}
