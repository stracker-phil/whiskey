<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;

/**
 * Configures PayPal merchant credentials for both Legacy and Modern UI.
 * Group: PayPal
 */
class SetPayPalMerchantIngredient extends Ingredient {
	public const NAME        = 'set_paypal_merchant';
	public const CATEGORY    = 'paypal';
	public const DESCRIPTION = 'Sets PayPal merchant credentials; accepts array with merchant_id, merchant_email, client_id, client_secret, or false to clear';

	private const MODERN_OPTION = 'woocommerce-ppcp-data-common';
	private const LEGACY_OPTION = 'woocommerce-ppcp-settings';
	private const ONBOARDING_OPTION = 'woocommerce-ppcp-data-onboarding';

	private const REQUIRED_KEYS = [
		'merchant_id',
		'merchant_email',
		'client_id',
		'client_secret',
	];

	private const OPTIONAL_KEYS = [
		'merchant_country',
		'casual_seller',
	];

	public function validate( $value ): bool {
		// Accept false to clear merchant data
		if ( false === $value ) {
			return true;
		}

		if ( ! is_array( $value ) ) {
			return false;
		}

		// Check all required keys are present and are strings
		foreach ( self::REQUIRED_KEYS as $key ) {
			if ( ! isset( $value[ $key ] ) || ! is_string( $value[ $key ] ) ) {
				return false;
			}
		}

		// Check optional keys if present are valid types
		foreach ( self::OPTIONAL_KEYS as $key ) {
			if ( ! isset( $value[ $key ] ) ) {
				continue;
			}

			// casual_seller must be boolean
			if ( $key === 'casual_seller' && ! is_bool( $value[ $key ] ) ) {
				return false;
			}

			// merchant_country must be string
			if ( $key === 'merchant_country' && ! is_string( $value[ $key ] ) ) {
				return false;
			}
		}

		// Check for unknown keys
		$allowed_keys = array_merge( self::REQUIRED_KEYS, self::OPTIONAL_KEYS );
		foreach ( array_keys( $value ) as $key ) {
			if ( ! in_array( $key, $allowed_keys, true ) ) {
				return false;
			}
		}

		return true;
	}

	public function execute( $value ): ExecutionResult {
		if ( false === $value ) {
			return $this->clear_merchant_data();
		}

		// Update Modern UI, Legacy UI, and Onboarding options
		$modern_result = $this->update_modern_ui( $value );
		$legacy_result = $this->update_legacy_ui( $value );
		$onboarding_result = $this->update_onboarding( $value );

		if ( ! $modern_result || ! $legacy_result || ! $onboarding_result ) {
			return new ExecutionResult(
				false,
				'Failed to update PayPal merchant credentials.',
				[
					'modern_ui_updated'  => $modern_result,
					'legacy_ui_updated'  => $legacy_result,
					'onboarding_updated' => $onboarding_result,
				]
			);
		}

		return new ExecutionResult(
			true,
			'PayPal merchant credentials configured successfully.',
			[
				'merchant_id'    => $value['merchant_id'],
				'merchant_email' => $value['merchant_email'],
				'sandbox_mode'   => true,
			]
		);
	}

	private function update_modern_ui( array $config ): bool {
		$data = get_option( self::MODERN_OPTION, [] );

		if ( ! is_array( $data ) ) {
			$data = [];
		}

		$data['merchant_id']        = $config['merchant_id'];
		$data['merchant_email']     = $config['merchant_email'];
		$data['client_id']          = $config['client_id'];
		$data['client_secret']      = $config['client_secret'];
		$data['merchant_country']   = $config['merchant_country'] ?? '';
		$data['seller_type']        = ( $config['casual_seller'] ?? false ) === true ? 'personal' : 'business';
		$data['sandbox_merchant']   = true;
		$data['merchant_connected'] = true;

		return update_option( self::MODERN_OPTION, $data );
	}

	private function update_legacy_ui( array $config ): bool {
		$data = get_option( self::LEGACY_OPTION, [] );

		if ( ! is_array( $data ) ) {
			$data = [];
		}

		$data['merchant_id']            = $config['merchant_id'];
		$data['merchant_email']         = $config['merchant_email'];
		$data['client_id']              = $config['client_id'];
		$data['client_secret']          = $config['client_secret'];
		$data['merchant_id_sandbox']    = $config['merchant_id'];
		$data['merchant_email_sandbox'] = $config['merchant_email'];

		return update_option( self::LEGACY_OPTION, $data );
	}

	private function update_onboarding( array $config ): bool {
		$data = get_option( self::ONBOARDING_OPTION, [] );

		if ( ! is_array( $data ) ) {
			$data = [];
		}

		$data['completed']          = true;
		$data['setup_done']         = false;
		$data['gateways_synced']    = false;
		$data['gateways_refreshed'] = false;

		return update_option( self::ONBOARDING_OPTION, $data );
	}

	private function clear_merchant_data(): ExecutionResult {
		$modern_data = get_option( self::MODERN_OPTION, [] );
		$legacy_data = get_option( self::LEGACY_OPTION, [] );

		if ( is_array( $modern_data ) ) {
			unset(
				$modern_data['merchant_id'],
				$modern_data['merchant_email'],
				$modern_data['client_id'],
				$modern_data['client_secret'],
				$modern_data['merchant_country'],
				$modern_data['seller_type'],
				$modern_data['sandbox_merchant'],
				$modern_data['merchant_connected']
			);
			update_option( self::MODERN_OPTION, $modern_data );
		}

		if ( is_array( $legacy_data ) ) {
			unset(
				$legacy_data['merchant_id'],
				$legacy_data['merchant_email'],
				$legacy_data['client_id'],
				$legacy_data['client_secret'],
				$legacy_data['merchant_id_sandbox'],
				$legacy_data['merchant_email_sandbox']
			);
			update_option( self::LEGACY_OPTION, $legacy_data );
		}

		// Delete the onboarding option entirely
		delete_option( self::ONBOARDING_OPTION );

		return new ExecutionResult(
			true,
			'PayPal merchant credentials cleared.',
			[ 'cleared' => true ]
		);
	}
}

add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, SetPayPalMerchantIngredient::class ]
);
