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
	 * @param ValidationCode $code     Validation code enum
	 * @param mixed          $context  Optional context for error messages
	 * @param ?Closure       $executor Optional executor to apply the valid ingredient
	 */
	private function __construct(
		private readonly ValidationCode $code,
		private readonly mixed $context = null,
		private readonly ?Closure $executor = null
	) {
	}

	/**
	 * Create successful validation result.
	 */
	public static function valid( callable $executor ): self {
		return new self( ValidationCode::Valid, null, $executor );
	}

	/**
	 * Create invalid type validation result.
	 *
	 * @param mixed|null $context Expected type description
	 */
	public static function invalid_type( mixed $context = null ): self {
		return new self( ValidationCode::InvalidType, $context );
	}

	/**
	 * Create missing required key validation result.
	 *
	 * @param string $key Missing key name
	 */
	public static function missing_key( string $key ): self {
		return new self( ValidationCode::MissingRequiredKey, $key );
	}

	/**
	 * Create invalid array structure validation result.
	 */
	public static function invalid_array_structure(): self {
		return new self( ValidationCode::InvalidArrayStructure );
	}

	/**
	 * Create invalid format validation result.
	 *
	 * @param mixed|null $context Format description
	 */
	public static function invalid_format( mixed $context = null ): self {
		return new self( ValidationCode::InvalidFormat, $context );
	}

	/**
	 * Create invalid value validation result.
	 *
	 * @param mixed|null $context Value description
	 */
	public static function invalid_value( mixed $context = null ): self {
		return new self( ValidationCode::InvalidValue, $context );
	}

	/**
	 * Check if validation passed.
	 */
	public function is_valid(): bool {
		return $this->code->is_valid();
	}

	/**
	 * Get human-readable validation message.
	 */
	public function get_message(): string {
		return $this->code->get_message( $this->context );
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
