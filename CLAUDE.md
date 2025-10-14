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

### Registration Flow

1. Files loaded from `src/Ingredients/` and `src/Recipes/`
2. Registries fire `do_action('whiskey:register_*', $this)`
3. Files self-register via `add_action('whiskey:register_*')`
4. Lazy instantiation: ingredients created only on `get()`

### Key Patterns

- Manual DI (no container)
- Hook-based registration
- Lazy loading via class names
- WordPress integration (Brain Monkey for tests)

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

## Testing

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

	public function testValidateAcceptsString(): void {
		$this->assertTrue( $this->ingredient->validate( 'valid' ) );
	}

	public function testValidateRejectsInteger(): void {
		$this->assertFalse( $this->ingredient->validate( 123 ) );
	}

	public function testExecuteSuccess(): void {
		when( 'update_option' )->justReturn( true );

		$result = $this->ingredient->execute( 'value' );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'Set to value', $result->get_message() );
	}
}
```

### Brain Monkey Mocking

```php
use function Brain\Monkey\Functions\when;
use function Brain\Monkey\Functions\expect;

// Simple stub
when( 'get_option' )->justReturn( 'value' );

// Conditional
when( 'get_post' )->alias( fn( $id ) => $id === 123 ? (object)['ID' => 123] : null );

// Expectation
expect( 'update_option' )->once()->with( 'key', 'val' )->andReturn( true );
$this->assertedByMockery();
```

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
