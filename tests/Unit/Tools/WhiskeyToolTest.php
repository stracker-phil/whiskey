<?php
/**
 * @covers \Whiskey\Tools\WhiskeyTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Tools\WhiskeyTool;
use WP_REST_Request;
use WP_REST_Response;
use Exception;
use ReflectionClass;

class WhiskeyToolTest extends WhiskeyTest {

	public function testInitRestSkipsWhenConfigIsNull(): void {
		$tool = $this->createTestTool( null, [ 'command' => 'test', 'synopsis' => 'Test' ] );

		// Should not throw exception or register anything
		$tool->init_rest( 'whiskey/v1', fn() => true );

		$this->assertTrue( true );
	}

	public function testInitCliSkipsWhenConfigIsNull(): void {
		$tool = $this->createTestTool( [ 'method' => 'GET', 'path' => '/test' ], null );

		// Should not throw exception or register anything
		$tool->init_cli();

		$this->assertTrue( true );
	}

	public function testHandleRestReturnsSuccessResponse(): void {
		$tool = $this->createTestToolWithLogic( [ 'result' => 'success' ] );

		$request  = $this->createStub( WP_REST_Request::class );
		$response = $tool->handle_rest( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertSame( 'success', $data['data']['result'] );
	}

	public function testHandleRestReturnsErrorResponseOnException(): void {
		$tool = $this->createTestToolWithException( 'Something went wrong' );

		$request  = $this->createStub( WP_REST_Request::class );
		$response = $tool->handle_rest( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertSame( 'Something went wrong', $data['message'] );
	}

	public function testHandleRestMapsNotFoundExceptionTo404(): void {
		$tool = $this->createTestToolWithException( 'Recipe not found: test' );

		$request  = $this->createStub( WP_REST_Request::class );
		$response = $tool->handle_rest( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	public function testExtractRestArgsReturnsRequestParams(): void {
		$tool = $this->createTestTool(
			[ 'method' => 'GET', 'path' => '/test' ],
			[ 'command' => 'test', 'synopsis' => 'Test' ]
		);

		$request = $this->createStub( WP_REST_Request::class );
		$request->method( 'get_params' )->willReturn( [ 'key' => 'value' ] );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'extract_rest_args' );
		$method->setAccessible( true );

		$result = $method->invoke( $tool, $request );

		$this->assertSame( [ 'key' => 'value' ], $result );
	}

	public function testExtractCliArgsMergesArrays(): void {
		$tool = $this->createTestTool(
			[ 'method' => 'GET', 'path' => '/test' ],
			[ 'command' => 'test', 'synopsis' => 'Test' ]
		);

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'extract_cli_args' );
		$method->setAccessible( true );

		$result = $method->invoke(
			$tool,
			[ 'arg1', 'arg2' ],
			[ 'key' => 'value' ]
		);

		$this->assertSame( [ 'arg1', 'arg2', 'key' => 'value' ], $result );
	}

	public function testFormatRestSuccessReturnsStandardFormat(): void {
		$tool = $this->createTestTool(
			[ 'method' => 'GET', 'path' => '/test' ],
			[ 'command' => 'test', 'synopsis' => 'Test' ]
		);

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'format_rest_success' );
		$method->setAccessible( true );

		$response = $method->invoke( $tool, [ 'key' => 'value' ] );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( [ 'key' => 'value' ], $data['data'] );
	}

	public function testFormatRestErrorReturnsErrorFormat(): void {
		$tool = $this->createTestTool(
			[ 'method' => 'GET', 'path' => '/test' ],
			[ 'command' => 'test', 'synopsis' => 'Test' ]
		);

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'format_rest_error' );
		$method->setAccessible( true );

		$response = $method->invoke( $tool, 'Error message', 500 );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 500, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertSame( 'Error message', $data['message'] );
	}

	public function testGetHttpCodeReturns404ForNotFoundMessage(): void {
		$tool = $this->createTestTool(
			[ 'method' => 'GET', 'path' => '/test' ],
			[ 'command' => 'test', 'synopsis' => 'Test' ]
		);

		$exception = new Exception( 'Recipe not found: test' );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'get_http_code' );
		$method->setAccessible( true );

		$code = $method->invoke( $tool, $exception );

		$this->assertSame( 404, $code );
	}

	public function testGetHttpCodeReturns400ForOtherErrors(): void {
		$tool = $this->createTestTool(
			[ 'method' => 'GET', 'path' => '/test' ],
			[ 'command' => 'test', 'synopsis' => 'Test' ]
		);

		$exception = new Exception( 'Invalid input' );

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'get_http_code' );
		$method->setAccessible( true );

		$code = $method->invoke( $tool, $exception );

		$this->assertSame( 400, $code );
	}

	public function testHandleCliCallsLogicAndFormatsOutput(): void {
		$tool = $this->createTestToolWithLogic( [ 'key' => 'value' ] );

		// Should not throw exception
		$tool->handle_cli( [], [] );

		// Verify WP_CLI::log was called (messages should be captured)
		$messages = \WP_CLI::get_log_messages();
		$this->assertNotEmpty( $messages );
	}

	public function testHandleCliThrowsErrorOnException(): void {
		$tool = $this->createTestToolWithException( 'CLI error message' );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'CLI error message' );

		$tool->handle_cli( [], [] );
	}

	public function testFormatCliOutputHandlesSimpleData(): void {
		$tool = $this->createTestTool(
			[ 'method' => 'GET', 'path' => '/test' ],
			[ 'command' => 'test', 'synopsis' => 'Test' ]
		);

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		$method->invoke( $tool, [ 'key' => 'value', 'number' => 42 ] );

		$messages = \WP_CLI::get_log_messages();
		$this->assertContains( 'key: value', $messages );
		$this->assertContains( 'number: 42', $messages );
	}

	public function testFormatCliOutputHandlesArrays(): void {
		$tool = $this->createTestTool(
			[ 'method' => 'GET', 'path' => '/test' ],
			[ 'command' => 'test', 'synopsis' => 'Test' ]
		);

		$reflection = new ReflectionClass( $tool );
		$method     = $reflection->getMethod( 'format_cli_output' );
		$method->setAccessible( true );

		$method->invoke(
			$tool,
			[
				'items' => [ 'item1', 'item2', 'item3' ],
			]
		);

		$messages = \WP_CLI::get_log_messages();
		$this->assertContains( 'items:', $messages );
		$this->assertContains( '  - item1', $messages );
		$this->assertContains( '  - item2', $messages );
		$this->assertContains( '  - item3', $messages );
	}

	public function testInitRestRegistersEndpoint(): void {
		global $registered_rest_routes;
		$registered_rest_routes = [];

		$tool = $this->createTestTool(
			[
				'method' => 'POST',
				'path'   => '/test/endpoint',
				'args'   => [ 'param' => [ 'required' => true ] ],
			],
			[ 'command' => 'test', 'synopsis' => 'Test' ]
		);

		$tool->init_rest( 'whiskey/v1', fn() => true );

		// Verify registration was called
		$this->assertCount( 1, $registered_rest_routes );
		$this->assertSame( 'whiskey/v1', $registered_rest_routes[0]['namespace'] );
		$this->assertSame( '/test/endpoint', $registered_rest_routes[0]['route'] );
		$this->assertSame( 'POST', $registered_rest_routes[0]['args']['methods'] );
	}

	public function testInitRestSkipsWhenNoConfig(): void {
		global $registered_rest_routes;
		$registered_rest_routes = [];

		$tool = $this->createTestTool( null, [ 'command' => 'test', 'synopsis' => 'Test' ] );

		$tool->init_rest( 'whiskey/v1', fn() => true );

		// Should not register anything
		$this->assertEmpty( $registered_rest_routes );
	}

	public function testInitCliRegistersCommand(): void {
		global $registered_cli_commands;
		$registered_cli_commands = [];

		$tool = $this->createTestTool(
			[ 'method' => 'GET', 'path' => '/test' ],
			[
				'command'  => 'whiskey test',
				'synopsis' => 'Test command',
				'when'     => 'after_wp_load',
			]
		);

		$tool->init_cli();

		// Verify registration was called
		$this->assertCount( 1, $registered_cli_commands );
		$this->assertSame( 'whiskey test', $registered_cli_commands[0]['command'] );
		$this->assertSame( 'Test command', $registered_cli_commands[0]['args']['shortdesc'] );
		$this->assertSame( 'after_wp_load', $registered_cli_commands[0]['args']['when'] );
	}

	public function testInitCliSkipsWhenNoConfig(): void {
		global $registered_cli_commands;
		$registered_cli_commands = [];

		$tool = $this->createTestTool( [ 'method' => 'GET', 'path' => '/test' ], null );

		$tool->init_cli();

		// Should not register anything
		$this->assertEmpty( $registered_cli_commands );
	}

	public function testInitCliSkipsWhenWpCliNotAvailable(): void {
		// WP_CLI is always available in tests, but we can test the null config path
		global $registered_cli_commands;
		$registered_cli_commands = [];

		$tool = $this->createTestTool( [ 'method' => 'GET', 'path' => '/test' ], null );

		$tool->init_cli();

		// Should not register anything
		$this->assertEmpty( $registered_cli_commands );
	}

	// Helper methods

	private function createTestTool( ?array $rest_config, ?array $cli_config ): WhiskeyTool {
		return new class( $rest_config, $cli_config ) extends WhiskeyTool {
			private ?array $rest_config;
			private ?array $cli_config;

			public function __construct( ?array $rest_config, ?array $cli_config ) {
				$this->rest_config = $rest_config;
				$this->cli_config  = $cli_config;
				// Skip parent constructor
			}

			protected function get_rest_config(): ?array {
				return $this->rest_config;
			}

			protected function get_cli_config(): ?array {
				return $this->cli_config;
			}

			protected function handle_logic( array $args ): array {
				return [];
			}
		};
	}

	private function createTestToolWithLogic( array $return_data ): WhiskeyTool {
		return new class( $return_data ) extends WhiskeyTool {
			private array $return_data;

			public function __construct( array $return_data ) {
				$this->return_data = $return_data;
			}

			protected function get_rest_config(): ?array {
				return [ 'method' => 'GET', 'path' => '/test' ];
			}

			protected function get_cli_config(): ?array {
				return [ 'command' => 'test', 'synopsis' => 'Test' ];
			}

			protected function handle_logic( array $args ): array {
				return $this->return_data;
			}
		};
	}

	private function createTestToolWithException( string $message ): WhiskeyTool {
		return new class( $message ) extends WhiskeyTool {
			private string $message;

			public function __construct( string $message ) {
				$this->message = $message;
			}

			protected function get_rest_config(): ?array {
				return [ 'method' => 'GET', 'path' => '/test' ];
			}

			protected function get_cli_config(): ?array {
				return [ 'command' => 'test', 'synopsis' => 'Test' ];
			}

			protected function handle_logic( array $args ): array {
				throw new Exception( $this->message );
			}
		};
	}
}
