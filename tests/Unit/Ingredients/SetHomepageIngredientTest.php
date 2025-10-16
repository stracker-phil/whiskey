<?php
/**
 * @covers \Whiskey\Ingredients\SetHomepageIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\SetHomepageIngredient;
use WP_Post;
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

	public function testValidateAcceptsString(): void {
		$this->assertTrue( $this->ingredient->validate( 'home' ) );
	}

	public function testValidateAcceptsInteger(): void {
		$this->assertTrue( $this->ingredient->validate( 42 ) );
	}

	public function testValidateRejectsArray(): void {
		$this->assertFalse( $this->ingredient->validate( array( 'invalid' ) ) );
	}

	public function testValidateRejectsBoolean(): void {
		$this->assertFalse( $this->ingredient->validate( true ) );
	}

	public function testExecuteWithValidStringSlug(): void {
		WP_Functions::mock( 'get_page_by_path', function ( $slug ) {
			return $this->createMockPost( 123 );
		} );
		WP_Functions::mock( 'update_option', function () {
			return true;
		} );

		$result = $this->ingredient->execute( 'home' );

		$this->assertExecutionSuccess( $result, '123' );
	}

	public function testExecuteWithValidPostId(): void {
		WP_Functions::mock( 'get_post', function ( $id ) {
			return $this->createMockPost( $id, 'page' );
		} );
		WP_Functions::mock( 'update_option', function () {
			return true;
		} );

		$result = $this->ingredient->execute( 456 );

		$this->assertExecutionSuccess( $result, '456' );
	}

	public function testExecuteFailsWithInvalidSlug(): void {
		WP_Functions::mock( 'get_page_by_path', function () {
			return null;
		} );

		$result = $this->ingredient->execute( 'nonexistent' );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Did not find', $result->get_message() );
	}

	public function testExecuteFailsWithInvalidPostId(): void {
		WP_Functions::mock( 'get_post', function () {
			return null;
		} );

		$result = $this->ingredient->execute( 999 );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Did not find', $result->get_message() );
	}

	public function testExecuteFailsWhenPostIsNotAPage(): void {
		WP_Functions::mock( 'get_post', function ( $id ) {
			return $this->createMockPost( $id, 'post' ); // Not a page!
		} );

		$result = $this->ingredient->execute( 789 );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Did not find', $result->get_message() );
	}
}
