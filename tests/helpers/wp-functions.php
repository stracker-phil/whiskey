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

// WordPress function stubs for ingredient tests
global $wp_functions_mock;
$wp_functions_mock = array();

function get_page_by_path( string $path ) {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['get_page_by_path'] ) ) {
		return $wp_functions_mock['get_page_by_path']( $path );
	}

	return null;
}

function get_post( int $id ) {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['get_post'] ) ) {
		return $wp_functions_mock['get_post']( $id );
	}

	return null;
}

function update_option( string $option, $value ): bool {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['update_option'] ) ) {
		return $wp_functions_mock['update_option']( $option, $value );
	}

	return true;
}

function get_option( string $option, $default = false ) {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['get_option'] ) ) {
		return $wp_functions_mock['get_option']( $option, $default );
	}

	return $default;
}

function flush_rewrite_rules(): void {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['flush_rewrite_rules'] ) ) {
		$wp_functions_mock['flush_rewrite_rules']();
	}
}

function wp_insert_post( array $data, bool $wp_error = false ) {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['wp_insert_post'] ) ) {
		return $wp_functions_mock['wp_insert_post']( $data, $wp_error );
	}

	return 0;
}

function update_post_meta( int $post_id, string $key, $value ): bool {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['update_post_meta'] ) ) {
		return $wp_functions_mock['update_post_meta']( $post_id, $key, $value );
	}

	return true;
}

function is_wp_error( $thing ): bool {
	return false; // Simplified for testing
}

function wp_get_nav_menu_object( string $menu ) {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['wp_get_nav_menu_object'] ) ) {
		return $wp_functions_mock['wp_get_nav_menu_object']( $menu );
	}

	return false;
}

function wp_create_nav_menu( string $name ) {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['wp_create_nav_menu'] ) ) {
		return $wp_functions_mock['wp_create_nav_menu']( $name );
	}

	return 0;
}

function wp_get_nav_menu_items( int $menu_id ) {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['wp_get_nav_menu_items'] ) ) {
		return $wp_functions_mock['wp_get_nav_menu_items']( $menu_id );
	}

	return false;
}

function wp_delete_post( int $post_id, bool $force_delete = false ): bool {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['wp_delete_post'] ) ) {
		return $wp_functions_mock['wp_delete_post']( $post_id, $force_delete );
	}

	return true;
}

function wp_update_nav_menu_item( int $menu_id, int $menu_item_db_id, array $menu_item_data ) {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['wp_update_nav_menu_item'] ) ) {
		return $wp_functions_mock['wp_update_nav_menu_item']( $menu_id, $menu_item_db_id, $menu_item_data );
	}

	return 0;
}

function get_theme_mod( string $name, $default = false ) {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['get_theme_mod'] ) ) {
		return $wp_functions_mock['get_theme_mod']( $name, $default );
	}

	return $default;
}

function set_theme_mod( string $name, $value ): void {
	global $wp_functions_mock;
	if ( isset( $wp_functions_mock['set_theme_mod'] ) ) {
		$wp_functions_mock['set_theme_mod']( $name, $value );
	}
}

function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
	return json_encode( $data, $options, $depth );
}
