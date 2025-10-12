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
git checkout php-8.3  # or php-7.4, php-8.0, php-8.1, php-8.2

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
| `/wp-json/whiskey/v1/status`              | GET    | Plugin status and PHP version info |

## Recipes

The plugin comes with a set of recipes (configuration blueprints) that are defined in `src/Recipes/*.php` files.

Want to add a custom recipe? Use the same hook our built-in recipes use:

```php
add_action('whiskey:register_recipe', function( RecipeRegistry $registry ) {
    $registry->register(
        'paypal',
        'my-custom-setup',
        [ /* your config */ ]
    );
});
```

## Development

### Running Tests

```bash
# Install dependencies
composer install

# Run PHPUnit tests
composer test
```

### Creating New Recipe Types

1. Create handler class in `src/Handlers/`
2. Extend the base class `RecipeHandler`
3. Register in `src/RecipeRegistry.php`
4. Implement the behavior in the handler class
5. Write tests


## License

MIT License - see [LICENSE](LICENSE) file for details.

---

**Note**: This is an educational project demonstrating PHP language evolution. Each version branch contains functionally identical code optimized for its target PHP version.
