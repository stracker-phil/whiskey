<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Handlers;

use Whiskey\Handlers\PayPalHandler;
use Whiskey\Handlers\RecipeHandler;

/**
 * @covers PayPalHandler
 */
final class PayPalHandlerTest extends RecipeHandlerTest {
	protected function createHandler(): RecipeHandler {
		return new PayPalHandler();
	}

	/**
	 * GIVEN a PayPal handler
	 * WHEN checking its TYPE constant
	 * THEN it should be 'paypal'
	 */
	public function testTypeConstantIsPayPal(): void {
		$reflection = new \ReflectionClass( $this->handler );
		$type       = $reflection->getConstant( 'TYPE' );

		$this->assertSame( 'paypal', $type );
	}

	/**
	 * GIVEN a PayPal configuration with sandbox mode
	 * WHEN execute() is called
	 * THEN the result should indicate PayPal-specific success
	 */
	public function testExecuteReturnsPayPalSpecificMessage(): void {
		$config = [ 'mode' => 'sandbox' ];
		$result = $this->handler->execute( $config );

		$array = $result->to_array();
		$this->assertTrue( $array['success'] );
		$this->assertStringContainsString( 'PayPal', $array['message'] );
	}

	public function validConfigProvider(): array {
		return [
			'sandbox mode'              => [
				'config' => [ 'mode' => 'sandbox' ],
			],
			'live mode'                 => [
				'config' => [ 'mode' => 'live' ],
			],
			'sandbox with extra fields' => [
				'config' => [
					'mode'      => 'sandbox',
					'client_id' => 'test-id',
					'secret'    => 'test-secret',
				],
			],
			'live with extra fields'    => [
				'config' => [
					'mode'        => 'live',
					'webhook_url' => 'https://example.com/webhook',
				],
			],
		];
	}

	public function invalidConfigProvider(): array {
		return [
			'empty mode string'  => [
				'config' => [ 'mode' => '' ],
			],
			'invalid mode value' => [
				'config' => [ 'mode' => 'production' ],
			],
			'numeric mode'       => [
				'config' => [ 'mode' => 123 ],
			],
			'null mode'          => [
				'config' => [ 'mode' => null ],
			],
			'boolean mode'       => [
				'config' => [ 'mode' => true ],
			],
			'test mode'          => [
				'config' => [ 'mode' => 'test' ],
			],
		];
	}
}
