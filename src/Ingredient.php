<?php
declare( strict_types = 1 );

namespace Whiskey;

/**
 * An ingredient is the most granular part of a recipe and performs
 * a pre-defined action.
 */
abstract class Ingredient {
	/**
	 * Ingredient category for documentation/filtering
	 * Can be overwritten in the child class.
	 */
	public const CATEGORY = 'general';

	abstract public function validate( $value ): bool;

	abstract public function execute( $value ): ExecutionResult;
}
