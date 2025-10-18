<?php
/**
 * @covers \Whiskey\Tools\WhiskeyTool
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Tools;

use Exception;
use WP_CLI;
use WP_Functions;
use WP_REST_Request;
use WP_REST_Response;
use Whiskey\Tools\WhiskeyTool;

class WhiskeyToolTest extends ToolTest {

	protected function setUp(): void {
		parent::setUp();

		// Create a concrete implementation for testing
		$this->tool = new class( $this->recipes, $this->ingredients, $this->executor ) extends WhiskeyTool {
			public bool $handle_logic_called = false;
			public array $received_args      = [];

			protected function get_rest_config(): ?array {
				return [
					'method' => 'GET',
					'path'   => '/test',
				];
			}

			protected function get_cli_config(): ?array {
				return [
					'command'  => 'whiskey test',
					'synopsis' => 'Test command',
				];
			}

			protected function handle_logic( array $args ): array {
				$this->handle_logic_called = true;
				$this->received_args       = $args;

				if ( isset( $args['throw'] ) && $args['throw'] ) {
					throw new Exception( 'Test exception' );
				}

				return [ 'result' => 'success' ];
			}
		};
	}

	// ===== init_rest() tests =====

	public function test_init_rest_registers_route(): void {
		global $registered_rest_routes;
		$registered_rest_routes = [];

		$this->tool->init_rest( 'test/v1', fn() => true );

		$this->assertCount( 1, $registered_rest_routes );
		$this->assertSame( 'test/v1', $registered_rest_routes[0]['namespace'] );
		$this->assertSame( '/test', $registered_rest_routes[0]['route'] );
	}

	public function test_init_rest_skips_when_config_null(): void {
		global $registered_rest_routes;
		$registered_rest_routes = [];

		$tool = new class( $this->recipes, $this->ingredients, $this->executor ) extends WhiskeyTool {
			protected function get_rest_config(): ?array {
				return null;
			}

			protected function get_cli_config(): ?array {
				return null;
			}

			protected function handle_logic( array $args ): array {
				return [];
			}
		};

		$tool->init_rest( 'test/v1', fn() => true );

		$this->assertCount( 0, $registered_rest_routes );
	}

	// ===== init_cli() tests =====

	public function test_init_cli_registers_command(): void {
		global $registered_cli_commands;
		$registered_cli_commands = [];

		$this->tool->init_cli();

		$this->assertCount( 1, $registered_cli_commands );
		$this->assertSame( 'whiskey test', $registered_cli_commands[0]['command'] );
	}

	public function test_init_cli_skips_when_config_null(): void {
		global $registered_cli_commands;
		$registered_cli_commands = [];

		$tool = new class( $this->recipes, $this->ingredients, $this->executor ) extends WhiskeyTool {
			protected function get_rest_config(): ?array {
				return null;
			}

			protected function get_cli_config(): ?array {
				return null;
			}

			protected function handle_logic( array $args ): array {
				return [];
			}
		};

		$tool->init_cli();

		$this->assertCount( 0, $registered_cli_commands );
	}

	public function test_init_cli_skips_when_cli_not_available(): void {
		global $registered_cli_commands;
		$registered_cli_commands = [];

		$tool = new class( $this->recipes, $this->ingredients, $this->executor ) extends WhiskeyTool {
			protected function get_rest_config(): ?array {
				return null;
			}

			protected function get_cli_config(): ?array {
				return [
					'command'  => 'whiskey test',
					'synopsis' => 'Test command',
				];
			}

			protected function handle_logic( array $args ): array {
				return [];
			}

			protected function is_cli_available(): bool {
				return false;
			}
		};

		$tool->init_cli();

		$this->assertCount( 0, $registered_cli_commands );
	}

	// ===== handle_rest() tests =====

	public function test_handle_rest_returns_success_response(): void {
		$request = $this->createStub( WP_REST_Request::class );
		$request->method( 'get_params' )->willReturn( [ 'test' => 'value' ] );

		$response = $this->tool->handle_rest( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( [ 'result' => 'success' ], $data['data'] );
	}

	public function test_handle_rest_calls_handle_logic(): void {
		$request = $this->createStub( WP_REST_Request::class );
		$request->method( 'get_params' )->willReturn( [ 'test' => 'value' ] );

		$this->tool->handle_rest( $request );

		$this->assertTrue( $this->tool->handle_logic_called );
		$this->assertSame( [ 'test' => 'value' ], $this->tool->received_args );
	}

	public function test_handle_rest_returns_error_on_exception(): void {
		$request = $this->createStub( WP_REST_Request::class );
		$request->method( 'get_params' )->willReturn( [ 'throw' => true ] );

		$response = $this->tool->handle_rest( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertSame( 'Test exception', $data['message'] );
	}

	public function test_handle_rest_returns_404_for_not_found_error(): void {
		$tool = new class( $this->recipes, $this->ingredients, $this->executor ) extends WhiskeyTool {
			protected function get_rest_config(): ?array {
				return [ 'method' => 'GET', 'path' => '/test' ];
			}

			protected function get_cli_config(): ?array {
				return null;
			}

			protected function handle_logic( array $args ): array {
				throw new Exception( 'Recipe not found: test' );
			}
		};

		$request = $this->createStub( WP_REST_Request::class );
		$request->method( 'get_params' )->willReturn( [] );

		$response = $tool->handle_rest( $request );

		$this->assertSame( 404, $response->get_status() );
	}

	// ===== handle_cli() tests =====

	public function test_handle_cli_calls_handle_logic(): void {
		$this->tool->handle_cli( [ 'arg1' ], [ 'key' => 'value' ] );

		$this->assertTrue( $this->tool->handle_logic_called );
		$this->assertSame( [ 0 => 'arg1', 'key' => 'value' ], $this->tool->received_args );
	}

	public function test_handle_cli_outputs_success(): void {
		$this->tool->handle_cli( [], [] );

		$messages = WP_CLI::get_log_messages();
		$this->assertStringContainsString( 'result: success', implode( '', $messages ) );
	}

	public function test_handle_cli_outputs_error_on_exception(): void {
		try {
			$this->tool->handle_cli( [ 'throw' => true ], [] );
		} catch ( \Exception $e ) {
			// Expected - WP_CLI::error() throws by default
		}

		$errors = WP_CLI::get_error_messages();
		$this->assertCount( 1, $errors );
		$this->assertSame( 'Test exception', $errors[0] );
	}

	// ===== extract_rest_args() tests =====

	public function test_extract_rest_args_returns_params(): void {
		$request = $this->createStub( WP_REST_Request::class );
		$request->method( 'get_params' )->willReturn( [ 'key' => 'value' ] );

		$args = $this->invoke_protected_method( $this->tool, 'extract_rest_args', [ $request ] );

		$this->assertSame( [ 'key' => 'value' ], $args );
	}

	// ===== extract_cli_args() tests =====

	public function test_extract_cli_args_merges_positional_and_assoc(): void {
		$args = $this->invoke_protected_method(
			$this->tool,
			'extract_cli_args',
			[ [ 'pos1', 'pos2' ], [ 'key' => 'value' ] ]
		);

		$this->assertSame(
			[ 0 => 'pos1', 1 => 'pos2', 'key' => 'value' ],
			$args
		);
	}

	// ===== format_rest_success() tests =====

	public function test_format_rest_success_returns_success_response(): void {
		$response = $this->invoke_protected_method(
			$this->tool,
			'format_rest_success',
			[ [ 'result' => 'test' ] ]
		);

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertSame( [ 'result' => 'test' ], $data['data'] );
	}

	// ===== format_rest_error() tests =====

	public function test_format_rest_error_returns_error_response(): void {
		$response = $this->invoke_protected_method(
			$this->tool,
			'format_rest_error',
			[ 'Error message', 404 ]
		);

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertFalse( $data['success'] );
		$this->assertSame( 'Error message', $data['message'] );
	}

	public function test_format_rest_error_defaults_to_400(): void {
		$response = $this->invoke_protected_method(
			$this->tool,
			'format_rest_error',
			[ 'Error message' ]
		);

		$this->assertSame( 400, $response->get_status() );
	}

	// ===== format_cli_output() tests =====

	public function test_format_cli_output_with_simple_values(): void {
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[ [ 'key1' => 'value1', 'key2' => 'value2' ] ]
		);

		$messages = WP_CLI::get_log_messages();
		$output   = implode( "\n", $messages );
		$this->assertStringContainsString( 'key1: value1', $output );
		$this->assertStringContainsString( 'key2: value2', $output );
	}

	public function test_format_cli_output_with_array_values(): void {
		$this->invoke_protected_method(
			$this->tool,
			'format_cli_output',
			[
				[
					'items' => [ 'item1', 'item2', 'item3' ],
				],
			]
		);

		$messages = WP_CLI::get_log_messages();
		$output   = implode( "\n", $messages );
		$this->assertStringContainsString( 'items:', $output );
		$this->assertStringContainsString( '  - item1', $output );
		$this->assertStringContainsString( '  - item2', $output );
		$this->assertStringContainsString( '  - item3', $output );
	}

	// ===== get_http_code() tests =====

	public function test_get_http_code_returns_404_for_not_found(): void {
		$exception = new Exception( 'Recipe not found: test' );

		$code = $this->invoke_protected_method(
			$this->tool,
			'get_http_code',
			[ $exception ]
		);

		$this->assertSame( 404, $code );
	}

	public function test_get_http_code_returns_400_by_default(): void {
		$exception = new Exception( 'Some other error' );

		$code = $this->invoke_protected_method(
			$this->tool,
			'get_http_code',
			[ $exception ]
		);

		$this->assertSame( 400, $code );
	}
}
