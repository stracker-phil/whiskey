<?php
/**
 * @covers \Whiskey\Ingredients\SetWooStorePagesIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\SetWooStorePagesIngredient;
use WP_Functions;

class SetWooStorePagesIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return SetWooStorePagesIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'set_woo_store_pages';
	}

	protected function getExpectedCategory(): string {
		return 'woocommerce';
	}

	// ===== Validation Tests =====

	public function test_validate_accepts_cart_only(): void {
		$this->assertValidationAccepts( [ 'cart' => 'block-cart' ] );
	}

	public function test_validate_accepts_checkout_only(): void {
		$this->assertValidationAccepts( [ 'checkout' => 'block-checkout' ] );
	}

	public function test_validate_accepts_my_account_only(): void {
		$this->assertValidationAccepts( [ 'my-account' => 'my-account' ] );
	}

	public function test_validate_accepts_all_pages(): void {
		$this->assertValidationAccepts( [
			'cart'       => 'block-cart',
			'checkout'   => 'block-checkout',
			'my-account' => 'my-account',
		] );
	}

	public function test_validate_accepts_cart_and_checkout(): void {
		$this->assertValidationAccepts( [
			'cart'     => 'classic-cart',
			'checkout' => 'classic-checkout',
		] );
	}

	public function test_validate_rejects_empty_array(): void {
		$this->assertValidationRejects( [] );
	}

	public function test_validate_rejects_string(): void {
		$this->assertValidationRejects( 'block-cart' );
	}

	public function test_validate_rejects_invalid_key(): void {
		$this->assertValidationRejects( [ 'invalid' => 'block-cart' ] );
	}

	public function test_validate_rejects_non_string_value(): void {
		$this->assertValidationRejects( [ 'cart' => 123 ] );
	}

	public function test_validate_rejects_array_value(): void {
		$this->assertValidationRejects( [ 'cart' => [ 'block-cart' ] ] );
	}

	public function test_validate_rejects_boolean_value(): void {
		$this->assertValidationRejects( [ 'cart' => true ] );
	}

	public function test_validate_rejects_mixed_valid_invalid_keys(): void {
		$this->assertValidationRejects( [
			'cart'    => 'block-cart',
			'invalid' => 'test',
		] );
	}

	// ===== Execution Tests =====

	public function test_execute_updates_cart_page(): void {
		$cart_page = $this->createMockPost( 123 );

		WP_Functions::mock( 'get_page_by_path', $cart_page );
		WP_Functions::mock( 'get_option', 0 );
		WP_Functions::mock( 'update_option', true );

		$validation_result = $this->ingredient->validate( [ 'cart' => 'block-cart' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'pages', $data );
		$this->assertArrayHasKey( 'cart', $data['pages'] );
		$this->assertSame( 123, $data['pages']['cart']['page_id'] );
		$this->assertSame( 'block-cart', $data['pages']['cart']['slug'] );
	}

	public function test_execute_updates_checkout_page(): void {
		$checkout_page = $this->createMockPost( 456 );

		WP_Functions::mock( 'get_page_by_path', $checkout_page );
		WP_Functions::mock( 'get_option', 0 );
		WP_Functions::mock( 'update_option', true );

		$validation_result = $this->ingredient->validate( [ 'checkout' => 'block-checkout' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'pages', $data );
		$this->assertArrayHasKey( 'checkout', $data['pages'] );
		$this->assertSame( 456, $data['pages']['checkout']['page_id'] );
		$this->assertSame( 'block-checkout', $data['pages']['checkout']['slug'] );
	}

	public function test_execute_updates_my_account_page(): void {
		$my_account_page = $this->createMockPost( 789 );

		WP_Functions::mock( 'get_page_by_path', $my_account_page );
		WP_Functions::mock( 'get_option', 0 );
		WP_Functions::mock( 'update_option', true );

		$validation_result = $this->ingredient->validate( [ 'my-account' => 'my-account' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'pages', $data );
		$this->assertArrayHasKey( 'my-account', $data['pages'] );
		$this->assertSame( 789, $data['pages']['my-account']['page_id'] );
		$this->assertSame( 'my-account', $data['pages']['my-account']['slug'] );
	}

	public function test_execute_updates_multiple_pages(): void {
		$call_count = 0;
		WP_Functions::mock( 'get_page_by_path', function ( $slug ) use ( &$call_count ) {
			$call_count++;
			return $this->createMockPost( 100 + $call_count );
		} );
		WP_Functions::mock( 'get_option', 0 );
		WP_Functions::mock( 'update_option', true );

		$validation_result = $this->ingredient->validate( [
			'cart'       => 'block-cart',
			'checkout'   => 'block-checkout',
			'my-account' => 'my-account',
		] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'pages', $data );
		$this->assertCount( 3, $data['pages'] );
		$this->assertSame( 101, $data['pages']['cart']['page_id'] );
		$this->assertSame( 102, $data['pages']['checkout']['page_id'] );
		$this->assertSame( 103, $data['pages']['my-account']['page_id'] );
	}

	public function test_execute_returns_previous_value(): void {
		$cart_page = $this->createMockPost( 123 );

		WP_Functions::mock( 'get_page_by_path', $cart_page );
		WP_Functions::mock( 'get_option', 999 );
		WP_Functions::mock( 'update_option', true );

		$validation_result = $this->ingredient->validate( [ 'cart' => 'block-cart' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( 999, $data['pages']['cart']['previous'] );
	}

	public function test_execute_fails_when_page_not_found(): void {
		WP_Functions::mock( 'get_page_by_path', null );

		$validation_result = $this->ingredient->validate( [ 'cart' => 'non-existent' ] );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'errors', $data );
		$this->assertArrayHasKey( 'cart', $data['errors'] );
		$this->assertStringContainsString( 'non-existent', $data['errors']['cart'] );
	}

	public function test_execute_fails_when_update_option_fails(): void {
		$cart_page = $this->createMockPost( 123 );

		WP_Functions::mock( 'get_page_by_path', $cart_page );
		WP_Functions::mock( 'get_option', 0 );
		WP_Functions::mock( 'update_option', false );

		$validation_result = $this->ingredient->validate( [ 'cart' => 'block-cart' ] );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'errors', $data );
		$this->assertArrayHasKey( 'cart', $data['errors'] );
	}

	public function test_execute_handles_partial_success(): void {
		$call_count = 0;
		WP_Functions::mock( 'get_page_by_path', function ( $slug ) use ( &$call_count ) {
			$call_count++;
			// First call succeeds, second returns null
			return $call_count === 1 ? $this->createMockPost( 123 ) : null;
		} );
		WP_Functions::mock( 'get_option', 0 );
		WP_Functions::mock( 'update_option', true );

		$validation_result = $this->ingredient->validate( [
			'cart'     => 'block-cart',
			'checkout' => 'non-existent',
		] );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result );
		$data = $result->get_data();
		$this->assertArrayHasKey( 'updated', $data );
		$this->assertArrayHasKey( 'errors', $data );
		$this->assertArrayHasKey( 'cart', $data['updated'] );
		$this->assertArrayHasKey( 'checkout', $data['errors'] );
	}

	public function test_execute_succeeds_when_value_unchanged(): void {
		$cart_page = $this->createMockPost( 123 );

		WP_Functions::mock( 'get_page_by_path', $cart_page );
		WP_Functions::mock( 'get_option', 123 ); // Already set to same value
		WP_Functions::mock( 'update_option', false ); // Returns false when unchanged

		$validation_result = $this->ingredient->validate( [ 'cart' => 'block-cart' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( 123, $data['pages']['cart']['page_id'] );
		$this->assertSame( 123, $data['pages']['cart']['previous'] );
	}
}
