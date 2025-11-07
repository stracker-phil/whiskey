<?php
/**
 * @covers \Whiskey\Ingredients\PayPalStartOverIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\PayPalStartOverIngredient;
use WP_Functions;

class PayPalStartOverIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return PayPalStartOverIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'paypal_start_over';
	}

	protected function getExpectedCategory(): string {
		return 'paypal';
	}

	// ===== Validation Tests =====

	/**
	 * Override base test - this ingredient accepts any value including null
	 */
	public function test_validate_rejects_null(): void {
		$this->assertValidationAccepts( null );
	}

	public function test_validate_accepts_true(): void {
		$this->assertValidationAccepts( true );
	}

	public function test_validate_accepts_false(): void {
		$this->assertValidationAccepts( false );
	}

	public function test_validate_accepts_empty_string(): void {
		$this->assertValidationAccepts( '' );
	}

	public function test_validate_accepts_string(): void {
		$this->assertValidationAccepts( 'any value' );
	}

	public function test_validate_accepts_array(): void {
		$this->assertValidationAccepts( [] );
	}

	public function test_validate_accepts_integer(): void {
		$this->assertValidationAccepts( 123 );
	}

	// ===== Execution Tests =====

	public function test_execute_deletes_woocommerce_ppcp_dash_options(): void {
		$this->mock_wpdb_query( [ 'woocommerce-ppcp-setting1', 'woocommerce-ppcp-setting2' ] );
		WP_Functions::mock( 'delete_option', true );

		$validation_result = $this->ingredient->validate( null );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'deleted', $data );
		$this->assertContains( 'woocommerce-ppcp-setting1', $data['deleted'] );
		$this->assertContains( 'woocommerce-ppcp-setting2', $data['deleted'] );
	}

	public function test_execute_deletes_woocommerce_ppcp_underscore_options(): void {
		$this->mock_wpdb_query( [ 'woocommerce_ppcp-gateway', 'woocommerce_ppcp-merchant' ] );
		WP_Functions::mock( 'delete_option', true );

		$validation_result = $this->ingredient->validate( null );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'deleted', $data );
		$this->assertContains( 'woocommerce_ppcp-gateway', $data['deleted'] );
		$this->assertContains( 'woocommerce_ppcp-merchant', $data['deleted'] );
	}

	public function test_execute_deletes_specific_options(): void {
		$this->mock_wpdb_query( [] ); // No prefix options
		$deleted_options = [];
		WP_Functions::mock( 'delete_option', function ( $option ) use ( &$deleted_options ) {
			$deleted_options[] = $option;
			return true;
		} );

		$validation_result = $this->ingredient->validate( null );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$this->assertContains( 'ppcp-settings', $deleted_options );
		$this->assertContains( 'woocommerce_payments_nox_profile', $deleted_options );
		$this->assertContains( 'ppcp-webhook-simulation', $deleted_options );
		$this->assertContains( 'ppcp-webhook', $deleted_options );
	}

	public function test_execute_deletes_all_paypal_options(): void {
		$this->mock_wpdb_query( [
			'woocommerce-ppcp-setting1',
			'woocommerce_ppcp-setting2',
		] );
		WP_Functions::mock( 'delete_option', true );

		$validation_result = $this->ingredient->validate( null );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'count', $data );
		// 2 options × 2 prefix queries + 4 specific options = 8 total
		$this->assertSame( 8, $data['count'] );
	}

	public function test_execute_succeeds_when_no_options_found(): void {
		$this->mock_wpdb_query( [] );
		WP_Functions::mock( 'delete_option', false );

		$validation_result = $this->ingredient->validate( null );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$this->assertStringContainsString( 'No PayPal settings found', $result->get_message() );
		$data = $result->get_data();
		$this->assertSame( [], $data['deleted'] );
	}

	public function test_execute_handles_partial_deletion(): void {
		$this->mock_wpdb_query( [ 'woocommerce-ppcp-setting1', 'woocommerce-ppcp-setting2' ] );
		$call_count = 0;
		WP_Functions::mock( 'delete_option', function ( $option ) use ( &$call_count ) {
			$call_count++;
			// First call succeeds, others fail
			return $call_count === 1;
		} );

		$validation_result = $this->ingredient->validate( null );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'deleted', $data );
		// Only the successfully deleted option should be in the list
		$this->assertSame( 1, $data['count'] );
	}

	public function test_execute_returns_deleted_option_names(): void {
		$this->mock_wpdb_query( [ 'woocommerce-ppcp-test' ] );
		WP_Functions::mock( 'delete_option', true );

		$validation_result = $this->ingredient->validate( null );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'deleted', $data );
		$this->assertIsArray( $data['deleted'] );
		$this->assertContains( 'woocommerce-ppcp-test', $data['deleted'] );
		$this->assertContains( 'ppcp-settings', $data['deleted'] );
	}

	public function test_execute_works_with_any_input_value(): void {
		$this->mock_wpdb_query( [ 'woocommerce-ppcp-test' ] );
		WP_Functions::mock( 'delete_option', true );

		// Test with different input values - should all work the same
		$test_values = [ null, true, false, 'anything', 123, [] ];

		foreach ( $test_values as $value ) {
			$validation_result = $this->ingredient->validate( $value );
			$result = $validation_result->execute();
			$this->assertExecutionSuccess( $result );
		}
	}

	// ===== Helper Methods =====

	/**
	 * Mock the wpdb query for getting options by prefix.
	 *
	 * @param array $options List of option names to return.
	 */
	private function mock_wpdb_query( array $options ): void {
		global $wpdb;

		// Create a mock wpdb object
		$wpdb = new class( $options ) {
			public string $options = 'wp_options';
			private array $mock_results;

			public function __construct( array $mock_results ) {
				$this->mock_results = $mock_results;
			}

			public function prepare( string $query, ...$args ): string {
				return $query;
			}

			public function esc_like( string $text ): string {
				return $text;
			}

			public function get_col( string $query ): array {
				return $this->mock_results;
			}
		};
	}
}
