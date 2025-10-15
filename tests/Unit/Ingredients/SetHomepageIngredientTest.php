<?php
/**
 * @covers \Whiskey\Ingredients\SetHomepageIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Ingredients\SetHomepageIngredient;
use WP_Post;

class SetHomepageIngredientTest extends WhiskeyTest {
	private SetHomepageIngredient $ingredient;

	protected function setUp(): void {
		parent::setUp();
		$this->ingredient = new SetHomepageIngredient();
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

	public function testValidateRejectsNull(): void {
		$this->assertFalse( $this->ingredient->validate( null ) );
	}

	public function testExecuteWithValidStringSlug(): void {
		// Mock WordPress functions
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_page_by_path' => function ( $slug ) {
				$post     = new WP_Post();
				$post->ID = 123;

				return $post;
			},
			'update_option'    => function () {
				return true;
			},
		);

		$result = $this->ingredient->execute( 'home' );

		$this->assertTrue( $result->is_success() );
		$this->assertStringContainsString( '123', $result->get_message() );
	}

	public function testExecuteWithValidPostId(): void {
		// Mock WordPress functions
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_post'       => function ( $id ) {
				$post            = new WP_Post();
				$post->ID        = $id;
				$post->post_type = 'page';

				return $post;
			},
			'update_option'  => function () {
				return true;
			},
		);

		$result = $this->ingredient->execute( 456 );

		$this->assertTrue( $result->is_success() );
		$this->assertStringContainsString( '456', $result->get_message() );
	}

	public function testExecuteFailsWithInvalidSlug(): void {
		// Mock WordPress functions
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_page_by_path' => function () {
				return null;
			},
		);

		$result = $this->ingredient->execute( 'nonexistent' );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Did not find', $result->get_message() );
	}

	public function testExecuteFailsWithInvalidPostId(): void {
		// Mock WordPress functions
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_post' => function () {
				return null;
			},
		);

		$result = $this->ingredient->execute( 999 );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Did not find', $result->get_message() );
	}

	public function testExecuteFailsWhenPostIsNotAPage(): void {
		// Mock WordPress functions
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_post' => function ( $id ) {
				$post            = new WP_Post();
				$post->ID        = $id;
				$post->post_type = 'post'; // Not a page!

				return $post;
			},
		);

		$result = $this->ingredient->execute( 789 );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Did not find', $result->get_message() );
	}

	public function testConstantsAreDefined(): void {
		$this->assertSame( 'set_homepage', SetHomepageIngredient::NAME );
		$this->assertSame( 'wordpress', SetHomepageIngredient::CATEGORY );
		$this->assertNotEmpty( SetHomepageIngredient::DESCRIPTION );
	}
}
