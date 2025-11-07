<?php
/**
 * @covers \Whiskey\Ingredients\SetPayPalMerchantIngredient
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Ingredients\SetPayPalMerchantIngredient;
use WP_Functions;

class SetPayPalMerchantIngredientTest extends IngredientTest {

	protected function getIngredientClass(): string {
		return SetPayPalMerchantIngredient::class;
	}

	protected function getExpectedName(): string {
		return 'set_paypal_merchant';
	}

	protected function getExpectedCategory(): string {
		return 'paypal';
	}

	// ===== Validation Tests =====

	/**
	 * GIVEN various input configurations
	 * WHEN validating
	 * THEN should accept valid merchant data and false, reject invalid inputs
	 *
	 * @dataProvider validation_provider
	 */
	public function test_validate( $input, bool $expected_valid ): void {
		$result = $this->ingredient->validate( $input );

		$this->assertSame( $expected_valid, $result->is_valid() );
	}

	public function validation_provider(): array {
		$valid_base = array(
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		);

		return array(
			'complete merchant data'      => array( $valid_base, true ),
			'with country'                => array(
				array_merge( $valid_base, array( 'merchant_country' => 'US' ) ),
				true,
			),
			'with casual_seller true'     => array(
				array_merge( $valid_base, array( 'casual_seller' => true ) ),
				true,
			),
			'with casual_seller false'    => array(
				array_merge( $valid_base, array( 'casual_seller' => false ) ),
				true,
			),
			'all optional fields'         => array(
				array_merge( $valid_base, array(
					'merchant_country' => 'US',
					'casual_seller'    => true,
				) ),
				true,
			),
			'false value accepted'        => array( false, true ),

			// Missing required fields
			'missing merchant_id'         => array(
				array(
					'merchant_email' => 'merchant@example.com',
					'client_id'      => 'CLIENT123',
					'client_secret'  => 'SECRET123',
				),
				false,
			),
			'missing merchant_email'      => array(
				array(
					'merchant_id'   => 'MERCHANT123',
					'client_id'     => 'CLIENT123',
					'client_secret' => 'SECRET123',
				),
				false,
			),
			'missing client_id'           => array(
				array(
					'merchant_id'    => 'MERCHANT123',
					'merchant_email' => 'merchant@example.com',
					'client_secret'  => 'SECRET123',
				),
				false,
			),
			'missing client_secret'       => array(
				array(
					'merchant_id'    => 'MERCHANT123',
					'merchant_email' => 'merchant@example.com',
					'client_id'      => 'CLIENT123',
				),
				false,
			),

			// Invalid types for required fields
			'non-string merchant_id'      => array(
				array_merge( $valid_base, array( 'merchant_id' => 123 ) ),
				false,
			),
			'non-string merchant_email'   => array(
				array_merge( $valid_base, array( 'merchant_email' => 123 ) ),
				false,
			),

			// Invalid types for optional fields
			'non-boolean casual_seller'   => array(
				array_merge( $valid_base, array( 'casual_seller' => 'true' ) ),
				false,
			),
			'non-string merchant_country' => array(
				array_merge( $valid_base, array( 'merchant_country' => 123 ) ),
				false,
			),

			// Wrong input types
			'string rejected'             => array( 'MERCHANT123', false ),
			'integer rejected'            => array( 123, false ),
			'true rejected'               => array( true, false ),
			'empty array rejected'        => array( array(), false ),
		);
	}

	// ===== Execution Tests - Basic Configuration =====

	/**
	 * GIVEN valid merchant configuration
	 * WHEN executing
	 * THEN should update modern UI option with merchant data and sandbox mode
	 */
	public function test_execute_updates_modern_ui_option(): void {
		WP_Functions::mock( 'get_option', array() );
		WP_Functions::mock( 'update_option', true );

		$config = array(
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		);

		$validation_result = $this->ingredient->validate( $config );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( 'MERCHANT123', $data['merchant_id'] );
		$this->assertSame( 'merchant@example.com', $data['merchant_email'] );
		$this->assertTrue( $data['sandbox_mode'] );
	}

	/**
	 * GIVEN valid merchant configuration
	 * WHEN executing
	 * THEN should set onboarding flags appropriately
	 */
	public function test_execute_sets_onboarding_flags(): void {
		$onboarding_data = array();

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$onboarding_data ) {
			if ( $option === 'woocommerce-ppcp-data-onboarding' ) {
				return $onboarding_data;
			}

			return array();
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$onboarding_data ) {
			if ( $option === 'woocommerce-ppcp-data-onboarding' ) {
				$onboarding_data = $value;
			}

			return true;
		} );

		$config = array(
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		);

		$validation_result = $this->ingredient->validate( $config );
		$validation_result->execute();

		$this->assertTrue( $onboarding_data['completed'] );
		$this->assertFalse( $onboarding_data['setup_done'] );
		$this->assertFalse( $onboarding_data['gateways_synced'] );
		$this->assertFalse( $onboarding_data['gateways_refreshed'] );
	}

	/**
	 * GIVEN valid merchant configuration
	 * WHEN executing
	 * THEN should update legacy UI option with merchant data
	 */
	public function test_execute_updates_legacy_ui_option(): void {
		$legacy_data = array();

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$legacy_data ) {
			if ( $option === 'woocommerce-ppcp-settings' ) {
				return $legacy_data;
			}

			return array();
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$legacy_data ) {
			if ( $option === 'woocommerce-ppcp-settings' ) {
				$legacy_data = $value;
			}

			return true;
		} );

		$config = array(
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		);

		$validation_result = $this->ingredient->validate( $config );
		$validation_result->execute();

		$this->assertSame( 'MERCHANT123', $legacy_data['merchant_id'] );
		$this->assertSame( 'merchant@example.com', $legacy_data['merchant_email'] );
		$this->assertSame( 'CLIENT123', $legacy_data['client_id'] );
		$this->assertSame( 'SECRET123', $legacy_data['client_secret'] );
		$this->assertSame( 'MERCHANT123', $legacy_data['merchant_id_sandbox'] );
		$this->assertSame( 'merchant@example.com', $legacy_data['merchant_email_sandbox'] );
	}

	// ===== Execution Tests - Optional Fields =====

	/**
	 * GIVEN merchant configuration with optional fields
	 * WHEN executing
	 * THEN should handle merchant_country and casual_seller correctly
	 *
	 * @dataProvider optional_fields_provider
	 */
	public function test_execute_handles_optional_fields(
		array $config,
		string $expected_country,
		string $expected_seller_type
	): void {
		$modern_data = array();

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				return $modern_data;
			}

			return array();
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$modern_data = $value;
			}

			return true;
		} );

		$validation_result = $this->ingredient->validate( $config );
		$validation_result->execute();

		$this->assertSame( $expected_country, $modern_data['merchant_country'] );
		$this->assertSame( $expected_seller_type, $modern_data['seller_type'] );
	}

	public function optional_fields_provider(): array {
		$base_config = array(
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		);

		return array(
			'no optional fields defaults to empty country and business' => array(
				$base_config,
				'',
				'business',
			),
			'with merchant_country'                                     => array(
				array_merge( $base_config, array( 'merchant_country' => 'US' ) ),
				'US',
				'business',
			),
			'casual_seller true sets personal'                          => array(
				array_merge( $base_config, array( 'casual_seller' => true ) ),
				'',
				'personal',
			),
			'casual_seller false sets business'                         => array(
				array_merge( $base_config, array( 'casual_seller' => false ) ),
				'',
				'business',
			),
		);
	}

	// ===== Execution Tests - Edge Cases =====

	/**
	 * GIVEN existing option data
	 * WHEN executing
	 * THEN should preserve existing keys not related to merchant data
	 */
	public function test_execute_preserves_existing_option_data(): void {
		$modern_data = array( 'existing_key' => 'existing_value' );
		$legacy_data = array( 'other_key' => 'other_value' );

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$modern_data, &$legacy_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				return $modern_data;
			}

			return $legacy_data;
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$modern_data, &$legacy_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$modern_data = $value;
			} else {
				$legacy_data = $value;
			}

			return true;
		} );

		$config = array(
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		);

		$validation_result = $this->ingredient->validate( $config );
		$validation_result->execute();

		$this->assertSame( 'existing_value', $modern_data['existing_key'] );
		$this->assertSame( 'other_value', $legacy_data['other_key'] );
	}

	/**
	 * GIVEN corrupted option values (non-array)
	 * WHEN executing
	 * THEN should handle gracefully and succeed
	 */
	public function test_execute_handles_non_array_option_values(): void {
		WP_Functions::mock( 'get_option', 'not-an-array' );
		WP_Functions::mock( 'update_option', true );

		$config = array(
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		);

		$validation_result = $this->ingredient->validate( $config );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
	}

	// ===== Execution Tests - Failure Cases =====

	/**
	 * GIVEN WordPress option update failures
	 * WHEN executing
	 * THEN should return execution failure
	 *
	 * @dataProvider update_failure_provider
	 */
	public function test_execute_fails_when_option_update_fails( string $failing_option ): void {
		WP_Functions::mock( 'get_option', array() );
		WP_Functions::mock( 'update_option', function ( $option ) use ( $failing_option ) {
			return $option !== $failing_option;
		} );

		$config = array(
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		);

		$validation_result = $this->ingredient->validate( $config );
		$result = $validation_result->execute();

		$this->assertExecutionFailure( $result );
	}

	public function update_failure_provider(): array {
		return array(
			'modern UI update fails'  => array( 'woocommerce-ppcp-data-common' ),
			'onboarding update fails' => array( 'woocommerce-ppcp-data-onboarding' ),
		);
	}

	// ===== Execution Tests - Clear Merchant Data =====

	/**
	 * GIVEN false as input
	 * WHEN executing
	 * THEN should clear all merchant data and preserve other settings
	 */
	public function test_execute_clears_merchant_data_when_false(): void {
		$modern_data = array(
			'merchant_id'        => 'OLD123',
			'merchant_email'     => 'old@example.com',
			'client_id'          => 'OLDCLIENT',
			'client_secret'      => 'OLDSECRET',
			'merchant_country'   => 'US',
			'seller_type'        => 'business',
			'sandbox_merchant'   => true,
			'merchant_connected' => true,
			'other_key'          => 'keep_this',
		);

		$legacy_data = array(
			'merchant_id'            => 'OLD123',
			'merchant_email'         => 'old@example.com',
			'client_id'              => 'OLDCLIENT',
			'client_secret'          => 'OLDSECRET',
			'merchant_id_sandbox'    => 'OLD123',
			'merchant_email_sandbox' => 'old@example.com',
			'other_setting'          => 'keep_this_too',
		);

		$onboarding_deleted = false;

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$modern_data, &$legacy_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				return $modern_data;
			}

			return $legacy_data;
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$modern_data, &$legacy_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$modern_data = $value;
			} else {
				$legacy_data = $value;
			}

			return true;
		} );

		WP_Functions::mock( 'delete_option', function ( $option ) use ( &$onboarding_deleted ) {
			if ( $option === 'woocommerce-ppcp-data-onboarding' ) {
				$onboarding_deleted = true;
			}

			return true;
		} );

		$validation_result = $this->ingredient->validate( false );
		$result = $validation_result->execute();

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertTrue( $data['cleared'] );

		// Check merchant data is removed
		$this->assertArrayNotHasKey( 'merchant_id', $modern_data );
		$this->assertArrayNotHasKey( 'merchant_email', $modern_data );
		$this->assertArrayNotHasKey( 'client_id', $modern_data );
		$this->assertArrayNotHasKey( 'client_secret', $modern_data );
		$this->assertArrayNotHasKey( 'merchant_country', $modern_data );
		$this->assertArrayNotHasKey( 'seller_type', $modern_data );
		$this->assertArrayNotHasKey( 'sandbox_merchant', $modern_data );
		$this->assertArrayNotHasKey( 'merchant_connected', $modern_data );

		$this->assertArrayNotHasKey( 'merchant_id', $legacy_data );
		$this->assertArrayNotHasKey( 'merchant_email', $legacy_data );
		$this->assertArrayNotHasKey( 'client_id', $legacy_data );
		$this->assertArrayNotHasKey( 'client_secret', $legacy_data );
		$this->assertArrayNotHasKey( 'merchant_id_sandbox', $legacy_data );
		$this->assertArrayNotHasKey( 'merchant_email_sandbox', $legacy_data );

		// Check other data is preserved
		$this->assertSame( 'keep_this', $modern_data['other_key'] );
		$this->assertSame( 'keep_this_too', $legacy_data['other_setting'] );

		// Check onboarding option was deleted
		$this->assertTrue( $onboarding_deleted );
	}
}
