# Whiskey - Developer Guide for AI Assistants

## Project Purpose

Educational WordPress plugin demonstrating PHP language evolution from 7.4 → 8.3 through practical automation. Each PHP version lives in its own branch with functionally identical code optimized for that version.

**Target:** 15+ ingredients across WordPress/WooCommerce/PayPal categories, demonstrating 20% complexity reduction from baseline to PHP 8.3.

---

## Directory Structure

```
whiskey/
├── whiskey.php         # Plugin entry point (manual DI bootstrap)
├── src/
│   ├── Main.php           # Bootstrap class (lifecycle management)
│   ├── Ingredient.php     # Abstract base class for ingredients
│   ├── ExecutionResult.php # Type-safe result object
│   ├── RecipeExecutor.php  # Orchestrates ingredient execution
│   ├── Registry/
│   │   ├── RecipeRegistry.php     # Hook-based recipe collection
│   │   └── IngredientRegistry.php # Hook-based ingredient collection
│   ├── Controllers/
│   │   └── RestController.php     # REST API endpoints
│   ├── Ingredients/      # Individual configuration operations
│   └── Recipes/          # Pre-built ingredient combinations
├── tests/
│   ├── Unit/            # PHPUnit tests
│   ├── stubs/           # WordPress function stubs
│   └── bootstrap.php    # Test setup with Brain Monkey
└── examples/            # Usage examples for external code
```

---

## Architecture Overview

### Component Flow

```
whiskey.php
    ↓ creates dependencies
Main.php
    ↓ hooks into WordPress 'init'
    ↓ loads files from src/Ingredients/ and src/Recipes/
    ↓ calls init() on registries
IngredientRegistry.php & RecipeRegistry.php
    ↓ fire do_action('whiskey:register_*', $this)
Ingredient files & Recipe files
    ↓ self-register via add_action('whiskey:register_*')
    ↓ call $registry->add(...)
```

### Key Design Patterns

**1. Manual Dependency Injection**
- `whiskey.php` creates all dependencies and wires them together
- No DI container (keeping it simple for PHP 7.4 baseline)
- All dependencies passed through constructors

**2. Hook-Based Registration**
- Ingredients and recipes self-register via WordPress hooks
- Files are loaded, hooks are registered, then registries fire the hooks
- Registry instances passed to hook callbacks for dependency injection

**3. Lazy Loading**
- Registries store class names, not instances
- Ingredients instantiate only when `get()` is called
- Metadata extracted from class constants without instantiation

**4. WordPress Integration**
- Unknown ingredients silently ignored (WordPress pattern)
- `manage_options` capability required for most REST endpoints
- Uses Brain Monkey for testing without full WordPress

---

## Ingredient Development Guide

### The Ingredient Lifecycle

1. **File Creation:** Create class in `src/Ingredients/`
2. **Self-Registration:** Add hook at bottom of file
3. **Validation:** Implement `validate($value)` method
4. **Execution:** Implement `execute($value)` method
5. **Testing:** Create test in `tests/Unit/Ingredients/`

### Ingredient Template

```php
<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\Registry\IngredientRegistry;

class MyIngredient extends Ingredient {
	public const NAME        = 'my_setting';
	public const CATEGORY    = 'wordpress'; // or 'woocommerce' or 'paypal'
	public const DESCRIPTION = 'What this ingredient does';

	public function validate( $value ): bool {
		// Check if $value is valid for this ingredient
		return is_string( $value );
	}

	public function execute( $value ): ExecutionResult {
		// Perform the configuration
		$result = $this->do_something( $value );

		if ( ! $result ) {
			return new ExecutionResult(
				false,
				'Helpful error message explaining what went wrong'
			);
		}

		return new ExecutionResult(
			true,
			'Success message explaining what changed'
		);
	}

	private function do_something( $value ): bool {
		// Complex logic extracted to private methods
		// This keeps execute() clean and testable
		return true;
	}
}

// Self-register via hook (called when registry->init() fires)
add_action(
	'whiskey:register_ingredient',
	static fn( IngredientRegistry $registry ) => $registry->add( MyIngredient::class )
);
```

### Ingredient Best Practices

**Validation:**
- Always validate type first (`is_string`, `is_int`, `is_array`)
- Check for required array keys before accessing
- Return `false` on invalid input (no exceptions)
- Keep validation simple - just check if we CAN execute

**Execution:**
- Extract complex logic to private methods
- Always return `ExecutionResult` (never throw exceptions)
- Provide helpful error messages (users see these in REST responses)
- Success messages should describe what changed
- Check WordPress function return values (many return false on failure)

**WordPress Integration:**
- Check if posts/pages exist before using IDs
- Verify post types match expectations (`'page'`, `'post'`, etc.)
- Use `WP_Post instanceof WP_Post` checks
- Remember: `get_page_by_path()` can return null
- Remember: `get_post()` can return null or wrong post type

**Example pattern:**
```php
// ✅ GOOD: Extract logic, check types, provide helpful messages
private function resolve_page_id( $value ): int {
	if ( is_string( $value ) ) {
		$page = get_page_by_path( $value );
		if ( $page instanceof WP_Post ) {
			return $page->ID;
		}
	}
	
	if ( is_int( $value ) ) {
		$page = get_post( $value );
		if ( $page instanceof WP_Post && 'page' === $page->post_type ) {
			return $page->ID;
		}
	}
	
	return 0; // Clear failure indicator
}

public function execute( $value ): ExecutionResult {
	$page_id = $this->resolve_page_id( $value );
	
	if ( ! $page_id ) {
		return new ExecutionResult(
			false,
			"Did not find a page with id or slug '{$value}'."
		);
	}
	
	// Perform the actual work...
	update_option( 'page_on_front', $page_id );
	
	return new ExecutionResult(
		true,
		"Successfully set page_id {$page_id}."
	);
}
```

---

## Recipe Development Guide

### Recipe Structure

Recipes are simple arrays mapping ingredient names to values:

```php
add_action( 'whiskey:register_recipe', static function ( RecipeRegistry $registry ) {
	$registry->add(
		'us_merchant',  // Recipe name
		[               // Ingredient configuration
			'set_homepage'          => 'shop',
			'woocommerce_country'   => 'US',
			'woocommerce_currency'  => 'USD',
			'paypal_mode'           => 'sandbox',
		]
	);
} );
```

### Recipe Files

- Live in `src/Recipes/` directory
- Loaded automatically by `Main::load_builtin_recipes()`
- Can group multiple related recipes in one file
- Self-register via `whiskey:register_recipe` hook

---

## Testing Guide

### Test Infrastructure

- **Framework:** PHPUnit 9.5
- **WordPress Mocking:** Brain Monkey 2.6
- **Base Class:** `WhiskeyTest` (wraps Brain Monkey setup)
- **Run Tests:** `composer test` or `vendor/bin/phpunit`

### Test Structure

```php
<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Ingredients\MyIngredient;

class MyIngredientTest extends WhiskeyTest {
	private ?MyIngredient $ingredient = null;

	protected function setUp(): void {
		parent::setUp();
		$this->ingredient = new MyIngredient();
	}

	public function testValidateAcceptsValidInput(): void {
		$this->assertTrue( $this->ingredient->validate( 'valid' ) );
	}

	public function testValidateRejectsInvalidInput(): void {
		$this->assertFalse( $this->ingredient->validate( 123 ) );
	}

	public function testExecuteReturnsSuccess(): void {
		// Mock WordPress functions as needed
		when( 'update_option' )->justReturn( true );

		$result = $this->ingredient->execute( 'valid' );

		$this->assertTrue( $result->is_success() );
	}
}
```

### Mocking WordPress Functions

```php
use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

// Simple stub
when( 'get_option' )->justReturn( 'value' );

// Conditional return
when( 'get_post' )->alias( function( $id ) {
	return $id === 123 ? (object)['ID' => 123] : null;
} );

// Expectation with assertion count
expect( 'update_option' )
	->once()
	->with( 'option_name', 'value' )
	->andReturn( true );

// Use $this->assertedByMockery() when using expect()
$this->assertedByMockery();
```

---

## Important Architectural Considerations

### 1. Registry Auto-Initialization

Calling `get()`, `has()`, or `all()` automatically calls `init()` if not already initialized. This is by design but can surprise you during testing.

### 2. Hook Execution Timing

- Ingredient/recipe files loaded at priority 11 on `init`
- Registration hooks fire immediately after loading
- REST routes registered on `rest_api_init`
- Plan accordingly if adding hooks that depend on this timing

---

## Git Workflow

### Branch Strategy

Each branch is **self-contained and functional:**

```
main         → Project overview only
php-7.4      → Baseline implementation
php-8.0      → + PHP 8.0 features + docs/changes-from-7.4.md
php-8.1      → + PHP 8.1 features + docs/changes-from-8.0.md
php-8.2      → + PHP 8.2 features + docs/changes-from-8.1.md
php-8.3      → + PHP 8.3 features + docs/changes-from-8.2.md
```

### Commit Message Convention

Use conventional commits with emojis:

```
✨ Add WooCommerceCountryIngredient
🐛 Fix null check in SetHomepageIngredient
♻️ Extract validation logic to private method
📝 Add docblocks to ExecutionResult
🧪 Add tests for PayPalModeIngredient
```

### Multi-Branch Development

1. Complete php-7.4 baseline first (15+ ingredients)
2. Merge to php-8.0 branch and refactor for PHP 8.0
3. Document changes in `docs/changes-from-7.4.md`
4. Repeat for each subsequent PHP version
5. Each branch remains functional independently

---

## REST API Reference

Base URL: `/wp-json/whiskey/v1`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/recipes` | GET | Yes | List all recipes |
| `/recipe/{name}` | GET | Yes | Get specific recipe |
| `/recipe/{name}/apply` | POST | Yes | Execute recipe |
| `/ingredients` | GET | Yes | List ingredient metadata |
| `/status` | GET | No | Plugin status & PHP version |

**Auth:** Most endpoints require `manage_options` capability

---

## Code Style (PHP 7.4)

```php
// Long array syntax (WordPress standard)
array( 'key' => 'value' )  // ✅
[ 'key' => 'value' ]        // ❌

// Indentation: tabs not spaces
if ( isset( $data['key'] ) ) {  // ✅
if (isset($data['key'])) {      // ❌

// Type hints (PHP 7.4 compatible)
public function id(): ?string {      // ✅
public function id(): string|null {  // ❌ (PHP 8.0+)

// Opening braces on same line
public function test(): void {  // ✅
public function test(): void    // ❌
{

// White space around parentheses and brackets
if ( isset( $data['key'] ) ) {  // ✅
if (isset($data['key'])) {      // ❌
```

---

## Useful Commands

```bash
# Run tests
composer test

# Run specific test
vendor/bin/phpunit tests/Unit/RecipeRegistryTest.php

# Start DDEV environment
ddev start

# Access WordPress
# URL: https://whiskey.ddev.site
# User: admin / admin

# SSH into container
ddev ssh

# View logs
ddev logs
```

---

## Educational Goals Reminder

This project demonstrates PHP evolution **through working code**. When implementing on php-7.4:

- Use verbose syntax where PHP 8+ would be cleaner
- Document pain points in code comments
- These pain points become examples in later branch docs
- Think: "How will PHP 8.x improve this?"

**Example:**
```php
// PHP 7.4 - Verbose constructor (baseline for comparison)
private RecipeRegistry $recipes;
private IngredientRegistry $ingredients;

public function __construct(
	RecipeRegistry $recipes,
	IngredientRegistry $ingredients
) {
	$this->recipes = $recipes;
	$this->ingredients = $ingredients;
}

// Later in php-8.0: Constructor promotion makes this 3 lines!
```
