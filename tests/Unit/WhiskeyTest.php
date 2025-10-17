<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_Hooks;
use WP_Functions;
use WP_CLI;

/**
 * Base class for tests
 */
abstract class WhiskeyTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();

		// Reset WordPress hooks before each test
		WP_Hooks::reset();

		// Reset WordPress function mocks before each test
		WP_Functions::reset();

		// Reset WP_CLI messages before each test
		WP_CLI::reset();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}
}
