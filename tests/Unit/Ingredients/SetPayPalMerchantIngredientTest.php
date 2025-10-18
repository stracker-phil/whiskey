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

	public function test_validate_accepts_complete_merchant_data(): void {
		$this->assertValidationAccepts( [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		] );
	}

	public function test_validate_accepts_merchant_data_with_country(): void {
		$this->assertValidationAccepts( [
			'merchant_id'      => 'MERCHANT123',
			'merchant_email'   => 'merchant@example.com',
			'client_id'        => 'CLIENT123',
			'client_secret'    => 'SECRET123',
			'merchant_country' => 'US',
		] );
	}

	public function test_validate_accepts_merchant_data_with_casual_seller_true(): void {
		$this->assertValidationAccepts( [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
			'casual_seller'  => true,
		] );
	}

	public function test_validate_accepts_merchant_data_with_casual_seller_false(): void {
		$this->assertValidationAccepts( [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
			'casual_seller'  => false,
		] );
	}

	public function test_validate_accepts_all_optional_fields(): void {
		$this->assertValidationAccepts( [
			'merchant_id'      => 'MERCHANT123',
			'merchant_email'   => 'merchant@example.com',
			'client_id'        => 'CLIENT123',
			'client_secret'    => 'SECRET123',
			'merchant_country' => 'US',
			'casual_seller'    => true,
		] );
	}

	public function test_validate_accepts_false(): void {
		$this->assertValidationAccepts( false );
	}

	public function test_validate_rejects_missing_merchant_id(): void {
		$this->assertValidationRejects( [
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		] );
	}

	public function test_validate_rejects_missing_merchant_email(): void {
		$this->assertValidationRejects( [
			'merchant_id'   => 'MERCHANT123',
			'client_id'     => 'CLIENT123',
			'client_secret' => 'SECRET123',
		] );
	}

	public function test_validate_rejects_missing_client_id(): void {
		$this->assertValidationRejects( [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_secret'  => 'SECRET123',
		] );
	}

	public function test_validate_rejects_missing_client_secret(): void {
		$this->assertValidationRejects( [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
		] );
	}

	public function test_validate_rejects_non_string_merchant_id(): void {
		$this->assertValidationRejects( [
			'merchant_id'    => 123,
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		] );
	}

	public function test_validate_rejects_non_string_merchant_email(): void {
		$this->assertValidationRejects( [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 123,
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		] );
	}

	public function test_validate_rejects_non_boolean_casual_seller(): void {
		$this->assertValidationRejects( [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
			'casual_seller'  => 'true',
		] );
	}

	public function test_validate_rejects_non_string_merchant_country(): void {
		$this->assertValidationRejects( [
			'merchant_id'      => 'MERCHANT123',
			'merchant_email'   => 'merchant@example.com',
			'client_id'        => 'CLIENT123',
			'client_secret'    => 'SECRET123',
			'merchant_country' => 123,
		] );
	}

	public function test_validate_rejects_string(): void {
		$this->assertValidationRejects( 'MERCHANT123' );
	}

	public function test_validate_rejects_integer(): void {
		$this->assertValidationRejects( 123 );
	}

	public function test_validate_rejects_true(): void {
		$this->assertValidationRejects( true );
	}

	public function test_validate_rejects_empty_array(): void {
		$this->assertValidationRejects( [] );
	}

	// ===== Execution Tests =====

	public function test_execute_updates_modern_ui_option(): void {
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', true );

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		];

		$result = $this->ingredient->execute( $config );

		$this->assertExecutionSuccess( $result );
		$data = $result->get_data();
		$this->assertSame( 'MERCHANT123', $data['merchant_id'] );
		$this->assertSame( 'merchant@example.com', $data['merchant_email'] );
		$this->assertTrue( $data['sandbox_mode'] );
	}

	public function test_execute_sets_onboarding_flags(): void {
		$onboarding_data = [];

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$onboarding_data ) {
			if ( $option === 'woocommerce-ppcp-data-onboarding' ) {
				return $onboarding_data;
			}
			return [];
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$onboarding_data ) {
			if ( $option === 'woocommerce-ppcp-data-onboarding' ) {
				$onboarding_data = $value;
			}
			return true;
		} );

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		];

		$this->ingredient->execute( $config );

		$this->assertTrue( $onboarding_data['completed'] );
		$this->assertFalse( $onboarding_data['setup_done'] );
		$this->assertFalse( $onboarding_data['gateways_synced'] );
		$this->assertFalse( $onboarding_data['gateways_refreshed'] );
	}

	public function test_execute_sets_merchant_country_to_empty_string_when_not_provided(): void {
		$modern_data = [];

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				return $modern_data;
			}
			return [];
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$modern_data = $value;
			}
			return true;
		} );

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		];

		$this->ingredient->execute( $config );

		$this->assertSame( '', $modern_data['merchant_country'] );
	}

	public function test_execute_sets_merchant_country_when_provided(): void {
		$modern_data = [];

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				return $modern_data;
			}
			return [];
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$modern_data = $value;
			}
			return true;
		} );

		$config = [
			'merchant_id'      => 'MERCHANT123',
			'merchant_email'   => 'merchant@example.com',
			'client_id'        => 'CLIENT123',
			'client_secret'    => 'SECRET123',
			'merchant_country' => 'US',
		];

		$this->ingredient->execute( $config );

		$this->assertSame( 'US', $modern_data['merchant_country'] );
	}

	public function test_execute_defaults_seller_type_to_business(): void {
		$modern_data = [];

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				return $modern_data;
			}
			return [];
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$modern_data = $value;
			}
			return true;
		} );

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		];

		$this->ingredient->execute( $config );

		$this->assertSame( 'business', $modern_data['seller_type'] );
	}

	public function test_execute_sets_seller_type_to_personal_when_casual_seller_true(): void {
		$modern_data = [];

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				return $modern_data;
			}
			return [];
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$modern_data = $value;
			}
			return true;
		} );

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
			'casual_seller'  => true,
		];

		$this->ingredient->execute( $config );

		$this->assertSame( 'personal', $modern_data['seller_type'] );
	}

	public function test_execute_sets_seller_type_to_business_when_casual_seller_false(): void {
		$modern_data = [];

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				return $modern_data;
			}
			return [];
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$modern_data ) {
			if ( $option === 'woocommerce-ppcp-data-common' ) {
				$modern_data = $value;
			}
			return true;
		} );

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
			'casual_seller'  => false,
		];

		$this->ingredient->execute( $config );

		$this->assertSame( 'business', $modern_data['seller_type'] );
	}

	public function test_execute_updates_legacy_ui_option(): void {
		$legacy_data = [];

		WP_Functions::mock( 'get_option', function ( $option ) use ( &$legacy_data ) {
			if ( $option === 'woocommerce-ppcp-settings' ) {
				return $legacy_data;
			}
			return [];
		} );

		WP_Functions::mock( 'update_option', function ( $option, $value ) use ( &$legacy_data ) {
			if ( $option === 'woocommerce-ppcp-settings' ) {
				$legacy_data = $value;
			}
			return true;
		} );

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		];

		$this->ingredient->execute( $config );

		$this->assertSame( 'MERCHANT123', $legacy_data['merchant_id'] );
		$this->assertSame( 'merchant@example.com', $legacy_data['merchant_email'] );
		$this->assertSame( 'CLIENT123', $legacy_data['client_id'] );
		$this->assertSame( 'SECRET123', $legacy_data['client_secret'] );
		$this->assertSame( 'MERCHANT123', $legacy_data['merchant_id_sandbox'] );
		$this->assertSame( 'merchant@example.com', $legacy_data['merchant_email_sandbox'] );
	}

	public function test_execute_preserves_existing_option_data(): void {
		$modern_data = [ 'existing_key' => 'existing_value' ];
		$legacy_data = [ 'other_key' => 'other_value' ];

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

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		];

		$this->ingredient->execute( $config );

		$this->assertSame( 'existing_value', $modern_data['existing_key'] );
		$this->assertSame( 'other_value', $legacy_data['other_key'] );
	}

	public function test_execute_handles_non_array_option_values(): void {
		WP_Functions::mock( 'get_option', 'not-an-array' );
		WP_Functions::mock( 'update_option', true );

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		];

		$result = $this->ingredient->execute( $config );

		$this->assertExecutionSuccess( $result );
	}

	public function test_execute_fails_when_modern_ui_update_fails(): void {
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', function ( $option ) {
			return $option !== 'woocommerce-ppcp-data-common';
		} );

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		];

		$result = $this->ingredient->execute( $config );

		$this->assertExecutionFailure( $result );
	}

	public function test_execute_fails_when_onboarding_update_fails(): void {
		WP_Functions::mock( 'get_option', [] );
		WP_Functions::mock( 'update_option', function ( $option ) {
			return $option !== 'woocommerce-ppcp-data-onboarding';
		} );

		$config = [
			'merchant_id'    => 'MERCHANT123',
			'merchant_email' => 'merchant@example.com',
			'client_id'      => 'CLIENT123',
			'client_secret'  => 'SECRET123',
		];

		$result = $this->ingredient->execute( $config );

		$this->assertExecutionFailure( $result );
	}

	public function test_execute_clears_merchant_data_when_false(): void {
		$modern_data = [
			'merchant_id'        => 'OLD123',
			'merchant_email'     => 'old@example.com',
			'client_id'          => 'OLDCLIENT',
			'client_secret'      => 'OLDSECRET',
			'merchant_country'   => 'US',
			'seller_type'        => 'business',
			'sandbox_merchant'   => true,
			'merchant_connected' => true,
			'other_key'          => 'keep_this',
		];

		$legacy_data = [
			'merchant_id'            => 'OLD123',
			'merchant_email'         => 'old@example.com',
			'client_id'              => 'OLDCLIENT',
			'client_secret'          => 'OLDSECRET',
			'merchant_id_sandbox'    => 'OLD123',
			'merchant_email_sandbox' => 'old@example.com',
			'other_setting'          => 'keep_this_too',
		];

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

		$result = $this->ingredient->execute( false );

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
