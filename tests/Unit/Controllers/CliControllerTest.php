<?php
/**
 * @covers \Whiskey\Controllers\CliController
 */
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Controllers;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Controllers\CliController;
use Whiskey\Tools\WhiskeyTool;

class CliControllerTest extends WhiskeyTest {

	public function testConstructorCreatesInstance(): void {
		$tool       = $this->createStub( WhiskeyTool::class );
		$controller = new CliController( [ $tool ] );

		$this->assertInstanceOf( CliController::class, $controller );
	}

	public function testRegisterCommandsCallsInitCliOnEachTool(): void {
		$tool1 = $this->createMock( WhiskeyTool::class );
		$tool2 = $this->createMock( WhiskeyTool::class );
		$tool3 = $this->createMock( WhiskeyTool::class );

		$controller = new CliController( [ $tool1, $tool2, $tool3 ] );

		$tool1->expects( $this->once() )->method( 'init_cli' );
		$tool2->expects( $this->once() )->method( 'init_cli' );
		$tool3->expects( $this->once() )->method( 'init_cli' );

		$controller->register_commands();
	}

	public function testRegisterCommandsHandlesEmptyToolsArray(): void {
		$controller = new CliController( [] );

		// Should not throw exception
		$controller->register_commands();

		$this->assertTrue( true );
	}

	public function testRegisterCommandsIteratesInOrder(): void {
		$callOrder = [];

		$tool1 = $this->createMock( WhiskeyTool::class );
		$tool1->expects( $this->once() )
			->method( 'init_cli' )
			->willReturnCallback( function () use ( &$callOrder ) {
				$callOrder[] = 'tool1';
			} );

		$tool2 = $this->createMock( WhiskeyTool::class );
		$tool2->expects( $this->once() )
			->method( 'init_cli' )
			->willReturnCallback( function () use ( &$callOrder ) {
				$callOrder[] = 'tool2';
			} );

		$tool3 = $this->createMock( WhiskeyTool::class );
		$tool3->expects( $this->once() )
			->method( 'init_cli' )
			->willReturnCallback( function () use ( &$callOrder ) {
				$callOrder[] = 'tool3';
			} );

		$controller = new CliController( [ $tool1, $tool2, $tool3 ] );
		$controller->register_commands();

		$this->assertSame( [ 'tool1', 'tool2', 'tool3' ], $callOrder );
	}
}
