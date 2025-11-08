<?php
/**
 * REST API Controller
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey\Controllers;

use Whiskey\Tools\WhiskeyTool;

/**
 * REST API controller for recipe endpoints
 */
class RestController {

	private const NAMESPACE = 'whiskey/v1';

	/**
	 * @param WhiskeyTool[] $tools
	 */
	public function __construct( private readonly array $tools ) {
	}

	public function register_routes(): void {
		foreach ( $this->tools as $tool ) {
			$tool->init_rest( self::NAMESPACE, [ $this, 'permission_callback' ] );
		}
	}

	/**
	 * As this is a plugin to set up dev/test environments, we do not
	 * add any permission check here.
	 */
	public function permission_callback(): bool {
		return true;
	}
}
