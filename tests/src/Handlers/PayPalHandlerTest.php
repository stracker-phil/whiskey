<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\ExecutionResult;
use Whiskey\RecipeHandlerInterface;
use Whiskey\Handlers\PayPalHandler;

/**
 * @covers PayPalHandler
 */
final class PayPalHandlerTest extends WhiskeyTest {
	private ?PayPalHandler $handler = null;

	protected function setUp(): void {
		parent::setUp();

		$this->handler = new PayPalHandler();
	}

	/**
	 * GIVEN PayPalHandler is instantiated
	 * WHEN checking its type
	 * THEN it should implement RecipeHandlerInterface
	 */
	public function testImplementsRecipeHandlerInterface(): void {
		$this->assertInstanceOf( RecipeHandlerInterface::class, $this->handler );
	}

	/**
	 * GIVEN a valid PayPal configuration with allowed mode
	 * WHEN validate() is called
	 * THEN it should return true
	 *
	 * @dataProvider validConfigProvider
	 */
	public function testValidateAcceptsValidConfiguration( array $config ): void {
		$result = $this->handler->validate( $config );

		$this->assertTrue( $result );
	}

	/**
	 * GIVEN an invalid PayPal configuration
	 * WHEN validate() is called
	 * THEN it should return false
	 *
	 * @dataProvider invalidConfigProvider
	 */
	public function testValidateRejectsInvalidConfiguration( array $config ): void {
		$result = $this->handler->validate( $config );

		$this->assertFalse( $result );
	}

	/**
	 * GIVEN a valid PayPal configuration
	 * WHEN execute() is called
	 * THEN an ExecutionResult should be returned
	 * AND it should indicate success
	 * AND provide access to message and data
	 */
	public function testExecuteReturnsSuccessfulResult(): void {
		$config = [ 'mode' => 'sandbox' ];

		$result = $this->handler->execute( $config );

		$this->assertInstanceOf( ExecutionResult::class, $result );
		$this->assertTrue( $result->is_success() );
		$this->assertIsString( $result->get_message() );
		$this->assertIsArray( $result->get_data() );
	}

	/**
	 * GIVEN a valid PayPal configuration
	 * WHEN execute() result is converted to array
	 * THEN it should contain all required keys
	 * AND the success flag should be true
	 */
	public function testExecuteResultConvertsToArray(): void {
		$config = [ 'mode' => 'sandbox' ];
		$result = $this->handler->execute( $config );

		$array = $result->to_array();

		$this->assertIsArray( $array );
		$this->assertArrayHasKey( 'success', $array );
		$this->assertArrayHasKey( 'message', $array );
		$this->assertArrayHasKey( 'data', $array );
		$this->assertTrue( $array['success'] );
		$this->assertSame( 'PayPal recipe executed successfully', $array['message'] );
	}

	/**
	 * GIVEN different valid configurations
	 * WHEN execute() is called
	 * THEN the result should be consistent
	 *
	 * @dataProvider validConfigProvider
	 */
	public function testExecuteHandlesDifferentValidConfigurations( array $config ): void {
		$result = $this->handler->execute( $config );

		$resultArray = $result->to_array();
		$this->assertTrue( $resultArray['success'] );
		$this->assertArrayHasKey( 'message', $resultArray );
		$this->assertArrayHasKey( 'data', $resultArray );
	}

	/**
	 * @return array<string, array<string, array>>
	 */
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

	/**
	 * @return array<string, array<string, array>>
	 */
	public function invalidConfigProvider(): array {
		return [
			'missing mode'       => [
				'config' => [],
			],
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
