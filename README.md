# Whiskey - WordPress Configuration Plugin

> Like fine whiskey, code gets better with age. This plugin demonstrates PHP language improvements from 7.4 to 8.3 through practical WordPress automation.

## Overview

Whiskey is a WordPress helper plugin that exposes REST API endpoints for site configuration tasks. It's designed to showcase PHP language improvements across versions while solving real automation needs.

**Key Features:**

- 🍻 **Recipe System**: Hook-based configuration recipes for flexible site setup
- 🔧 **Multi-Platform**: WordPress core, WooCommerce, and PayPal configurations
- 🚀 **REST-First**: API endpoints for testing automation and AI tool integration
- 📊 **PHP Evolution**: Five versions (7.4, 8.0, 8.1, 8.2, 8.3) demonstrating language improvements
- 🧪 **Educational**: 20% code complexity reduction target through modern PHP features

## Installation

```bash
# Clone the repository
git clone https://github.com/stracker-phil/whiskey.git

# Switch to desired PHP version branch
git checkout php-7.4  # later php-8.0, php-8.1, php-8.2, php-8.3

# Start WordPress environment:
ddev start

# First time only, sets up WordPress:
ddev orchestrate
```

## Access Dev Site

The following details are pre-defined in the DDEV configuration:

- URL: https://whiskey.ddev.site
- Username: `admin`
- Password: `admin`

## Quick Start

### 1. List Available Recipes

```bash
GET /wp-json/whiskey/v1/recipes
```

### 2. Execute via REST API

Apply the recipe for `paypal-us-merchant`:

```bash
POST /wp-json/whiskey/v1/recipe/paypal-us-merchant/apply
```

### API Endpoints

| Endpoint                                  | Method | Description                        |
|-------------------------------------------|--------|------------------------------------|
| `/wp-json/whiskey/v1/recipes`             | GET    | List all registered recipes        |
| `/wp-json/whiskey/v1/recipe/{name}`       | GET    | Get specific recipe details        |
| `/wp-json/whiskey/v1/recipe/{name}/apply` | POST   | Execute a recipe                   |
| `/wp-json/whiskey/v1/ingredients`         | GET    | List all available ingredients     |
| `/wp-json/whiskey/v1/ingredient/{name}`   | GET    | Get specific ingredient details    |
| `/wp-json/whiskey/v1/status`              | GET    | Plugin status and PHP version info |

### WP-CLI Commands

```bash
wp whiskey recipes                    # List all recipes
wp whiskey recipe <name>              # Show recipe details
wp whiskey apply <name>               # Execute a recipe
wp whiskey apply <name> --dry-run     # Validate without executing
wp whiskey ingredients                # List all ingredients
wp whiskey ingredient <name>          # Show ingredient details
wp whiskey status                     # Show plugin status
```

## Architecture

### Ingredients vs. Recipes

**Ingredients** define *what can be configured*. They're focused operations that know how to perform specific configuration tasks (create pages, set homepage, configure PayPal mode, etc.).

**Recipes** combine ingredients into complete configurations. They're blueprints that specify which ingredients to use and what values to pass them.

Think of it this way:

- Ingredient = "I know how to set the homepage"
- Recipe = "Set homepage to 'shop', create cart page, configure permalinks"

## Development

### Running Tests

```bash
# Install dependencies
composer install

# Run PHPUnit tests
ddev composer test
```

### See test coverage

**Option 1**

```bash
# Run tests and generate a detailed coverage report
ddev composer test
```

The report is available at: https://whiskey.ddev.site/coverage

**Option 2**

During development, a direct coverage output in the terminal is often more helpful than checking the HTML report:

```bash
# Run tests and display code coverage details in the terminal
ddev composer coverage
```

### Creating New Ingredients

Ingredients are individual configuration operations that recipes can use.

**When to create an ingredient:**

- You want to configure a specific setting (Stripe API key, email SMTP, etc.)
- You need validation and execution logic for one focused task

**Steps:**

1. Create ingredient class in `src/Ingredients/` extending `Ingredient` base class
2. Implement `validate()` and `execute()` methods
3. Add self-registration via `whiskey:register_ingredient` hook at bottom of file
4. Write tests

**Example ingredient:**

```php
class MyCustomIngredient extends Ingredient {
    public const NAME = 'my_custom_setting';
    public const CATEGORY = 'my-plugin';
    public const DESCRIPTION = 'Optional description for documentation';
    
    public function validate( $value ): bool {
        return is_string( $value );
    }
    
    public function execute( $value ): ExecutionResult {
        // Collect response details for output.
        $details = [];
        // Do the configuration work
        return new ExecutionResult( true, 'Success', $details );
    }
}

// Self-register
add_action( 'whiskey:register_ingredient', function( $registry ) {
    $registry->add( MyCustomIngredient::class );
});
```

### Creating New Recipes

Recipes combine ingredients into complete configuration blueprints.

The plugin includes built-in recipes in `src/Recipes/*.php` that work out-of-the-box, registered via the `whiskey:register_recipe` hook.

To create custom recipes, use the same hook in any file loaded on/before `init` action at priority 10.

**Sample recipe:**

```php
// In a custom plugin or theme.
add_action('whiskey:register_recipe', function( \Whiskey\Registry\RecipeRegistry $registry ) {
    $registry->add(
        'my-shop-setup',     // Unique recipe name
        [                    // Ingredient configuration
            'set_homepage' => 'shop',
            'create_shop_pages' => ['shop', 'cart'],
            'woocommerce_country' => 'US',
            MyCustomIngredient::NAME => true, // Custom ingredient
        ]
    );
});
```

**Note:** If a recipe with the same name already exists, it will be replaced by the new recipe.

## License

MIT License - see [LICENSE](LICENSE) file for details.

---

**Note**: This is an educational project demonstrating PHP language evolution. Each version branch contains functionally identical code optimized for its target PHP version.
