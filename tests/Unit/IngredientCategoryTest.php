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
	 * GIVEN category enum case
	 * WHEN getting display name
	 * THEN should return human-readable name
	 *
	 * @dataProvider display_name_provider
	 */
	public function test_get_display_name( IngredientCategory $category, string $expected_name ): void {
		$this->assertSame( $expected_name, $category->get_display_name() );
	}

	public function display_name_provider(): array {
		return [
			'general'     => [ IngredientCategory::General, 'Generic' ],
			'wordpress'   => [ IngredientCategory::WordPress, 'WordPress Core' ],
			'woocommerce' => [ IngredientCategory::WooCommerce, 'WooCommerce' ],
			'paypal'      => [ IngredientCategory::PayPal, 'PayPal Integration' ],
		];
	}

	// ===== get_color() Tests =====

	/**
	 * GIVEN valid category enum case
	 * WHEN getting color
	 * THEN should return ANSI color code
	 *
	 * @dataProvider valid_categories_provider
	 */
	public function test_get_color_returns_ansi_codes_for_valid_categories( IngredientCategory $category ): void {
		$color = $category->get_color();

		$this->assertStringStartsWith( "\033[", $color, "Category {$category->value} should return ANSI code" );
		$this->assertStringEndsWith( 'm', $color, "Category {$category->value} should end with 'm'" );
	}

	public function valid_categories_provider(): array {
		return [
			'general'     => [ IngredientCategory::General ],
			'wordpress'   => [ IngredientCategory::WordPress ],
			'woocommerce' => [ IngredientCategory::WooCommerce ],
			'paypal'      => [ IngredientCategory::PayPal ],
		];
	}

	/**
	 * GIVEN valid categories
	 * WHEN getting colors
	 * THEN each category should have unique color (General gets default)
	 */
	public function test_get_color_returns_unique_colors_per_category(): void {
		$colors = [
			'general'     => IngredientCategory::General->get_color(),
			'wordpress'   => IngredientCategory::WordPress->get_color(),
			'woocommerce' => IngredientCategory::WooCommerce->get_color(),
			'paypal'      => IngredientCategory::PayPal->get_color(),
		];

		// WordPress, WooCommerce, and PayPal should have unique colors
		// General uses the default reset code
		$specific_colors = array_filter(
			$colors,
			static fn( $color ) => $color !== "\033[0m"
		);

		$unique_colors = array_unique( $specific_colors );
		$this->assertCount( 3, $unique_colors, 'WordPress, WooCommerce, and PayPal should have unique colors' );
	}

	// ===== Enum Cases Tests =====

	/**
	 * GIVEN IngredientCategory enum
	 * WHEN checking available cases
	 * THEN should have all expected category cases
	 */
	public function test_enum_has_all_category_cases(): void {
		$cases = IngredientCategory::cases();

		$this->assertCount( 4, $cases );
		$this->assertContains( IngredientCategory::General, $cases );
		$this->assertContains( IngredientCategory::WordPress, $cases );
		$this->assertContains( IngredientCategory::WooCommerce, $cases );
		$this->assertContains( IngredientCategory::PayPal, $cases );
	}

	/**
	 * GIVEN string value
	 * WHEN converting to enum
	 * THEN should return correct case or throw exception
	 *
	 * @dataProvider from_string_provider
	 */
	public function test_from_string_creates_correct_case( string $value, ?IngredientCategory $expected ): void {
		if ( $expected === null ) {
			$this->expectException( \ValueError::class );
			IngredientCategory::from( $value );
		} else {
			$this->assertSame( $expected, IngredientCategory::from( $value ) );
		}
	}

	public function from_string_provider(): array {
		return [
			'general is valid'                 => [ 'general', IngredientCategory::General ],
			'wordpress is valid'               => [ 'wordpress', IngredientCategory::WordPress ],
			'woocommerce is valid'             => [ 'woocommerce', IngredientCategory::WooCommerce ],
			'paypal is valid'                  => [ 'paypal', IngredientCategory::PayPal ],
			'unknown throws error'             => [ 'invalid', null ],
			'empty string throws error'        => [ '', null ],
			'WordPress capitalized is invalid' => [ 'WordPress', null ],
			'WORDPRESS uppercase is invalid'   => [ 'WORDPRESS', null ],
		];
	}
}
