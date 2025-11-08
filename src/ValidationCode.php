<?php
/**
 * Possible outcomes of Ingredient::validate().
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

enum ValidationCode: string {
	case Valid = 'valid';
	case InvalidType = 'invalid_type';
	case MissingRequiredKey = 'missing_required_key';
	case InvalidArrayStructure = 'invalid_array_structure';
	case InvalidFormat = 'invalid_format';
	case InvalidValue = 'invalid_value';

	/**
	 * Check if this validation code represents a valid result.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this === self::Valid;
	}

	/**
	 * Get human-readable message for this validation code.
	 *
	 * @param mixed|null $context Optional context for error messages
	 * @return string
	 */
	public function get_message( mixed $context = null ): string {
		return match ( $this ) {
			self::Valid => 'Validation passed',
			self::InvalidType => sprintf( 'Invalid type: expected %s', $context ?? 'unknown' ),
			self::MissingRequiredKey => sprintf( 'Missing required key: %s', $context ?? 'unknown' ),
			self::InvalidArrayStructure => 'Invalid array structure',
			self::InvalidFormat => sprintf( 'Invalid format: %s', $context ?? 'unknown' ),
			self::InvalidValue => sprintf( 'Invalid value: %s', $context ?? 'unknown' ),
		};
	}
}
