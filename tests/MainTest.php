<?php
/**
 * Tests for Main
 *
 * @package Whiskey\Tests
 */

declare( strict_types = 1 );

namespace Whiskey\Tests;

use PHPUnit\Framework\TestCase;
use Whiskey\HandlerFactory;
use Whiskey\Main;
use Whiskey\RecipeRegistry;
use Whiskey\RestController;

/**
 * Main test case
 */
final class MainTest extends TestCase {

	private RecipeRegistry $registry;
	private HandlerFactory $factory;
	private RestController $rest_controller;
	private Main $main;

	protected function setUp(): void {
		parent::setUp();
		$this->registry        = new RecipeRegistry();
		$this->factory         = new HandlerFactory();
		$this->rest_controller = new RestController( $this->registry, $this->factory );
		$this->main            = new Main( $this->registry, $this->rest_controller );

		// Reset global hooks between tests.
		global $wp_filter;
		$wp_filter = [];
	}

	public function test_constructor_accepts_dependencies(): void {
		$registry        = new RecipeRegistry();
		$factory         = new HandlerFactory();
		$rest_controller = new RestController( $registry, $factory );
		$main            = new Main( $registry, $rest_controller );

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
