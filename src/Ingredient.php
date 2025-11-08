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
	public const string NAME = '';

	/**
	 * Ingredient category for documentation/filtering
	 * Can be overwritten in the child class.
	 */
	public const IngredientCategory CATEGORY = IngredientCategory::General;

	/**
	 * Optional. Description provided by the child class to document
	 * the ingredient.
	 */
	public const string DESCRIPTION = '';

	abstract public function validate( mixed $value ): ValidationResult;
}
