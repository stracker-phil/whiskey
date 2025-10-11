<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Whiskey\HandlerFactory;
use Whiskey\RecipeHandlerInterface;
use Whiskey\Handlers\PayPalHandler;

/**
 * @covers HandlerFactory
 */
final class HandlerFactoryTest extends TestCase {
	private ?HandlerFactory $factory = null;

	protected function setUp(): void {
		parent::setUp();

		$this->factory = new HandlerFactory();
	}

	/**
	 * GIVEN the factory is initialized
	 * WHEN get_handler is called with 'paypal' type
	 * THEN a PayPalHandler instance should be returned
	 * AND it should implement RecipeHandlerInterface
	 */
	public function testGetHandlerReturnsPayPalHandlerForPayPalType(): void {
		$handler = $this->factory->get_handler( 'paypal' );

		$this->assertInstanceOf( RecipeHandlerInterface::class, $handler );
		$this->assertInstanceOf( PayPalHandler::class, $handler );
	}

	/**
	 * GIVEN the factory is initialized
	 * WHEN get_handler is called with an unknown type
	 * THEN null should be returned
	 *
	 * @dataProvider unknownTypeProvider
	 */
	public function testGetHandlerReturnsNullForUnknownType( string $unknownType ): void {
		$handler = $this->factory->get_handler( $unknownType );

		$this->assertNull( $handler );
	}

	/**
	 * GIVEN the factory is initialized
	 * WHEN get_handler is called multiple times with the same type
	 * THEN new instances should be returned each time
	 */
	public function testGetHandlerReturnsNewInstancesEachTime(): void {
		$handler1 = $this->factory->get_handler( 'paypal' );
		$handler2 = $this->factory->get_handler( 'paypal' );

		$this->assertNotSame( $handler1, $handler2 );
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public function unknownTypeProvider(): array {
		return [
			'empty string'   => [ 'type' => '' ],
			'woocommerce'    => [ 'type' => 'woocommerce' ],
			'stripe'         => [ 'type' => 'stripe' ],
			'random text'    => [ 'type' => 'random-unknown-type' ],
			'numeric string' => [ 'type' => '12345' ],
		];
	}
}
