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
		if ( self::VALID === $result ) {
			return 'Validation passed';
		}

		if ( self::INVALID_TYPE === $result ) {
			return sprintf( 'Invalid type: expected %s', $context ?? 'unknown' );
		}

		if ( self::MISSING_REQUIRED_KEY === $result ) {
			return sprintf( 'Missing required key: %s', $context ?? 'unknown' );
		}

		if ( self::INVALID_ARRAY_STRUCTURE === $result ) {
			return 'Invalid array structure';
		}

		if ( self::INVALID_FORMAT === $result ) {
			return sprintf( 'Invalid format: %s', $context ?? 'unknown' );
		}

		if ( self::INVALID_VALUE === $result ) {
			return sprintf( 'Invalid value: %s', $context ?? 'unknown' );
		}

		return 'Unknown validation error';
	}
}
