<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Handlers;

use Whiskey\Handlers\WordPressHandler;
use Whiskey\Handlers\RecipeHandler;

/**
 * @covers WordPressHandler
 */
final class WordPressHandlerTest extends RecipeHandlerTest {
	protected function createHandler(): RecipeHandler {
		return new WordPressHandler();
	}

	/**
	 * GIVEN a WordPress handler
	 * WHEN checking its TYPE constant
	 * THEN it should be 'wordpress'
	 */
	public function testTypeConstantIsWordPress(): void {
		$reflection = new \ReflectionClass( $this->handler );
		$type       = $reflection->getConstant( 'TYPE' );

		$this->assertSame( 'wordpress', $type );
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
