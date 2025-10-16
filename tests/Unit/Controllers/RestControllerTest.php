<?php
/**
 * @covers \Whiskey\Controllers\RestController
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Controllers;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Controllers\RestController;
use Whiskey\Tools\WhiskeyTool;

class RestControllerTest extends WhiskeyTest {

	public function testConstructorCreatesInstance(): void {
		$tool       = $this->createStub( WhiskeyTool::class );
		$controller = new RestController( array( $tool ) );

		$this->assertInstanceOf( RestController::class, $controller );
	}

	public function testRegisterRoutesCallsInitRestOnEachTool(): void {
		$tool1 = $this->createMock( WhiskeyTool::class );
		$tool2 = $this->createMock( WhiskeyTool::class );
		$tool3 = $this->createMock( WhiskeyTool::class );

		$controller = new RestController( array( $tool1, $tool2, $tool3 ) );

		$tool1->expects( $this->once() )
			->method( 'init_rest' )
			->with( 'whiskey/v1', $this->isType( 'array' ) );

		$tool2->expects( $this->once() )
			->method( 'init_rest' )
			->with( 'whiskey/v1', $this->isType( 'array' ) );

		$tool3->expects( $this->once() )
			->method( 'init_rest' )
			->with( 'whiskey/v1', $this->isType( 'array' ) );

		$controller->register_routes();
	}

	public function testRegisterRoutesPassesPermissionCallback(): void {
		$tool       = $this->createMock( WhiskeyTool::class );
		$controller = new RestController( array( $tool ) );

		$capturedCallback = null;
		$tool->expects( $this->once() )
			->method( 'init_rest' )
			->willReturnCallback( function ( $namespace, $callback ) use ( &$capturedCallback ) {
				$capturedCallback = $callback;
			} );

		$controller->register_routes();

		$this->assertIsArray( $capturedCallback );
		$this->assertSame( $controller, $capturedCallback[0] );
		$this->assertSame( 'permission_callback', $capturedCallback[1] );
	}

	public function testRegisterRoutesHandlesEmptyToolsArray(): void {
		$controller = new RestController( array() );

		// Should not throw exception
		$controller->register_routes();

		$this->assertTrue( true );
	}

	public function testPermissionCallbackReturnsTrue(): void {
		$controller = new RestController( array() );

		$result = $controller->permission_callback();

		$this->assertTrue( $result );
	}
}
