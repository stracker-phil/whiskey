# PHP 7.4 Baseline

**Branch:** `php-7.4`  
**Completion Date:** October 24, 2025

This baseline captures metrics and PHP 7.4 patterns before introducing PHP 8.0+ improvements.

---

## Baseline Metrics

### Code Statistics

| Metric           | Value |
|------------------|-------|
| Lines of Code    | 2,128 |
| Executable Lines | 1,655 |
| Classes          | 34    |
| Methods          | 138   |
| Test Coverage    | 100%  |

### Complexity Metrics

| Metric                    | Value | Target (PHP 8.3)      |
|---------------------------|-------|-----------------------|
| Avg Cyclomatic Complexity | 9.50  | ≤7.60 (20% reduction) |
| Highest Method CCN        | 13    | ≤10                   |
| Maintainability Index     | 47.88 | ≥57                   |

### Complexity Distribution

| Class                       | CCN | Priority |
|-----------------------------|-----|----------|
| ApplyRecipeTool             | 31  | High     |
| SetPayPalMerchantIngredient | 24  | High     |
| ProcessImagesIngredient     | 21  | High     |
| RecipeExecutor              | 20  | High     |
| SetMenuItemsIngredient      | 13  | Medium   |
| SetWooStorePagesIngredient  | 11  | Medium   |
| All others                  | <10 | Low      |

---

## PHP 7.4 Language Patterns

These patterns demonstrate PHP 7.4 capabilities and limitations:

### 1. Typed Properties with Constructor Assignment

**Used in:** All 34 classes

```php
class Main {
    private RecipeRegistry $recipes;
    private IngredientRegistry $ingredients;
    
    public function __construct(
        RecipeRegistry $recipes,
        IngredientRegistry $ingredients
    ) {
        $this->recipes = $recipes;
        $this->ingredients = $ingredients;
    }
}
```

### 2. Strict Type Declarations

**Used in:** Every file

```php
declare(strict_types=1);
```

Combined with typed properties and return types throughout.

### 3. Null Coalescing Operator

**Used in:** 47+ locations

```php
$extends = $config[self::EXTENDS] ?? null;
$errors = $result->get_errors() ?? [];
```

Works well, no improvements needed in PHP 8.0+.

### 4. Untyped Parameters (No Mixed Type)

**Used in:** Abstract base class

```php
abstract public function validate($value): ValidationResult;
```

Cannot type as `mixed` in PHP 7.4. The `execute()` method is implemented as private in each ingredient, accepting type-safe parameters via closures.

### 5. Array Return Types

**Used in:** Registry methods

```php
public function get_all(): array {
    return $this->items;
}
```

Cannot specify array contents (`string[]`, `Ingredient[]`).

### 6. Class Constants Without Types

**Used in:** 45+ usages across classes

```php
public const NAME = '';
public const CATEGORY = 'general';
public const DESCRIPTION = '';
```

No type enforcement in PHP 7.4.

### 7. Enum-like Classes

**Used in:** ValidationCode, ExecutionStrategy, IngredientCategory

```php
class ExecutionStrategy {
    public const STOP_ON_FAILURE = 'stop_on_failure';
    public const CONTINUE_ON_FAILURE = 'continue_on_failure';
    
    public function should_stop_on_failure(): bool {
        return $this->value === self::STOP_ON_FAILURE;
    }
}
```

Verbose pattern with switch statements for logic.

---

## PHP 8.0 Identified Improvements

Ordered by implementation priority:

1. **Constructor Property Promotion**
2. **Mixed Type Hint** – Type the abstract `Ingredient` methods
3. **Attributes** – Replace class constants for ingredient metadata
4. **Named Arguments** – Simplify `ProcessImagesIngredient` calls, `ExecutionResult` instantiation
5. **Enums** – Convert ValidationCode, ExecutionStrategy, IngredientCategory to native enums
