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

	public function test_validate_accepts_array_with_image_url(): void {
		$this->assertValidationAccepts( [ 'image_url' => 'https://example.com/image.jpg' ] );
	}

	public function test_validate_accepts_array_with_image_url_and_alt(): void {
		$this->assertValidationAccepts(
			[
				'image_url' => 'https://example.com/image.jpg',
				'alt'       => 'Product photo',
			]
		);
	}

	public function test_validate_rejects_string(): void {
		$this->assertValidationRejects( 'https://example.com/image.jpg' );
	}

	public function test_validate_rejects_array_without_image_url(): void {
		$this->assertValidationRejects( [ 'alt' => 'Product photo' ] );
	}

	public function test_validate_rejects_non_string_image_url(): void {
		$this->assertValidationRejects( [ 'image_url' => 123 ] );
	}

	public function test_validate_rejects_non_string_alt_text(): void {
		$this->assertValidationRejects(
			[
				'image_url' => 'https://example.com/image.jpg',
				'alt'       => 123,
			]
		);
	}

	// ===== Execution Tests =====

	public function test_execute_processes_all_five_variants(): void {
		$this->mock_successful_image_processing();

		$result = $this->ingredient->execute(
			[
				'image_url' => 'https://example.com/product.jpg',
				'alt'       => 'Product image',
			]
		);

		$this->assertExecutionSuccess( $result, 'Successfully processed 5 image variants' );
		$data = $result->get_data();

		$this->assertArrayHasKey( 'thumbnail', $data );
		$this->assertArrayHasKey( 'hero', $data );
		$this->assertArrayHasKey( 'gallery', $data );
		$this->assertArrayHasKey( 'avatar', $data );
		$this->assertArrayHasKey( 'og', $data );

		$this->assertSame( 101, $data['thumbnail'] );
		$this->assertSame( 102, $data['hero'] );
		$this->assertSame( 103, $data['gallery'] );
		$this->assertSame( 104, $data['avatar'] );
		$this->assertSame( 105, $data['og'] );
	}

	public function test_execute_uses_default_alt_when_not_provided(): void {
		$this->mock_successful_image_processing();

		$result = $this->ingredient->execute( [ 'image_url' => 'https://example.com/product.jpg' ] );

		$this->assertExecutionSuccess( $result );
	}

	public function test_execute_fails_when_thumbnail_processing_fails(): void {
		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', 0 ); // Fail on first call
		WP_Functions::mock( 'is_wp_error', false );
		WP_Functions::mock( 'unlink', true );

		$result = $this->ingredient->execute( [ 'image_url' => 'https://example.com/product.jpg' ] );

		$this->assertExecutionFailure( $result, 'Failed to process thumbnail image' );
	}

	public function test_execute_fails_when_hero_processing_fails(): void {
		$call_count = 0;
		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', static function ( $file, $post_id ) use ( &$call_count ) {
			$call_count++;
			return $call_count === 1 ? 101 : 0; // Success first, fail second
		} );
		WP_Functions::mock( 'is_wp_error', false );
		WP_Functions::mock( 'update_post_meta', true );
		WP_Functions::mock( 'get_attached_file', '/path/to/file.jpg' );
		WP_Functions::mock( 'wp_get_image_editor', $this->create_mock_image_editor() );
		WP_Functions::mock( 'unlink', true );

		$result = $this->ingredient->execute(
			[
				'image_url' => 'https://example.com/product.jpg',
				'alt'       => 'Product',
			]
		);

		$this->assertExecutionFailure( $result, 'Failed to process hero image' );
		$data = $result->get_data();
		$this->assertSame( 101, $data['thumbnail'] );
	}

	public function test_execute_fails_when_gallery_processing_fails(): void {
		$call_count = 0;
		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', static function ( $file, $post_id ) use ( &$call_count ) {
			$call_count++;
			return $call_count <= 2 ? 100 + $call_count : 0; // Success first two, fail third
		} );
		WP_Functions::mock( 'is_wp_error', false );
		WP_Functions::mock( 'update_post_meta', true );
		WP_Functions::mock( 'get_attached_file', '/path/to/file.jpg' );
		WP_Functions::mock( 'wp_get_image_editor', $this->create_mock_image_editor() );
		WP_Functions::mock( 'unlink', true );

		$result = $this->ingredient->execute( [ 'image_url' => 'https://example.com/product.jpg' ] );

		$this->assertExecutionFailure( $result, 'Failed to process gallery image' );
		$data = $result->get_data();
		$this->assertSame( 101, $data['thumbnail'] );
		$this->assertSame( 102, $data['hero'] );
	}

	public function test_execute_handles_download_failure(): void {
		WP_Functions::mock( 'download_url', 'WP_Error_object' );
		WP_Functions::mock( 'is_wp_error', true );

		$result = $this->ingredient->execute( [ 'image_url' => 'https://example.com/product.jpg' ] );

		$this->assertExecutionFailure( $result, 'Failed to process thumbnail image' );
	}

	public function test_execute_sets_alt_text_on_gallery_variant(): void {
		$alt_calls = [];
		$this->mock_successful_image_processing();
		WP_Functions::mock( 'update_post_meta', static function ( $id, $key, $value ) use ( &$alt_calls ) {
			if ( $key === '_wp_attachment_image_alt' ) {
				$alt_calls[] = [ $id, $value ];
			}
			return true;
		} );

		$result = $this->ingredient->execute(
			[
				'image_url' => 'https://example.com/product.jpg',
				'alt'       => 'Premium Soap',
			]
		);

		$this->assertExecutionSuccess( $result );

		// Should set alt text on gallery variant (third image)
		$has_gallery_alt = false;
		foreach ( $alt_calls as $call ) {
			if ( $call[0] === 103 && $call[1] === 'Premium Soap' ) {
				$has_gallery_alt = true;
				break;
			}
		}
		$this->assertTrue( $has_gallery_alt, 'Alt text should be set on gallery variant' );
	}

	// ===== Helper Methods =====

	private function mock_successful_image_processing(): void {
		$call_count = 0;

		WP_Functions::mock( 'download_url', '/tmp/image.jpg' );
		WP_Functions::mock( 'media_handle_sideload', static function ( $file, $post_id ) use ( &$call_count ) {
			$call_count++;
			return 100 + $call_count;
		} );
		WP_Functions::mock( 'is_wp_error', false );
		WP_Functions::mock( 'update_post_meta', true );
		WP_Functions::mock( 'get_attached_file', '/path/to/file.jpg' );
		WP_Functions::mock( 'wp_get_image_editor', $this->create_mock_image_editor() );
	}

	private function create_mock_image_editor(): object {
		return new class() {
			public function resize( $width, $height, $crop ) {
				return true;
			}

			public function set_quality( int $quality ) {
				return true;
			}

			public function save() {
				return [ 'path' => '/path/to/resized.jpg' ];
			}
		};
	}
}
