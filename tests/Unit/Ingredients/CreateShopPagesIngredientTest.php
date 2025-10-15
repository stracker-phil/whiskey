<?php
/**
 * @covers \Whiskey\Ingredients\CreateShopPagesIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Ingredients\CreateShopPagesIngredient;
use WP_Post;

class CreateShopPagesIngredientTest extends WhiskeyTest {
	private CreateShopPagesIngredient $ingredient;

	protected function setUp(): void {
		parent::setUp();
		$this->ingredient = new CreateShopPagesIngredient();
	}

	public function testValidateAcceptsArrayOfStrings(): void {
		$this->assertTrue( $this->ingredient->validate( array( 'shop', 'cart' ) ) );
	}

	public function testValidateAcceptsEmptyArray(): void {
		$this->assertTrue( $this->ingredient->validate( array() ) );
	}

	public function testValidateRejectsString(): void {
		$this->assertFalse( $this->ingredient->validate( 'shop' ) );
	}

	public function testValidateRejectsArrayWithNonStringValue(): void {
		$this->assertFalse( $this->ingredient->validate( array( 'shop', 123 ) ) );
	}

	public function testValidateRejectsArrayWithNestedArray(): void {
		$this->assertFalse( $this->ingredient->validate( array( 'shop', array( 'nested' ) ) ) );
	}

	public function testValidateRejectsNull(): void {
		$this->assertFalse( $this->ingredient->validate( null ) );
	}

	public function testExecuteCreatesNewPage(): void {
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_page_by_path' => function () {
				return null; // Page doesn't exist
			},
			'wp_insert_post'   => function ( $data ) {
				return 123; // New post ID
			},
			'update_post_meta' => function () {
				return true;
			},
		);

		// Create a test template file
		$template_dir = __DIR__ . '/../../../src/Ingredients/ShopPages';
		if ( ! is_dir( $template_dir ) ) {
			mkdir( $template_dir, 0755, true );
		}

		$template_file = $template_dir . '/test-page.php';
		file_put_contents(
			$template_file,
			'<?php return ["title" => "Test Page", "content" => "Test content"];'
		);

		$result = $this->ingredient->execute( array( 'test-page' ) );

		// Clean up
		unlink( $template_file );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertSame( 123, $data['pages']['test-page'] );
	}

	public function testExecuteUpdatesExistingPage(): void {
		global $wp_functions_mock;
		$existing_page     = new WP_Post();
		$existing_page->ID = 456;

		$wp_functions_mock = array(
			'get_page_by_path' => function () use ( $existing_page ) {
				return $existing_page;
			},
			'wp_insert_post'   => function ( $data ) {
				return $data['ID']; // Return the ID from update
			},
			'update_post_meta' => function () {
				return true;
			},
		);

		// Create a test template file
		$template_dir = __DIR__ . '/../../../src/Ingredients/ShopPages';
		if ( ! is_dir( $template_dir ) ) {
			mkdir( $template_dir, 0755, true );
		}

		$template_file = $template_dir . '/test-page.php';
		file_put_contents(
			$template_file,
			'<?php return ["title" => "Test Page", "content" => "Test content"];'
		);

		$result = $this->ingredient->execute( array( 'test-page' ) );

		// Clean up
		unlink( $template_file );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertSame( 456, $data['pages']['test-page'] );
	}

	public function testExecuteHandlesMissingTemplate(): void {
		global $wp_functions_mock;
		$wp_functions_mock = array();

		$result = $this->ingredient->execute( array( 'nonexistent-page' ) );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
		$data = $result->get_data();
		$this->assertSame( 0, $data['pages']['nonexistent-page'] );
	}

	public function testExecuteHandlesWpInsertPostFailure(): void {
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_page_by_path' => function () {
				return null;
			},
			'wp_insert_post'   => function () {
				return 0; // Failure
			},
		);

		// Create a test template file
		$template_dir = __DIR__ . '/../../../src/Ingredients/ShopPages';
		if ( ! is_dir( $template_dir ) ) {
			mkdir( $template_dir, 0755, true );
		}

		$template_file = $template_dir . '/test-page.php';
		file_put_contents(
			$template_file,
			'<?php return ["title" => "Test Page", "content" => "Test content"];'
		);

		$result = $this->ingredient->execute( array( 'test-page' ) );

		// Clean up
		unlink( $template_file );

		$this->assertFalse( $result->is_success() );
		$this->assertStringContainsString( 'Failed', $result->get_message() );
	}

	public function testExecuteProcessesMultiplePages(): void {
		global $wp_functions_mock;
		$wp_functions_mock = array(
			'get_page_by_path' => function () {
				return null;
			},
			'wp_insert_post'   => function ( $data ) {
				static $id = 100;

				return ++$id;
			},
			'update_post_meta' => function () {
				return true;
			},
		);

		// Create test template files
		$template_dir = __DIR__ . '/../../../src/Ingredients/ShopPages';
		if ( ! is_dir( $template_dir ) ) {
			mkdir( $template_dir, 0755, true );
		}

		file_put_contents(
			$template_dir . '/page1.php',
			'<?php return ["title" => "Page 1", "content" => "Content 1"];'
		);
		file_put_contents(
			$template_dir . '/page2.php',
			'<?php return ["title" => "Page 2", "content" => "Content 2"];'
		);

		$result = $this->ingredient->execute( array( 'page1', 'page2' ) );

		// Clean up
		unlink( $template_dir . '/page1.php' );
		unlink( $template_dir . '/page2.php' );

		$this->assertTrue( $result->is_success() );
		$data = $result->get_data();
		$this->assertSame( 101, $data['pages']['page1'] );
		$this->assertSame( 102, $data['pages']['page2'] );
	}

	public function testExecuteHandlesPostMetaInTemplate(): void {
		global $wp_functions_mock;
		$meta_calls = array();
		$wp_functions_mock = array(
			'get_page_by_path' => function () {
				return null;
			},
			'wp_insert_post'   => function () {
				return 123;
			},
			'update_post_meta' => function ( $post_id, $key, $value ) use ( &$meta_calls ) {
				$meta_calls[] = array( $post_id, $key, $value );

				return true;
			},
		);

		// Create template with post_meta
		$template_dir = __DIR__ . '/../../../src/Ingredients/ShopPages';
		if ( ! is_dir( $template_dir ) ) {
			mkdir( $template_dir, 0755, true );
		}

		$template_file = $template_dir . '/meta-page.php';
		file_put_contents(
			$template_file,
			'<?php return ["title" => "Test", "content" => "Test", "post_meta" => ["key1" => "value1", "key2" => "value2"]];'
		);

		$result = $this->ingredient->execute( array( 'meta-page' ) );

		// Clean up
		unlink( $template_file );

		$this->assertTrue( $result->is_success() );
		$this->assertCount( 2, $meta_calls );
		$this->assertSame( 123, $meta_calls[0][0] );
		$this->assertSame( 'key1', $meta_calls[0][1] );
	}

	public function testConstantsAreDefined(): void {
		$this->assertSame( 'create_shop_pages', CreateShopPagesIngredient::NAME );
		$this->assertSame( 'woocommerce', CreateShopPagesIngredient::CATEGORY );
		$this->assertNotEmpty( CreateShopPagesIngredient::DESCRIPTION );
	}
}
