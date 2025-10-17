<?php
/**
 * @covers \Whiskey\Ingredients\SetActiveThemeIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\SetActiveThemeIngredient;
use WP_Functions;
use WP_Theme;

class SetActiveThemeIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return SetActiveThemeIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'set_active_theme';
	}

	protected function getExpectedCategory(): string {
		return 'wordpress';
	}

	public function test_validate_accepts_string(): void {
		$this->assertValidationAccepts( 'twentytwentyfour' );
	}

	public function test_validate_accepts_theme_with_hyphen(): void {
		$this->assertValidationAccepts( 'storefront-child' );
	}

	public function test_validate_rejects_integer(): void {
		$this->assertValidationRejects( 123 );
	}

	public function test_validate_rejects_array(): void {
		$this->assertValidationRejects( [ 'invalid' ] );
	}

	public function test_validate_rejects_boolean(): void {
		$this->assertValidationRejects( true );
	}

	public function test_validate_rejects_empty_string(): void {
		$this->assertValidationRejects( '' );
	}

	public function test_execute_activates_existing_theme(): void {
		$previous_theme = $this->createMockTheme( 'twentytwentythree' );
		$new_theme      = $this->createMockTheme( 'twentytwentyfour' );
		$switched       = false;

		WP_Functions::mock( 'wp_get_theme', function ( $stylesheet = null ) use ( $previous_theme, $new_theme, &$switched ) {
			if ( $stylesheet === 'twentytwentyfour' ) {
				return $new_theme;
			}

			// Before switch, return previous theme; after switch, return new theme
			return $switched ? $new_theme : $previous_theme;
		} );
		WP_Functions::mock( 'switch_theme', static function () use ( &$switched ) {
			$switched = true;
		} );

		$result = $this->ingredient->execute( 'twentytwentyfour' );

		$this->assertExecutionSuccess( $result, 'activated successfully' );
		$this->assertSame( 'twentytwentythree', $result->get_data()['previous'] );
		$this->assertSame( 'twentytwentyfour', $result->get_data()['current'] );
	}

	public function test_execute_fails_when_theme_does_not_exist(): void {
		$non_existent_theme = $this->createMockTheme( 'nonexistent', false );

		WP_Functions::mock( 'wp_get_theme', fn( $stylesheet ) => $non_existent_theme );

		$result = $this->ingredient->execute( 'nonexistent' );

		$this->assertExecutionFailure( $result, 'does not exist' );
		$this->assertSame( 'nonexistent', $result->get_data()['theme'] );
	}

	public function test_execute_fails_when_switch_theme_fails(): void {
		$previous_theme  = $this->createMockTheme( 'twentytwentythree' );
		$requested_theme = $this->createMockTheme( 'twentytwentyfour' );

		WP_Functions::mock( 'wp_get_theme', function ( $stylesheet = null ) use ( $previous_theme, $requested_theme ) {
			if ( $stylesheet === 'twentytwentyfour' ) {
				return $requested_theme;
			}

			// After switch_theme, still return the previous theme (simulating failure)
			return $previous_theme;
		} );
		WP_Functions::mock( 'switch_theme' );

		$result = $this->ingredient->execute( 'twentytwentyfour' );

		$this->assertExecutionFailure( $result, 'Failed to activate' );
		$this->assertSame( 'twentytwentyfour', $result->get_data()['requested'] );
		$this->assertSame( 'twentytwentythree', $result->get_data()['previous'] );
	}

	public function test_execute_returns_previous_and_current_theme(): void {
		$previous_theme = $this->createMockTheme( 'storefront' );
		$new_theme      = $this->createMockTheme( 'astra' );
		$switched       = false;

		WP_Functions::mock( 'wp_get_theme', function ( $stylesheet = null ) use ( $previous_theme, $new_theme, &$switched ) {
			if ( $stylesheet === 'astra' ) {
				return $new_theme;
			}

			// Before switch, return previous theme; after switch, return new theme
			return $switched ? $new_theme : $previous_theme;
		} );
		WP_Functions::mock( 'switch_theme', static function () use ( &$switched ) {
			$switched = true;
		} );

		$result = $this->ingredient->execute( 'astra' );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'previous', $data );
		$this->assertArrayHasKey( 'current', $data );
		$this->assertSame( 'storefront', $data['previous'] );
		$this->assertSame( 'astra', $data['current'] );
	}

	/**
	 * Create a mock WP_Theme object
	 */
	private function createMockTheme( string $stylesheet, bool $exists = true ): WP_Theme {
		$theme = $this->createMock( WP_Theme::class );
		$theme->method( 'exists' )->willReturn( $exists );
		$theme->method( 'get_stylesheet' )->willReturn( $stylesheet );

		return $theme;
	}
}
