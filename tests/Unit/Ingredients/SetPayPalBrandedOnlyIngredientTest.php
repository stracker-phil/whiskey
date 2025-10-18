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

	public function test_validate_accepts_true(): void {
		$this->assertValidationAccepts( true );
	}

	public function test_validate_accepts_false(): void {
		$this->assertValidationAccepts( false );
	}

	public function test_validate_rejects_string(): void {
		$this->assertValidationRejects( 'true' );
	}

	public function test_validate_rejects_integer(): void {
		$this->assertValidationRejects( 1 );
	}

	public function test_validate_rejects_integer_zero(): void {
		$this->assertValidationRejects( 0 );
	}

	public function test_validate_rejects_array(): void {
		$this->assertValidationRejects( [ true ] );
	}

	public function test_validate_rejects_null(): void {
		$this->assertValidationRejects( null );
	}

	// ===== Execution Tests - Branded Mode =====

	public function test_execute_enables_branded_only_mode(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( 'branded-only', $result->get_message() );
		$this->assertStringContainsString( 'core-profiler', $result->get_message() );
	}

	public function test_execute_branded_mode_deletes_nox_profile(): void {
		$deleted_options = [];
		WP_Functions::mock( 'delete_option', function ( $option ) use ( &$deleted_options ) {
			$deleted_options[] = $option;
			return true;
		} );
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', true );

		$this->ingredient->execute( true );

		$this->assertContains( 'woocommerce_payments_nox_profile', $deleted_options );
	}

	public function test_execute_branded_mode_sets_core_profiler_path(): void {
		$updated_data = null;
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$updated_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$updated_data = $value;
			}
			return true;
		} );

		$this->ingredient->execute( true );

		$this->assertIsArray( $updated_data );
		$this->assertArrayHasKey( 'wc_installation_path', $updated_data );
		$this->assertSame( 'core-profiler', $updated_data['wc_installation_path'] );
	}

	public function test_execute_branded_mode_preserves_existing_data(): void {
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
	}

	public function test_execute_branded_mode_returns_previous_path(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [ 'wc_installation_path' => 'direct' ] );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( true );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'previous_path', $data );
		$this->assertSame( 'direct', $data['previous_path'] );
	}

	// ===== Execution Tests - White-Label Mode =====

	public function test_execute_enables_white_label_mode(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( false );

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( 'white-label', $result->get_message() );
		$this->assertStringContainsString( 'direct', $result->get_message() );
	}

	public function test_execute_white_label_mode_deletes_nox_profile(): void {
		$deleted_options = [];
		WP_Functions::mock( 'delete_option', function ( $option ) use ( &$deleted_options ) {
			$deleted_options[] = $option;
			return true;
		} );
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', true );

		$this->ingredient->execute( false );

		$this->assertContains( 'woocommerce_payments_nox_profile', $deleted_options );
	}

	public function test_execute_white_label_mode_sets_direct_path(): void {
		$updated_data = null;
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$updated_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$updated_data = $value;
			}
			return true;
		} );

		$this->ingredient->execute( false );

		$this->assertIsArray( $updated_data );
		$this->assertArrayHasKey( 'wc_installation_path', $updated_data );
		$this->assertSame( 'direct', $updated_data['wc_installation_path'] );
	}

	// ===== Edge Cases =====

	public function test_execute_handles_non_array_option_value(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', 'not-an-array' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionSuccess( $result );
	}

	public function test_execute_handles_missing_option(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertNull( $data['previous_path'] );
	}

	public function test_execute_succeeds_when_value_unchanged(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [ 'wc_installation_path' => 'core-profiler' ] );
		WP_Functions::mock( 'update_option', false ); // Returns false when unchanged

		$result = $this->ingredient->execute( true );

		$this->assertExecutionSuccess( $result );
	}

	public function test_execute_fails_when_update_fails(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'get_option', [ 'wc_installation_path' => 'direct' ] );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}

	public function test_execute_returns_all_data_fields(): void {
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
