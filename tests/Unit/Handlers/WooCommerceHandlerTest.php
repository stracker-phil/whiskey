<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Handlers;

use Whiskey\Handlers\WooCommerceHandler;
use Whiskey\Handlers\RecipeHandler;

/**
 * @covers WooCommerceHandler
 */
final class WooCommerceHandlerTest extends RecipeHandlerTest {
	protected function createHandler(): RecipeHandler {
		return new WooCommerceHandler();
	}

	/**
	 * GIVEN a WooCommerce handler
	 * WHEN checking its TYPE constant
	 * THEN it should be 'woocommerce'
	 */
	public function testTypeConstantIsWooCommerce(): void {
		$reflection = new \ReflectionClass( $this->handler );
		$type       = $reflection->getConstant( 'TYPE' );

		$this->assertSame( 'woocommerce', $type );
	}

	public function validConfigProvider(): array {
		return [
			'no cases yet' => [],
		];
	}

	public function invalidConfigProvider(): array {
		return [
			'no cases yet' => [],
		];
	}
}
