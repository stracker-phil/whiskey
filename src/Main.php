<?php
/**
 * Main Plugin Bootstrap Class
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

/**
 * Plugin class - Singleton pattern for main plugin initialization
 */
final class Main {

	private static ?Main $instance = null;

	private function __construct() {
		// Intentionally empty - use instance() to get plugin.
	}

	public static function instance(): Main {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init(): void {
		add_action( 'init', [ $this, 'register_components' ] );
	}

	public function register_components(): void {
		// Future: Register REST routes, recipe registry, etc.
	}
}
