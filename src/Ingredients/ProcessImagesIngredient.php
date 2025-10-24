<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\IngredientCategory;
use Whiskey\ValidationResult;

/**
 * Processes an image into multiple variants (thumbnail, hero, gallery, etc.).
 * Demonstrates PHP 8.0 named arguments by calling the same helper method
 * with different parameter combinations for each variant.
 * Group: WordPress core
 */
class ProcessImagesIngredient extends Ingredient {
	public const NAME        = 'process_images';
	public const CATEGORY    = IngredientCategory::WORDPRESS;
	public const DESCRIPTION = 'Processes an image URL into multiple variants; accepts array with image_url and optional alt text';

	public function validate( $value ): ValidationResult {
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

		return ValidationResult::valid();
	}

	public function execute( $value ): ExecutionResult {
		$url     = $value['image_url'];
		$alt     = $value['alt'] ?? 'Image';
		$results = [];

		// CALL SITE 1: Thumbnail - small, cropped, compressed
		// PHP 7.4: Must pass null for height, format, alt_text, crop_position
		$thumb = $this->process_image(
			$url,
			150,
			null,
			70,
			true,
			'jpg',
			null,
			null
		);

		if ( $thumb === 0 ) {
			return new ExecutionResult(
				false,
				'Failed to process thumbnail image.',
				[ 'variant' => 'thumbnail' ]
			);
		}
		$results['thumbnail'] = $thumb;

		// CALL SITE 2: Hero - large, specific dimensions, high quality
		// PHP 7.4: Must pass null for crop, format, alt_text, crop_position
		$hero = $this->process_image(
			$url,
			1920,
			600,
			95,
			false,
			'jpg',
			null,
			null
		);

		if ( $hero === 0 ) {
			return new ExecutionResult(
				false,
				'Failed to process hero image.',
				[ 'variant' => 'hero', 'thumbnail' => $thumb ]
			);
		}
		$results['hero'] = $hero;

		// CALL SITE 3: Gallery - medium size with alt text
		// PHP 7.4: Must pass null for height, quality, crop, format, crop_position
		// just to reach alt_text parameter!
		$gallery = $this->process_image(
			$url,
			800,
			null,
			85,
			false,
			'jpg',
			$alt,
			null
		);

		if ( $gallery === 0 ) {
			return new ExecutionResult(
				false,
				'Failed to process gallery image.',
				[ 'variant' => 'gallery', 'thumbnail' => $thumb, 'hero' => $hero ]
			);
		}
		$results['gallery'] = $gallery;

		// CALL SITE 4: Avatar - square crop, webp format
		// PHP 7.4: Must pass null for quality, alt_text, crop_position
		$avatar = $this->process_image(
			$url,
			200,
			200,
			85,
			true,
			'webp',
			null,
			null
		);

		if ( $avatar === 0 ) {
			return new ExecutionResult(
				false,
				'Failed to process avatar image.',
				[
					'variant'   => 'avatar',
					'thumbnail' => $thumb,
					'hero'      => $hero,
					'gallery'   => $gallery,
				]
			);
		}
		$results['avatar'] = $avatar;

		// CALL SITE 5: OG (Open Graph) - specific dimensions with custom crop position
		// PHP 7.4: Must pass null for quality, format, alt_text to reach crop_position
		$og = $this->process_image(
			$url,
			1200,
			630,
			85,
			true,
			'jpg',
			null,
			[ 'x' => 'center', 'y' => 'top' ]
		);

		if ( $og === 0 ) {
			return new ExecutionResult(
				false,
				'Failed to process OG image.',
				[
					'variant'   => 'og',
					'thumbnail' => $thumb,
					'hero'      => $hero,
					'gallery'   => $gallery,
					'avatar'    => $avatar,
				]
			);
		}
		$results['og'] = $og;

		return new ExecutionResult(
			true,
			'Successfully processed 5 image variants.',
			$results
		);
	}

	/**
	 * Process an image with specified parameters.
	 *
	 * This method demonstrates PHP 8.0 named arguments. In PHP 7.4, callers
	 * would need to pass null for every skipped parameter. With PHP 8.0,
	 * callers can skip optional parameters in the middle of the signature.
	 *
	 * @param string      $url           Image URL to process
	 * @param int|null    $width         Target width (null = original)
	 * @param int|null    $height        Target height (null = original)
	 * @param int|null    $quality       JPEG quality 1-100
	 * @param bool|null   $crop          Crop to exact dimensions
	 * @param string|null $format        Output format: jpg, png, webp
	 * @param string|null $alt_text      Alt text for accessibility
	 * @param array|null  $crop_position Crop position: ['x' => 'center', 'y' => 'top']
	 * @return int Attachment ID, or 0 on failure
	 */
	private function process_image(
		string $url,
		?int $width = null,
		?int $height = null,
		?int $quality = 85,
		?bool $crop = false,
		?string $format = 'jpg',
		?string $alt_text = null,
		?array $crop_position = null
	): int {
		// Download image from URL
		$temp_file = download_url( $url );

		if ( is_wp_error( $temp_file ) ) {
			return 0;
		}

		// Generate filename
		$filename = basename( $url );
		if ( $format !== 'jpg' ) {
			$filename = preg_replace( '/\.[^.]+$/', '.' . $format, $filename );
		}

		// Prepare file array for wp_handle_sideload
		$file_array = [
			'name'     => $filename,
			'tmp_name' => $temp_file,
		];

		// Import into media library
		$attachment_id = media_handle_sideload( $file_array, 0 );

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $temp_file );
			return 0;
		}

		// Set alt text if provided
		if ( $alt_text ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		}

		// If no dimensions specified, return original
		if ( ! $width && ! $height ) {
			return $attachment_id;
		}

		// Generate resized version
		$file = get_attached_file( $attachment_id );
		if ( ! $file ) {
			return 0;
		}

		$editor = wp_get_image_editor( $file );
		if ( is_wp_error( $editor ) ) {
			return $attachment_id;
		}

		// Apply resize/crop
		if ( $crop ) {
			if ( $crop_position ) {
				$editor->resize( $width, $height, $crop_position );
			} else {
				$editor->resize( $width, $height, $crop );
			}
		} else {
			$editor->resize( $width, $height, false );
		}

		// Set quality
		if ( $quality ) {
			$editor->set_quality( $quality );
		}

		// Save
		$saved = $editor->save();
		if ( is_wp_error( $saved ) ) {
			return $attachment_id;
		}

		return $attachment_id;
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, ProcessImagesIngredient::class ]
);
