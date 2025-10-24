<?php
/**
 * @covers \Whiskey\ExecutionStrategy
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\ExecutionStrategy;

class ExecutionStrategyTest extends WhiskeyTest {

	// ===== Constants Tests =====

	/**
	 * GIVEN ExecutionStrategy class constants
	 * WHEN accessing constant values
	 * THEN should return expected string values
	 *
	 * @dataProvider constants_provider
	 */
	public function test_constants_have_correct_values( string $constant_name, string $expected_value ): void {
		$reflection = new \ReflectionClass( ExecutionStrategy::class );
		$constants  = $reflection->getConstants();

		$this->assertArrayHasKey( $constant_name, $constants );
		$this->assertSame( $expected_value, $constants[ $constant_name ] );
	}

	public function constants_provider(): array {
		return [
			'sequential'        => [ 'SEQUENTIAL', 'sequential' ],
			'continue on error' => [ 'CONTINUE_ON_ERROR', 'continue_on_error' ],
			'dry run'           => [ 'DRY_RUN', 'dry_run' ],
		];
	}

	// ===== Strategy Behavior Tests =====

	/**
	 * GIVEN different execution strategies
	 * WHEN checking behavior flags
	 * THEN should return expected stop_on_failure and should_execute values
	 *
	 * @dataProvider strategy_behavior_provider
	 */
	public function test_strategy_behavior(
		string $strategy,
		bool $expected_stop_on_failure,
		bool $expected_should_execute
	): void {
		$this->assertSame(
			$expected_stop_on_failure,
			ExecutionStrategy::should_stop_on_failure( $strategy )
		);

		$this->assertSame(
			$expected_should_execute,
			ExecutionStrategy::should_execute( $strategy )
		);
	}

	public function strategy_behavior_provider(): array {
		return [
			'sequential stops and executes'            => [
				ExecutionStrategy::SEQUENTIAL,
				true,
				true,
			],
			'continue_on_error continues and executes' => [
				ExecutionStrategy::CONTINUE_ON_ERROR,
				false,
				true,
			],
			'dry_run continues but does not execute'   => [
				ExecutionStrategy::DRY_RUN,
				false,
				false,
			],
		];
	}

	// ===== is_valid() Tests =====

	/**
	 * GIVEN various strategy strings
	 * WHEN validating strategy
	 * THEN should return true only for known strategies
	 *
	 * @dataProvider is_valid_provider
	 */
	public function test_is_valid( string $strategy, bool $expected ): void {
		$this->assertSame( $expected, ExecutionStrategy::is_valid( $strategy ) );
	}

	public function is_valid_provider(): array {
		return [
			'sequential is valid'        => [ ExecutionStrategy::SEQUENTIAL, true ],
			'continue_on_error is valid' => [ ExecutionStrategy::CONTINUE_ON_ERROR, true ],
			'dry_run is valid'           => [ ExecutionStrategy::DRY_RUN, true ],
			'unknown is invalid'         => [ 'unknown', false ],
			'empty string is invalid'    => [ '', false ],
		];
	}

	// ===== get_default() Tests =====

	/**
	 * GIVEN no strategy specified
	 * WHEN getting default strategy
	 * THEN should return sequential
	 */
	public function test_get_default_returns_sequential(): void {
		$this->assertSame( ExecutionStrategy::SEQUENTIAL, ExecutionStrategy::get_default() );
	}

	// ===== from_string() Tests =====

	/**
	 * GIVEN various string inputs
	 * WHEN converting to strategy
	 * THEN should normalize to correct strategy constant
	 *
	 * @dataProvider from_string_provider
	 */
	public function test_from_string( ?string $input, string $expected ): void {
		$this->assertSame( $expected, ExecutionStrategy::from_string( $input ) );
	}

	public function from_string_provider(): array {
		return [
			// Exact matches
			'sequential exact'             => [ 'sequential', ExecutionStrategy::SEQUENTIAL ],

			// Continue variations
			'continue short form'          => [ 'continue', ExecutionStrategy::CONTINUE_ON_ERROR ],
			'continue_on_error underscore' => [
				'continue_on_error',
				ExecutionStrategy::CONTINUE_ON_ERROR,
			],
			'continue-on-error hyphen'     => [
				'continue-on-error',
				ExecutionStrategy::CONTINUE_ON_ERROR,
			],

			// Dry run variations
			'dry_run underscore'           => [ 'dry_run', ExecutionStrategy::DRY_RUN ],
			'dry-run hyphen'               => [ 'dry-run', ExecutionStrategy::DRY_RUN ],
			'dryrun no separator'          => [ 'dryrun', ExecutionStrategy::DRY_RUN ],

			// Case insensitivity
			'CONTINUE uppercase'           => [ 'CONTINUE', ExecutionStrategy::CONTINUE_ON_ERROR ],
			'DRY-RUN uppercase'            => [ 'DRY-RUN', ExecutionStrategy::DRY_RUN ],
			'Sequential mixed case'        => [ 'Sequential', ExecutionStrategy::SEQUENTIAL ],

			// Whitespace handling
			'continue with spaces'         => [
				'  continue  ',
				ExecutionStrategy::CONTINUE_ON_ERROR,
			],

			// Defaults
			'null returns default'         => [ null, ExecutionStrategy::SEQUENTIAL ],
			'empty string returns default' => [ '', ExecutionStrategy::SEQUENTIAL ],
			'unknown returns default'      => [ 'unknown', ExecutionStrategy::SEQUENTIAL ],
		];
	}

	/**
	 * GIVEN any output from from_string
	 * WHEN validating the result
	 * THEN should always be a valid strategy
	 */
	public function test_from_string_always_produces_valid_strategies(): void {
		$inputs = [ 'continue', 'dry-run', 'sequential', null, '', 'invalid', 'random' ];

		foreach ( $inputs as $input ) {
			$strategy = ExecutionStrategy::from_string( $input );
			$this->assertTrue(
				ExecutionStrategy::is_valid( $strategy ),
				"from_string('{$input}') should produce a valid strategy but got: {$strategy}"
			);
		}
	}

	// ===== from_cli_args() Tests =====

	/**
	 * GIVEN CLI flag combinations
	 * WHEN determining strategy from flags
	 * THEN should return correct strategy with proper precedence
	 *
	 * @dataProvider from_cli_args_provider
	 */
	public function test_from_cli_args( bool $dry_run, bool $continue, string $expected ): void {
		$this->assertSame( $expected, ExecutionStrategy::from_cli_args( $dry_run, $continue ) );
	}

	public function from_cli_args_provider(): array {
		return [
			'no flags returns sequential'             => [
				false,
				false,
				ExecutionStrategy::SEQUENTIAL,
			],
			'dry_run flag returns dry_run'            => [
				true,
				false,
				ExecutionStrategy::DRY_RUN,
			],
			'continue flag returns continue_on_error' => [
				false,
				true,
				ExecutionStrategy::CONTINUE_ON_ERROR,
			],
			'both flags dry_run takes precedence'     => [ true, true, ExecutionStrategy::DRY_RUN ],
		];
	}

	// ===== get_all() Tests =====

	/**
	 * GIVEN ExecutionStrategy class
	 * WHEN getting all strategies
	 * THEN should return array containing all strategy constants
	 */
	public function test_get_all_returns_all_strategies(): void {
		$strategies = ExecutionStrategy::get_all();

		$this->assertIsArray( $strategies );
		$this->assertCount( 3, $strategies );
		$this->assertContains( ExecutionStrategy::SEQUENTIAL, $strategies );
		$this->assertContains( ExecutionStrategy::CONTINUE_ON_ERROR, $strategies );
		$this->assertContains( ExecutionStrategy::DRY_RUN, $strategies );
	}

	// ===== get_description() Tests =====

	/**
	 * GIVEN strategy constant
	 * WHEN getting description
	 * THEN should return human-readable description
	 *
	 * @dataProvider description_provider
	 */
	public function test_get_description( string $strategy, string $expected_description ): void {
		$this->assertSame( $expected_description, ExecutionStrategy::get_description( $strategy ) );
	}

	public function description_provider(): array {
		return [
			'sequential'        => [
				ExecutionStrategy::SEQUENTIAL,
				'Stop on first failure',
			],
			'continue_on_error' => [
				ExecutionStrategy::CONTINUE_ON_ERROR,
				'Continue executing even if ingredients fail',
			],
			'dry_run'           => [
				ExecutionStrategy::DRY_RUN,
				'Validate ingredients without executing',
			],
			'invalid strategy'  => [
				'invalid',
				'Unknown strategy',
			],
		];
	}
}
