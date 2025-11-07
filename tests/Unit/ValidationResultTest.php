<?php
/**
 * @covers \Whiskey\ValidationResult
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\ValidationResult;
use Whiskey\ValidationCode;

class ValidationResultTest extends WhiskeyTest {

	// ===== Factory Method Tests =====

	/**
	 * GIVEN valid validation result
	 * WHEN checking validity status
	 * THEN should return true and appropriate message
	 */
	public function test_valid_creates_valid_result(): void {
		$result = ValidationResult::valid( fn() => new \Whiskey\ExecutionResult( true, 'test' ) );

		$this->assertTrue( $result->is_valid() );
		$this->assertSame( 'Validation passed', $result->get_message() );
	}

	/**
	 * GIVEN invalid type factory method
	 * WHEN creating result with or without context
	 * THEN should create invalid result with appropriate message
	 *
	 * @dataProvider invalid_type_provider
	 */
	public function test_invalid_type_creates_invalid_result( ?string $context, string $expected_message ): void {
		$result = ValidationResult::invalid_type( $context );

		$this->assertFalse( $result->is_valid() );
		$this->assertSame( $expected_message, $result->get_message() );
	}

	public function invalid_type_provider(): array {
		return [
			'with context'    => [ 'string', 'Invalid type: expected string' ],
			'without context' => [ null, 'Invalid type: expected unknown' ],
		];
	}

	/**
	 * GIVEN missing key factory method
	 * WHEN creating result with key name
	 * THEN should create invalid result with key in message
	 */
	public function test_missing_key_creates_invalid_result(): void {
		$result = ValidationResult::missing_key( 'image_url' );

		$this->assertFalse( $result->is_valid() );
		$this->assertSame( 'Missing required key: image_url', $result->get_message() );
	}

	/**
	 * GIVEN invalid array structure factory method
	 * WHEN creating result
	 * THEN should create invalid result with structure message
	 */
	public function test_invalid_array_structure_creates_invalid_result(): void {
		$result = ValidationResult::invalid_array_structure();

		$this->assertFalse( $result->is_valid() );
		$this->assertSame( 'Invalid array structure', $result->get_message() );
	}

	/**
	 * GIVEN invalid format factory method
	 * WHEN creating result with or without context
	 * THEN should create invalid result with appropriate message
	 *
	 * @dataProvider invalid_format_provider
	 */
	public function test_invalid_format_creates_invalid_result( ?string $context, string $expected_message ): void {
		$result = ValidationResult::invalid_format( $context );

		$this->assertFalse( $result->is_valid() );
		$this->assertSame( $expected_message, $result->get_message() );
	}

	public function invalid_format_provider(): array {
		return [
			'with context'    => [ 'ISO 8601 date', 'Invalid format: ISO 8601 date' ],
			'without context' => [ null, 'Invalid format: unknown' ],
		];
	}

	/**
	 * GIVEN invalid value factory method
	 * WHEN creating result with or without context
	 * THEN should create invalid result with appropriate message
	 *
	 * @dataProvider invalid_value_provider
	 */
	public function test_invalid_value_creates_invalid_result( ?string $context, string $expected_message ): void {
		$result = ValidationResult::invalid_value( $context );

		$this->assertFalse( $result->is_valid() );
		$this->assertSame( $expected_message, $result->get_message() );
	}

	public function invalid_value_provider(): array {
		return [
			'with context'    => [
				'allowed: cart, checkout, my-account',
				'Invalid value: allowed: cart, checkout, my-account',
			],
			'without context' => [ null, 'Invalid value: unknown' ],
		];
	}

	// ===== is_valid() Tests =====

	/**
	 * GIVEN various validation results
	 * WHEN checking is_valid status
	 * THEN only valid code should return true
	 *
	 * @dataProvider validation_status_provider
	 */
	public function test_is_valid_returns_correct_status( ValidationResult $result, bool $expected ): void {
		$this->assertSame( $expected, $result->is_valid() );
	}

	public function validation_status_provider(): array {
		$executor = fn() => new \Whiskey\ExecutionResult( true, 'test' );

		return [
			'valid code'        => [ ValidationResult::valid( $executor ), true ],
			'invalid type'      => [ ValidationResult::invalid_type(), false ],
			'missing key'       => [ ValidationResult::missing_key( 'test' ), false ],
			'invalid structure' => [ ValidationResult::invalid_array_structure(), false ],
			'invalid format'    => [ ValidationResult::invalid_format(), false ],
			'invalid value'     => [ ValidationResult::invalid_value(), false ],
		];
	}

	// ===== ValidationCode Tests =====

	/**
	 * GIVEN validation code string
	 * WHEN checking if code is valid
	 * THEN should correctly identify valid vs invalid codes
	 *
	 * @dataProvider validation_code_provider
	 */
	public function test_validation_code_is_valid( string $code, bool $expected ): void {
		$this->assertSame( $expected, ValidationCode::is_valid( $code ) );
	}

	public function validation_code_provider(): array {
		return [
			'valid code'   => [ 'valid', true ],
			'invalid code' => [ 'invalid_type', false ],
		];
	}

	/**
	 * GIVEN undefined validation code
	 * WHEN getting message
	 * THEN should return unknown error message
	 */
	public function test_undefined_code_returns_unknown_message(): void {
		$this->assertSame( 'Unknown validation error', ValidationCode::get_message( '?' ) );
	}

	// ===== Object Identity Tests =====

	/**
	 * GIVEN factory methods called multiple times
	 * WHEN creating results with same parameters
	 * THEN should create distinct instances
	 */
	public function test_factory_methods_create_distinct_instances(): void {
		$executor = fn() => new \Whiskey\ExecutionResult( true, 'test' );

		$valid1 = ValidationResult::valid( $executor );
		$valid2 = ValidationResult::valid( $executor );

		$this->assertTrue( $valid1->is_valid() );
		$this->assertTrue( $valid2->is_valid() );
		$this->assertNotSame( $valid1, $valid2 );
	}

	// ===== Execution Tests =====

	/**
	 * GIVEN valid validation result with executor
	 * WHEN calling execute
	 * THEN should execute the callback and return result
	 */
	public function test_execute_runs_callback_and_returns_result(): void {
		$expected_result = new \Whiskey\ExecutionResult( true, 'executed successfully' );
		$result          = ValidationResult::valid( fn() => $expected_result );

		$execution_result = $result->execute();

		$this->assertSame( $expected_result, $execution_result );
	}

	/**
	 * GIVEN invalid validation result
	 * WHEN calling execute
	 * THEN should return error execution result
	 */
	public function test_execute_on_invalid_result_returns_error(): void {
		$result = ValidationResult::invalid_type( 'string' );

		$execution_result = $result->execute();

		$this->assertFalse( $execution_result->is_success() );
		$this->assertStringContainsString( 'Cannot execute invalid result', $execution_result->get_message() );
	}

	/**
	 * GIVEN valid validation result with parameterized executor
	 * WHEN calling execute with arguments
	 * THEN should forward arguments to callback
	 */
	public function test_execute_forwards_arguments_to_callback(): void {
		$result = ValidationResult::valid(
			fn( $arg1, $arg2 ) => new \Whiskey\ExecutionResult( true, "{$arg1} {$arg2}" )
		);

		$execution_result = $result->execute( 'hello', 'world' );

		$this->assertTrue( $execution_result->is_success() );
		$this->assertSame( 'hello world', $execution_result->get_message() );
	}
}
