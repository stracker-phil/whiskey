# Whiskey Plugin - Examples

This folder contains practical examples for extending Whiskey with custom ingredients and recipes from your own theme or plugin.

## Quick Start

All examples use WordPress hooks, so you can place them in:
- Your theme's `functions.php`
- A custom plugin file
- A mu-plugin (must-use plugin)

## Available Examples

### 1. Register Custom Recipes
**File:** `register-recipe.php`

Shows how to register recipes from external code. Recipes combine multiple ingredients into complete configuration blueprints.

**Key Points:**
- Use `whiskey:register_recipe` hook
- Pass recipe name and configuration array
- Can register multiple recipes in one callback

**Usage:**
```php
add_action(
    'whiskey:register_recipe',
    static fn( RecipeRegistry $r ) => $r->add(
        'my-setup',
        [ 'set_homepage' => 'shop' ]
    )
);
```

### 2. Register Custom Ingredients
**File:** `register-ingredient.php`

Shows how to register your own ingredient classes with Whiskey.

**Key Points:**
- Use `whiskey:register_ingredient` hook
- Pass ingredient class name (must extend `Whiskey\Ingredient`)
- Can register multiple ingredients in one callback

**Usage:**
```php
add_action(
    'whiskey:register_ingredient',
    static fn( IngredientRegistry $r ) => $r->add( MyIngredient::class )
);
```

### 3. Simple Custom Ingredient
**File:** `custom-ingredient.php`

Complete example of a simple ingredient that sets a plugin option.

**Key Points:**
- Extend `Whiskey\Ingredient` base class
- Define `NAME`, `CATEGORY`, and `DESCRIPTION` constants
- Implement `validate()` for type checking
- Implement `execute()` for the actual work
- Return `ExecutionResult` with success/failure and details
- Self-register at bottom of file

**When to Use:**
Create custom ingredients when you need to:
- Configure settings from your own plugins
- Automate third-party plugin configurations
- Add reusable configuration operations

### 4. Advanced Custom Ingredient
**File:** `advanced-ingredient.php`

Advanced example showing complex validation, array inputs, and WordPress object lookups.

**Key Points:**
- Handle complex array inputs
- Validate required keys in arrays
- Resolve WordPress objects (posts, menus, etc.)
- Provide detailed error messages
- Include execution details in response

**When to Use:**
- Multi-step operations
- Operations requiring WordPress object lookups
- Complex validation requirements
- Operations with multiple potential failure modes

## Ingredient Development Best Practices

### Validation
- **Type check only** - Don't verify objects exist in `validate()`
- Return `false` for invalid input (no exceptions)
- Keep it simple - "Can we execute with this input?"

### Execution
- **Always return ExecutionResult** (never throw exceptions)
- Check WordPress function returns (many return `false` on failure)
- Provide helpful error messages (users see these via REST/CLI)
- Include execution details in the data array
- Extract complex logic to private methods

### WordPress Integration
- Use `instanceof` checks for WordPress objects
- Handle null returns from WordPress functions
- Verify post types and object types
- Sanitize and validate user input

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

## Common Use Cases

### Plugin Configuration
Create ingredients to configure:
- Plugin API keys and credentials
- Feature toggles
- Third-party service settings
- Plugin-specific options

### WordPress Setup
Create ingredients for:
- Custom post type configurations
- Taxonomy settings
- User role modifications
- Media settings

### E-commerce Setup
Create ingredients for:
- Payment gateway settings
- Shipping configurations
- Tax settings
- Store policies

## Need Help?

- Check `CLAUDE.md` in the project root for detailed technical guidance
- Review existing ingredients in `src/Ingredients/`
- Look at built-in recipes in `src/Recipes/`
- Run `composer test` to see test examples

## Example: Complete Custom Setup

Here's how everything fits together:

```php
<?php
// 1. Create your ingredient class
namespace MyPlugin\Whiskey;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\Registry\IngredientRegistry;

class MySettingIngredient extends Ingredient {
    public const NAME = 'my_setting';
    public const CATEGORY = 'myplugin';
    public const DESCRIPTION = 'Configure my plugin';

    public function validate( $value ): bool {
        return is_string( $value );
    }

    public function execute( $value ): ExecutionResult {
        update_option( 'my_plugin_setting', $value );
        return new ExecutionResult( true, 'Setting updated', [ 'value' => $value ] );
    }
}

// 2. Register your ingredient
add_action(
    'whiskey:register_ingredient',
    static fn( IngredientRegistry $r ) => $r->add( MySettingIngredient::class )
);

// 3. Create a recipe using your ingredient
add_action(
    'whiskey:register_recipe',
    static fn( RecipeRegistry $r ) => $r->add(
        'my-plugin-setup',
        [ 'my_setting' => 'production' ]
    )
);

// 4. Apply via REST or CLI
// wp whiskey apply my-plugin-setup
```
