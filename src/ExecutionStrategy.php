<?php
declare( strict_types = 1 );

namespace Whiskey;

/**
 * Defines execution strategies for recipe execution.
 *
 * This is a static utility class that provides constants and helper methods
 * for determining how recipes should be executed.
 */
class ExecutionStrategy {

	public const SEQUENTIAL        = 'sequential';
	public const CONTINUE_ON_ERROR = 'continue_on_error';
	public const DRY_RUN           = 'dry_run';

	/**
	 * Check if execution should stop on the first ingredient failure.
	 *
	 * @param string $strategy The execution strategy
	 * @return bool True if should stop on failure, false otherwise
	 */
	public static function should_stop_on_failure( string $strategy ): bool {
		return self::SEQUENTIAL === $strategy;
	}

	/**
	 * Check if ingredients should actually be executed.
	 *
	 * @param string $strategy The execution strategy
	 * @return bool True if should execute, false for dry-run
	 */
	public static function should_execute( string $strategy ): bool {
		return self::DRY_RUN !== $strategy;
	}

	/**
	 * Check if a strategy value is valid.
	 *
	 * @param string $strategy The strategy to validate
	 * @return bool True if valid, false otherwise
	 */
	public static function is_valid( string $strategy ): bool {
		return in_array(
			$strategy,
			[ self::SEQUENTIAL, self::CONTINUE_ON_ERROR, self::DRY_RUN ],
			true
		);
	}

	/**
	 * Get the default execution strategy.
	 *
	 * @return string The default strategy
	 */
	public static function get_default(): string {
		return self::SEQUENTIAL;
	}

	/**
	 * Parse a strategy string from REST parameter input.
	 *
	 * Accepts user-friendly values like "continue", "dry-run", "sequential"
	 * and returns the corresponding strategy constant. Falls back to default
	 * if the value is invalid or empty.
	 *
	 * Usage: GET /recipe/my-recipe?strategy=dry-run
	 *
	 * @param string|null $value The strategy value from user input
	 * @return string A valid strategy constant
	 */
	public static function from_string( ?string $value ): string {
		if ( ! $value ) {
			return self::get_default();
		}

		// Normalize to lowercase
		$normalized = strtolower( trim( $value ) );

		// Map user-friendly values to constants
		$map = [
			'continue'          => self::CONTINUE_ON_ERROR,
			'continue_on_error' => self::CONTINUE_ON_ERROR,
			'continue-on-error' => self::CONTINUE_ON_ERROR,
			'dry-run'           => self::DRY_RUN,
			'dry_run'           => self::DRY_RUN,
			'dryrun'            => self::DRY_RUN,
			'sequential'        => self::SEQUENTIAL,
		];

		return $map[ $normalized ] ?? self::get_default();
	}

	/**
	 * Determine strategy from CLI flag arguments.
	 *
	 * Accepts boolean flags from WP-CLI arguments and returns the appropriate
	 * strategy. If both flags are present, --dry-run takes precedence.
	 *
	 * Usage: wp whiskey apply my-recipe --dry-run
	 *        wp whiskey apply my-recipe --continue
	 *
	 * @param bool $dry_run  Whether --dry-run flag is present
	 * @param bool $continue Whether --continue flag is present
	 * @return string A valid strategy constant
	 */
	public static function from_cli_args( bool $dry_run, bool $continue ): string {
		// --dry-run takes precedence
		if ( $dry_run ) {
			return self::DRY_RUN;
		}

		if ( $continue ) {
			return self::CONTINUE_ON_ERROR;
		}

		return self::SEQUENTIAL;
	}

	/**
	 * Get all available execution strategies.
	 *
	 * @return array Array of strategy constants
	 */
	public static function get_all(): array {
		return [
			self::SEQUENTIAL,
			self::CONTINUE_ON_ERROR,
			self::DRY_RUN,
		];
	}

	/**
	 * Get a human-readable description of a strategy.
	 *
	 * @param string $strategy The execution strategy
	 * @return string Description text
	 */
	public static function get_description( string $strategy ): string {
		switch ( $strategy ) {
			case self::SEQUENTIAL:
				return 'Stop on first failure';

			case self::CONTINUE_ON_ERROR:
				return 'Continue executing even if ingredients fail';

			case self::DRY_RUN:
				return 'Validate ingredients without executing';

			default:
				return 'Unknown strategy';
		}
	}
}
