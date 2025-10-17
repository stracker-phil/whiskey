# Testing Guidelines

## Framework

**Use PHPUnit native features only** - no external mocking libraries (Brain Monkey, Mockery)

**PHPUnit version must match PHP version:**

- PHP 7.4 → PHPUnit 9.x (`^9.6`)
- PHP 8.1+ → PHPUnit 10.x+

## Test Structure

**Extend `WhiskeyTest`** - provides WordPress hook reset between tests

**Use `setUp()` to eliminate duplication:**

```php
protected function setUp(): void {
    parent::setUp();
    $this->dependency = $this->createMock( Dependency::class );
    $this->sut = new SystemUnderTest( $this->dependency );
}
```

**Test file location mirrors source:**

- `src/RecipeExecutor.php` → `tests/Unit/RecipeExecutorTest.php`
- `src/Ingredients/SetHomepage.php` → `tests/Unit/Ingredients/SetHomepageTest.php`

## Mocking Strategy

**Prefer stubs, only use mocks when needed:**

```php
// Stub - when you don't care about method calls
$stub = $this->createStub( Registry::class );

// Mock - when you need to verify behavior
$mock = $this->createMock( Registry::class );
$mock->expects( $this->once() )->method( 'init' );
```

**Rule:** Use `createStub()` by default. Upgrade to `createMock()` only when asserting method calls.

## WordPress Functions

**Custom stubs in `tests/helpers/`** - no external dependencies

**Available functions:**

- `add_action()` / `do_action()`
- `add_filter()` / `apply_filters()`
- Add more as needed in `tests/helpers/wp-functions.php`

**Hooks reset automatically** via `WhiskeyTest::setUp()` → `WP_Hooks::reset()`

## Coverage Goal

**Target: 100% coverage for all business logic**

**Acceptable exceptions:**

- WordPress glue code (Main class with side effects)
- Filesystem operations (`glob()`, `include_once`)
- Integration points requiring real WordPress

**Check coverage:** `ddev composer coverage`

## Quick Reference

**Run unit tests**

```bash
ddev composer test
```

This command generates a detailed test coverage report at:
https://whiskey.ddev.site/coverage

**Run a single test**

```bash
ddev exec vendor/bin/phpunit --filter RecipeExecutor
```

**Output test coverage to terminal**

```bash
ddev composer coverage
```

## Example Test

```php
<?php
declare( strict_types = 1 );

namespace Whiskey\Tests\Unit;

use Whiskey\RecipeExecutor;
use Whiskey\Registry\IngredientRegistry;

/**
 * @covers RecipeExecutor
 */
class RecipeExecutorTest extends WhiskeyTest {
    private IngredientRegistry $ingredients;
    private RecipeExecutor $executor;

    protected function setUp(): void {
        parent::setUp();
        
        $this->ingredients = $this->createMock( IngredientRegistry::class );
        $this->executor = new RecipeExecutor( $this->ingredients );
    }

    public function test_validate_returns_false_for_empty_config(): void {
        $result = $this->executor->validate( array() );
        
        $this->assertFalse( $result );
    }
}
```

## Code Standards

- All functions use snake_case, especially test functions
- Only exceptions are assert-helpers, which stay in camelCase for compatibility with PHPUnit built-in assertions
