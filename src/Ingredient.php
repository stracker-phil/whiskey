<?php
declare( strict_types = 1 );

namespace Whiskey;

/**
 * An ingredient is the most granular part of a recipe and performs
 * a pre-defined action.
 */
abstract class Ingredient {
	/**
	 * Must be defined by the child class. This is the public name
	 * of the ingredient that's used in recipes.
	 */
	public const NAME = '';

	/**
	 * Ingredient category for documentation/filtering
	 * Can be overwritten in the child class.
	 */
	public const CATEGORY = 'general';

	/**
	 * Optional. Description provided by the child class to document
	 * the ingredient.
	 */
	public const DESCRIPTION = '';

	abstract public function validate( $value ): bool;

	abstract public function execute( $value ): ExecutionResult;
}
