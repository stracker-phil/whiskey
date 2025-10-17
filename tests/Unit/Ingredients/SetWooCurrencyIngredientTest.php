<?php
/**
 * @covers \Whiskey\Ingredients\SetWooCurrencyIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\SetWooCurrencyIngredient;
use WP_Functions;

class SetWooCurrencyIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return SetWooCurrencyIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'set_woo_currency';
	}

	protected function getExpectedCategory(): string {
		return 'woocommerce';
	}

	// Validation tests

	public function test_validate_accepts_usd(): void {
		$this->assertValidationAccepts( 'USD' );
	}

	public function test_validate_accepts_eur(): void {
		$this->assertValidationAccepts( 'EUR' );
	}

	public function test_validate_accepts_gbp(): void {
		$this->assertValidationAccepts( 'GBP' );
	}

	public function test_validate_accepts_jpy(): void {
		$this->assertValidationAccepts( 'JPY' );
	}

	public function test_validate_rejects_lowercase(): void {
		$this->assertValidationRejects( 'usd' );
	}

	public function test_validate_rejects_mixed_case(): void {
		$this->assertValidationRejects( 'Usd' );
	}

	public function test_validate_rejects_two_letters(): void {
		$this->assertValidationRejects( 'US' );
	}

	public function test_validate_rejects_four_letters(): void {
		$this->assertValidationRejects( 'USDD' );
	}

	public function test_validate_rejects_numbers(): void {
		$this->assertValidationRejects( '123' );
	}

	public function test_validate_rejects_mixed_alphanumeric(): void {
		$this->assertValidationRejects( 'US1' );
	}

	public function test_validate_rejects_integer(): void {
		$this->assertValidationRejects( 123 );
	}

	public function test_validate_rejects_array(): void {
		$this->assertValidationRejects( [ 'USD' ] );
	}

	public function test_validate_rejects_boolean(): void {
		$this->assertValidationRejects( true );
	}

	public function test_validate_rejects_empty_string(): void {
		$this->assertValidationRejects( '' );
	}

	public function test_validate_rejects_string_with_spaces(): void {
		$this->assertValidationRejects( 'U S' );
	}

	public function test_validate_rejects_special_characters(): void {
		$this->assertValidationRejects( 'US$' );
	}

	// Execution tests

	public function test_execute_updates_currency_to_usd(): void {
		WP_Functions::mock( 'get_option', 'GBP' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( 'USD' );

		$this->assertExecutionSuccess( $result, 'USD' );
		$this->assertSame( 'GBP', $result->get_data()['previous'] );
		$this->assertSame( 'USD', $result->get_data()['current'] );
	}

	public function test_execute_updates_currency_to_eur(): void {
		WP_Functions::mock( 'get_option', 'USD' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( 'EUR' );

		$this->assertExecutionSuccess( $result, 'EUR' );
		$this->assertSame( 'EUR', $result->get_data()['current'] );
	}

	public function test_execute_updates_from_empty_currency(): void {
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( 'JPY' );

		$this->assertExecutionSuccess( $result );
		$this->assertSame( '', $result->get_data()['previous'] );
		$this->assertSame( 'JPY', $result->get_data()['current'] );
	}

	public function test_execute_fails_when_update_fails(): void {
		WP_Functions::mock( 'get_option', 'USD' );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( 'EUR' );

		$this->assertExecutionFailure( $result, 'Failed to update' );
		$this->assertSame( 'USD', $result->get_data()['previous'] );
		$this->assertSame( 'EUR', $result->get_data()['requested'] );
	}

	public function test_execute_succeeds_when_currency_unchanged(): void {
		WP_Functions::mock( 'get_option', 'USD' );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( 'USD' );

		$this->assertExecutionSuccess( $result );
	}

	public function test_execute_returns_previous_and_current_values(): void {
		WP_Functions::mock( 'get_option', 'GBP' );
		WP_Functions::mock( 'update_option', true );

		$result = $this->ingredient->execute( 'EUR' );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'previous', $data );
		$this->assertArrayHasKey( 'current', $data );
		$this->assertSame( 'GBP', $data['previous'] );
		$this->assertSame( 'EUR', $data['current'] );
	}

	public function test_execute_handles_various_currencies(): void {
		$currencies = [ 'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNY' ];

		foreach ( $currencies as $currency ) {
			WP_Functions::mock( 'get_option', '' );
			WP_Functions::mock( 'update_option', true );

			$result = $this->ingredient->execute( $currency );

			$this->assertTrue( $result->is_success(), "Failed for currency: $currency" );
			$this->assertSame( $currency, $result->get_data()['current'] );
		}
	}
}
