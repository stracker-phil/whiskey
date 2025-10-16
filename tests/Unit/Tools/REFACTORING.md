# Tool Test Suite - Phase 2 Refactoring Opportunities

## Phase 1 Results ✅

**Completed:** Base `ToolTest` class with reflection helpers  
**Lines Saved:** 243 lines across 6 test files  
**Impact:** Eliminated duplicate setUp() and reflection boilerplate

---

## Identified Patterns for Phase 2

### Pattern 1: Config Testing Boilerplate ⭐⭐⭐

**Frequency:** 12 tests (2 per tool class)  
**Current State:** Nearly identical structure, only values differ

```php
// REST Config - Appears in ALL 6 tool tests
public function testGetRestConfigReturnsConfiguration(): void {
    $config = $this->invoke_protected_method( $this->tool, 'get_rest_config' );
    
    $this->assertIsArray( $config );
    $this->assertSame( 'GET', $config['method'] );          // Only this differs
    $this->assertSame( '/recipes', $config['path'] );       // Only this differs
}

// CLI Config - Appears in ALL 6 tool tests
public function testGetCliConfigReturnsConfiguration(): void {
    $config = $this->invoke_protected_method( $this->tool, 'get_cli_config' );
    
    $this->assertIsArray( $config );
    $this->assertSame( 'whiskey recipes', $config['command'] );      // Only this differs
    $this->assertStringContainsString( 'recipes', $config['synopsis'] ); // Only this differs
}
```

**Duplication:** 7-8 lines × 12 tests = ~90 lines of near-identical code

**Proposed Solution: Assertion Helper Methods**

```php
// In ToolTest base class
protected function assert_rest_config(
    string $expected_method,
    string $expected_path_fragment
): void {
    $config = $this->invoke_protected_method( $this->tool, 'get_rest_config' );
    
    $this->assertIsArray( $config );
    $this->assertSame( $expected_method, $config['method'] );
    $this->assertStringContainsString( $expected_path_fragment, $config['path'] );
}

protected function assert_cli_config(
    string $expected_command,
    string $expected_synopsis_fragment
): void {
    $config = $this->invoke_protected_method( $this->tool, 'get_cli_config' );
    
    $this->assertIsArray( $config );
    $this->assertSame( $expected_command, $config['command'] );
    $this->assertStringContainsString( $expected_synopsis_fragment, $config['synopsis'] );
}
```

**Usage in Concrete Tests:**

```php
// Before: 8 lines
public function testGetRestConfigReturnsConfiguration(): void {
    $config = $this->invoke_protected_method( $this->tool, 'get_rest_config' );
    $this->assertIsArray( $config );
    $this->assertSame( 'GET', $config['method'] );
    $this->assertSame( '/recipes', $config['path'] );
}

// After: 3 lines
public function testGetRestConfigReturnsConfiguration(): void {
    $this->assert_rest_config( 'GET', '/recipes' );
}
```

**Estimated Savings:** ~60 lines across all tool tests

**Trade-offs:**
- ✅ Significant reduction in duplication
- ✅ Config tests become one-liners
- ⚠️ Hides the assertion details (but they're always the same)
- ⚠️ Need to decide: exact match or fragment match for paths?

**Decision Required:** Should `path` use exact match or `assertStringContainsString`?
- StatusTool: `/status` (exact)
- ShowRecipeTool: `/recipe/(?P<n>[a-zA-Z0-9-_]+)` (regex pattern)
- ApplyRecipeTool: `/recipe/(?P<n>[a-zA-Z0-9-_]+)/apply` (complex pattern)

**Recommendation:** Use `assertStringContainsString` for flexibility, or provide both helpers.

---

### Pattern 2: Stub-with-Return-Value Creation ⭐⭐

**Frequency:** Appears in ~20 tests  
**Current State:** 4-5 lines to create stub with specific behavior

```php
// Creating a stub with a return value - very common pattern
$recipes = $this->createStub( RecipeRegistry::class );
$recipes->method( 'get' )->willReturn( $recipe_config );

$tool = new ShowRecipeTool( $recipes, $this->ingredients, $this->executor );
```

**Proposed Solution: Factory Helper Methods**

```php
// In ToolTest base class
protected function create_recipes_stub_returning( $method_name, $return_value ): RecipeRegistry {
    $stub = $this->createStub( RecipeRegistry::class );
    $stub->method( $method_name )->willReturn( $return_value );
    return $stub;
}

protected function create_ingredients_stub_returning( $method_name, $return_value ): IngredientRegistry {
    $stub = $this->createStub( IngredientRegistry::class );
    $stub->method( $method_name )->willReturn( $return_value );
    return $stub;
}

protected function create_executor_stub_returning( $method_name, $return_value ): RecipeExecutor {
    $stub = $this->createStub( RecipeExecutor::class );
    $stub->method( $method_name )->willReturn( $return_value );
    return $stub;
}

// More specific helper for common case
protected function create_tool_with_recipe_stub( $tool_class, $return_value ) {
    $recipes = $this->create_recipes_stub_returning( 'get', $return_value );
    return new $tool_class( $recipes, $this->ingredients, $this->executor );
}
```

**Usage:**

```php
// Before: 4 lines
$recipes = $this->createStub( RecipeRegistry::class );
$recipes->method( 'get' )->willReturn( $recipe_config );
$tool = new ShowRecipeTool( $recipes, $this->ingredients, $this->executor );
$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'test' ] ] );

// After: 2 lines
$tool = $this->create_tool_with_recipe_stub( ShowRecipeTool::class, $recipe_config );
$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [ 'name' => 'test' ] ] );
```

**Estimated Savings:** ~40-50 lines across all tests

**Trade-offs:**
- ✅ Reduces repetitive stub creation
- ✅ Clarifies test intent (what data is being stubbed)
- ⚠️ Adds another layer of abstraction
- ⚠️ Not flexible for complex mock expectations
- ❌ Less explicit about what's being stubbed

**Recommendation:** Consider carefully. This might obscure test setup too much. The current pattern is already quite clear.

---

### Pattern 3: Format Method Smoke Tests ⭐

**Frequency:** ~12-15 tests  
**Current State:** Just verify method doesn't throw exception

```php
public function testFormatCliOutputWithRecipes(): void {
    // Test with recipes - should not throw exception
    $this->invoke_protected_method(
        $this->tool,
        'format_cli_output',
        [ [ 'recipes' => [ 'recipe1', 'recipe2' ] ] ]
    );
    
    $this->assertTrue( true );
}
```

**Analysis:**
- These are smoke tests - just checking methods don't crash
- Not testing actual behavior or output
- The `$this->assertTrue(true)` is a code smell

**Proposed Solution: Smoke Test Helper**

```php
// In ToolTest base class
protected function assert_method_does_not_throw( string $method_name, array $args ): void {
    // If method throws, test fails automatically
    // No need for explicit assertion
    $this->invoke_protected_method( $this->tool, $method_name, $args );
}
```

**Usage:**

```php
// Before: 9 lines
public function testFormatCliOutputWithRecipes(): void {
    // Test with recipes - should not throw exception
    $this->invoke_protected_method(
        $this->tool,
        'format_cli_output',
        [ [ 'recipes' => [ 'recipe1', 'recipe2' ] ] ]
    );
    $this->assertTrue( true );
}

// After: 3 lines (or inline in a data provider)
public function testFormatCliOutputWithRecipes(): void {
    $this->assert_method_does_not_throw( 'format_cli_output', [ [ 'recipes' => [ 'recipe1', 'recipe2' ] ] ] );
}
```

**Estimated Savings:** ~10-15 lines

**Trade-offs:**
- ✅ More explicit about test intent
- ✅ Removes the awkward `assertTrue(true)`
- ⚠️ Still smoke tests - could be improved by testing actual output
- ⚠️ Limited value - only saves 1-2 lines per test

**Recommendation:** LOW PRIORITY. The helper is marginally better, but the real solution is to test actual behavior instead of smoke tests.

---

### Pattern 4: Custom Tool Instance Creation ⭐⭐

**Frequency:** Most tests with custom stub behavior  
**Current State:** Repeated tool instantiation with custom stubs

```php
$ingredients = $this->createStub( IngredientRegistry::class );
$ingredients->method( 'all' )->willReturn( [ 'ing1' => 'Class1', 'ing2' => 'Class2' ] );

$tool = new StatusTool( $this->recipes, $ingredients, $this->executor );
$result = $this->invoke_protected_method( $tool, 'handle_logic', [ [] ] );
```

**Analysis:**
- Mixing two concerns: stub setup + tool instantiation
- The tool instantiation line is always the same pattern
- The real variability is in the stub configuration

**Proposed Solution: Don't Extract**

This pattern is actually quite readable and explicit. Extracting it would hide what's being customized.

**Recommendation:** SKIP - Leave as-is for clarity

---

### Pattern 5: Array Assertion Chains ⭐

**Frequency:** Many tests  
**Current State:** Multiple assertions on array structure

```php
$this->assertIsArray( $result );
$this->assertArrayHasKey( 'name', $result );
$this->assertArrayHasKey( 'category', $result );
$this->assertArrayHasKey( 'description', $result );
$this->assertSame( 'test-ingredient', $result['name'] );
$this->assertSame( 'wordpress', $result['category'] );
```

**Proposed Solution: Assertion Helper**

```php
// In ToolTest base class
protected function assert_array_structure( array $array, array $expected_keys_and_values ): void {
    $this->assertIsArray( $array );
    
    foreach ( $expected_keys_and_values as $key => $expected_value ) {
        $this->assertArrayHasKey( $key, $array );
        if ( $expected_value !== null ) {
            $this->assertSame( $expected_value, $array[ $key ] );
        }
    }
}
```

**Usage:**

```php
// Before: 6 lines
$this->assertIsArray( $result );
$this->assertArrayHasKey( 'name', $result );
$this->assertArrayHasKey( 'category', $result );
$this->assertSame( 'test-ingredient', $result['name'] );
$this->assertSame( 'wordpress', $result['category'] );

// After: 5 lines (saves only 1 line, but more declarative)
$this->assert_array_structure( $result, [
    'name'     => 'test-ingredient',
    'category' => 'wordpress',
    'description' => null, // present but not checked
] );
```

**Estimated Savings:** ~20 lines, but more about readability

**Trade-offs:**
- ✅ More declarative - shows expected structure at a glance
- ✅ Reduces repetitive assertion chains
- ⚠️ Less granular failure messages
- ⚠️ Mixing "has key" and "equals value" checks might be confusing

**Recommendation:** MAYBE - Useful for complex result arrays, but might not save much

---

## Summary & Recommendations

### High Value (Implement)
1. **Config Testing Helpers** ⭐⭐⭐
   - Clear win: ~60 lines saved
   - Makes config tests trivial
   - Low risk of obscuring important details

### Medium Value (Consider)
2. **Stub Factory Methods** ⭐⭐
   - Moderate savings: ~40-50 lines
   - Risk of obscuring test setup
   - **Decision:** Only if it improves readability significantly

3. **Array Structure Assertions** ⭐
   - Modest savings: ~20 lines
   - Better declarative style
   - **Decision:** Good for complex arrays, overkill for simple ones

### Low Value (Skip)
4. **Smoke Test Helper** ⭐
   - Minimal savings: ~10-15 lines
   - Better to improve tests than wrap them
   - **Decision:** SKIP - Fix the smoke tests instead

5. **Custom Tool Instance Pattern**
   - No real benefit to extraction
   - **Decision:** SKIP - Keep explicit

---

## Phase 2 Implementation Plan

### Option A: Conservative (Recommended)
**Implement only Pattern 1 (Config Helpers)**
- Add `assert_rest_config()` and `assert_cli_config()` to ToolTest
- Update all 12 config tests to use helpers
- Estimated effort: 30 minutes
- Estimated savings: ~60 lines

### Option B: Moderate
**Implement Patterns 1 + 3**
- Add config helpers + array structure helper
- Update tests where applicable
- Estimated effort: 1 hour
- Estimated savings: ~80 lines

### Option C: Aggressive
**Implement Patterns 1 + 2 + 3**
- Add all helpers
- Refactor tests comprehensively
- Estimated effort: 2-3 hours
- Estimated savings: ~120-130 lines
- Risk: Over-abstraction

---

## Decision Criteria

**Questions to ask:**
1. Does the helper improve readability, or just reduce lines?
2. Will future developers understand what's being tested?
3. Does it hide important test details?
4. Is the pattern frequent enough to justify abstraction?

**My recommendation:** Start with **Option A** (Config Helpers only). They're a clear win with minimal downside. Evaluate the results before considering further abstraction.
