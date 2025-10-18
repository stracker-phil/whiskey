<?php
/**
 * @covers \Whiskey\Ingredients\SetPayPalLegacyModeIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\SetPayPalLegacyModeIngredient;
use WP_Functions;

class SetPayPalLegacyModeIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return SetPayPalLegacyModeIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'set_paypal_legacy_mode';
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

	// ===== Execution Tests - Legacy Mode =====

	public function test_execute_enables_legacy_mode(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( 'legacy UI', $result->get_message() );
	}

	public function test_execute_legacy_mode_deletes_new_merchant_flag(): void {
		$deleted_options = [];
		WP_Functions::mock( 'delete_option', function ( $option ) use ( &$deleted_options ) {
			$deleted_options[] = $option;
			return true;
		} );
		WP_Functions::mock( 'update_option', true );

		$this->ingredient->execute( true );

		$this->assertContains( 'woocommerce-ppcp-is-new-merchant', $deleted_options );
	}

	public function test_execute_legacy_mode_enables_old_ui(): void {
		$updated_options = [];
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$updated_options ) {
			$updated_options[ $option ] = $value;
			return true;
		} );

		$this->ingredient->execute( true );

		$this->assertArrayHasKey( 'woocommerce_ppcp-settings-should-use-old-ui', $updated_options );
		$this->assertSame( 'yes', $updated_options['woocommerce_ppcp-settings-should-use-old-ui'] );
	}

	public function test_execute_legacy_mode_returns_changes_in_data(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( true );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'deleted_new_merchant', $data );
		$this->assertArrayHasKey( 'enabled_old_ui', $data );
	}

	public function test_execute_legacy_mode_fails_when_update_fails(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}

	// ===== Execution Tests - Modern Mode =====

	public function test_execute_enables_modern_mode(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( false );

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( 'modern UI', $result->get_message() );
	}

	public function test_execute_modern_mode_sets_new_merchant_flag(): void {
		$updated_options = [];
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$updated_options ) {
			$updated_options[ $option ] = $value;
			return true;
		} );

		$this->ingredient->execute( false );

		$this->assertArrayHasKey( 'woocommerce-ppcp-is-new-merchant', $updated_options );
		$this->assertSame( '1', $updated_options['woocommerce-ppcp-is-new-merchant'] );
	}

	public function test_execute_modern_mode_deletes_old_ui_option(): void {
		$deleted_options = [];
		WP_Functions::mock( 'delete_option', function ( $option ) use ( &$deleted_options ) {
			$deleted_options[] = $option;
			return true;
		} );
		WP_Functions::mock( 'update_option', true );

		$this->ingredient->execute( false );

		$this->assertContains( 'woocommerce_ppcp-settings-should-use-old-ui', $deleted_options );
	}

	public function test_execute_modern_mode_returns_changes_in_data(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( false );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'enabled_new_merchant', $data );
		$this->assertArrayHasKey( 'deleted_old_ui', $data );
	}

	public function test_execute_modern_mode_fails_when_update_fails(): void {
		WP_Functions::mock( 'delete_option', true );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( false );

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}
}
