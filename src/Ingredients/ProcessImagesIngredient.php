<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\IngredientCategory;
use Whiskey\ValidationResult;

/**
 * Processes an image into multiple variants (thumbnail, hero, gallery, etc.).
 * Group: WordPress core
 */
class ProcessImagesIngredient extends Ingredient {
	public const NAME        = 'process_images';
	public const CATEGORY    = IngredientCategory::WordPress;
	public const DESCRIPTION = 'Processes an image URL into multiple variants; accepts array with image_url and optional alt text';

	public function validate( mixed $value ): ValidationResult {
		if ( ! is_array( $value ) ) {
			return ValidationResult::invalid_type( 'array' );
		}

		if ( ! isset( $value['image_url'] ) ) {
			return ValidationResult::missing_key( 'image_url' );
		}

		if ( ! is_string( $value['image_url'] ) ) {
			return ValidationResult::invalid_type( 'string for image_url' );
		}

		if ( isset( $value['alt'] ) && ! is_string( $value['alt'] ) ) {
			return ValidationResult::invalid_type( 'string for alt text' );
		}

		return ValidationResult::valid( fn() => $this->execute( $value ) );
	}

	private function execute( array $value ): ExecutionResult {
		$url = $value['image_url'];
		$alt = $value['alt'] ?? 'Image';

		// Download image once
		$temp_file = download_url( $url );

		if ( is_wp_error( $temp_file ) ) {
			return new ExecutionResult(
				success: false,
				message: 'Failed to download image from URL.',
				data: [ 'url' => $url ]
			);
		}

		// Generate filename
		$filename = basename( $url );

		// Prepare file array for wp_handle_sideload
		$file_array = [
			'name'     => $filename,
			'tmp_name' => $temp_file,
		];

		// Import into media library - creates ONE attachment post
		$attachment_id = media_handle_sideload( $file_array, 0 );

		// Clean up temp file
		@unlink( $temp_file );

		if ( is_wp_error( $attachment_id ) ) {
			return new ExecutionResult(
				success: false,
				message: 'Failed to import image to media library.',
				data: [ 'url' => $url ]
			);
		}

		// Set alt text
		if ( $alt ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		}

		// Get the original file path
		$file = get_attached_file( $attachment_id );
		if ( ! $file ) {
			return new ExecutionResult(
				success: false,
				message: 'Failed to get attachment file path.',
				data: [ 'attachment_id' => $attachment_id ]
			);
		}

		// Get image editor for processing
		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return new ExecutionResult(
				success: false,
				message: 'Failed to initialize image editor.',
				data: [ 'attachment_id' => $attachment_id ]
			);
		}

		// Get existing metadata
		$metadata = wp_get_attachment_metadata( $attachment_id );
		if ( ! $metadata ) {
			$metadata = [];
		}
		if ( ! isset( $metadata['sizes'] ) ) {
			$metadata['sizes'] = [];
		}

		// CALL SITE 1: Thumbnail - small, cropped, compressed
		$thumb_data = $this->create_image_size(
			editor: $editor,
			original_file: $file,
			size_name: 'whiskey_thumbnail',
			width: 150,
			quality: 70,
			crop: true,
		);

		$metadata['sizes']['whiskey_thumbnail'] = $thumb_data;

		// CALL SITE 2: Hero - large, specific dimensions, high quality
		$hero_data = $this->create_image_size(
			editor: $editor,
			original_file: $file,
			size_name: 'whiskey_hero',
			width: 1920,
			height: 600,
			quality: 95,
		);

		$metadata['sizes']['whiskey_hero'] = $hero_data;

		// CALL SITE 3: Gallery - medium size, maintain aspect ratio
		$gallery_data = $this->create_image_size(
			editor: $editor,
			original_file: $file,
			size_name: 'whiskey_gallery',
			width: 800,
		);

		$metadata['sizes']['whiskey_gallery'] = $gallery_data;

		// CALL SITE 4: Avatar - square crop, webp format
		$avatar_data = $this->create_image_size(
			editor: $editor,
			original_file: $file,
			size_name: 'whiskey_avatar',
			width: 200,
			height: 200,
			crop: true,
			format: 'webp',
		);

		$metadata['sizes']['whiskey_avatar'] = $avatar_data;

		// CALL SITE 5: OG (Open Graph) - specific dimensions with custom crop position
		$og_data = $this->create_image_size(
			editor: $editor,
			original_file: $file,
			size_name: 'whiskey_og',
			width: 1200,
			height: 630,
			crop: true,
			crop_position: [ 'x' => 'center', 'y' => 'top' ]
		);

		$metadata['sizes']['whiskey_og'] = $og_data;

		// Update attachment metadata with all new sizes
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return new ExecutionResult(
			success: true,
			message: 'Successfully processed image with 5 custom variants.',
			data: [
				'attachment_id' => $attachment_id,
				'sizes'         => array_keys( $metadata['sizes'] ),
			]
		);
	}

	/**
	 * Create a single image size variant.
	 *
	 * @param object      $editor        WP_Image_Editor instance
	 * @param string      $original_file Path to original file
	 * @param string      $size_name     Size identifier (e.g., 'whiskey_thumbnail')
	 * @param int         $width         Target width
	 * @param int|null    $height        Target height (null = maintain aspect ratio)
	 * @param int|null    $quality       JPEG quality 1-100
	 * @param bool|null   $crop          Crop to exact dimensions
	 * @param string|null $format        Output format: jpg, png, webp
	 * @param array|null  $crop_position Crop position: ['x' => 'center', 'y' => 'top']
	 * @return string|false Size string or false on failure
	 */
	private function create_image_size(
		object $editor,
		string $original_file,
		string $size_name,
		int $width,
		?int $height = null,
		?int $quality = 85,
		?bool $crop = false,
		?string $format = 'jpg',
		?array $crop_position = null
	): bool|string {
		// Clone editor to avoid modifying original
		$size_editor = clone $editor;

		// Set quality
		if ( $quality ) {
			$size_editor->set_quality( $quality );
		}

		// Calculate height if not provided (maintain aspect ratio)
		$actual_height = $height ?? 0;

		// Apply resize/crop
		if ( $crop ) {
			if ( $crop_position ) {
				$resize_result = $size_editor->resize( $width, $actual_height, $crop_position );
			} else {
				$resize_result = $size_editor->resize( $width, $actual_height, $crop );
			}
		} else {
			// Resize without cropping
			$resize_result = $size_editor->resize( $width, $actual_height, false );
		}

		if ( is_wp_error( $resize_result ) ) {
			return false;
		}

		// Generate filename for this size
		$path_info = pathinfo( $original_file );
		$suffix    = $width . 'x' . $actual_height;
		$extension = $format ?? $path_info['extension'];
		$new_file  = $path_info['dirname'] . '/' .
			$path_info['filename'] . '-' . $size_name . '-' . $suffix . '.' . $extension;

		// Save the size
		$saved = $size_editor->save( $new_file );

		if ( is_wp_error( $saved ) ) {
			return false;
		}

		return "{$width}x{$actual_height}";
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, ProcessImagesIngredient::class ]
);
