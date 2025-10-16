<?php
/**
 * @covers \Whiskey\Ingredients\CreateShopPagesIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\CreateShopPagesIngredient;
use WP_Functions;

class CreateShopPagesIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return CreateShopPagesIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'create_shop_pages';
	}

	protected function getExpectedCategory(): string {
		return 'woocommerce';
	}

	// ===== Validation Tests =====

	public function testValidateAcceptsArrayOfStrings(): void {
		$this->assertValidationAccepts( [ 'shop', 'cart' ] );
	}

	public function testValidateAcceptsEmptyArray(): void {
		$this->assertValidationAccepts( [] );
	}

	public function testValidateRejectsString(): void {
		$this->assertValidationRejects( 'shop' );
	}

	public function testValidateRejectsArrayWithNonStringValue(): void {
		$this->assertValidationRejects( [ 'shop', 123 ] );
	}

	public function testValidateRejectsArrayWithNestedArray(): void {
		$this->assertValidationRejects( [ 'shop', [ 'nested' ] ] );
	}

	// ===== Execution Tests =====

	public function testExecuteCreatesNewPage(): void {
		WP_Functions::mock( 'get_page_by_path', null );
		WP_Functions::mock( 'wp_insert_post', 123 );
		WP_Functions::mock( 'update_post_meta', true );

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

		$result = $this->ingredient->execute( [ 'test-page' ] );

		// Clean up
		unlink( $template_file );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( 123, $data['pages']['test-page'] );
	}

	public function testExecuteUpdatesExistingPage(): void {
		$existing_page = $this->createMockPost( 456 );

		WP_Functions::mock( 'get_page_by_path', $existing_page );
		WP_Functions::mock( 'wp_insert_post', static fn( $data ) => $data['ID'] );
		WP_Functions::mock( 'update_post_meta', true );

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

		$result = $this->ingredient->execute( [ 'test-page' ] );

		// Clean up
		unlink( $template_file );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( 456, $data['pages']['test-page'] );
	}

	public function testExecuteHandlesMissingTemplate(): void {
		$result = $this->ingredient->execute( [ 'nonexistent-page' ] );

		$this->assertExecutionFailure( $result, 'Failed' );
		$data = $result->get_data();
		$this->assertSame( 0, $data['pages']['nonexistent-page'] );
	}

	public function testExecuteHandlesWpInsertPostFailure(): void {
		WP_Functions::mock( 'get_page_by_path', null );
		WP_Functions::mock( 'wp_insert_post', 0 );

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

		$result = $this->ingredient->execute( [ 'test-page' ] );

		// Clean up
		unlink( $template_file );

		$this->assertExecutionFailure( $result, 'Failed' );
	}

	public function testExecuteProcessesMultiplePages(): void {
		WP_Functions::mock( 'get_page_by_path', null );
		WP_Functions::mock( 'wp_insert_post', static function ( $data ) {
			static $id = 100;

			return ++ $id;
		} );
		WP_Functions::mock( 'update_post_meta', true );

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

		$result = $this->ingredient->execute( [ 'page1', 'page2' ] );

		// Clean up
		unlink( $template_dir . '/page1.php' );
		unlink( $template_dir . '/page2.php' );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( 101, $data['pages']['page1'] );
		$this->assertSame( 102, $data['pages']['page2'] );
	}

	public function testExecuteHandlesPostMetaInTemplate(): void {
		$meta_calls = [];
		WP_Functions::mock( 'get_page_by_path', null );
		WP_Functions::mock( 'wp_insert_post', 123 );
		WP_Functions::mock( 'update_post_meta', static function ( $post_id, $key, $value ) use ( &$meta_calls ) {
			$meta_calls[] = [ $post_id, $key, $value ];

			return true;
		} );

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

		$result = $this->ingredient->execute( [ 'meta-page' ] );

		// Clean up
		unlink( $template_file );

		$this->assertExecutionSuccess( $result );
		$this->assertCount( 2, $meta_calls );
		$this->assertSame( 123, $meta_calls[0][0] );
		$this->assertSame( 'key1', $meta_calls[0][1] );
	}

	public function testExecuteHandlesInvalidTemplateFormat(): void {
		// Create a template file with invalid format (missing required fields)
		$template_dir = __DIR__ . '/../../../src/Ingredients/ShopPages';
		if ( ! is_dir( $template_dir ) ) {
			mkdir( $template_dir, 0755, true );
		}

		$template_file = $template_dir . '/invalid-template.php';
		file_put_contents(
			$template_file,
			'<?php return ["title" => "Test"];' // Missing content field
		);

		$result = $this->ingredient->execute( [ 'invalid-template' ] );

		// Clean up
		unlink( $template_file );

		$this->assertExecutionFailure( $result );
		$data = $result->get_data();
		$this->assertSame( 0, $data['pages']['invalid-template'] );
	}

	public function testExecuteHandlesTemplateReturningNonArray(): void {
		// Create a template file that returns non-array
		$template_dir = __DIR__ . '/../../../src/Ingredients/ShopPages';
		if ( ! is_dir( $template_dir ) ) {
			mkdir( $template_dir, 0755, true );
		}

		$template_file = $template_dir . '/bad-template.php';
		file_put_contents( $template_file, '<?php return "not an array";' );

		$result = $this->ingredient->execute( [ 'bad-template' ] );

		// Clean up
		unlink( $template_file );

		$this->assertExecutionFailure( $result );
	}

	public function testExecuteAppliesDefaultPostTypeWhenNotSpecified(): void {
		$captured_post_data = null;
		WP_Functions::mock( 'get_page_by_path', null );
		WP_Functions::mock( 'wp_insert_post', static function ( $data ) use ( &$captured_post_data ) {
			$captured_post_data = $data;

			return 123;
		} );
		WP_Functions::mock( 'update_post_meta', true );

		// Create template without post_type
		$template_dir = __DIR__ . '/../../../src/Ingredients/ShopPages';
		if ( ! is_dir( $template_dir ) ) {
			mkdir( $template_dir, 0755, true );
		}

		$template_file = $template_dir . '/no-type.php';
		file_put_contents(
			$template_file,
			'<?php return ["title" => "Test", "content" => "Test"];'
		);

		$result = $this->ingredient->execute( [ 'no-type' ] );

		// Clean up
		unlink( $template_file );

		$this->assertExecutionSuccess( $result );
		$this->assertSame( 'page', $captured_post_data['post_type'] );
	}

	public function testExecuteAppliesDefaultPostMetaWhenNotSpecified(): void {
		$meta_calls = [];
		WP_Functions::mock( 'get_page_by_path', null );
		WP_Functions::mock( 'wp_insert_post', 123 );
		WP_Functions::mock( 'update_post_meta', static function ( $post_id, $key, $value ) use ( &$meta_calls ) {
			$meta_calls[] = [ $post_id, $key, $value ];

			return true;
		} );

		// Create template without post_meta
		$template_dir = __DIR__ . '/../../../src/Ingredients/ShopPages';
		if ( ! is_dir( $template_dir ) ) {
			mkdir( $template_dir, 0755, true );
		}

		$template_file = $template_dir . '/no-meta.php';
		file_put_contents(
			$template_file,
			'<?php return ["title" => "Test", "content" => "Test"];'
		);

		$result = $this->ingredient->execute( [ 'no-meta' ] );

		// Clean up
		unlink( $template_file );

		$this->assertExecutionSuccess( $result );
		$this->assertEmpty( $meta_calls );
	}
}
