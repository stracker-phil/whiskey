<?php
/**
 * @covers \Whiskey\IngredientCategory
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\IngredientCategory;

class IngredientCategoryTest extends WhiskeyTest {

	// ===== get_display_name() Tests =====

	/**
	 * GIVEN category constant
	 * WHEN getting display name
	 * THEN should return human-readable name
	 *
	 * @dataProvider display_name_provider
	 */
	public function test_get_display_name( string $category, string $expected_name ): void {
		$this->assertSame( $expected_name, IngredientCategory::get_display_name( $category ) );
	}

	public function display_name_provider(): array {
		return [
			'general'                        => [ IngredientCategory::GENERAL, 'Generic' ],
			'wordpress'                      => [ IngredientCategory::WORDPRESS, 'WordPress Core' ],
			'woocommerce'                    => [ IngredientCategory::WOOCOMMERCE, 'WooCommerce' ],
			'paypal'                         => [
				IngredientCategory::PAYPAL,
				'PayPal Integration',
			],
			'unknown category returns input' => [ 'unknown', 'unknown' ],
		];
	}

	// ===== get_color() Tests =====

	/**
	 * GIVEN valid category constant
	 * WHEN getting color
	 * THEN should return ANSI color code
	 *
	 * @dataProvider valid_categories_provider
	 */
	public function test_get_color_returns_ansi_codes_for_valid_categories( string $category ): void {
		$color = IngredientCategory::get_color( $category );

		$this->assertStringStartsWith( "\033[", $color, "Category {$category} should return ANSI code" );
		$this->assertStringEndsWith( 'm', $color, "Category {$category} should end with 'm'" );
	}

	public function valid_categories_provider(): array {
		return [
			'general'     => [ IngredientCategory::GENERAL ],
			'wordpress'   => [ IngredientCategory::WORDPRESS ],
			'woocommerce' => [ IngredientCategory::WOOCOMMERCE ],
			'paypal'      => [ IngredientCategory::PAYPAL ],
		];
	}

	/**
	 * GIVEN valid categories
	 * WHEN getting colors
	 * THEN each category should have unique color
	 */
	public function test_get_color_returns_unique_colors_per_category(): void {
		$colors = [
			'general'     => IngredientCategory::get_color( IngredientCategory::GENERAL ),
			'wordpress'   => IngredientCategory::get_color( IngredientCategory::WORDPRESS ),
			'woocommerce' => IngredientCategory::get_color( IngredientCategory::WOOCOMMERCE ),
			'paypal'      => IngredientCategory::get_color( IngredientCategory::PAYPAL ),
		];

		$unique_colors = array_unique( $colors );
		$this->assertCount( 4, $unique_colors, 'Each category should have a unique color' );
	}

	/**
	 * GIVEN unknown category
	 * WHEN getting color
	 * THEN should return ANSI reset code
	 */
	public function test_get_color_returns_reset_for_unknown_category(): void {
		$this->assertSame( "\033[0m", IngredientCategory::get_color( 'unknown' ) );
	}

	// ===== all() Tests =====

	/**
	 * GIVEN IngredientCategory class
	 * WHEN getting all categories
	 * THEN should return array containing all category constants
	 */
	public function test_all_returns_complete_category_list(): void {
		$categories = IngredientCategory::all();

		$this->assertIsArray( $categories );
		$this->assertCount( 4, $categories );
		$this->assertContains( IngredientCategory::GENERAL, $categories );
		$this->assertContains( IngredientCategory::WORDPRESS, $categories );
		$this->assertContains( IngredientCategory::WOOCOMMERCE, $categories );
		$this->assertContains( IngredientCategory::PAYPAL, $categories );
	}

	// ===== is_valid() Tests =====

	/**
	 * GIVEN various category strings
	 * WHEN validating category
	 * THEN should return true only for defined categories
	 *
	 * @dataProvider is_valid_provider
	 */
	public function test_is_valid( string $category, bool $expected ): void {
		$this->assertSame( $expected, IngredientCategory::is_valid( $category ) );
	}

	public function is_valid_provider(): array {
		return [
			'general is valid'                 => [ IngredientCategory::GENERAL, true ],
			'wordpress is valid'               => [ IngredientCategory::WORDPRESS, true ],
			'woocommerce is valid'             => [ IngredientCategory::WOOCOMMERCE, true ],
			'paypal is valid'                  => [ IngredientCategory::PAYPAL, true ],
			'unknown is invalid'               => [ 'invalid', false ],
			'empty string is invalid'          => [ '', false ],
			'WordPress capitalized is invalid' => [ 'WordPress', false ],
			'WORDPRESS uppercase is invalid'   => [ 'WORDPRESS', false ],
		];
	}
}
