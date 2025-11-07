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

	// ===== Validation Tests =====

	/**
	 * GIVEN various input types
	 * WHEN validating
	 * THEN should accept only arrays of strings
	 *
	 * @dataProvider validation_provider
	 */
	public function test_validate( $input, bool $expected_valid ): void {
		$result = $this->ingredient->validate( $input );

		$this->assertSame( $expected_valid, $result->is_valid() );
	}

	public function validation_provider(): array {
		return [
			'array of strings'      => [ [ 'home', 'about', 'contact' ], true ],
			'empty array'           => [ [], true ],
			'string rejected'       => [ 'home', false ],
			'array with non-string' => [ [ 'home', 123 ], false ],
		];
	}

	// ===== Execution Tests: Menu Creation =====

	/**
	 * GIVEN menu exists or needs creation
	 * WHEN executing
	 * THEN should use or create menu appropriately
	 *
	 * @dataProvider menu_creation_provider
	 */
	public function test_execute_handles_menu_creation(
		$menu_object,
		$create_return,
		int $expected_menu_id
	): void {
		WP_Functions::mock( 'wp_get_nav_menu_object', $menu_object );
		if ( ! $menu_object ) {
			WP_Functions::mock( 'wp_create_nav_menu', $create_return );
		}
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', [] );
		WP_Functions::mock( 'set_theme_mod' );

		$validation_result = $this->ingredient->validate( [ 'home' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( $expected_menu_id, $data['menu_id'] );
	}

	public function menu_creation_provider(): array {
		$existing_menu          = new stdClass();
		$existing_menu->term_id = 99;

		return [
			'creates new menu'   => [ false, 42, 42 ],
			'uses existing menu' => [ $existing_menu, null, 99 ],
		];
	}

	/**
	 * GIVEN menu creation that fails
	 * WHEN executing
	 * THEN should return failure
	 *
	 * @dataProvider menu_creation_failure_provider
	 */
	public function test_execute_fails_when_menu_creation_fails(
		$create_return,
		bool $is_wp_error
	): void {
		WP_Functions::mock( 'wp_get_nav_menu_object', false );
		WP_Functions::mock( 'wp_create_nav_menu', $create_return );
		WP_Functions::mock( 'is_wp_error', $is_wp_error );

		$validation_result = $this->ingredient->validate( [ 'home' ] );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed to create', $result->get_message() );
	}

	public function menu_creation_failure_provider(): array {
		return [
			'returns zero'     => [ 0, false ],
			'returns WP_Error' => [ 'error_object', true ],
		];
	}

	// ===== Execution Tests: Menu Items =====

	/**
	 * GIVEN existing menu items
	 * WHEN executing
	 * THEN should clear existing items before adding new ones
	 */
	public function test_execute_clears_existing_menu_items(): void {
		$deleted_posts = [];
		$menu          = $this->createMockMenu( 42 );
		$item1         = $this->createMockMenuItem( 1 );
		$item2         = $this->createMockMenuItem( 2 );

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', [ $item1, $item2 ] );
		WP_Functions::mock( 'wp_delete_post', static function ( $id ) use ( &$deleted_posts ) {
			$deleted_posts[] = $id;

			return true;
		} );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', [] );
		WP_Functions::mock( 'set_theme_mod' );

		$validation_result = $this->ingredient->validate( [ 'home' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$this->assertContains( 1, $deleted_posts );
		$this->assertContains( 2, $deleted_posts );
	}

	/**
	 * GIVEN multiple page slugs
	 * WHEN executing
	 * THEN should add menu items for valid pages
	 */
	public function test_execute_adds_menu_items_for_valid_pages(): void {
		$menu = $this->createMockMenu( 42 );

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', static function ( $slug ) {
			$page            = new WP_Post();
			$page->ID        = $slug === 'home' ? 10 : 20;
			$page->post_name = $slug;

			return $page;
		} );
		WP_Functions::mock( 'wp_update_nav_menu_item', static fn( $menu_id, $item_id, $data ) => 100 + $data['menu-item-object-id'] );
		WP_Functions::mock( 'get_theme_mod', [] );
		WP_Functions::mock( 'set_theme_mod' );

		$validation_result = $this->ingredient->validate( [ 'home', 'about' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( 110, $data['items']['home'] );
		$this->assertSame( 120, $data['items']['about'] );
	}

	/**
	 * GIVEN pages that don't exist or menu item creation failures
	 * WHEN executing
	 * THEN should return failure with details
	 *
	 * @dataProvider menu_item_failure_provider
	 */
	public function test_execute_handles_menu_item_failures(
		array $slugs,
		callable $page_by_path_mock,
		$update_nav_item_return,
		bool $is_wp_error,
		array $expected_items
	): void {
		$menu = $this->createMockMenu( 42 );

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', $page_by_path_mock );
		WP_Functions::mock( 'wp_update_nav_menu_item', $update_nav_item_return );
		WP_Functions::mock( 'is_wp_error', $is_wp_error );
		WP_Functions::mock( 'get_theme_mod', [] );
		WP_Functions::mock( 'set_theme_mod' );

		$validation_result = $this->ingredient->validate( $slugs );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result );
		$this->assertStringContainsString( 'Failed to add 1 menu item', $result->get_message() );

		$data = $result->get_data();
		foreach ( $expected_items as $slug => $expected_id ) {
			$this->assertSame( $expected_id, $data['items'][ $slug ] );
		}
	}

	public function menu_item_failure_provider(): array {
		return [
			'nonexistent page'        => [
				[ 'home', 'nonexistent' ],
				fn( $slug ) => $slug === 'nonexistent' ? null : $this->createMockPost( 10 ),
				100,
				false,
				[ 'home' => 100, 'nonexistent' => 0 ],
			],
			'update returns zero'     => [
				[ 'home' ],
				fn() => $this->createMockPost( 10 ),
				0,
				false,
				[ 'home' => 0 ],
			],
			'update returns WP_Error' => [
				[ 'home' ],
				fn() => $this->createMockPost( 10 ),
				'error_object',
				true,
				[ 'home' => 0 ],
			],
		];
	}

	// ===== Execution Tests: Menu Location =====

	/**
	 * GIVEN menu location assignment
	 * WHEN executing
	 * THEN should set menu to primary location
	 */
	public function test_execute_sets_menu_location(): void {
		$theme_mod_calls = [];
		$menu            = $this->createMockMenu( 42 );

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', [] );
		WP_Functions::mock( 'set_theme_mod', static function ( $name, $value ) use ( &$theme_mod_calls ) {
			$theme_mod_calls[] = [ $name, $value ];
		} );

		$validation_result = $this->ingredient->validate( [ 'home' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$this->assertCount( 1, $theme_mod_calls );
		$this->assertSame( 'nav_menu_locations', $theme_mod_calls[0][0] );
		$this->assertSame( 42, $theme_mod_calls[0][1]['primary'] );
	}

	/**
	 * GIVEN existing menu locations
	 * WHEN executing
	 * THEN should preserve existing locations
	 */
	public function test_execute_preserves_existing_menu_locations(): void {
		$existing_locations = [ 'secondary' => 99, 'footer' => 88 ];
		$theme_mod_calls    = [];
		$menu               = $this->createMockMenu( 42 );

		WP_Functions::mock( 'wp_get_nav_menu_object', $menu );
		WP_Functions::mock( 'wp_get_nav_menu_items', false );
		WP_Functions::mock( 'get_page_by_path', $this->createMockPost( 10 ) );
		WP_Functions::mock( 'wp_update_nav_menu_item', 100 );
		WP_Functions::mock( 'get_theme_mod', $existing_locations );
		WP_Functions::mock( 'set_theme_mod', static function ( $name, $value ) use ( &$theme_mod_calls ) {
			$theme_mod_calls[] = [ $name, $value ];
		} );

		$validation_result = $this->ingredient->validate( [ 'home' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );

		$updated_locations = $theme_mod_calls[0][1];
		$this->assertSame( 42, $updated_locations['primary'] );
		$this->assertSame( 99, $updated_locations['secondary'] );
		$this->assertSame( 88, $updated_locations['footer'] );
	}

	// ===== Helper Methods =====

	private function createMockMenu( int $term_id ): stdClass {
		$menu          = new stdClass();
		$menu->term_id = $term_id;

		return $menu;
	}

	private function createMockMenuItem( int $id ): stdClass {
		$item     = new stdClass();
		$item->ID = $id;

		return $item;
	}
}
