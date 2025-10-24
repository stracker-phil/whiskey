<?php
/**
 * @covers \Whiskey\Ingredients\SetPayPalBrandedOnlyIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\SetPayPalBrandedOnlyIngredient;
use WP_Functions;

class SetPayPalBrandedOnlyIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return SetPayPalBrandedOnlyIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'set_paypal_branded_only';
	}

	protected function getExpectedCategory(): string {
		return 'paypal';
	}

	// ===== Validation Tests =====

	/**
	 * GIVEN various input types
	 * WHEN validating
	 * THEN should accept only boolean values
	 *
	 * @dataProvider validation_provider
	 */
	public function test_validate( $input, bool $expected_valid ): void {
		$result = $this->ingredient->validate( $input );

		$this->assertSame( $expected_valid, $result->is_valid() );
	}

	public function validation_provider(): array {
		return [
			'true accepted'      => [ true, true ],
			'false accepted'     => [ false, true ],
			'string rejected'    => [ 'true', false ],
			'integer 1 rejected' => [ 1, false ],
			'integer 0 rejected' => [ 0, false ],
			'array rejected'     => [ [ true ], false ],
			'null rejected'      => [ null, false ],
		];
	}

	// ===== Execution Tests: Mode Setting =====

	/**
	 * GIVEN boolean value for mode
	 * WHEN executing
	 * THEN should set appropriate installation path and delete nox profile
	 *
	 * @dataProvider execute_mode_provider
	 */
	public function test_execute_sets_mode(
		bool $branded_mode,
		string $expected_path,
		array $expected_message_fragments
	): void {
		$deleted_options = [];
		$updated_data    = null;

		WP_Functions::mock( 'delete_option', function ( $option ) use ( &$deleted_options ) {
			$deleted_options[] = $option;

			return true;
		} );
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$updated_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$updated_data = $value;
			}

			return true;
		} );

		$result = $this->ingredient->execute( $branded_mode );

		$this->assertExecutionSuccess( $result );

		// Verify message content
		foreach ( $expected_message_fragments as $fragment ) {
			$this->assertStringContainsString( $fragment, $result->get_message() );
		}

		// Verify nox profile deleted
		$this->assertContains( 'woocommerce_payments_nox_profile', $deleted_options );

		// Verify path set correctly
		$this->assertIsArray( $updated_data );
		$this->assertArrayHasKey( 'wc_installation_path', $updated_data );
		$this->assertSame( $expected_path, $updated_data['wc_installation_path'] );
	}

	public function execute_mode_provider(): array {
		return [
			'branded mode'     => [
				true,
				'core-profiler',
				[ 'branded-only', 'core-profiler' ],
			],
			'white-label mode' => [
				false,
				'direct',
				[ 'white-label', 'direct' ],
			],
		];
	}

	/**
	 * GIVEN existing data in option
	 * WHEN executing
	 * THEN should preserve existing data while updating path
	 */
	public function test_execute_preserves_existing_data(): void {
		$existing_data = [
			'some_key'   => 'some_value',
			'other_data' => [ 'nested' => 'value' ],
		];

		$updated_data = null;
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', $existing_data );
		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$updated_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$updated_data = $value;
			}

			return true;
		} );

		$this->ingredient->execute( true );

		$this->assertArrayHasKey( 'some_key', $updated_data );
		$this->assertArrayHasKey( 'other_data', $updated_data );
		$this->assertSame( 'some_value', $updated_data['some_key'] );
		$this->assertArrayHasKey( 'wc_installation_path', $updated_data );
	}

	/**
	 * GIVEN various previous path states
	 * WHEN executing
	 * THEN should return previous path correctly
	 *
	 * @dataProvider previous_path_provider
	 */
	public function test_execute_returns_previous_path(
		$option_value,
		$expected_previous_path
	): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', $option_value );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'previous_path', $data );
		$this->assertSame( $expected_previous_path, $data['previous_path'] );
	}

	public function previous_path_provider(): array {
		return [
			'with previous path' => [
				[ 'wc_installation_path' => 'direct' ],
				'direct',
			],
			'empty option'       => [
				[],
				null,
			],
			'non-array option'   => [
				'not-an-array',
				null,
			],
		];
	}

	/**
	 * GIVEN path value that matches current
	 * WHEN executing
	 * THEN should succeed even when update returns false
	 */
	public function test_execute_succeeds_when_value_unchanged(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [ 'wc_installation_path' => 'core-profiler' ] );
		WP_Functions::mock( 'update_option', false ); // Returns false when unchanged

		$result = $this->ingredient->execute( true );

		$this->assertExecutionSuccess( $result );
	}

	/**
	 * GIVEN path value that differs but update fails
	 * WHEN executing
	 * THEN should return failure
	 */
	public function test_execute_fails_when_update_fails(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [ 'wc_installation_path' => 'direct' ] );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}

	/**
	 * GIVEN successful execution
	 * WHEN getting result data
	 * THEN should return all expected fields
	 */
	public function test_execute_returns_complete_data(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [ 'wc_installation_path' => 'direct' ] );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( true );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'deleted_nox_profile', $data );
		$this->assertArrayHasKey( 'previous_path', $data );
		$this->assertArrayHasKey( 'current_path', $data );
	}
}
