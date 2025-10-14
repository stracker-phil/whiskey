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
use Whiskey\Controllers\CliController;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\Tools\ApplyRecipeTool;
use Whiskey\Tools\ListIngredientsTool;
use Whiskey\Tools\ListRecipesTool;
use Whiskey\Tools\ShowIngredientTool;
use Whiskey\Tools\ShowRecipeTool;
use Whiskey\Tools\StatusTool;

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

// Initialize core dependencies.
$recipes     = new RecipeRegistry();
$ingredients = new IngredientRegistry();
$executor    = new RecipeExecutor( $ingredients );

// Initialize tools - each tool encapsulates one feature.
$tools = [
	new ListRecipesTool( $recipes, $ingredients, $executor ),
	new ShowRecipeTool( $recipes, $ingredients, $executor ),
	new ApplyRecipeTool( $recipes, $ingredients, $executor ),
	new ListIngredientsTool( $recipes, $ingredients, $executor ),
	new ShowIngredientTool( $recipes, $ingredients, $executor ),
	new StatusTool( $recipes, $ingredients, $executor ),
];

// Initialize controllers with tools.
$rest_controller = new RestController( $tools );
$cli_controller  = new CliController( $tools );

$main = new Main(
	$recipes,
	$ingredients,
	$rest_controller,
	$cli_controller
);

$main->init();
