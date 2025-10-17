<?php
/**
 * @covers \Whiskey\Ingredients\SetWooCountryIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\SetWooCountryIngredient;
use WP_Functions;

class SetWooCountryIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return SetWooCountryIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'set_woo_country';
	}

	protected function getExpectedCategory(): string {
		return 'woocommerce';
	}

	// Validation tests for string format

	public function test_validate_accepts_country_with_state(): void {
		$this->assertValidationAccepts( 'US:CA' );
	}

	public function test_validate_accepts_country_only(): void {
		$this->assertValidationAccepts( 'AT' );
	}

	public function test_validate_accepts_lowercase_country_with_state(): void {
		$this->assertValidationAccepts( 'us:ca' );
	}

	public function test_validate_accepts_country_only_lowercase(): void {
		$this->assertValidationAccepts( 'de' );
	}

	public function test_validate_rejects_string_with_one_letter(): void {
		$this->assertValidationRejects( 'U' );
	}

	public function test_validate_rejects_string_with_three_letters(): void {
		$this->assertValidationRejects( 'USA' );
	}

	public function test_validate_rejects_invalid_format(): void {
		$this->assertValidationRejects( 'US-CA' );
	}

	public function test_validate_rejects_string_with_only_colon(): void {
		$this->assertValidationRejects( ':' );
	}

	public function test_validate_rejects_string_with_incomplete_state(): void {
		$this->assertValidationRejects( 'US:C' );
	}

	// Validation tests for array format

	public function test_validate_accepts_array_with_two_elements(): void {
		$this->assertValidationAccepts( [ 'US', 'CA' ] );
	}

	public function test_validate_accepts_array_with_lowercase(): void {
		$this->assertValidationAccepts( [ 'us', 'ca' ] );
	}

	public function test_validate_rejects_array_with_one_element(): void {
		$this->assertValidationRejects( [ 'US' ] );
	}

	public function test_validate_rejects_array_with_three_elements(): void {
		$this->assertValidationRejects( [ 'US', 'CA', 'LA' ] );
	}

	public function test_validate_rejects_array_with_invalid_country(): void {
		$this->assertValidationRejects( [ 'USA', 'CA' ] );
	}

	public function test_validate_rejects_array_with_invalid_state(): void {
		$this->assertValidationRejects( [ 'US', 'C' ] );
	}

	public function test_validate_rejects_array_with_integers(): void {
		$this->assertValidationRejects( [ 12, 34 ] );
	}

	// Validation tests for other types

	public function test_validate_rejects_integer(): void {
		$this->assertValidationRejects( 123 );
	}

	public function test_validate_rejects_boolean(): void {
		$this->assertValidationRejects( true );
	}

	public function test_validate_rejects_empty_string(): void {
		$this->assertValidationRejects( '' );
	}

	public function test_validate_rejects_empty_array(): void {
		$this->assertValidationRejects( [] );
	}

	// Execution tests with string format

	public function test_execute_updates_country_with_state_string(): void {
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( 'US:CA' );

		$this->assertExecutionSuccess( $result, 'US:CA' );
		$this->assertSame( '', $result->get_data()['previous'] );
		$this->assertSame( 'US:CA', $result->get_data()['current'] );
	}

	public function test_execute_updates_country_only_string(): void {
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( 'AT' );

		$this->assertExecutionSuccess( $result, 'AT' );
		$this->assertSame( 'AT', $result->get_data()['current'] );
	}

	// Execution tests with array format

	public function test_execute_updates_country_with_state_array(): void {
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( [ 'US', 'CA' ] );

		$this->assertExecutionSuccess( $result, 'US:CA' );
		$this->assertSame( 'US:CA', $result->get_data()['current'] );
	}

	public function test_execute_converts_array_to_string_format(): void {
		WP_Functions::mock( 'get_option', 'DE' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( [ 'GB', 'LN' ] );

		$this->assertExecutionSuccess( $result );
		$this->assertSame( 'GB:LN', $result->get_data()['current'] );
	}

	// Execution error handling

	public function test_execute_fails_when_update_fails(): void {
		WP_Functions::mock( 'get_option', 'US:NY' );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( 'US:CA' );

		$this->assertExecutionFailure( $result, 'Failed to update' );
		$this->assertSame( 'US:NY', $result->get_data()['previous'] );
		$this->assertSame( 'US:CA', $result->get_data()['requested'] );
	}

	public function test_execute_succeeds_when_value_unchanged(): void {
		WP_Functions::mock( 'get_option', 'US:CA' );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( 'US:CA' );

		$this->assertExecutionSuccess( $result );
	}

	public function test_execute_returns_previous_and_current_values(): void {
		WP_Functions::mock( 'get_option', 'DE' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( 'AT' );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'previous', $data );
		$this->assertArrayHasKey( 'current', $data );
		$this->assertSame( 'DE', $data['previous'] );
		$this->assertSame( 'AT', $data['current'] );
	}
}
