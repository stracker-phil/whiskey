<?php
/**
 * WP_CLI stub for testing
 */
declare( strict_types = 1 );

/**
 * Minimal WP_CLI implementation for testing
 */
class WP_CLI {
	private static array $log_messages = [];
	private static array $warning_messages = [];
	private static array $success_messages = [];
	private static array $error_messages = [];

	public static function reset(): void {
		self::$log_messages     = [];
		self::$warning_messages = [];
		self::$success_messages = [];
		self::$error_messages   = [];
	}

	public static function log( string $message ): void {
		self::$log_messages[] = $message;
	}

	public static function warning( string $message ): void {
		self::$warning_messages[] = $message;
	}

	public static function success( string $message ): void {
		self::$success_messages[] = $message;
	}

	public static function error( string $message, bool $exit = true ): void {
		self::$error_messages[] = $message;
		if ( $exit ) {
			throw new \Exception( $message );
		}
	}

	public static function add_command( string $command, callable $callback, array $args = [] ): void {
		global $registered_cli_commands;
		$registered_cli_commands[] = array(
			'command'  => $command,
			'callback' => $callback,
			'args'     => $args,
		);
	}

	public static function get_log_messages(): array {
		return self::$log_messages;
	}

	public static function get_warning_messages(): array {
		return self::$warning_messages;
	}

	public static function get_success_messages(): array {
		return self::$success_messages;
	}

	public static function get_error_messages(): array {
		return self::$error_messages;
	}
}
