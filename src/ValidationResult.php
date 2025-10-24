<?php
/**
 * Validation result value object.
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

/**
 * Immutable value object holding validation result code + optional context.
 */
class ValidationResult {

	private string $code;
	private $context;

	/**
	 * Private constructor - use factory methods for type safety.
	 *
	 * @param string $code    Validation code constant from ValidationCode
	 * @param mixed  $context Optional context for error messages
	 */
	private function __construct( string $code, $context = null ) {
		$this->code    = $code;
		$this->context = $context;
	}

	/**
	 * Create successful validation result.
	 */
	public static function valid(): self {
		return new self( ValidationCode::VALID );
	}

	/**
	 * Create invalid type validation result.
	 *
	 * @param mixed $context Expected type description
	 */
	public static function invalid_type( $context = null ): self {
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
	 * @param mixed $context Format description
	 */
	public static function invalid_format( $context = null ): self {
		return new self( ValidationCode::INVALID_FORMAT, $context );
	}

	/**
	 * Create invalid value validation result.
	 *
	 * @param mixed $context Value description
	 */
	public static function invalid_value( $context = null ): self {
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
}
