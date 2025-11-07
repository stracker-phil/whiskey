<?php
/**
 * Validation result value object.
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

use Closure;

/**
 * Immutable value object holding validation result code + optional context.
 */
class ValidationResult {

	/**
	 * Private constructor - use factory methods for type safety.
	 *
	 * @param string   $code     Validation code constant from ValidationCode
	 * @param mixed    $context  Optional context for error messages
	 * @param ?Closure $executor Optional executor to apply the valid ingredient
	 */
	private function __construct(
		private string $code,
		private mixed $context = null,
		private ?Closure $executor = null
	) {
	}

	/**
	 * Create successful validation result.
	 */
	public static function valid( callable $executor ): self {
		return new self( ValidationCode::VALID, null, $executor );
	}

	/**
	 * Create invalid type validation result.
	 *
	 * @param mixed|null $context Expected type description
	 */
	public static function invalid_type( mixed $context = null ): self {
		return new self( ValidationCode::INVALID_TYPE, $context );
	}

	/**
	 * Create missing required key validation result.
	 *
	 * @param string $key Missing key name
	 */
	public static function missing_key( string $key ): self {
		return new self( ValidationCode::MISSING_REQUIRED_KEY, $key );
	}

	/**
	 * Create invalid array structure validation result.
	 */
	public static function invalid_array_structure(): self {
		return new self( ValidationCode::INVALID_ARRAY_STRUCTURE );
	}

	/**
	 * Create invalid format validation result.
	 *
	 * @param mixed|null $context Format description
	 */
	public static function invalid_format( mixed $context = null ): self {
		return new self( ValidationCode::INVALID_FORMAT, $context );
	}

	/**
	 * Create invalid value validation result.
	 *
	 * @param mixed|null $context Value description
	 */
	public static function invalid_value( mixed $context = null ): self {
		return new self( ValidationCode::INVALID_VALUE, $context );
	}

	/**
	 * Check if validation passed.
	 */
	public function is_valid(): bool {
		return $this->code === ValidationCode::VALID;
	}

	/**
	 * Get human-readable validation message.
	 */
	public function get_message(): string {
		return ValidationCode::get_message( $this->code, $this->context );
	}

	/**
	 * The only way to execute the ingredient.
	 *
	 * As the executor can only be set via the `::valid()` factory, it's impossible to execute an
	 * ingredient that does not pass validation.
	 *
	 * @param mixed ...$args Optional arguments to pass to the executor callback
	 * @return ExecutionResult
	 */
	public function execute( ...$args ): ExecutionResult {
		if ( ! $this->executor || ! $this->is_valid() ) {
			return new ExecutionResult(
				success: false,
				message: 'Cannot execute invalid result: ' . $this->get_message()
			);
		}

		return call_user_func( $this->executor, ...$args );
	}
}
