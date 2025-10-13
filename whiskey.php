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
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;

defined( 'ABSPATH' ) || exit;

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
$recipes         = new RecipeRegistry();
$ingredients     = new IngredientRegistry();
$executor        = new RecipeExecutor( $ingredients );
$rest_controller = new RestController( $recipes, $ingredients, $executor );
$main            = new Main( $recipes, $ingredients, $rest_controller );

$main->init();
