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

	public function testValidateAcceptsString(): void {
		$this->assertTrue( $this->ingredient->validate( '/%postname%/' ) );
	}

	public function testValidateAcceptsEmptyString(): void {
		$this->assertTrue( $this->ingredient->validate( '' ) );
	}

	public function testValidateRejectsInteger(): void {
		$this->assertFalse( $this->ingredient->validate( 123 ) );
	}

	public function testValidateRejectsArray(): void {
		$this->assertFalse( $this->ingredient->validate( [ 'invalid' ] ) );
	}

	public function testExecuteUpdatesPermalinkStructure(): void {
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

	public function testExecuteReturnsDataWithPreviousAndCurrentStructure(): void {
		WP_Functions::mock( 'get_option', '/old-structure/' );
		WP_Functions::mock( 'update_option', true );
		WP_Functions::mock( 'flush_rewrite_rules' );

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( '/old-structure/', $data['previous'] );
		$this->assertSame( '/%postname%/', $data['current'] );
	}

	public function testExecuteHandlesFailedUpdate(): void {
		WP_Functions::mock( 'get_option', '/different/' );
		WP_Functions::mock( 'update_option', false );
		WP_Functions::mock( 'flush_rewrite_rules' );

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertExecutionFailure( $result, 'Failed' );
	}

	public function testExecuteSucceedsWhenStructureUnchanged(): void {
		WP_Functions::mock( 'get_option', '/%postname%/' );
		WP_Functions::mock( 'update_option', false );
		WP_Functions::mock( 'flush_rewrite_rules' );

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertExecutionSuccess( $result );
	}
}
