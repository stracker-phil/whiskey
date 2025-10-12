<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Handlers;

use Whiskey\ExecutionResult;
use Whiskey\Handlers\RecipeHandler;
use Whiskey\Tests\Unit\WhiskeyTest;

/**
 * Base test class for all RecipeHandler implementations
 *
 * Verifies contract compliance and common behavior.
 * Extend this class for handler-specific tests.
 */
abstract class RecipeHandlerTest extends WhiskeyTest {
	/** @var RecipeHandler */
	protected RecipeHandler $handler;

	/**
	 * Child classes must return their handler instance
	 */
	abstract protected function createHandler(): RecipeHandler;

	/**
	 * Child classes must provide valid config examples
	 *
	 * @return array<string, array<string, array>>
	 */
	abstract public function validConfigProvider(): array;

	/**
	 * Child classes must provide invalid config examples
	 *
	 * @return array<string, array<string, array>>
	 */
	abstract public function invalidConfigProvider(): array;

	protected function setUp(): void {
		parent::setUp();

		$this->handler = $this->createHandler();
	}

	/**
	 * GIVEN a RecipeHandler is instantiated
	 * WHEN checking its class hierarchy
	 * THEN it should extend RecipeHandler base class
	 */
	public function testExtendsRecipeHandler(): void {
		$this->assertInstanceOf( RecipeHandler::class, $this->handler );
	}

	/**
	 * GIVEN a RecipeHandler is instantiated
	 * WHEN checking its TYPE constant
	 * THEN it should be defined and non-empty
	 */
	public function testHasTypeConstantDefined(): void {
		$reflection = new \ReflectionClass( $this->handler );
		$this->assertTrue( $reflection->hasConstant( 'TYPE' ) );

		$type = $reflection->getConstant( 'TYPE' );
		$this->assertNotEmpty( $type, 'TYPE constant must not be empty' );
		$this->assertIsString( $type, 'TYPE constant must be a string' );
	}

	/**
	 * GIVEN a valid configuration
	 * WHEN validate() is called
	 * THEN it should return true
	 *
	 * @dataProvider validConfigProvider
	 */
	public function testValidateAcceptsValidConfiguration( array $config = [] ): void {
		if ( ! $config ) {
			$this->expectNotToPerformAssertions();

			return;
		}

		$result = $this->handler->validate( $config );

		$this->assertTrue( $result );
	}

	/**
	 * GIVEN an invalid configuration
	 * WHEN validate() is called
	 * THEN it should return false
	 *
	 * @dataProvider invalidConfigProvider
	 */
	public function testValidateRejectsInvalidConfiguration( array $config = [] ): void {
		if ( ! $config ) {
			$this->expectNotToPerformAssertions();

			return;
		}

		$result = $this->handler->validate( $config );

		$this->assertFalse( $result );
	}

	/**
	 * GIVEN a valid configuration
	 * WHEN execute() is called
	 * THEN an ExecutionResult should be returned
	 * AND it should have proper structure
	 *
	 * @dataProvider validConfigProvider
	 */
	public function testExecuteReturnsExecutionResult( array $config = [] ): void {
		if ( ! $config ) {
			$this->expectNotToPerformAssertions();

			return;
		}

		$result = $this->handler->execute( $config );

		$this->assertInstanceOf( ExecutionResult::class, $result );
		$this->assertIsBool( $result->is_success() );
		$this->assertIsString( $result->get_message() );
		$this->assertIsArray( $result->get_data() );
	}

	/**
	 * GIVEN a valid configuration
	 * WHEN execute() result is converted to array
	 * THEN it should contain all required keys
	 *
	 * @dataProvider validConfigProvider
	 */
	public function testExecuteResultConvertsToArray( array $config = [] ): void {
		if ( ! $config ) {
			$this->expectNotToPerformAssertions();

			return;
		}

		$result = $this->handler->execute( $config );
		$array  = $result->to_array();

		$this->assertIsArray( $array );
		$this->assertArrayHasKey( 'success', $array );
		$this->assertArrayHasKey( 'message', $array );
		$this->assertArrayHasKey( 'data', $array );
		$this->assertIsBool( $array['success'] );
		$this->assertIsString( $array['message'] );
		$this->assertIsArray( $array['data'] );
	}
}
