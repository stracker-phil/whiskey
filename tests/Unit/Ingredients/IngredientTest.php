<?php
/**
 * Base class for Ingredient tests
 *
 * @package Whiskey\Tests\Unit\Ingredients
 */

declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use WP_Post;

/**
 * Base test class providing common helpers for ingredient tests
 */
abstract class IngredientTest extends WhiskeyTest {

	/**
	 * The ingredient being tested
	 */
	protected Ingredient $ingredient;

	/**
	 * Get the ingredient class name to test
	 *
	 * @return string Fully qualified class name
	 */
	abstract protected function getIngredientClass(): string;

	/**
	 * Get expected ingredient NAME constant value
	 *
	 * @return string
	 */
	abstract protected function getExpectedName(): string;

	/**
	 * Get expected ingredient CATEGORY constant value
	 *
	 * @return string
	 */
	abstract protected function getExpectedCategory(): string;

	protected function setUp(): void {
		parent::setUp();

		$class            = $this->getIngredientClass();
		$this->ingredient = new $class();
	}

	/**
	 * Test that ingredient constants are properly defined
	 *
	 * This test is automatically run for all ingredients
	 */
	public function test_constants_are_defined(): void {
		$class = $this->getIngredientClass();

		$this->assertSame( $this->getExpectedName(), $class::NAME );
		$this->assertSame( $this->getExpectedCategory(), $class::CATEGORY->value );
		$this->assertNotEmpty( $class::DESCRIPTION );
	}

	/**
	 * Assert that execution was successful
	 *
	 * @param ExecutionResult $result          Execution result to check
	 * @param string|null     $messageFragment Optional message fragment to verify
	 */
	protected function assertExecutionSuccess( ExecutionResult $result, ?string $messageFragment = null ): void {
		$this->assertTrue(
			$result->is_success(),
			'Expected execution to succeed but it failed with: ' . $result->get_message()
		);

		if ( $messageFragment !== null ) {
			$this->assertStringContainsString(
				$messageFragment,
				$result->get_message(),
				'Success message does not contain expected fragment'
			);
		}
	}

	/**
	 * Assert that execution failed
	 *
	 * @param ExecutionResult $result          Execution result to check
	 * @param string|null     $messageFragment Optional message fragment to verify
	 */
	protected function assertExecutionFailure( ExecutionResult $result, ?string $messageFragment = null ): void {
		$this->assertFalse(
			$result->is_success(),
			'Expected execution to fail but it succeeded with: ' . $result->get_message()
		);

		if ( $messageFragment !== null ) {
			$this->assertStringContainsString(
				$messageFragment,
				$result->get_message(),
				'Failure message does not contain expected fragment'
			);
		}
	}

	/**
	 * Create a mock WP_Post object
	 *
	 * @param int    $id         Post ID
	 * @param string $post_type  Post type (default: 'page')
	 * @param array  $additional Additional properties to set
	 * @return WP_Post
	 */
	protected function createMockPost( int $id, string $post_type = 'page', array $additional = [] ): WP_Post {
		$post            = new WP_Post();
		$post->ID        = $id;
		$post->post_type = $post_type;

		foreach ( $additional as $key => $value ) {
			$post->$key = $value;
		}

		return $post;
	}

	/**
	 * Assert that validation accepts a value
	 *
	 * @param mixed  $value       Value to validate
	 * @param string $description Optional description for assertion message
	 */
	protected function assertValidationAccepts( $value, string $description = '' ): void {
		$type = gettype( $value );
		if ( $type === 'object' ) {
			$type = get_class( $value );
		}

		$message = $description ?: "Expected validation to accept $type value";

		$result = $this->ingredient->validate( $value );
		$this->assertTrue(
			$result->is_valid(),
			$message
		);
	}

	/**
	 * Assert that validation rejects a value
	 *
	 * @param mixed  $value       Value to validate
	 * @param string $description Optional description for assertion message
	 */
	protected function assertValidationRejects( $value, string $description = '' ): void {
		$type = gettype( $value );
		if ( $type === 'object' ) {
			$type = get_class( $value );
		}

		$message = $description ?: "Expected validation to reject $type value";

		$result = $this->ingredient->validate( $value );
		$this->assertFalse(
			$result->is_valid(),
			$message
		);
	}

	/**
	 * Common validation test: null should always be rejected
	 */
	public function test_validate_rejects_null(): void {
		$this->assertValidationRejects( null );
	}
}
