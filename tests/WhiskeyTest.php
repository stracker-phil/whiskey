<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

/**
 * Base class for tests that provides brain monkey integration.
 */
abstract class WhiskeyTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		setUp();
	}

	protected function tearDown(): void {
		tearDown();
		Mockery::close();
		parent::tearDown();
	}
}
