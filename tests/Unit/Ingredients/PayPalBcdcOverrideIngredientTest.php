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

	public function test_validate_accepts_true(): void {
		$this->assertValidationAccepts( true );
	}

	public function test_validate_accepts_false(): void {
		$this->assertValidationAccepts( false );
	}

	public function test_validate_accepts_empty_array(): void {
		$this->assertValidationAccepts( [] );
	}

	public function test_validate_accepts_array_with_data(): void {
		$this->assertValidationAccepts( [ 'key' => 'value' ] );
	}

	public function test_validate_accepts_complex_array(): void {
		$this->assertValidationAccepts( [
			'enabled'  => true,
			'settings' => [ 'mode' => 'test' ],
			'version'  => 2,
		] );
	}

	public function test_validate_rejects_string(): void {
		$this->assertValidationRejects( 'true' );
	}

	public function test_validate_rejects_integer(): void {
		$this->assertValidationRejects( 1 );
	}

	public function test_validate_rejects_null(): void {
		$this->assertValidationRejects( null );
	}

	public function test_validate_rejects_object(): void {
		$this->assertValidationRejects( (object) [ 'key' => 'value' ] );
	}

	// ===== Execution Tests: Boolean true =====

	public function test_execute_enables_flag_when_true(): void {
		WP_Functions::mock( 'get_option', null );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( 'enabled', $result->get_message() );
		$data = $result->get_data();
		$this->assertTrue( $data['current'] );
		$this->assertNull( $data['previous'] );
	}

	public function test_execute_enables_flag_when_previously_false(): void {
		WP_Functions::mock( 'get_option', false );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertTrue( $data['current'] );
		$this->assertFalse( $data['previous'] );
	}

	public function test_execute_succeeds_when_already_enabled(): void {
		WP_Functions::mock( 'get_option', true );
		WP_Functions::mock( 'update_option', false ); // Returns false when unchanged

		$result = $this->ingredient->execute( true );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertTrue( $data['current'] );
		$this->assertTrue( $data['previous'] );
	}

	public function test_execute_fails_when_enable_fails(): void {
		WP_Functions::mock( 'get_option', false );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( true );

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}

	// ===== Execution Tests: Boolean false =====

	public function test_execute_deletes_flag_when_false(): void {
		WP_Functions::mock( 'get_option', true );
		WP_Functions::mock( 'delete_option', true );

		$result = $this->ingredient->execute( false );

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( 'deleted', $result->get_message() );
		$data = $result->get_data();
		$this->assertNull( $data['current'] );
		$this->assertTrue( $data['previous'] );
	}

	public function test_execute_deletes_when_previously_array(): void {
		WP_Functions::mock( 'get_option', [ 'key' => 'value' ] );
		WP_Functions::mock( 'delete_option', true );

		$result = $this->ingredient->execute( false );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertNull( $data['current'] );
		$this->assertIsArray( $data['previous'] );
	}

	public function test_execute_succeeds_when_already_deleted(): void {
		WP_Functions::mock( 'get_option', null );
		WP_Functions::mock( 'delete_option', false ); // Returns false when already absent

		$result = $this->ingredient->execute( false );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertNull( $data['current'] );
		$this->assertNull( $data['previous'] );
	}

	public function test_execute_fails_when_delete_fails(): void {
		WP_Functions::mock( 'get_option', true );
		WP_Functions::mock( 'delete_option', false );

		$result = $this->ingredient->execute( false );

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}

	// ===== Execution Tests: Array =====

	public function test_execute_saves_array_data(): void {
		WP_Functions::mock( 'get_option', null );
		WP_Functions::mock( 'update_option', true );

		$data_to_save = [ 'mode' => 'sandbox', 'version' => 2 ];
		$result       = $this->ingredient->execute( $data_to_save );

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( 'updated', $result->get_message() );
		$data = $result->get_data();
		$this->assertSame( $data_to_save, $data['current'] );
		$this->assertNull( $data['previous'] );
	}

	public function test_execute_updates_existing_array(): void {
		$old_data = [ 'old' => 'value' ];
		$new_data = [ 'new' => 'value' ];

		WP_Functions::mock( 'get_option', $old_data );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( $new_data );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( $new_data, $data['current'] );
		$this->assertSame( $old_data, $data['previous'] );
	}

	public function test_execute_replaces_boolean_with_array(): void {
		WP_Functions::mock( 'get_option', true );
		WP_Functions::mock( 'update_option', true );

		$array_data = [ 'migration' => 'complete' ];
		$result     = $this->ingredient->execute( $array_data );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( $array_data, $data['current'] );
		$this->assertTrue( $data['previous'] );
	}

	public function test_execute_succeeds_when_array_unchanged(): void {
		$data = [ 'key' => 'value' ];

		WP_Functions::mock( 'get_option', $data );
		WP_Functions::mock( 'update_option', false ); // Returns false when unchanged

		$result = $this->ingredient->execute( $data );

		$this->assertExecutionSuccess( $result );
		$result_data = $result->get_data();
		$this->assertSame( $data, $result_data['current'] );
		$this->assertSame( $data, $result_data['previous'] );
	}

	public function test_execute_fails_when_array_update_fails(): void {
		WP_Functions::mock( 'get_option', null );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( [ 'key' => 'value' ] );

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}

	public function test_execute_handles_empty_array(): void {
		WP_Functions::mock( 'get_option', [ 'old' => 'data' ] );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( [] );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( [], $data['current'] );
	}
}
