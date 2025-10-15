<?php
/**
 * @covers \Whiskey\Ingredients\UpdatePermalinksIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Ingredients\UpdatePermalinksIngredient;

class UpdatePermalinksIngredientTest extends WhiskeyTest {
	private UpdatePermalinksIngredient $ingredient;

	protected function setUp(): void {
		parent::setUp();
		$this->ingredient = new UpdatePermalinksIngredient();
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
		$this->assertFalse( $this->ingredient->validate( array( 'invalid' ) ) );
	}

	public function testValidateRejectsNull(): void {
		$this->assertFalse( $this->ingredient->validate( null ) );
	}

	public function testExecuteUpdatesPermalinkStructure(): void {
		global $wp_functions_mock;
		$flush_called = false;
		$wp_functions_mock = array(
			'get_option'           => function ( $option ) {
				return ''; // Previous structure
			},
			'update_option'        => function ( $option, $value ) {
				return true;
			},
			'flush_rewrite_rules'  => function () use ( &$flush_called ) {
				$flush_called = true;
			},
		);

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertTrue( $result->is_success() );
		$this->assertTrue( $flush_called );
		$this->assertStringContainsString( 'updated', $result->get_message() );
	}

	public function testExecuteReturnsDataWithPreviousAndCurrentStructure(): void {
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_option'           => function ( $option ) {
				return '/old-structure/';
			},
			'update_option'        => function ( $option, $value ) {
				return true;
			},
			'flush_rewrite_rules'  => function () {
			},
		);

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertSame( '/old-structure/', $data['previous'] );
		$this->assertSame( '/%postname%/', $data['current'] );
	}

	public function testExecuteHandlesFailedUpdate(): void {
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_option'           => function ( $option ) {
				return '/different/';
			},
			'update_option'        => function ( $option, $value ) {
				return false; // Update failed
			},
			'flush_rewrite_rules'  => function () {
			},
		);

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}

	public function testExecuteSucceedsWhenStructureUnchanged(): void {
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_option'           => function ( $option ) {
				return '/%postname%/';
			},
			'update_option'        => function ( $option, $value ) {
				return false; // No update needed - already same
			},
			'flush_rewrite_rules'  => function () {
			},
		);

		$result = $this->ingredient->execute( '/%postname%/' );

		$this->assertTrue( $result->is_success() );
	}

	public function testConstantsAreDefined(): void {
		$this->assertSame( 'permalink_structure', UpdatePermalinksIngredient::NAME );
		$this->assertSame( 'wordpress', UpdatePermalinksIngredient::CATEGORY );
		$this->assertNotEmpty( UpdatePermalinksIngredient::DESCRIPTION );
	}
}
