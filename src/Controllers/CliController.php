<?php
/**
 * WP-CLI Controller
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Controllers;

use Whiskey\Tools\WhiskeyTool;

/**
 * WP-CLI commands for Whiskey plugin
 */
class CliController {

	/**
	 * @param WhiskeyTool[] $tools
	 */
	public function __construct( private array $tools ) {
	}

	/**
	 * Register WP-CLI commands
	 */
	public function register_commands(): void {
		foreach ( $this->tools as $tool ) {
			$tool->init_cli();
		}
	}
}
