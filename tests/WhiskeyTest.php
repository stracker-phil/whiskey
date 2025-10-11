<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Mockery;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;
use function Brain\Monkey\Actions\expectAdded as brainExpectAdded;
use function Brain\Monkey\Actions\expectDone as brainExpectDone;
use function Brain\Monkey\Filters\expectApplied as brainExpectApplied;
use Brain\Monkey\Expectation\Expectation;

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

	/**
	 * Expect add_action/add_filter to be called
	 */
	protected function expectAdded( string $hook ): Expectation {
		$this->addToAssertionCount( 1 );

		return brainExpectAdded( $hook );
	}

	/**
	 * Expect do_action to be called
	 */
	protected function expectDone( string $hook ): Expectation {
		$this->addToAssertionCount( 1 );

		return brainExpectDone( $hook );
	}

	/**
	 * Expect apply_filters to be called
	 */
	protected function expectApplied( string $hook ): Expectation {
		$this->addToAssertionCount( 1 );

		return brainExpectApplied( $hook );
	}

	/**
	 * Mark that a Mockery expectation counts as an assertion
	 */
	protected function assertedByMockery(): void {
		$this->addToAssertionCount( 1 );
	}
}
