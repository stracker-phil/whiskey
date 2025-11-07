<?php
/**
 * @covers \Whiskey\Ingredients\SetHomepageIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\SetHomepageIngredient;
use WP_Functions;

class SetHomepageIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return SetHomepageIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'set_homepage';
	}

	protected function getExpectedCategory(): string {
		return 'wordpress';
	}

	public function test_validate_accepts_string(): void {
		$this->assertTrue( $this->ingredient->validate( 'home' )->is_valid() );
	}

	public function test_validate_accepts_integer(): void {
		$this->assertTrue( $this->ingredient->validate( 42 )->is_valid() );
	}

	public function test_validate_rejects_array(): void {
		$this->assertFalse( $this->ingredient->validate( [ 'invalid' ] )->is_valid() );
	}

	public function test_validate_rejects_boolean(): void {
		$this->assertFalse( $this->ingredient->validate( true )->is_valid() );
	}

	public function test_execute_with_valid_string_slug(): void {
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 123 ) );
		WP_Functions::mock( 'update_option', true );

		$validation_result = $this->ingredient->validate( 'home' );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result, '123' );
	}

	public function test_execute_with_valid_post_id(): void {
		WP_Functions::mock( 'get_post', fn( $id ) => $this->createMockPost( $id, 'page' ) );
		WP_Functions::mock( 'update_option', true );

		$validation_result = $this->ingredient->validate( 456 );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result, '456' );
	}

	public function test_execute_fails_with_invalid_slug(): void {
		WP_Functions::mock( 'get_page_by_path', null );

		$validation_result = $this->ingredient->validate( 'nonexistent' );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result, 'Did not find' );
	}

	public function test_execute_fails_with_invalid_post_id(): void {
		WP_Functions::mock( 'get_post', null );

		$validation_result = $this->ingredient->validate( 999 );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result, 'Did not find' );
	}

	public function test_execute_fails_when_post_is_not_apage(): void {
		WP_Functions::mock( 'get_post', fn( $id ) => $this->createMockPost( $id, 'post' ) );

		$validation_result = $this->ingredient->validate( 789 );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result, 'Did not find' );
	}
}
