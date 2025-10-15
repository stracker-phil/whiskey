<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_Hooks;

/**
 * Base class for tests
 */
abstract class WhiskeyTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		// Reset WordPress hooks before each test
		WP_Hooks::reset();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
