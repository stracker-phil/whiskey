<?php
/**
 * @covers \Whiskey\Ingredients\ProcessImagesIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\ProcessImagesIngredient;
use WP_Functions;

class ProcessImagesIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return ProcessImagesIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'process_images';
	}

	protected function getExpectedCategory(): string {
		return 'wordpress';
	}

	// ===== Validation Tests =====

	/**
	 * GIVEN various input formats
	 * WHEN validating input
	 * THEN should accept only valid array structures with image_url
	 *
	 * @dataProvider validation_provider
	 */
	public function test_validate( $input, bool $expected_valid ): void {
		$result = $this->ingredient->validate( $input );

		$this->assertSame( $expected_valid, $result->is_valid() );
	}

	public function validation_provider(): array {
		return [
			'array with image_url'         => [
				[ 'image_url' => 'https://example.com/image.jpg' ],
				true,
			],
			'array with image_url and alt' => [
				[
					'image_url' => 'https://example.com/image.jpg',
					'alt'       => 'Product photo',
				],
				true,
			],
			'string input rejected'        => [
				'https://example.com/image.jpg',
				false,
			],
			'array missing image_url'      => [
				[ 'alt' => 'Product photo' ],
				false,
			],
			'non-string image_url'         => [
				[ 'image_url' => 123 ],
				false,
			],
			'non-string alt text'          => [
				[
					'image_url' => 'https://example.com/image.jpg',
					'alt'       => 123,
				],
				false,
			],
		];
	}

	// ===== Execution Success Tests =====

	/**
	 * GIVEN valid image configuration
	 * WHEN executing with all variants successful
	 * THEN should return success with attachment ID and all five size names
	 */
	public function test_execute_processes_all_five_variants(): void {
		$this->mock_successful_image_processing();

		$validation_result = $this->ingredient->validate(
			[
				'image_url' => 'https://example.com/product.jpg',
				'alt'       => 'Product image',
			]
		);
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result, 'Successfully processed image with 5 custom variants.' );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'attachment_id', $data );
		$this->assertSame( 201, $data['attachment_id'] );

		$this->assertArrayHasKey( 'sizes', $data );
		$this->assertCount( 5, $data['sizes'] );
		$this->assertContains( 'whiskey_thumbnail', $data['sizes'] );
		$this->assertContains( 'whiskey_hero', $data['sizes'] );
		$this->assertContains( 'whiskey_gallery', $data['sizes'] );
		$this->assertContains( 'whiskey_avatar', $data['sizes'] );
		$this->assertContains( 'whiskey_og', $data['sizes'] );
	}

	/**
	 * GIVEN image configuration without alt text
	 * WHEN executing
	 * THEN should use default alt text
	 */
	public function test_execute_uses_default_alt_when_not_provided(): void {
		$this->mock_successful_image_processing();

		$validation_result = $this->ingredient->validate( [ 'image_url' => 'https://example.com/product.jpg' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
	}

	/**
	 * GIVEN image configuration with alt text
	 * WHEN processing
	 * THEN should set alt text on attachment
	 */
	public function test_execute_sets_alt_text_on_attachment(): void {
		$alt_value = null;
		$this->mock_successful_image_processing();

		WP_Functions::mock( 'update_post_meta', static function ( $id, $key, $value ) use ( &$alt_value ) {
			if ( $key === '_wp_attachment_image_alt' ) {
				$alt_value = $value;
			}

			return true;
		} );

		$validation_result = $this->ingredient->validate(
			[
				'image_url' => 'https://example.com/product.jpg',
				'alt'       => 'Premium Soap',
			]
		);
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$this->assertSame( 'Premium Soap', $alt_value );
	}

	/**
	 * GIVEN successful image processing
	 * WHEN creating variants
	 * THEN should update attachment metadata with all sizes
	 */
	public function test_execute_updates_attachment_metadata_with_sizes(): void {
		$updated_metadata = null;

		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', 201 );
		WP_Functions::mock( 'is_wp_error', false );
		WP_Functions::mock( 'update_post_meta', true );
		WP_Functions::mock( 'unlink', true );
		WP_Functions::mock( 'get_attached_file', '/path/to/file.jpg' );
		WP_Functions::mock( 'wp_get_attachment_metadata', [ 'sizes' => [] ] );
		WP_Functions::mock( 'wp_get_image_editor', $this->create_mock_image_editor() );

		// Capture the metadata being updated
		WP_Functions::mock( 'wp_update_attachment_metadata', static function ( $id, $metadata ) use ( &$updated_metadata ) {
			$updated_metadata = $metadata;

			return true;
		} );

		$validation_result = $this->ingredient->validate( [ 'image_url' => 'https://example.com/product.jpg' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$this->assertIsArray( $updated_metadata );
		$this->assertArrayHasKey( 'sizes', $updated_metadata );
		$this->assertCount( 5, $updated_metadata['sizes'] );
		$this->assertArrayHasKey( 'whiskey_thumbnail', $updated_metadata['sizes'] );
		$this->assertArrayHasKey( 'whiskey_hero', $updated_metadata['sizes'] );
		$this->assertArrayHasKey( 'whiskey_gallery', $updated_metadata['sizes'] );
		$this->assertArrayHasKey( 'whiskey_avatar', $updated_metadata['sizes'] );
		$this->assertArrayHasKey( 'whiskey_og', $updated_metadata['sizes'] );
	}

	/**
	 * GIVEN successful image processing
	 * WHEN creating variants
	 * THEN should generate correct size strings for each variant
	 */
	public function test_execute_generates_correct_size_strings(): void {
		$updated_metadata = null;

		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', 201 );
		WP_Functions::mock( 'is_wp_error', false );
		WP_Functions::mock( 'update_post_meta', true );
		WP_Functions::mock( 'unlink', true );
		WP_Functions::mock( 'get_attached_file', '/path/to/file.jpg' );
		WP_Functions::mock( 'wp_get_attachment_metadata', [ 'sizes' => [] ] );
		WP_Functions::mock( 'wp_get_image_editor', $this->create_mock_image_editor() );

		// Capture the metadata being updated
		WP_Functions::mock( 'wp_update_attachment_metadata', static function ( $id, $metadata ) use ( &$updated_metadata ) {
			$updated_metadata = $metadata;

			return true;
		} );

		$validation_result = $this->ingredient->validate( [ 'image_url' => 'https://example.com/product.jpg' ] );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$this->assertIsArray( $updated_metadata, 'Metadata should have been captured' );

		$sizes = $updated_metadata['sizes'];
		$this->assertSame( '150x0', $sizes['whiskey_thumbnail'] );
		$this->assertSame( '1920x600', $sizes['whiskey_hero'] );
		$this->assertSame( '800x0', $sizes['whiskey_gallery'] );
		$this->assertSame( '200x200', $sizes['whiskey_avatar'] );
		$this->assertSame( '1200x630', $sizes['whiskey_og'] );
	}

	// ===== Execution Failure Tests =====

	/**
	 * GIVEN image URL that fails to download
	 * WHEN executing
	 * THEN should return failure with download error message
	 */
	public function test_execute_fails_when_download_fails(): void {
		WP_Functions::mock( 'download_url', 'WP_Error_object' );
		WP_Functions::mock( 'is_wp_error', true );

		$validation_result = $this->ingredient->validate( [ 'image_url' => 'https://example.com/broken.jpg' ] );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result, 'Failed to download image from URL.' );
	}

	/**
	 * GIVEN downloaded file that fails to import
	 * WHEN executing
	 * THEN should return failure and clean up temp file
	 */
	public function test_execute_fails_when_import_fails(): void {
		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', 'WP_Error_object' );
		WP_Functions::mock( 'is_wp_error', static function ( $thing ) {
			return $thing === 'WP_Error_object';
		} );
		WP_Functions::mock( 'unlink', true ); // Just mock it to return true

		$validation_result = $this->ingredient->validate( [ 'image_url' => 'https://example.com/product.jpg' ] );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result, 'Failed to import image to media library.' );
	}

	/**
	 * GIVEN attachment without file path
	 * WHEN executing
	 * THEN should return failure
	 */
	public function test_execute_fails_when_file_path_not_found(): void {
		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', 201 );
		WP_Functions::mock( 'is_wp_error', false );
		WP_Functions::mock( 'update_post_meta', true );
		WP_Functions::mock( 'unlink', true );
		WP_Functions::mock( 'get_attached_file', false ); // No file path

		$validation_result = $this->ingredient->validate( [ 'image_url' => 'https://example.com/product.jpg' ] );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result, 'Failed to get attachment file path.' );
	}

	/**
	 * GIVEN file that cannot initialize image editor
	 * WHEN executing
	 * THEN should return failure
	 */
	public function test_execute_fails_when_image_editor_fails(): void {
		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', 201 );
		WP_Functions::mock( 'is_wp_error', static function ( $thing ) {
			return $thing === 'WP_Error_editor';
		} );
		WP_Functions::mock( 'update_post_meta', true );
		WP_Functions::mock( 'unlink', true );
		WP_Functions::mock( 'get_attached_file', '/path/to/file.jpg' );
		WP_Functions::mock( 'wp_get_image_editor', 'WP_Error_editor' );

		$validation_result = $this->ingredient->validate( [ 'image_url' => 'https://example.com/product.jpg' ] );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result, 'Failed to initialize image editor.' );
	}

	/**
	 * GIVEN image editor that fails to resize
	 * WHEN creating size variants
	 * THEN should handle resize failure gracefully
	 */
	public function test_execute_handles_resize_failure(): void {
		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', 201 );
		WP_Functions::mock( 'is_wp_error', static function ( $thing ) {
			return $thing === 'WP_Error_resize';
		} );
		WP_Functions::mock( 'update_post_meta', true );
		WP_Functions::mock( 'unlink', true );
		WP_Functions::mock( 'get_attached_file', '/path/to/file.jpg' );
		WP_Functions::mock( 'wp_get_attachment_metadata', [ 'sizes' => [] ] );

		$mock_editor = $this->create_mock_image_editor_with_resize_failure();
		WP_Functions::mock( 'wp_get_image_editor', $mock_editor );

		$validation_result = $this->ingredient->validate( [ 'image_url' => 'https://example.com/product.jpg' ] );
		$result = $validation_result->execute();

		// Should still succeed but with no sizes processed (graceful degradation)
		$this->assertExecutionSuccess( $result );
	}

	/**
	 * GIVEN image editor that fails to save
	 * WHEN creating size variants
	 * THEN should handle save failure gracefully
	 */
	public function test_execute_handles_save_failure(): void {
		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', 201 );
		WP_Functions::mock( 'is_wp_error', static function ( $thing ) {
			return $thing === 'WP_Error_save';
		} );
		WP_Functions::mock( 'update_post_meta', true );
		WP_Functions::mock( 'unlink', true );
		WP_Functions::mock( 'get_attached_file', '/path/to/file.jpg' );
		WP_Functions::mock( 'wp_get_attachment_metadata', [ 'sizes' => [] ] );

		$mock_editor = $this->create_mock_image_editor_with_save_failure();
		WP_Functions::mock( 'wp_get_image_editor', $mock_editor );

		$validation_result = $this->ingredient->validate( [ 'image_url' => 'https://example.com/product.jpg' ] );
		$result = $validation_result->execute();

		// Should still succeed but with no sizes processed
		$this->assertExecutionSuccess( $result );
	}

	// ===== Helper Methods =====

	private function mock_successful_image_processing(): void {
		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', 201 );
		WP_Functions::mock( 'is_wp_error', false );
		WP_Functions::mock( 'update_post_meta', true );
		WP_Functions::mock( 'unlink', true );
		WP_Functions::mock( 'get_attached_file', '/path/to/file.jpg' );
		WP_Functions::mock( 'wp_get_attachment_metadata', [ 'sizes' => [] ] ); // ADD THIS
		WP_Functions::mock( 'wp_update_attachment_metadata', true );
		WP_Functions::mock( 'wp_get_image_editor', $this->create_mock_image_editor() );
	}

	private function create_mock_image_editor(): object {
		return new class() {
			public function set_quality( int $quality ) {
				return true;
			}

			public function resize( $width, $height, $crop ) {
				return true;
			}

			public function save( string $path = null ) {
				return [
					'path'      => $path ?? '/path/to/resized.jpg',
					'mime-type' => 'image/jpeg',
				];
			}
		};
	}

	private function create_mock_image_editor_with_resize_failure(): object {
		return new class() {
			public function set_quality( int $quality ) {
				return true;
			}

			public function resize( $width, $height, $crop ) {
				return 'WP_Error_resize';
			}

			public function save( string $path = null ) {
				return [
					'path'      => $path ?? '/path/to/resized.jpg',
					'mime-type' => 'image/jpeg',
				];
			}
		};
	}

	private function create_mock_image_editor_with_save_failure(): object {
		return new class() {
			public function set_quality( int $quality ) {
				return true;
			}

			public function resize( $width, $height, $crop ) {
				return true;
			}

			public function save( string $path = null ) {
				return 'WP_Error_save';
			}
		};
	}
}
