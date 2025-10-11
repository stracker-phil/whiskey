<?php
/**
 * Tests for HandlerFactory
 *
 * @package Whiskey\Tests
 */

declare( strict_types = 1 );


use PHPUnit\Framework\TestCase;
use Whiskey\HandlerFactory;
use Whiskey\Handlers\PayPalHandler;
use Whiskey\RecipeHandlerInterface;

/**
 * HandlerFactory test case
 */
final class HandlerFactoryTest extends TestCase {

	private HandlerFactory $factory;

	protected function setUp(): void {
		parent::setUp();
		$this->factory = new HandlerFactory();
	}

	public function test_get_handler_returns_paypal_handler(): void {
		$handler = $this->factory->get_handler( 'paypal' );

		$this->assertInstanceOf( PayPalHandler::class, $handler );
	}

	public function test_get_handler_returns_handler_interface(): void {
		$handler = $this->factory->get_handler( 'paypal' );

		$this->assertInstanceOf( RecipeHandlerInterface::class, $handler );
	}

	public function test_get_handler_returns_null_for_unknown_type(): void {
		$handler = $this->factory->get_handler( 'unknown' );

		$this->assertNull( $handler );
	}

	public function test_get_handler_returns_null_for_empty_type(): void {
		$handler = $this->factory->get_handler( '' );

		$this->assertNull( $handler );
	}

	public function test_get_handler_returns_null_for_woocommerce_type(): void {
		$handler = $this->factory->get_handler( 'woocommerce' );

		$this->assertNull( $handler );
	}

	public function test_get_handler_returns_null_for_wordpress_type(): void {
		$handler = $this->factory->get_handler( 'wordpress' );

		$this->assertNull( $handler );
	}
}
