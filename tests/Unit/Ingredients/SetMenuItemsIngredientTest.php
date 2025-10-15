<?php
/**
 * @covers \Whiskey\Ingredients\SetMenuItemsIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Ingredients\SetMenuItemsIngredient;
use WP_Post;
use stdClass;

class SetMenuItemsIngredientTest extends WhiskeyTest {
	private SetMenuItemsIngredient $ingredient;

	protected function setUp(): void {
		parent::setUp();
		$this->ingredient = new SetMenuItemsIngredient();
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

	public function testValidateRejectsNull(): void {
		$this->assertFalse( $this->ingredient->validate( null ) );
	}

	public function testExecuteCreatesNewMenuIfNotExists(): void {
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'wp_get_nav_menu_object'  => function () {
				return false; // Menu doesn't exist
			},
			'wp_create_nav_menu'      => function () {
				return 42; // New menu ID
			},
			'wp_get_nav_menu_items'   => function () {
				return false;
			},
			'get_page_by_path'        => function () {
				$page     = new WP_Post();
				$page->ID = 10;

				return $page;
			},
			'wp_update_nav_menu_item' => function () {
				return 100;
			},
			'get_theme_mod'           => function () {
				return array();
			},
			'set_theme_mod'           => function () {
			},
		);

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertSame( 42, $data['menu_id'] );
	}

	public function testExecuteUsesExistingMenu(): void {
		global $wp_functions_mock;
		$menu          = new stdClass();
		$menu->term_id = 99;

		$wp_functions_mock = array(
			'wp_get_nav_menu_object'  => function () use ( $menu ) {
				return $menu;
			},
			'wp_get_nav_menu_items'   => function () {
				return false;
			},
			'get_page_by_path'        => function () {
				$page     = new WP_Post();
				$page->ID = 10;

				return $page;
			},
			'wp_update_nav_menu_item' => function () {
				return 100;
			},
			'get_theme_mod'           => function () {
				return array();
			},
			'set_theme_mod'           => function () {
			},
		);

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertSame( 99, $data['menu_id'] );
	}

	public function testExecuteFailsIfMenuCreationFails(): void {
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'wp_get_nav_menu_object' => function () {
				return false;
			},
			'wp_create_nav_menu'     => function () {
				return 0; // Creation failed
			},
		);

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Failed to create', $result->get_message() );
	}

	public function testExecuteClearsExistingMenuItems(): void {
		global $wp_functions_mock;
		$deleted_posts = array();
		$menu          = new stdClass();
		$menu->term_id = 42;

		$item1     = new stdClass();
		$item1->ID = 1;
		$item2     = new stdClass();
		$item2->ID = 2;

		$wp_functions_mock = array(
			'wp_get_nav_menu_object'  => function () use ( $menu ) {
				return $menu;
			},
			'wp_get_nav_menu_items'   => function () use ( $item1, $item2 ) {
				return array( $item1, $item2 );
			},
			'wp_delete_post'          => function ( $id, $force ) use ( &$deleted_posts ) {
				$deleted_posts[] = $id;

				return true;
			},
			'get_page_by_path'        => function () {
				$page     = new WP_Post();
				$page->ID = 10;

				return $page;
			},
			'wp_update_nav_menu_item' => function () {
				return 100;
			},
			'get_theme_mod'           => function () {
				return array();
			},
			'set_theme_mod'           => function () {
			},
		);

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertTrue( $result->is_success() );
		$this->assertContains( 1, $deleted_posts );
		$this->assertContains( 2, $deleted_posts );
	}

	public function testExecuteAddsMenuItemsForValidPages(): void {
		global $wp_functions_mock;
		$menu          = new stdClass();
		$menu->term_id = 42;

		$wp_functions_mock = array(
			'wp_get_nav_menu_object'  => function () use ( $menu ) {
				return $menu;
			},
			'wp_get_nav_menu_items'   => function () {
				return false;
			},
			'get_page_by_path'        => function ( $slug ) {
				$page            = new WP_Post();
				$page->ID        = $slug === 'home' ? 10 : 20;
				$page->post_name = $slug;

				return $page;
			},
			'wp_update_nav_menu_item' => function ( $menu_id, $item_id, $data ) {
				return 100 + $data['menu-item-object-id'];
			},
			'get_theme_mod'           => function () {
				return array();
			},
			'set_theme_mod'           => function () {
			},
		);

		$result = $this->ingredient->execute( array( 'home', 'about' ) );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertSame( 110, $data['items']['home'] );
		$this->assertSame( 120, $data['items']['about'] );
	}

	public function testExecuteHandlesNonexistentPages(): void {
		global $wp_functions_mock;
		$menu          = new stdClass();
		$menu->term_id = 42;

		$wp_functions_mock = array(
			'wp_get_nav_menu_object'  => function () use ( $menu ) {
				return $menu;
			},
			'wp_get_nav_menu_items'   => function () {
				return false;
			},
			'get_page_by_path'        => function ( $slug ) {
				if ( $slug === 'nonexistent' ) {
					return null;
				}
				$page     = new WP_Post();
				$page->ID = 10;

				return $page;
			},
			'wp_update_nav_menu_item' => function () {
				return 100;
			},
			'get_theme_mod'           => function () {
				return array();
			},
			'set_theme_mod'           => function () {
			},
		);

		$result = $this->ingredient->execute( array( 'home', 'nonexistent' ) );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Failed to add 1 menu item', $result->get_message() );
		$data = $result->get_data();
		$this->assertSame( 100, $data['items']['home'] );
		$this->assertSame( 0, $data['items']['nonexistent'] );
	}

	public function testExecuteSetsMenuLocation(): void {
		global $wp_functions_mock;
		$theme_mod_calls = array();
		$menu            = new stdClass();
		$menu->term_id   = 42;

		$wp_functions_mock = array(
			'wp_get_nav_menu_object'  => function () use ( $menu ) {
				return $menu;
			},
			'wp_get_nav_menu_items'   => function () {
				return false;
			},
			'get_page_by_path'        => function () {
				$page     = new WP_Post();
				$page->ID = 10;

				return $page;
			},
			'wp_update_nav_menu_item' => function () {
				return 100;
			},
			'get_theme_mod'           => function () {
				return array();
			},
			'set_theme_mod'           => function ( $name, $value ) use ( &$theme_mod_calls ) {
				$theme_mod_calls[] = array( $name, $value );
			},
		);

		$result = $this->ingredient->execute( array( 'home' ) );

		$this->assertTrue( $result->is_success() );
		$this->assertCount( 1, $theme_mod_calls );
		$this->assertSame( 'nav_menu_locations', $theme_mod_calls[0][0] );
		$this->assertSame( 42, $theme_mod_calls[0][1]['primary'] );
	}

	public function testConstantsAreDefined(): void {
		$this->assertSame( 'set_menu_items', SetMenuItemsIngredient::NAME );
		$this->assertSame( 'wordpress', SetMenuItemsIngredient::CATEGORY );
		$this->assertNotEmpty( SetMenuItemsIngredient::DESCRIPTION );
	}
}
