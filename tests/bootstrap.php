<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Whiskey\Tests
 */

declare( strict_types = 1 );

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load mock WordPress functions.
require_once __DIR__ . '/helpers/wp-functions.php';

// Load functional stubs.
require_once __DIR__ . '/stubs/WP_REST_Response.php';
require_once __DIR__ . '/stubs/WP_Post.php';
