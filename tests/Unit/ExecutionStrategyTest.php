<?php
/**
 * @covers \Whiskey\ExecutionStrategy
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\ExecutionStrategy;

class ExecutionStrategyTest extends WhiskeyTest {

	// ===== Constants Tests =====

	public function test_sequential_constant_is_defined(): void {
		$this->assertSame( 'sequential', ExecutionStrategy::SEQUENTIAL );
	}

	public function test_continue_on_error_constant_is_defined(): void {
		$this->assertSame( 'continue_on_error', ExecutionStrategy::CONTINUE_ON_ERROR );
	}

	public function test_dry_run_constant_is_defined(): void {
		$this->assertSame( 'dry_run', ExecutionStrategy::DRY_RUN );
	}

	// ===== should_stop_on_failure() Tests =====

	public function test_sequential_should_stop_on_failure(): void {
		$this->assertTrue( ExecutionStrategy::should_stop_on_failure( ExecutionStrategy::SEQUENTIAL ) );
	}

	public function test_continue_on_error_should_not_stop_on_failure(): void {
		$this->assertFalse( ExecutionStrategy::should_stop_on_failure( ExecutionStrategy::CONTINUE_ON_ERROR ) );
	}

	public function test_dry_run_should_not_stop_on_failure(): void {
		$this->assertFalse( ExecutionStrategy::should_stop_on_failure( ExecutionStrategy::DRY_RUN ) );
	}

	// ===== should_execute() Tests =====

	public function test_sequential_should_execute(): void {
		$this->assertTrue( ExecutionStrategy::should_execute( ExecutionStrategy::SEQUENTIAL ) );
	}

	public function test_continue_on_error_should_execute(): void {
		$this->assertTrue( ExecutionStrategy::should_execute( ExecutionStrategy::CONTINUE_ON_ERROR ) );
	}

	public function test_dry_run_should_not_execute(): void {
		$this->assertFalse( ExecutionStrategy::should_execute( ExecutionStrategy::DRY_RUN ) );
	}

	// ===== is_valid() Tests =====

	public function test_is_valid_returns_true_for_sequential(): void {
		$this->assertTrue( ExecutionStrategy::is_valid( ExecutionStrategy::SEQUENTIAL ) );
	}

	public function test_is_valid_returns_true_for_continue_on_error(): void {
		$this->assertTrue( ExecutionStrategy::is_valid( ExecutionStrategy::CONTINUE_ON_ERROR ) );
	}

	public function test_is_valid_returns_true_for_dry_run(): void {
		$this->assertTrue( ExecutionStrategy::is_valid( ExecutionStrategy::DRY_RUN ) );
	}

	public function test_is_valid_returns_false_for_unknown_strategy(): void {
		$this->assertFalse( ExecutionStrategy::is_valid( 'unknown' ) );
	}

	public function test_is_valid_returns_false_for_empty_string(): void {
		$this->assertFalse( ExecutionStrategy::is_valid( '' ) );
	}

	// ===== get_default() Tests =====

	public function test_get_default_returns_sequential(): void {
		$this->assertSame( ExecutionStrategy::SEQUENTIAL, ExecutionStrategy::get_default() );
	}

	// ===== from_string() Tests =====

	public function test_from_string_returns_sequential_for_sequential(): void {
		$this->assertSame( ExecutionStrategy::SEQUENTIAL, ExecutionStrategy::from_string( 'sequential' ) );
	}

	public function test_from_string_returns_continue_for_continue(): void {
		$this->assertSame( ExecutionStrategy::CONTINUE_ON_ERROR, ExecutionStrategy::from_string( 'continue' ) );
	}

	public function test_from_string_returns_continue_for_continue_on_error(): void {
		$this->assertSame( ExecutionStrategy::CONTINUE_ON_ERROR, ExecutionStrategy::from_string( 'continue_on_error' ) );
	}

	public function test_from_string_returns_continue_for_continue_with_hyphen(): void {
		$this->assertSame( ExecutionStrategy::CONTINUE_ON_ERROR, ExecutionStrategy::from_string( 'continue-on-error' ) );
	}

	public function test_from_string_returns_dry_run_for_dry_run_with_hyphen(): void {
		$this->assertSame( ExecutionStrategy::DRY_RUN, ExecutionStrategy::from_string( 'dry-run' ) );
	}

	public function test_from_string_returns_dry_run_for_dry_run_with_underscore(): void {
		$this->assertSame( ExecutionStrategy::DRY_RUN, ExecutionStrategy::from_string( 'dry_run' ) );
	}

	public function test_from_string_returns_dry_run_for_dryrun(): void {
		$this->assertSame( ExecutionStrategy::DRY_RUN, ExecutionStrategy::from_string( 'dryrun' ) );
	}

	public function test_from_string_is_case_insensitive(): void {
		$this->assertSame( ExecutionStrategy::CONTINUE_ON_ERROR, ExecutionStrategy::from_string( 'CONTINUE' ) );
		$this->assertSame( ExecutionStrategy::DRY_RUN, ExecutionStrategy::from_string( 'DRY-RUN' ) );
		$this->assertSame( ExecutionStrategy::SEQUENTIAL, ExecutionStrategy::from_string( 'Sequential' ) );
	}

	public function test_from_string_trims_whitespace(): void {
		$this->assertSame( ExecutionStrategy::CONTINUE_ON_ERROR, ExecutionStrategy::from_string( '  continue  ' ) );
	}

	public function test_from_string_returns_default_for_null(): void {
		$this->assertSame( ExecutionStrategy::SEQUENTIAL, ExecutionStrategy::from_string( null ) );
	}

	public function test_from_string_returns_default_for_empty_string(): void {
		$this->assertSame( ExecutionStrategy::SEQUENTIAL, ExecutionStrategy::from_string( '' ) );
	}

	public function test_from_string_returns_default_for_unknown_value(): void {
		$this->assertSame( ExecutionStrategy::SEQUENTIAL, ExecutionStrategy::from_string( 'unknown' ) );
	}

	// ===== from_cli_args() Tests =====

	public function test_from_cli_args_returns_sequential_when_no_flags(): void {
		$this->assertSame( ExecutionStrategy::SEQUENTIAL, ExecutionStrategy::from_cli_args( false, false ) );
	}

	public function test_from_cli_args_returns_dry_run_when_dry_run_flag_present(): void {
		$this->assertSame( ExecutionStrategy::DRY_RUN, ExecutionStrategy::from_cli_args( true, false ) );
	}

	public function test_from_cli_args_returns_continue_when_continue_flag_present(): void {
		$this->assertSame( ExecutionStrategy::CONTINUE_ON_ERROR, ExecutionStrategy::from_cli_args( false, true ) );
	}

	public function test_from_cli_args_dry_run_takes_precedence_over_continue(): void {
		$this->assertSame( ExecutionStrategy::DRY_RUN, ExecutionStrategy::from_cli_args( true, true ) );
	}

	// ===== get_all() Tests =====

	public function test_get_all_returns_array_with_all_strategies(): void {
		$strategies = ExecutionStrategy::get_all();

		$this->assertIsArray( $strategies );
		$this->assertCount( 3, $strategies );
		$this->assertContains( ExecutionStrategy::SEQUENTIAL, $strategies );
		$this->assertContains( ExecutionStrategy::CONTINUE_ON_ERROR, $strategies );
		$this->assertContains( ExecutionStrategy::DRY_RUN, $strategies );
	}

	// ===== get_description() Tests =====

	public function test_get_description_returns_correct_text_for_sequential(): void {
		$this->assertSame( 'Stop on first failure', ExecutionStrategy::get_description( ExecutionStrategy::SEQUENTIAL ) );
	}

	public function test_get_description_returns_correct_text_for_continue_on_error(): void {
		$this->assertSame(
			'Continue executing even if ingredients fail',
			ExecutionStrategy::get_description( ExecutionStrategy::CONTINUE_ON_ERROR )
		);
	}

	public function test_get_description_returns_correct_text_for_dry_run(): void {
		$this->assertSame(
			'Validate ingredients without executing',
			ExecutionStrategy::get_description( ExecutionStrategy::DRY_RUN )
		);
	}

	public function test_get_description_returns_unknown_for_invalid_strategy(): void {
		$this->assertSame( 'Unknown strategy', ExecutionStrategy::get_description( 'invalid' ) );
	}

	// ===== Integration Tests =====

	public function test_from_string_produces_valid_strategies(): void {
		$inputs = [ 'continue', 'dry-run', 'sequential', null, 'invalid' ];

		foreach ( $inputs as $input ) {
			$strategy = ExecutionStrategy::from_string( $input );
			$this->assertTrue(
				ExecutionStrategy::is_valid( $strategy ),
				"from_string('{$input}') should produce a valid strategy"
			);
		}
	}

	public function test_strategy_behavior_matrix(): void {
		// Test the behavior matrix for all strategies
		$expectations = [
			ExecutionStrategy::SEQUENTIAL        => [
				'stop_on_failure' => true,
				'should_execute'  => true,
			],
			ExecutionStrategy::CONTINUE_ON_ERROR => [
				'stop_on_failure' => false,
				'should_execute'  => true,
			],
			ExecutionStrategy::DRY_RUN           => [
				'stop_on_failure' => false,
				'should_execute'  => false,
			],
		];

		foreach ( $expectations as $strategy => $expected ) {
			$this->assertSame(
				$expected['stop_on_failure'],
				ExecutionStrategy::should_stop_on_failure( $strategy ),
				"should_stop_on_failure() for {$strategy}"
			);

			$this->assertSame(
				$expected['should_execute'],
				ExecutionStrategy::should_execute( $strategy ),
				"should_execute() for {$strategy}"
			);
		}
	}
}
