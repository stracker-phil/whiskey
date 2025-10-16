# Tool Test Suite Refactoring Strategy

## Project Context

**Whiskey** is a WordPress configuration plugin demonstrating PHP language improvements from 7.4 to 8.3. The plugin uses a tool-based architecture where each tool encapsulates business logic for both REST API endpoints and WP-CLI commands.

**Current PHP Version:** 7.4  
**Testing Framework:** PHPUnit 9.x  
**Base Test Class:** `WhiskeyTest` (provides WordPress hook reset)

## Current Test Structure Overview

### Test Files

The `tests/Unit/Tools/` directory contains tests for seven tool classes:

1. **WhiskeyToolTest.php** - Tests the abstract base `WhiskeyTool` class
2. **ApplyRecipeToolTest.php** - Tests recipe execution logic
3. **ListRecipesToolTest.php** - Tests recipe listing
4. **ShowRecipeToolTest.php** - Tests single recipe display
5. **ListIngredientsToolTest.php** - Tests ingredient listing
6. **ShowIngredientToolTest.php** - Tests single ingredient display
7. **StatusToolTest.php** - Tests plugin status reporting

### Common Architecture Pattern

All tool test classes follow a consistent pattern:

```php
class SomeToolTest extends WhiskeyTest {
    private SomeTool $tool;
    private RecipeRegistry $recipes;
    private IngredientRegistry $ingredients;
    private RecipeExecutor $executor;

    protected function setUp(): void {
        parent::setUp();
        $this->recipes = $this->createStub(RecipeRegistry::class);
        $this->ingredients = $this->createStub(IngredientRegistry::class);
        $this->executor = $this->createStub(RecipeExecutor::class);
        $this->tool = new SomeTool($this->recipes, $this->ingredients, $this->executor);
    }

    // ... test methods
}
```

## Identified Patterns for Extraction

### 1. Common Setup Pattern

**Every tool test** instantiates the same three dependencies:
- `RecipeRegistry` (stubbed)
- `IngredientRegistry` (stubbed)
- `RecipeExecutor` (stubbed)

**Location:** `setUp()` method in each test class  
**Frequency:** 7 occurrences (100% of tool tests)

### 2. Reflection-Based Method Testing

**Pattern:** Using PHP Reflection to test protected methods

```php
$reflection = new ReflectionClass($this->tool);
$method = $reflection->getMethod('get_rest_config');
$method->setAccessible(true);
$config = $method->invoke($this->tool);
```

**Locations:**
- `testGetRestConfigReturnsConfiguration()` - 6 tool test classes
- `testGetCliConfigReturnsConfiguration()` - 6 tool test classes
- `testHandleLogic*()` methods - All tool test classes
- Various other protected method tests

**Frequency:** ~40+ occurrences across all test files

### 3. Configuration Testing Structure

**Pattern:** Testing REST and CLI config methods with consistent assertions

```php
public function testGetRestConfigReturnsConfiguration(): void {
    // Reflection setup
    $config = // invoke method
    
    $this->assertIsArray($config);
    $this->assertSame('METHOD', $config['method']);
    $this->assertStringContainsString('path-fragment', $config['path']);
}

public function testGetCliConfigReturnsConfiguration(): void {
    // Reflection setup
    $config = // invoke method
    
    $this->assertIsArray($config);
    $this->assertSame('command string', $config['command']);
    $this->assertStringContainsString('keyword', $config['synopsis']);
}
```

**Frequency:** 2 tests per tool class (12 total tests with near-identical structure)

### 4. Argument Extraction Testing

**Pattern:** Testing name parameter extraction from multiple argument formats

```php
// Tests extraction from 'name' key
$result = $method->invoke($tool, ['name' => 'test-value']);

// Tests extraction from positional arg [0]
$result = $method->invoke($tool, [0 => 'test-value']);
```

**Frequency:** Most tool tests include both variations

### 5. Exception Testing Pattern

**Pattern:** Testing error conditions with consistent exception assertions

```php
$this->expectException(Exception::class);
$this->expectExceptionMessage('Expected message');

$method->invoke($tool, $args);
```

**Common error cases:**
- Missing required parameter (name)
- Resource not found (recipe/ingredient)
- Invalid configuration
- Execution failure

**Frequency:** 2-5 exception tests per tool class

### 6. Mock/Stub Creation with Custom Behavior

**Pattern:** Creating stubs with specific return values for testing

```php
$recipes = $this->createStub(RecipeRegistry::class);
$recipes->method('get')->willReturn($recipe_config);

$tool = new SomeTool($recipes, $this->ingredients, $this->executor);
```

**Frequency:** Appears in most test methods that need specific behavior

### 7. WP_CLI Message Verification

**Pattern:** Verifying CLI output by checking captured log messages

```php
$method->invoke($this->tool, $data);

$messages = \WP_CLI::get_log_messages();
$this->assertContains('expected message', $messages);
```

**Frequency:** In all `testFormatCliOutput*()` methods

## Duplication Analysis

### High Duplication (90-100% similar)

1. **setUp() method** - Identical across all 6 concrete tool tests
2. **Config testing methods** - Very similar structure with only string values different
3. **Reflection boilerplate** - Identical `ReflectionClass` setup in 40+ places

### Medium Duplication (50-80% similar)

4. **Exception testing** - Same pattern, different messages
5. **Argument extraction tests** - Same structure, different argument names
6. **WP_CLI message verification** - Same pattern, different expected messages

### Low Duplication (patterns, but context-dependent)

7. **handle_logic() tests** - Similar structure but business logic varies significantly
8. **Format method tests** - Similar pattern but formatting details differ per tool

## Proposed Base Class: `ToolTest`

### Purpose

Create an abstract `ToolTest` class (extending `WhiskeyTest`) to:
1. Eliminate duplicate dependency setup
2. Provide helper methods for reflection-based testing
3. Simplify common test patterns
4. Maintain type safety and readability
5. Reduce maintenance burden

### Design Principles

✅ **Extract only high-duplication patterns** (90%+ similarity)  
✅ **Maintain readability** - helpers should make tests clearer, not obscure them  
✅ **Preserve type safety** - no loss of IDE support or type hints  
✅ **Keep tests explicit** - don't hide critical test logic in base class  
✅ **Follow project conventions** - match existing style and patterns

### Proposed Structure

```php
<?php
declare(strict_types = 1);

namespace Whiskey\Tests\Unit\Tools;

use Whiskey\Tests\Unit\WhiskeyTest;
use Whiskey\Tools\WhiskeyTool;
use Whiskey\Registry\RecipeRegistry;
use Whiskey\Registry\IngredientRegistry;
use Whiskey\RecipeExecutor;
use ReflectionClass;
use ReflectionMethod;

/**
 * Base class for tool tests providing common setup and helper methods.
 */
abstract class ToolTest extends WhiskeyTest {
    protected RecipeRegistry $recipes;
    protected IngredientRegistry $ingredients;
    protected RecipeExecutor $executor;

    protected function setUp(): void {
        parent::setUp();
        $this->recipes = $this->createStub(RecipeRegistry::class);
        $this->ingredients = $this->createStub(IngredientRegistry::class);
        $this->executor = $this->createStub(RecipeExecutor::class);
    }

    /**
     * Helper: Call a protected method on the tool via reflection.
     *
     * @param WhiskeyTool $tool
     * @param string $method_name
     * @param array $args
     * @return mixed
     */
    protected function invoke_protected_method(
        WhiskeyTool $tool,
        string $method_name,
        array $args = []
    ) {
        $reflection = new ReflectionClass($tool);
        $method = $reflection->getMethod($method_name);
        $method->setAccessible(true);
        
        return $method->invoke($tool, ...$args);
    }

    /**
     * Helper: Get a ReflectionMethod for a protected method.
     *
     * Useful when you need the method object for multiple calls.
     *
     * @param WhiskeyTool $tool
     * @param string $method_name
     * @return ReflectionMethod
     */
    protected function get_protected_method(
        WhiskeyTool $tool,
        string $method_name
    ): ReflectionMethod {
        $reflection = new ReflectionClass($tool);
        $method = $reflection->getMethod($method_name);
        $method->setAccessible(true);
        
        return $method;
    }
}
```

## Refactoring Impact

### Before: ApplyRecipeToolTest (example)

```php
class ApplyRecipeToolTest extends WhiskeyTest {
    private ApplyRecipeTool $tool;
    private RecipeRegistry $recipes;
    private IngredientRegistry $ingredients;
    private RecipeExecutor $executor;

    protected function setUp(): void {
        parent::setUp();
        $this->recipes = $this->createStub(RecipeRegistry::class);
        $this->ingredients = $this->createStub(IngredientRegistry::class);
        $this->executor = $this->createStub(RecipeExecutor::class);
        $this->tool = new ApplyRecipeTool($this->recipes, $this->ingredients, $this->executor);
    }

    public function testGetRestConfigReturnsConfiguration(): void {
        $reflection = new ReflectionClass($this->tool);
        $method = $reflection->getMethod('get_rest_config');
        $method->setAccessible(true);

        $config = $method->invoke($this->tool);

        $this->assertIsArray($config);
        // ... assertions
    }

    public function testHandleLogicThrowsExceptionWhenNameMissing(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Recipe name is required');

        $reflection = new ReflectionClass($this->tool);
        $method = $reflection->getMethod('handle_logic');
        $method->setAccessible(true);

        $method->invoke($this->tool, []);
    }
}
```

### After: Using ToolTest Base Class

```php
class ApplyRecipeToolTest extends ToolTest {
    private ApplyRecipeTool $tool;

    protected function setUp(): void {
        parent::setUp(); // Now handles all dependency setup
        $this->tool = new ApplyRecipeTool($this->recipes, $this->ingredients, $this->executor);
    }

    public function testGetRestConfigReturnsConfiguration(): void {
        $config = $this->invoke_protected_method($this->tool, 'get_rest_config');

        $this->assertIsArray($config);
        // ... assertions
    }

    public function testHandleLogicThrowsExceptionWhenNameMissing(): void {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Recipe name is required');

        $this->invoke_protected_method($this->tool, 'handle_logic', [[]]);
    }
}
```

## Benefits

### Code Reduction

**Estimated impact:**
- **Remove ~42 lines** of duplicate setUp() code (7 lines × 6 classes)
- **Simplify ~40+ reflection blocks** - Each saves 3-4 lines
- **Total reduction:** ~160-200 lines across the test suite

### Maintenance

- **Single point of change** for dependency setup
- **Consistent reflection patterns** across all tool tests
- **Easier to add new tools** - less boilerplate to copy

### Readability

- **Clearer intent** - `invoke_protected_method()` is more descriptive than raw reflection
- **Less noise** - Test logic stands out from reflection mechanics
- **Better focus** - Tests emphasize what they're testing, not how they access it

### Type Safety

- **Preserved** - All type hints remain in place
- **IDE support** - Autocomplete and navigation still work
- **No magic** - Helper methods are explicit and well-typed

## What NOT to Extract

❌ **Test assertions** - Keep in individual tests for clarity  
❌ **Business logic setup** - Tool-specific mocking stays in concrete tests  
❌ **Expected values** - Each tool's config is unique and should be explicit  
❌ **Complex test scenarios** - Only extract truly common patterns

## Implementation Strategy

### Phase 1: Create Base Class
1. Create `tests/Unit/Tools/ToolTest.php`
2. Implement common setup and helper methods
3. Add comprehensive docblocks
4. Write tests for the base class (minimal - mostly smoke tests)

### Phase 2: Migrate Existing Tests (One by One)
1. **Start with simplest test:** StatusToolTest
   - Change `extends WhiskeyTest` to `extends ToolTest`
   - Remove duplicate setUp() code
   - Replace reflection blocks with helper methods
   - Run tests to verify no breakage

2. **Continue with remaining tests:**
   - ListRecipesToolTest
   - ListIngredientsToolTest
   - ShowRecipeToolTest
   - ShowIngredientToolTest
   - ApplyRecipeToolTest

3. **Leave WhiskeyToolTest unchanged** - It tests the base WhiskeyTool class and has unique requirements

### Phase 3: Documentation
1. Update this REFACTORING.md with final metrics
2. Add examples of the new pattern to TESTING.md (if needed)
3. Document any lessons learned or edge cases

## Success Criteria

✅ All existing tests still pass  
✅ Code coverage remains at 100%  
✅ Test execution time unchanged (or improved)  
✅ Reduced lines of code (~160-200 lines)  
✅ Improved readability in concrete test classes  
✅ No loss of type safety or IDE support

## Testing the Base Class

The `ToolTest` base class itself needs minimal testing:

```php
/**
 * Tests for ToolTest base class helpers
 */
class ToolTestTest extends WhiskeyTest {
    public function testInvokeProtectedMethodCallsMethod(): void {
        // Create concrete test tool
        // Call invoke_protected_method
        // Verify it works
    }

    public function testGetProtectedMethodReturnsReflectionMethod(): void {
        // Create concrete test tool
        // Call get_protected_method
        // Verify ReflectionMethod is returned and accessible
    }
}
```

**Note:** Since these are helper methods with no complex logic, smoke tests are sufficient.

## Risk Assessment

### Low Risk

✅ **No business logic changes** - Only test infrastructure  
✅ **Incremental migration** - One test class at a time  
✅ **Full test coverage** - Any issues caught immediately  
✅ **Easy rollback** - Can revert individual files if needed

### Potential Issues

⚠️ **IDE confusion** - Some IDEs may struggle with inherited protected properties  
   - **Mitigation:** Use explicit `$this->recipes` consistently

⚠️ **Merge conflicts** - If multiple people modify tests simultaneously  
   - **Mitigation:** Coordinate timing, migrate quickly

⚠️ **Over-abstraction temptation** - May want to extract too much  
   - **Mitigation:** Stick to the plan, only extract high-duplication patterns

## Future Considerations

### If More Tools Are Added

The base class makes adding new tools easier:

```php
class NewToolTest extends ToolTest {
    private NewTool $tool;

    protected function setUp(): void {
        parent::setUp();
        $this->tool = new NewTool($this->recipes, $this->ingredients, $this->executor);
    }

    // Write tests using helper methods - no boilerplate needed
}
```

### If Tool Architecture Changes

If the tool constructor signature changes (e.g., new dependency added):
- Update `ToolTest::setUp()` once
- All tool tests inherit the change automatically
- Minimal update effort across the test suite

### Additional Helpers (Future)

Potential additional helpers to consider after initial refactoring:

```php
// Helper for config testing pattern
protected function assert_rest_config(
    WhiskeyTool $tool,
    string $expected_method,
    string $expected_path_fragment
): void {
    // Common config assertions
}

// Helper for CLI config testing
protected function assert_cli_config(
    WhiskeyTool $tool,
    string $expected_command,
    string $expected_synopsis_fragment
): void {
    // Common config assertions
}
```

**Decision:** Add only if these patterns prove beneficial during Phase 2 migration.

---

## Summary

This refactoring will:
1. Create a `ToolTest` base class with common setup and reflection helpers
2. Migrate 6 tool test classes to use the new base class
3. Reduce ~160-200 lines of duplicate code
4. Improve test readability and maintainability
5. Make adding new tools easier in the future

All changes are low-risk, incremental, and fully testable. The refactoring focuses strictly on high-duplication patterns while preserving clarity and type safety.
