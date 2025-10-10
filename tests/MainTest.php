<?php
/**
 * Tests for Main
 *
 * @package Whiskey\Tests
 */

declare( strict_types = 1 );

namespace Whiskey\Tests;

use PHPUnit\Framework\TestCase;
use Whiskey\Main;
use Whiskey\RecipeRegistry;

/**
 * Main test case
 */
final class MainTest extends TestCase {

	private RecipeRegistry $registry;
	private Main $main;

	protected function setUp(): void {
		parent::setUp();
		$this->registry = new RecipeRegistry();
		$this->main     = new Main( $this->registry );

		// Reset global hooks between tests.
		global $wp_filter;
		$wp_filter = [];
	}

	public function test_constructor_accepts_registry(): void {
		$registry = new RecipeRegistry();
		$main     = new Main( $registry );

		$this->assertInstanceOf( Main::class, $main );
	}

	public function test_register_components_initializes_registry(): void {
		$hook_called = false;

		add_action(
			'whiskey:register_recipe',
			function () use ( &$hook_called ) {
				$hook_called = true;
			}
		);

		$this->main->register_components();

		$this->assertTrue( $hook_called, 'Registry init should fire registration hook' );
	}
}
