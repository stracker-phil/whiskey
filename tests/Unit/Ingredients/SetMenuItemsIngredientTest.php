<?php
/**
 * @covers \Whiskey\Ingredients\SetMenuItemsIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\SetMenuItemsIngredient;
use WP_Post;
use stdClass;
use WP_Functions;

class SetMenuItemsIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return SetMenuItemsIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'set_menu_items';
	}

	protected function getExpectedCategory(): string {
		return 'wordpress';
	}

	public function testValidateAcceptsArrayOfStrings(): void {
		$this->assertTrue( $this->ingredient->validate( array( 'home', 'about', 'contact' ) ) );
	}

	public function testValidateAcceptsEmptyArray(): void {
		$this->assertTrue( $this->ingredient->validate( array() ) );
	}

	public function testValidateRejectsString(): void {
		$this->assertFalse( $this->ingredient->validate( 'home' ) );
	}

	public function testValidateRejectsArrayWithNonStringValue(): void {
		$this->assertFalse( $this->ingredient->validate( array( 'home', 123 ) ) );
	}

	public function testExecuteCreatesNewMenuIfNotExists(): void {
		WP_Functions::mock( 'wp_get_nav_menu_object', false );
		WP_Functions::mock( 'wp_create_nav_menu', 42 );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', array() );
		WP_Functions::mock( 'set_theme_mod' );

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertSame( 42, $data['menu_id'] );
	}

	public function testExecuteUsesExistingMenu(): void {
		$menu          = new stdClass();
		$menu->term_id = 99;

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', array() );
		WP_Functions::mock( 'set_theme_mod' );

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertSame( 99, $data['menu_id'] );
	}

	public function testExecuteFailsIfMenuCreationFails(): void {
		WP_Functions::mock( 'wp_get_nav_menu_object', false );
		WP_Functions::mock( 'wp_create_nav_menu', 0 );

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Failed to create', $result->get_message() );
	}

	public function testExecuteClearsExistingMenuItems(): void {
		$deleted_posts = array();
		$menu          = new stdClass();
		$menu->term_id = 42;

		$item1     = new stdClass();
		$item1->ID = 1;
		$item2     = new stdClass();
		$item2->ID = 2;

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', array( $item1, $item2 ) );
		WP_Functions::mock( 'wp_delete_post', static function ( $id, $force ) use ( &$deleted_posts ) {
			$deleted_posts[] = $id;
			return true;
		} );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', array() );
		WP_Functions::mock( 'set_theme_mod' );

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertTrue( $result->is_success() );
		$this->assertContains( 1, $deleted_posts );
		$this->assertContains( 2, $deleted_posts );
	}

	public function testExecuteAddsMenuItemsForValidPages(): void {
		$menu          = new stdClass();
		$menu->term_id = 42;

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', static function ( $slug ) {
			$page            = new WP_Post();
			$page->ID        = $slug === 'home' ? 10 : 20;
			$page->post_name = $slug;
			return $page;
		} );
		WP_Functions::mock( 'wp_update_nav_menu_item', static fn( $menu_id, $item_id, $data ) => 100 + $data['menu-item-object-id'] );
		WP_Functions::mock( 'get_theme_mod', array() );
		WP_Functions::mock( 'set_theme_mod' );

		$result = $this->ingredient->execute( array( 'home', 'about' ) );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertSame( 110, $data['items']['home'] );
		$this->assertSame( 120, $data['items']['about'] );
	}

	public function testExecuteHandlesNonexistentPages(): void {
		$menu          = new stdClass();
		$menu->term_id = 42;

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', static fn( $slug ) => $slug === 'nonexistent' ? null : $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', array() );
		WP_Functions::mock( 'set_theme_mod' );

		$result = $this->ingredient->execute( array( 'home', 'nonexistent' ) );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Failed to add 1 menu item', $result->get_message() );
		$data = $result->get_data();
		$this->assertSame( 100, $data['items']['home'] );
		$this->assertSame( 0, $data['items']['nonexistent'] );
	}

	public function testExecuteSetsMenuLocation(): void {
		$theme_mod_calls = array();
		$menu            = new stdClass();
		$menu->term_id   = 42;

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', array() );
		WP_Functions::mock( 'set_theme_mod', static function ( $name, $value ) use ( &$theme_mod_calls ) {
			$theme_mod_calls[] = array( $name, $value );
		} );

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertTrue( $result->is_success() );
		$this->assertCount( 1, $theme_mod_calls );
		$this->assertSame( 'nav_menu_locations', $theme_mod_calls[0][0] );
		$this->assertSame( 42, $theme_mod_calls[0][1]['primary'] );
	}

	public function testExecuteHandlesWpUpdateNavMenuItemFailure(): void {
		$menu          = new stdClass();
		$menu->term_id = 42;

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 0 );
		WP_Functions::mock( 'get_theme_mod', array() );
		WP_Functions::mock( 'set_theme_mod' );

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Failed to add 1 menu item', $result->get_message() );
		$data = $result->get_data();
		$this->assertSame( 0, $data['items']['home'] );
	}

	public function testExecuteHandlesEmptyMenuItems(): void {
		$menu          = new stdClass();
		$menu->term_id = 42;

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', array() );
		WP_Functions::mock( 'set_theme_mod' );

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertTrue( $result->is_success() );
	}

	public function testExecutePreservesExistingMenuLocations(): void {
		$existing_locations = array(
			'secondary' => 99,
			'footer'    => 88,
		);
		$theme_mod_calls    = array();
		$menu               = new stdClass();
		$menu->term_id      = 42;

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', $existing_locations );
		WP_Functions::mock( 'set_theme_mod', static function ( $name, $value ) use ( &$theme_mod_calls ) {
			$theme_mod_calls[] = array( $name, $value );
		} );

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertTrue( $result->is_success() );
		$this->assertCount( 1, $theme_mod_calls );

		// Check that existing locations are preserved
		$updated_locations = $theme_mod_calls[0][1];
		$this->assertSame( 42, $updated_locations['primary'] );
		$this->assertSame( 99, $updated_locations['secondary'] );
		$this->assertSame( 88, $updated_locations['footer'] );
	}
}
