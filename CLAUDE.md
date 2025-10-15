# Whiskey - AI Assistant Guide

Educational WordPress plugin demonstrating PHP 7.4 → 8.3 evolution through practical automation.

## Quick Reference

**Create Ingredient:** `src/Ingredients/MyIngredient.php` extending `Ingredient` with `validate()` and `execute()`  
**Register Ingredient:** `add_action('whiskey:register_ingredient', fn($r) => $r->add(MyIngredient::class))`  
**Create Recipe:** `add_action('whiskey:register_recipe', fn($r) => $r->add('name', ['ingredient' => 'value']))`  
**Run Tests:** `composer test`

## Architecture

### Core Classes

- `Ingredient` - Abstract base for configuration operations
- `ExecutionResult` - Type-safe result object (success, message, data)
- `RecipeExecutor` - Orchestrates ingredient execution
- `RecipeRegistry` - Collects recipes via hooks
- `IngredientRegistry` - Collects ingredients via hooks
- `WhiskeyTool` - Abstract base for REST/CLI features
- `RestController` - Thin registration layer for REST endpoints
- `CliController` - Thin registration layer for WP-CLI commands

### Tool-Based Architecture

Controllers delegate all business logic to **tool classes**. Each tool:
- Defines its REST endpoint configuration (method, path, args)
- Defines its CLI command configuration (command, synopsis, when)
- Implements the core business logic once in `handle_logic()`
- Optionally customizes output formatting for REST/CLI

**Why tools?** Single source of truth - no duplication between REST and CLI. Adding a feature means creating one tool class, not updating both controllers.

**Available tools:**
- `ListRecipesTool` - List all recipes
- `ShowRecipeTool` - Show recipe details  
- `ApplyRecipeTool` - Execute recipes
- `ListIngredientsTool` - List all ingredients
- `ShowIngredientTool` - Show ingredient details
- `StatusTool` - Plugin status

### Registration Flow

1. Files loaded from `src/Ingredients/` and `src/Recipes/`
2. Registries fire `do_action('whiskey:register_*', $this)`
3. Files self-register via `add_action('whiskey:register_*')`
4. Lazy instantiation: ingredients created only on `get()`

### Key Patterns

- Manual DI (no container)
- Hook-based registration
- Lazy loading via class names
- Tool-based feature implementation
- Custom WordPress function stubs for testing

---

## Ingredient Development

### Required Structure

```php
<?php
declare( strict_types = 1 );

namespace Whiskey\Ingredients;

use Whiskey\Ingredient;
use Whiskey\ExecutionResult;
use Whiskey\Registry\IngredientRegistry;

class MyIngredient extends Ingredient {
	public const NAME        = 'my_setting';
	public const CATEGORY    = 'wordpress'; // wordpress|woocommerce|paypal
	public const DESCRIPTION = 'What this does';

	public function validate( $value ): bool {
		// Type check only - can we execute with this value?
		return is_string( $value );
	}

	public function execute( $value ): ExecutionResult {
		// Do the work, return result with details
		$result = update_option( 'my_key', $value );
		
		return new ExecutionResult(
			$result,
			$result ? "Set to {$value}" : 'Failed to update',
			[ 'value' => $value ]
		);
	}
}

// Self-register
add_action(
	'whiskey:register_ingredient',
	static fn( IngredientRegistry $r ) => $r->add( MyIngredient::class )
);
```

### Critical Rules

**Validation:**
- Check type only (`is_string`, `is_int`, `is_array`)
- Return `false` for invalid input (no exceptions)
- Keep simple - just verify we CAN execute

**Execution:**
- Always return `ExecutionResult` (never throw exceptions)
- Extract complex logic to private methods
- Check WordPress function returns (many return false on failure)
- Provide helpful messages (shown to users via REST/CLI)
- Include execution details in data array

**WordPress Integration:**
- Use `instanceof WP_Post` checks
- Handle null returns from `get_page_by_path()`, `get_post()`
- Verify post types match expectations

### Common Pattern

```php
private function resolve_page( $value ): ?WP_Post {
	if ( is_string( $value ) ) {
		$page = get_page_by_path( $value );
		return $page instanceof WP_Post ? $page : null;
	}
	
	if ( is_int( $value ) ) {
		$page = get_post( $value );
		return ( $page instanceof WP_Post && 'page' === $page->post_type ) ? $page : null;
	}
	
	return null;
}

public function execute( $value ): ExecutionResult {
	$page = $this->resolve_page( $value );
	
	if ( ! $page ) {
		return new ExecutionResult( false, 'Page not found', [ 'input' => $value ] );
	}
	
	update_option( 'page_on_front', $page->ID );
	return new ExecutionResult( true, "Set homepage to {$page->ID}", [ 'page_id' => $page->ID ] );
}
```

---

## Recipe Development

Recipes are arrays mapping ingredient names to values:

```php
add_action( 'whiskey:register_recipe', static function ( RecipeRegistry $r ) {
	$r->add(
		'recipe_name',
		[
			'set_homepage'     => 'shop',
			'create_shop_pages' => [ 'shop', 'cart', 'checkout' ],
			'my_ingredient'    => 'value',
		]
	);
} );
```

Recipe files live in `src/Recipes/` and are auto-loaded.

---

## Tool Development

Tools encapsulate REST/CLI features with shared business logic.

### When to Create a Tool

Create a tool when you want to add a new REST endpoint + CLI command that:
- Lists or shows data from registries
- Executes operations (recipes, validation)
- Provides plugin status or metadata

### Required Structure

```php
<?php
declare( strict_types = 1 );

namespace Whiskey\Tools;

use Exception;

class MyTool extends WhiskeyTool {
	
	protected function get_rest_config(): ?array {
		return [
			'method' => 'GET',
			'path'   => '/my-endpoint',
			'args'   => [], // Optional REST args validation
		];
	}
	
	protected function get_cli_config(): ?array {
		return [
			'command'  => 'whiskey my-command',
			'synopsis' => 'Description of what this does',
			'when'     => 'after_wp_load', // Optional
		];
	}
	
	protected function handle_logic( array $args ): array {
		// Extract args from REST or CLI (already normalized)
		$name = $args['name'] ?? $args[0] ?? null;
		
		// Validate input
		if ( ! $name ) {
			throw new Exception( 'Name is required.' );
		}
		
		// Do the work using $this->recipes, $this->ingredients, $this->executor
		$data = $this->recipes->get( $name );
		
		if ( ! $data ) {
			throw new Exception( sprintf( 'Not found: %s', $name ) );
		}
		
		// Return data array (will be formatted for REST/CLI automatically)
		return [
			'name' => $name,
			'data' => $data,
		];
	}
	
	// Optional: Customize CLI output format
	protected function format_cli_output( array $data ): void {
		WP_CLI::log( sprintf( 'Found: %s', $data['name'] ) );
		// ... custom formatting
	}
}
```

### Critical Rules

**Configuration:**
- Return `null` from config methods to skip REST or CLI registration
- REST paths can use regex patterns: `(?P<name>[a-zA-Z0-9-_]+)`
- CLI commands are strings: `'whiskey my command'`

**Business Logic:**
- Always throw `Exception` on validation/execution errors
- Return associative array with result data on success
- Base class handles exception → REST error response / CLI error display
- Use `$this->recipes`, `$this->ingredients`, `$this->executor` as needed

**Argument Extraction:**
- REST: `$args['name']` gets URL parameter `(?P<name>...)`
- CLI: `$args[0]` gets first positional argument
- Override `extract_rest_args()` or `extract_cli_args()` for custom logic

**Output Formatting:**
- Default REST: `{ success: true, data: {...} }`
- Default CLI: Simple key-value output
- Override `format_rest_success()`, `format_cli_output()` for custom formatting
- HTTP error codes auto-detected from exception messages ("not found" → 404)

### Adding Tools to Plugin

After creating a tool, register it in `whiskey.php`:

```php
$tools = [
	new ListRecipesTool( $recipes, $ingredients, $executor ),
	new MyTool( $recipes, $ingredients, $executor ), // Add your tool
	// ...
];
```

---

## Testing

**See `tests/TESTING.md` for complete testing guidelines.**

### Quick Start

```bash
composer test              # Run tests
composer coverage          # Run with coverage report
```

### Test Structure

All tests extend `WhiskeyTest` which provides WordPress hook reset between tests.

```php
<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit\Ingredients;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Ingredients\MyIngredient;

class MyIngredientTest extends WhiskeyTest {
	private MyIngredient $ingredient;

	protected function setUp(): void {
		parent::setUp();
		$this->ingredient = new MyIngredient();
	}

	public function testValidateAcceptsString(): void {
		$this->assertTrue( $this->ingredient->validate( 'valid' ) );
	}

	public function testValidateRejectsInteger(): void {
		$this->assertFalse( $this->ingredient->validate( 123 ) );
	}

	public function testExecuteSuccess(): void {
		// Mock WordPress functions as needed
		// See tests/helpers/wp-functions.php for available stubs
		
		$result = $this->ingredient->execute( 'value' );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'Set to value', $result->get_message() );
	}
}
```

### PHPUnit Mocking

Use PHPUnit's native mocking - no external libraries needed:

```php
// Stub - when you don't care about method calls
$stub = $this->createStub( Registry::class );

// Mock - when you need to verify behavior
$mock = $this->createMock( Registry::class );
$mock->expects( $this->once() )
	->method( 'init' )
	->willReturn( true );
```

### WordPress Functions

Custom stubs available in `tests/helpers/wp-functions.php`:
- `add_action()` / `do_action()` - Hook system with priority support
- `add_filter()` / `apply_filters()` - Filter system
- Add more as needed

**Note:** PHPUnit version must match PHP version:
- PHP 7.4 → PHPUnit 9.x
- PHP 8.1+ → PHPUnit 10.x+

---

## Code Style

```php
// Short array syntax (project convention)
[ 'key' => 'value' ]        // ✅
array( 'key' => 'value' )   // ❌

// WordPress spacing
if ( isset( $data['key'] ) ) {  // ✅
if (isset($data['key'])) {      // ❌

// Same-line braces
public function test(): void {  // ✅

// Tabs for indentation
```

---

## Branch Strategy

- `php-7.4` - Baseline (verbose syntax)
- `php-8.0` to `php-8.3` - Progressive modernization
- Each branch self-contained and functional

---

## Commit Messages

```
✨ Add ingredient/feature
🐛 Fix bug
♻️ Refactor code
📝 Update docs
🧪 Add/update tests
```
