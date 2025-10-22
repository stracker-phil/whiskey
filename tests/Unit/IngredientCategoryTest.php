<?php
/**
 * @covers \Whiskey\IngredientCategory
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\IngredientCategory;

class IngredientCategoryTest extends WhiskeyTest {

	public function test_constants_are_strings(): void {
		$this->assertIsString( IngredientCategory::WORDPRESS );
		$this->assertIsString( IngredientCategory::WOOCOMMERCE );
		$this->assertIsString( IngredientCategory::PAYPAL );
	}

	public function test_constants_have_expected_values(): void {
		$this->assertSame( 'wordpress', IngredientCategory::WORDPRESS );
		$this->assertSame( 'woocommerce', IngredientCategory::WOOCOMMERCE );
		$this->assertSame( 'paypal', IngredientCategory::PAYPAL );
	}

	public function test_get_display_name_returns_correct_names(): void {
		$this->assertSame( 'WordPress Core', IngredientCategory::get_display_name( IngredientCategory::WORDPRESS ) );
		$this->assertSame( 'WooCommerce', IngredientCategory::get_display_name( IngredientCategory::WOOCOMMERCE ) );
		$this->assertSame( 'PayPal Integration', IngredientCategory::get_display_name( IngredientCategory::PAYPAL ) );
	}

	public function test_get_display_name_returns_input_for_unknown_category(): void {
		$this->assertSame( 'unknown', IngredientCategory::get_display_name( 'unknown' ) );
	}

	public function test_get_color_returns_ansi_codes(): void {
		$this->assertStringContainsString( "\033[", IngredientCategory::get_color( IngredientCategory::WORDPRESS ) );
		$this->assertStringContainsString( "\033[", IngredientCategory::get_color( IngredientCategory::WOOCOMMERCE ) );
		$this->assertStringContainsString( "\033[", IngredientCategory::get_color( IngredientCategory::PAYPAL ) );
	}

	public function test_get_color_returns_different_codes_for_each_category(): void {
		$wordpress_color   = IngredientCategory::get_color( IngredientCategory::WORDPRESS );
		$woocommerce_color = IngredientCategory::get_color( IngredientCategory::WOOCOMMERCE );
		$paypal_color      = IngredientCategory::get_color( IngredientCategory::PAYPAL );

		$this->assertNotSame( $wordpress_color, $woocommerce_color );
		$this->assertNotSame( $wordpress_color, $paypal_color );
		$this->assertNotSame( $woocommerce_color, $paypal_color );
	}

	public function test_get_color_returns_reset_for_unknown_category(): void {
		$this->assertSame( "\033[0m", IngredientCategory::get_color( 'unknown' ) );
	}

	public function test_all_returns_array_of_categories(): void {
		$categories = IngredientCategory::all();

		$this->assertIsArray( $categories );
		$this->assertCount( 3, $categories );
		$this->assertContains( IngredientCategory::WORDPRESS, $categories );
		$this->assertContains( IngredientCategory::WOOCOMMERCE, $categories );
		$this->assertContains( IngredientCategory::PAYPAL, $categories );
	}

	public function test_is_valid_returns_true_for_valid_categories(): void {
		$this->assertTrue( IngredientCategory::is_valid( IngredientCategory::WORDPRESS ) );
		$this->assertTrue( IngredientCategory::is_valid( IngredientCategory::WOOCOMMERCE ) );
		$this->assertTrue( IngredientCategory::is_valid( IngredientCategory::PAYPAL ) );
	}

	public function test_is_valid_returns_false_for_invalid_categories(): void {
		$this->assertFalse( IngredientCategory::is_valid( 'invalid' ) );
		$this->assertFalse( IngredientCategory::is_valid( 'unknown' ) );
		$this->assertFalse( IngredientCategory::is_valid( '' ) );
	}

	public function test_is_valid_is_case_sensitive(): void {
		$this->assertFalse( IngredientCategory::is_valid( 'WordPress' ) );
		$this->assertFalse( IngredientCategory::is_valid( 'WORDPRESS' ) );
	}
}