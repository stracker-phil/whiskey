<?php
/**
 * @covers \Whiskey\Ingredients\PayPalSetPreviousVersionIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\PayPalSetPreviousVersionIngredient;
use WP_Functions;

class PayPalSetPreviousVersionIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return PayPalSetPreviousVersionIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'paypal_set_previous_version';
	}

	protected function getExpectedCategory(): string {
		return 'paypal';
	}

	// ===== Validation Tests =====

	public function test_validate_accepts_semantic_version(): void {
		$this->assertValidationAccepts( '2.0.0' );
	}

	public function test_validate_accepts_version_with_patch(): void {
		$this->assertValidationAccepts( '1.2.3' );
	}

	public function test_validate_accepts_version_with_prerelease(): void {
		$this->assertValidationAccepts( '2.5.0-beta' );
	}

	public function test_validate_accepts_version_with_build_metadata(): void {
		$this->assertValidationAccepts( '1.0.0+20130313144700' );
	}

	public function test_validate_accepts_complex_version(): void {
		$this->assertValidationAccepts( '1.0.0-alpha.1+build.123' );
	}

	public function test_validate_accepts_empty_string(): void {
		$this->assertValidationAccepts( '' );
	}

	public function test_validate_rejects_major_minor_only(): void {
		$this->assertValidationRejects( '2.0' );
	}

	public function test_validate_rejects_major_only(): void {
		$this->assertValidationRejects( '2' );
	}

	public function test_validate_rejects_non_numeric_version(): void {
		$this->assertValidationRejects( 'abc.def.ghi' );
	}

	public function test_validate_rejects_version_with_v_prefix(): void {
		$this->assertValidationRejects( 'v2.0.0' );
	}

	public function test_validate_rejects_integer(): void {
		$this->assertValidationRejects( 200 );
	}

	public function test_validate_rejects_float(): void {
		$this->assertValidationRejects( 2.0 );
	}

	public function test_validate_rejects_array(): void {
		$this->assertValidationRejects( [ '2.0.0' ] );
	}

	public function test_validate_rejects_boolean(): void {
		$this->assertValidationRejects( true );
	}

	public function test_validate_rejects_null(): void {
		$this->assertValidationRejects( null );
	}

	// ===== Execution Tests =====

	public function test_execute_sets_version(): void {
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( '2.0.0' );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( '2.0.0', $data['current'] );
		$this->assertSame( '', $data['previous'] );
		$this->assertSame( 'woocommerce-ppcp-version', $data['option'] );
	}

	public function test_execute_updates_existing_version(): void {
		WP_Functions::mock( 'get_option', '1.5.0' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( '2.0.0' );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( '2.0.0', $data['current'] );
		$this->assertSame( '1.5.0', $data['previous'] );
	}

	public function test_execute_deletes_option_on_empty_string(): void {
		WP_Functions::mock( 'get_option', '2.0.0' );
		WP_Functions::mock( 'delete_option', true );

		$result = $this->ingredient->execute( '' );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( 'deleted', $data['action'] );
		$this->assertSame( '2.0.0', $data['previous'] );
		$this->assertSame( 'woocommerce-ppcp-version', $data['option'] );
	}

	public function test_execute_succeeds_when_deleting_already_empty(): void {
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'delete_option', false ); // Returns false when option doesn't exist

		$result = $this->ingredient->execute( '' );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( 'deleted', $data['action'] );
		$this->assertSame( '', $data['previous'] );
	}

	public function test_execute_fails_when_update_fails(): void {
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( '2.0.0' );

		$this->assertExecutionFailure( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'requested', $data );
		$this->assertSame( '2.0.0', $data['requested'] );
		$this->assertSame( 'woocommerce-ppcp-version', $data['option'] );
	}

	public function test_execute_fails_when_delete_fails(): void {
		WP_Functions::mock( 'get_option', '2.0.0' );
		WP_Functions::mock( 'delete_option', false );

		$result = $this->ingredient->execute( '' );

		$this->assertExecutionFailure( $result );
		$data = $result->get_data();
		$this->assertSame( '2.0.0', $data['previous'] );
		$this->assertSame( 'woocommerce-ppcp-version', $data['option'] );
	}

	public function test_execute_succeeds_when_value_unchanged(): void {
		WP_Functions::mock( 'get_option', '2.0.0' );
		WP_Functions::mock( 'update_option', false ); // Returns false when value unchanged

		$result = $this->ingredient->execute( '2.0.0' );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( '2.0.0', $data['current'] );
		$this->assertSame( '2.0.0', $data['previous'] );
	}

	public function test_execute_handles_prerelease_version(): void {
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( '2.0.0-beta.1' );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( '2.0.0-beta.1', $data['current'] );
	}

	public function test_execute_handles_build_metadata(): void {
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( '1.0.0+20130313' );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( '1.0.0+20130313', $data['current'] );
	}
}
