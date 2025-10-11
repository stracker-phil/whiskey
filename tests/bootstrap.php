<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Whiskey\Tests
 */

declare( strict_types = 1 );

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load functional stubs.
require_once __DIR__ . '/stubs/WP_REST_Response.php';

// Brain\Monkey setup
\Brain\Monkey\setUp();

// WordPress function stubs for testing without WordPress.
if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Mock do_action for testing
	 *
	 * @param string $hook_name Hook name.
	 * @param mixed  ...$args   Arguments to pass to callbacks.
	 * @return void
	 */
	function do_action( string $hook_name, ...$args ): void {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $hook_name ] ) ) {
			return;
		}

		foreach ( $wp_filter[ $hook_name ] as $callback ) {
			call_user_func_array( $callback, $args );
		}
	}
}

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Mock add_action for testing
	 *
	 * @param string   $hook_name     Hook name.
	 * @param callable $callback      Callback function.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Accepted args.
	 * @return void
	 */
	function add_action( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		global $wp_filter;
		$wp_filter[ $hook_name ][] = $callback;
	}
}
