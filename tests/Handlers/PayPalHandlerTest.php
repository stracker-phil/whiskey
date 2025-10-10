<?php
/**
 * Tests for PayPalHandler
 *
 * @package Whiskey\Tests\Handlers
 */

declare( strict_types = 1 );

namespace Whiskey\Tests\Handlers;

use PHPUnit\Framework\TestCase;
use Whiskey\ExecutionResult;
use Whiskey\Handlers\PayPalHandler;

/**
 * PayPalHandler test case
 */
final class PayPalHandlerTest extends TestCase {

	private PayPalHandler $handler;

	protected function setUp(): void {
		parent::setUp();
		$this->handler = new PayPalHandler();
	}

	public function test_validate_accepts_valid_config(): void {
		$config = [
			'mode' => 'sandbox',
		];

		$this->assertTrue( $this->handler->validate( $config ) );
	}

	/**
	 * @dataProvider invalid_config_provider
	 */
	public function test_validate_rejects_invalid_config( array $config ): void {
		$this->assertFalse( $this->handler->validate( $config ) );
	}

	/**
	 * Data provider for invalid configs
	 *
	 * @return array<string, array<array>>
	 */
	public function invalid_config_provider(): array {
		return [
			'missing mode' => [ [] ],
			'invalid mode' => [ [ 'mode' => 'invalid' ] ],
			'numeric mode' => [ [ 'mode' => 123 ] ],
			'null mode'    => [ [ 'mode' => null ] ],
		];
	}

	public function test_validate_accepts_sandbox_mode(): void {
		$config = [ 'mode' => 'sandbox' ];
		$this->assertTrue( $this->handler->validate( $config ) );
	}

	public function test_validate_accepts_live_mode(): void {
		$config = [ 'mode' => 'live' ];
		$this->assertTrue( $this->handler->validate( $config ) );
	}

	public function test_execute_returns_execution_result(): void {
		$config = [ 'mode' => 'sandbox' ];
		$result = $this->handler->execute( $config );

		$this->assertInstanceOf( ExecutionResult::class, $result );
		$this->assertTrue( $result->is_success() );
		$this->assertIsString( $result->get_message() );
		$this->assertIsArray( $result->get_data() );
	}

	public function test_execute_result_can_be_converted_to_array(): void {
		$config = [ 'mode' => 'sandbox' ];
		$result = $this->handler->execute( $config );

		$array = $result->to_array();

		$this->assertIsArray( $array );
		$this->assertArrayHasKey( 'success', $array );
		$this->assertArrayHasKey( 'message', $array );
		$this->assertArrayHasKey( 'data', $array );
		$this->assertTrue( $array['success'] );
	}
}
