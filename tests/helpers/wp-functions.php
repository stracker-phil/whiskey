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

/**
 * WordPress function mocking for tests
 */
class WP_Functions {
	private static array $mocks = [];

	public static function reset(): void {
		self::$mocks = [];
	}

	public static function mock( string $function, $return_value = null ): void {
		if ( is_callable( $return_value ) ) {
			self::$mocks[ $function ] = $return_value;
		} elseif ( func_num_args() === 1 ) {
			// No second arg = void function
			self::$mocks[ $function ] = static fn() => null;
		} else {
			// Scalar value = return it
			self::$mocks[ $function ] = static fn() => $return_value;
		}
	}

	public static function call( string $function, ...$args ) {
		if ( isset( self::$mocks[ $function ] ) ) {
			return call_user_func_array( self::$mocks[ $function ], $args );
		}

		return null; // Default behavior for unmocked functions
	}

	public static function is_mocked( string $function ): bool {
		return isset( self::$mocks[ $function ] );
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

// WordPress function stubs using WP_Functions class
function get_page_by_path( string $path ) {
	return WP_Functions::call( 'get_page_by_path', $path );
}

function get_post( int $id ) {
	return WP_Functions::call( 'get_post', $id );
}

function update_option( string $option, $value ): bool {
	$result = WP_Functions::call( 'update_option', $option, $value );

	return $result !== null ? $result : true;
}

function get_option( string $option, $default = false ) {
	$result = WP_Functions::call( 'get_option', $option, $default );

	return $result !== null ? $result : $default;
}

function delete_option( string $option ): bool {
	$result = WP_Functions::call( 'delete_option', $option );

	return $result !== null ? $result : true;
}

function flush_rewrite_rules(): void {
	WP_Functions::call( 'flush_rewrite_rules' );
}

function wp_insert_post( array $data, bool $wp_error = false ) {
	$result = WP_Functions::call( 'wp_insert_post', $data, $wp_error );

	return $result !== null ? $result : 0;
}

function update_post_meta( int $post_id, string $key, $value ): bool {
	$result = WP_Functions::call( 'update_post_meta', $post_id, $key, $value );

	return $result !== null ? $result : true;
}

function is_wp_error( $thing ): bool {
	$result = WP_Functions::call( 'is_wp_error', $thing );

	return $result !== null ? $result : false;
}

function wp_get_nav_menu_object( string $menu ) {
	return WP_Functions::call( 'wp_get_nav_menu_object', $menu );
}

function wp_create_nav_menu( string $name ) {
	$result = WP_Functions::call( 'wp_create_nav_menu', $name );

	return $result !== null ? $result : 0;
}

function wp_get_nav_menu_items( int $menu_id ) {
	return WP_Functions::call( 'wp_get_nav_menu_items', $menu_id );
}

function wp_delete_post( int $post_id, bool $force_delete = false ): bool {
	$result = WP_Functions::call( 'wp_delete_post', $post_id, $force_delete );

	return $result !== null ? $result : true;
}

function wp_update_nav_menu_item( int $menu_id, int $menu_item_db_id, array $menu_item_data ) {
	$result = WP_Functions::call( 'wp_update_nav_menu_item', $menu_id, $menu_item_db_id, $menu_item_data );

	return $result !== null ? $result : 0;
}

function get_theme_mod( string $name, $default = false ) {
	$result = WP_Functions::call( 'get_theme_mod', $name, $default );

	return $result !== null ? $result : $default;
}

function set_theme_mod( string $name, $value ): void {
	WP_Functions::call( 'set_theme_mod', $name, $value );
}

function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
	return json_encode( $data, $options, $depth );
}

function wp_get_theme( ?string $stylesheet = null ) {
	return WP_Functions::call( 'wp_get_theme', $stylesheet );
}

function switch_theme( string $stylesheet ): void {
	WP_Functions::call( 'switch_theme', $stylesheet );
}

// Track REST route registrations for testing
global $registered_rest_routes;
$registered_rest_routes = [];

function register_rest_route( string $namespace, string $route, array $args = [] ): bool {
	global $registered_rest_routes;
	$registered_rest_routes[] = [
		'namespace' => $namespace,
		'route'     => $route,
		'args'      => $args,
	];

	return true;
}

// Track CLI command registrations for testing
global $registered_cli_commands;
$registered_cli_commands = [];

// Image processing functions
function download_url( string $url ) {
	return WP_Functions::call( 'download_url', $url );
}

function media_handle_sideload( array $file, int $post_id ) {
	$result = WP_Functions::call( 'media_handle_sideload', $file, $post_id );

	return $result !== null ? $result : 0;
}

function get_attached_file( int $attachment_id ) {
	return WP_Functions::call( 'get_attached_file', $attachment_id );
}

function wp_get_image_editor( string $path ) {
	return WP_Functions::call( 'wp_get_image_editor', $path );
}

