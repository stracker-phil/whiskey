<?php
/**
 * Possible outcomes of Ingredient::validate().
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

class ValidationCode {

	public const VALID                   = 'valid';
	public const INVALID_TYPE            = 'invalid_type';
	public const MISSING_REQUIRED_KEY    = 'missing_required_key';
	public const INVALID_ARRAY_STRUCTURE = 'invalid_array_structure';
	public const INVALID_FORMAT          = 'invalid_format';
	public const INVALID_VALUE           = 'invalid_value';

	public static function is_valid( string $result ): bool {
		return self::VALID === $result;
	}

	public static function get_message( string $result, $context = null ): string {
		return match ( $result ) {
			self::VALID => 'Validation passed',
			self::INVALID_TYPE => sprintf( 'Invalid type: expected %s', $context ?? 'unknown' ),
			self::MISSING_REQUIRED_KEY => sprintf( 'Missing required key: %s', $context ?? 'unknown' ),
			self::INVALID_ARRAY_STRUCTURE => 'Invalid array structure',
			self::INVALID_FORMAT => sprintf( 'Invalid format: %s', $context ?? 'unknown' ),
			self::INVALID_VALUE => sprintf( 'Invalid value: %s', $context ?? 'unknown' ),
			default => 'Unknown validation error',
		};
	}
}
