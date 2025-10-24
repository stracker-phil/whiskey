<?php
/**
 * @covers \Whiskey\Ingredients\UpdatePermalinksIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\UpdatePermalinksIngredient;
use WP_Functions;

class UpdatePermalinksIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return UpdatePermalinksIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'permalink_structure';
	}

	protected function getExpectedCategory(): string {
		return 'wordpress';
	}

	public function test_validate_accepts_string(): void {
		$this->assertTrue( $this->ingredient->validate( '/%postname%/' )->is_valid() );
	}

	public function test_validate_accepts_empty_string(): void {
		$this->assertTrue( $this->ingredient->validate( '' )->is_valid() );
	}

	public function test_validate_rejects_integer(): void {
		$this->assertFalse( $this->ingredient->validate( 123 )->is_valid() );
	}

	public function test_validate_rejects_array(): void {
		$this->assertFalse( $this->ingredient->validate( [ 'invalid' ] )->is_valid() );
	}

	public function test_execute_updates_permalink_structure(): void {
		$flush_called = false;
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'update_option', true );
		WP_Functions::mock( 'flush_rewrite_rules', static function () use ( &$flush_called ) {
			$flush_called = true;
		} );

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertExecutionSuccess( $result );
		$this->assertTrue( $flush_called );
		$this->assertStringContainsString( 'updated', $result->get_message() );
	}

	public function test_execute_returns_data_with_previous_and_current_structure(): void {
		WP_Functions::mock( 'get_option', '/old-structure/' );
		WP_Functions::mock( 'update_option', true );
		WP_Functions::mock( 'flush_rewrite_rules' );

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( '/old-structure/', $data['previous'] );
		$this->assertSame( '/%postname%/', $data['current'] );
	}

	public function test_execute_handles_failed_update(): void {
		WP_Functions::mock( 'get_option', '/different/' );
		WP_Functions::mock( 'update_option', false );
		WP_Functions::mock( 'flush_rewrite_rules' );

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertExecutionFailure( $result, 'Failed' );
	}

	public function test_execute_succeeds_when_structure_unchanged(): void {
		WP_Functions::mock( 'get_option', '/%postname%/' );
		WP_Functions::mock( 'update_option', false );
		WP_Functions::mock( 'flush_rewrite_rules' );

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertExecutionSuccess( $result );
	}
}
