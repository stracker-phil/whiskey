<?php
/**
 * @covers \Whiskey\Ingredients\PayPalSetPreviousVersionIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\PayPalSetPreviousVersionIngredient;
use WP_Functions;

class PayPalSetPreviousVersionIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return PayPalSetPreviousVersionIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'paypal_set_previous_version';
	}

	protected function getExpectedCategory(): string {
		return 'paypal';
	}

	// ===== Validation Tests =====

	/**
	 * GIVEN various version string formats
	 * WHEN validating
	 * THEN should accept valid semantic versions or empty string
	 *
	 * @dataProvider validation_provider
	 */
	public function test_validate( $input, bool $expected_valid ): void {
		$result = $this->ingredient->validate( $input );

		$this->assertSame( $expected_valid, $result->is_valid() );
	}

	public function validation_provider(): array {
		return [
			// Valid formats
			'semantic version'            => [ '2.0.0', true ],
			'version with patch'          => [ '1.2.3', true ],
			'version with prerelease'     => [ '2.5.0-beta', true ],
			'version with build metadata' => [ '1.0.0+20130313144700', true ],
			'complex version'             => [ '1.0.0-alpha.1+build.123', true ],
			'empty string'                => [ '', true ],

			// Invalid formats
			'major.minor only'            => [ '2.0', false ],
			'major only'                  => [ '2', false ],
			'non-numeric version'         => [ 'abc.def.ghi', false ],
			'v prefix'                    => [ 'v2.0.0', false ],
			'integer'                     => [ 200, false ],
			'float'                       => [ 2.0, false ],
			'array'                       => [ [ '2.0.0' ], false ],
			'boolean'                     => [ true, false ],
			'null'                        => [ null, false ],
		];
	}

	// ===== Execution Tests: Setting Versions =====

	/**
	 * GIVEN version string with various previous states
	 * WHEN executing
	 * THEN should update version and return appropriate state
	 *
	 * @dataProvider execute_set_version_provider
	 */
	public function test_execute_sets_version(
		string $new_version,
		$previous_value,
		bool $update_return,
		string $expected_current,
		$expected_previous
	): void {
		WP_Functions::mock( 'get_option', $previous_value );
		WP_Functions::mock( 'update_option', $update_return );

		$result = $this->ingredient->execute( $new_version );

		$this->assertExecutionSuccess( $result );

		$data = $result->get_data();
		$this->assertSame( $expected_current, $data['current'] );
		$this->assertSame( $expected_previous, $data['previous'] );
		$this->assertSame( 'woocommerce-ppcp-version', $data['option'] );
	}

	public function execute_set_version_provider(): array {
		return [
			'set new version'         => [
				'2.0.0',
				'',
				true,
				'2.0.0',
				'',
			],
			'update existing version' => [
				'2.0.0',
				'1.5.0',
				true,
				'2.0.0',
				'1.5.0',
			],
			'version unchanged'       => [
				'2.0.0',
				'2.0.0',
				false, // update_option returns false when unchanged
				'2.0.0',
				'2.0.0',
			],
			'prerelease version'      => [
				'2.0.0-beta.1',
				'',
				true,
				'2.0.0-beta.1',
				'',
			],
			'build metadata'          => [
				'1.0.0+20130313',
				'',
				true,
				'1.0.0+20130313',
				'',
			],
		];
	}

	/**
	 * GIVEN version string and update failure
	 * WHEN executing
	 * THEN should return failure
	 */
	public function test_execute_fails_when_update_fails(): void {
		WP_Functions::mock( 'get_option', '' );
		WP_Functions::mock( 'update_option', false );

		$result = $this->ingredient->execute( '2.0.0' );

		$this->assertExecutionFailure( $result );

		$data = $result->get_data();
		$this->assertArrayHasKey( 'requested', $data );
		$this->assertSame( '2.0.0', $data['requested'] );
		$this->assertSame( 'woocommerce-ppcp-version', $data['option'] );
	}

	// ===== Execution Tests: Deleting Version (Empty String) =====

	/**
	 * GIVEN empty string with various previous states
	 * WHEN executing
	 * THEN should delete option and return appropriate state
	 *
	 * @dataProvider execute_delete_version_provider
	 */
	public function test_execute_deletes_option_on_empty_string(
		$previous_value,
		bool $delete_return,
		$expected_previous
	): void {
		WP_Functions::mock( 'get_option', $previous_value );
		WP_Functions::mock( 'delete_option', $delete_return );

		$result = $this->ingredient->execute( '' );

		$this->assertExecutionSuccess( $result );

		$data = $result->get_data();
		$this->assertSame( 'deleted', $data['action'] );
		$this->assertSame( $expected_previous, $data['previous'] );
		$this->assertSame( 'woocommerce-ppcp-version', $data['option'] );
	}

	public function execute_delete_version_provider(): array {
		return [
			'delete existing version' => [
				'2.0.0',
				true,
				'2.0.0',
			],
			'delete already empty'    => [
				'',
				false, // delete_option returns false when option doesn't exist
				'',
			],
		];
	}

	/**
	 * GIVEN empty string and delete failure
	 * WHEN executing
	 * THEN should return failure
	 */
	public function test_execute_fails_when_delete_fails(): void {
		WP_Functions::mock( 'get_option', '2.0.0' );
		WP_Functions::mock( 'delete_option', false );

		$result = $this->ingredient->execute( '' );

		$this->assertExecutionFailure( $result );

		$data = $result->get_data();
		$this->assertSame( '2.0.0', $data['previous'] );
		$this->assertSame( 'woocommerce-ppcp-version', $data['option'] );
	}
}
