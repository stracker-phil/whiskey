<?php
/**
 * @covers \Whiskey\Ingredients\PayPalBcdcOverrideIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\PayPalBcdcOverrideIngredient;
use WP_Functions;

class PayPalBcdcOverrideIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return PayPalBcdcOverrideIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'paypal_bcdc_override';
	}

	protected function getExpectedCategory(): string {
		return 'paypal';
	}

	// ===== Validation Tests =====

	/**
	 * GIVEN various input types
	 * WHEN validating
	 * THEN should accept only boolean or array values
	 *
	 * @dataProvider validation_provider
	 */
	public function test_validate( $input, bool $expected_valid ): void {
		$result = $this->ingredient->validate( $input );

		$this->assertSame( $expected_valid, $result->is_valid() );
	}

	public function validation_provider(): array {
		return [
			'boolean true'     => [ true, true ],
			'boolean false'    => [ false, true ],
			'empty array'      => [ [], true ],
			'simple array'     => [ [ 'key' => 'value' ], true ],
			'complex array'    => [
				[
					'enabled'  => true,
					'settings' => [ 'mode' => 'test' ],
					'version'  => 2,
				],
				true,
			],
			'string rejected'  => [ 'true', false ],
			'integer rejected' => [ 1, false ],
			'null rejected'    => [ null, false ],
			'object rejected'  => [ (object) [ 'key' => 'value' ], false ],
		];
	}

	// ===== Execution Tests: Boolean true =====

	/**
	 * GIVEN true value with various previous states
	 * WHEN executing
	 * THEN should enable flag and return appropriate state
	 *
	 * @dataProvider execute_true_provider
	 */
	public function test_execute_enables_flag_when_true(
		$previous_value,
		bool $update_return,
		bool $expected_current,
		$expected_previous,
		string $expected_message_fragment
	): void {
		WP_Functions::mock( 'get_option', $previous_value );
		WP_Functions::mock( 'update_option', $update_return );

		$validation_result = $this->ingredient->validate( true );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( $expected_message_fragment, $result->get_message() );

		$data = $result->get_data();
		$this->assertSame( $expected_current, $data['current'] );
		$this->assertSame( $expected_previous, $data['previous'] );
	}

	public function execute_true_provider(): array {
		return [
			'enable from null'  => [
				null,
				true,
				true,
				null,
				'enabled',
			],
			'enable from false' => [
				false,
				true,
				true,
				false,
				'enabled',
			],
			'already enabled'   => [
				true,
				false, // update_option returns false when unchanged
				true,
				true,
				'enabled',
			],
		];
	}

	/**
	 * GIVEN true value and update failure
	 * WHEN executing
	 * THEN should return failure
	 */
	public function test_execute_fails_when_enable_fails(): void {
		WP_Functions::mock( 'get_option', false );
		WP_Functions::mock( 'update_option', false );

		$validation_result = $this->ingredient->validate( true );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}

	// ===== Execution Tests: Boolean false =====

	/**
	 * GIVEN false value with various previous states
	 * WHEN executing
	 * THEN should delete flag and return appropriate state
	 *
	 * @dataProvider execute_false_provider
	 */
	public function test_execute_deletes_flag_when_false(
		$previous_value,
		bool $delete_return,
		$expected_previous,
		string $expected_message_fragment
	): void {
		WP_Functions::mock( 'get_option', $previous_value );
		WP_Functions::mock( 'delete_option', $delete_return );

		$validation_result = $this->ingredient->validate( false );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( $expected_message_fragment, $result->get_message() );

		$data = $result->get_data();
		$this->assertNull( $data['current'] );
		$this->assertSame( $expected_previous, $data['previous'] );
	}

	public function execute_false_provider(): array {
		return [
			'delete from true'  => [
				true,
				true,
				true,
				'deleted',
			],
			'delete from array' => [
				[ 'key' => 'value' ],
				true,
				[ 'key' => 'value' ],
				'deleted',
			],
			'already deleted'   => [
				null,
				false, // delete_option returns false when already absent
				null,
				'deleted',
			],
		];
	}

	/**
	 * GIVEN false value and delete failure
	 * WHEN executing
	 * THEN should return failure
	 */
	public function test_execute_fails_when_delete_fails(): void {
		WP_Functions::mock( 'get_option', true );
		WP_Functions::mock( 'delete_option', false );

		$validation_result = $this->ingredient->validate( false );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}

	// ===== Execution Tests: Array =====

	/**
	 * GIVEN array value with various previous states
	 * WHEN executing
	 * THEN should save array and return appropriate state
	 *
	 * @dataProvider execute_array_provider
	 */
	public function test_execute_saves_array_data(
		array $new_data,
		$previous_value,
		bool $update_return,
		array $expected_current,
		$expected_previous,
		string $expected_message_fragment
	): void {
		WP_Functions::mock( 'get_option', $previous_value );
		WP_Functions::mock( 'update_option', $update_return );

		$validation_result = $this->ingredient->validate( $new_data );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( $expected_message_fragment, $result->get_message() );

		$data = $result->get_data();
		$this->assertSame( $expected_current, $data['current'] );
		$this->assertSame( $expected_previous, $data['previous'] );
	}

	public function execute_array_provider(): array {
		return [
			'save new array'             => [
				[ 'mode' => 'sandbox', 'version' => 2 ],
				null,
				true,
				[ 'mode' => 'sandbox', 'version' => 2 ],
				null,
				'updated',
			],
			'update existing array'      => [
				[ 'new' => 'value' ],
				[ 'old' => 'value' ],
				true,
				[ 'new' => 'value' ],
				[ 'old' => 'value' ],
				'updated',
			],
			'replace boolean with array' => [
				[ 'migration' => 'complete' ],
				true,
				true,
				[ 'migration' => 'complete' ],
				true,
				'updated',
			],
			'array unchanged'            => [
				[ 'key' => 'value' ],
				[ 'key' => 'value' ],
				false, // update_option returns false when unchanged
				[ 'key' => 'value' ],
				[ 'key' => 'value' ],
				'updated',
			],
			'empty array replaces data'  => [
				[],
				[ 'old' => 'data' ],
				true,
				[],
				[ 'old' => 'data' ],
				'updated',
			],
		];
	}

	/**
	 * GIVEN array value and update failure
	 * WHEN executing
	 * THEN should return failure
	 */
	public function test_execute_fails_when_array_update_fails(): void {
		WP_Functions::mock( 'get_option', null );
		WP_Functions::mock( 'update_option', false );

		$validation_result = $this->ingredient->validate( [ 'key' => 'value' ] );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}
}
