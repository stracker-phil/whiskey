<?php
/**
 * Plugin Name: Whiskey
 * Description: WordPress configuration recipes. Like fine whiskey, code gets better with age.
 * Version: 1.0.0
 * Requires at least: 5.4
 * Requires PHP: 7.4
 * Author: Philipp Stracker (p.stracker@syde.com)
 * License: MIT
 *
 * @package Whiskey
 */

declare( strict_types = 1 );

namespace Whiskey;

use Whiskey\Controllers\RestController;

defined( 'ABSPATH' ) || exit;
defined( 'WHISKEY_RECIPES_DIR' ) || define( 'WHISKEY_RECIPES_DIR', __DIR__ . '/src/Recipes' );

// Load Composer autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} else {
	// Fallback error for missing dependencies.
	wp_die(
		esc_html__( 'Whiskey plugin requires Composer dependencies. Run: composer install', 'whiskey' ),
		esc_html__( 'Missing Dependencies', 'whiskey' )
	);
}

// Initialize plugin with dependencies.
$registry        = new RecipeRegistry();
$factory         = new HandlerFactory();
$rest_controller = new RestController( $registry, $factory );
$main            = new Main( $registry, $rest_controller );

$main->init();
