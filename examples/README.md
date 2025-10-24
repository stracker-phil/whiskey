# Whiskey Plugin - Examples

This folder contains practical examples for extending Whiskey with custom ingredients and recipes from your own theme or plugin.

## Quick Start

All examples use WordPress hooks, so you can place them in:

- Your theme's `functions.php`
- A custom plugin file
- A mu-plugin (must-use plugin)

## Starter Examples

### 1. Register Custom Recipes

**File:** `register-recipe.php`

Shows how to register recipes from external code. Recipes combine multiple ingredients into complete configuration blueprints.

**Usage:**

```php
add_filter(
	'whiskey:register_recipes',
	static fn( array $items ) => array_merge( $items, [
	    'my-setup' => [ 'set_homepage' => 'shop' ],
    ] )
);
```

### 2. Register Custom Ingredients

**File:** `register-ingredient.php`

Shows how to register your own ingredient classes with Whiskey.

**Usage:**

```php
add_filter(
	'whiskey:register_ingredients',
	static fn( array $items ) => [ ...$items, MyIngredient::class ]
);
```

### 3. Full extension

**File:** `full-extension.php`

Creates a custom ingredient and uses it in a new recipe, as a custom WordPress plugin.

## Ingredient Development Best Practices

### Validation

- **Type check only** - Don't verify objects exist in `validate()`
- Return `ValidationResult` using factory methods (`valid()`, `invalid_type()`, `invalid_format()`, etc.)
- Keep it simple - "Can we execute with this input?"

### Execution

- **Always return `ExecutionResult`** (never throw exceptions)
- Provide helpful error messages (users see these via REST/CLI)
- Include execution details in the data array
- Extract complex logic to private methods
- Sanitize and validate config input, users might add unexpected values

## Testing Your Ingredients

After creating an ingredient, test it via:

**REST API:**

```bash
# Create a recipe using your ingredient
POST /wp-json/whiskey/v1/recipe/test/apply
{
    "your_ingredient_name": "test_value"
}
```

**WP-CLI:**

```bash
# List available ingredients
wp whiskey ingredients

# Check your ingredient details
wp whiskey ingredient your_ingredient_name

# Test via recipe
wp whiskey apply test-recipe
```

## Need Help?

- Easiest: Ask Claude for help; the plugin is well documented
- Review existing ingredients in `src/Ingredients/`
- Look at built-in recipes in `src/Recipes/`
- Run `composer test` to see test examples
