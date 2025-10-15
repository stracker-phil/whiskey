<?php
/**
 * Mock WordPress functions for testing
 *
 * @package Whiskey\Tests
 */

declare( strict_types = 1 );

/**
 * Simple hook storage for tests
 */
class WP_Hooks {
	private static array $hooks = [];

	public static function reset(): void {
		self::$hooks = [];
	}

	public static function add( string $hook, callable $callback, int $priority = 10 ): void {
		if ( ! isset( self::$hooks[ $hook ] ) ) {
			self::$hooks[ $hook ] = [];
		}
		if ( ! isset( self::$hooks[ $hook ][ $priority ] ) ) {
			self::$hooks[ $hook ][ $priority ] = [];
		}
		self::$hooks[ $hook ][ $priority ][] = $callback;
	}

	public static function call( string $hook, $value = null, ...$args ) {
		if ( ! isset( self::$hooks[ $hook ] ) ) {
			return $value;
		}

		// Sort by priority
		ksort( self::$hooks[ $hook ] );

		foreach ( self::$hooks[ $hook ] as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$value = call_user_func_array( $callback, array_merge( array( $value ), $args ) );
			}
		}

		return $value;
	}
}

function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	WP_Hooks::add( $hook, $callback, $priority );

	return true;
}

function do_action( string $hook, ...$args ): void {
	WP_Hooks::call( $hook, ...$args );
}

function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): bool {
	WP_Hooks::add( $hook, $callback, $priority );

	return true;
}

function apply_filters( string $hook, $value, ...$args ) {
	return WP_Hooks::call( $hook, $value, ...$args );
}
