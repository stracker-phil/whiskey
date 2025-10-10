<?php
/**
 * Main Plugin Bootstrap Class
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

/**
 * Main plugin bootstrap - manages plugin lifecycle and dependencies
 */
final class Main {

	private RecipeRegistry $registry;

	public function __construct( RecipeRegistry $registry ) {
		$this->registry = $registry;
	}

	public function init(): void {
		add_action( 'init', [ $this, 'register_components' ] );
	}

	public function register_components(): void {
		$this->registry->init();
	}
}
