# PHP 7.4 Baseline

**Branch:** `php-7.4`  
**Completion Date:** October 18, 2025

This baseline captures metrics and PHP 7.4 patterns before introducing PHP 8.0+ improvements.

---

## Baseline Metrics

### Code Statistics

| Metric           | Value |
|------------------|-------|
| Lines of Code    | 1,601 |
| Executable Lines | 1,307 |
| Classes          | 29    |
| Methods          | 113   |
| Test Coverage    | 100%  |

### Complexity Metrics

| Metric                    | Value | Target (PHP 8.3)      |
|---------------------------|-------|-----------------------|
| Avg Cyclomatic Complexity | 8.83  | ≤7.06 (20% reduction) |
| Highest Method CCN        | 10    | ≤8                    |
| Maintainability Index     | 62.23 | ≥65                   |

### Complexity Distribution

| Class              | CCN | Priority |
|--------------------|-----|----------|
| RecipeExecutor     | 17  | High     |
| RestController     | 12  | Medium   |
| IngredientRegistry | 11  | Medium   |
| All others         | <10 | Low      |

---

## PHP 7.4 Language Patterns

These patterns demonstrate PHP 7.4 capabilities and limitations:

### 1. Typed Properties with Constructor Assignment

**Used in:** All 29 classes

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

**Lines:** ~180 LOC across codebase (3 lines per property × 60 properties)

### 2. Strict Type Declarations

**Used in:** Every file

```php
declare(strict_types=1);
```

Combined with typed properties and return types throughout.

### 3. Null Coalescing Operator

**Used in:** 47 locations

```php
$extends = $config[self::EXTENDS] ?? null;
$errors = $result->get_errors() ?? [];
```

Works well, no improvements needed in PHP 8.0+.

### 4. Untyped Parameters (No Mixed Type)

**Used in:** Abstract base class

```php
abstract public function validate($value): bool;
abstract public function execute($value): ExecutionResult;
```

Cannot type as `mixed` in PHP 7.4.

### 5. Array Return Types

**Used in:** Registry methods

```php
public function get_all(): array {
    return $this->items;
}
```

Cannot specify array contents (`string[]`, `Ingredient[]`).

### 6. Class Constants Without Types

**Used in:** 45 usages in 15 classes

```php
public const NAME = '';
public const CATEGORY = 'general';
public const DESCRIPTION = '';
```

No type enforcement in PHP 7.4.

---

## PHP 8.0 Identified Improvements

Ordered by implementation priority:

1. **Constructor Property Promotion** – Eliminate ~180 LOC (all 29 classes)
2. **Mixed Type Hint** – Type the abstract `Ingredient` methods
3. **Named Arguments** – Simplify `ExecutionResult` instantiation (~30 calls)
4. **Attributes** – Replace class constants for "ingredient" metadata (14 ingredient classes)

**Expected impact:** ~180 LOC reduction, minimal CCN improvement (constructor promotion is syntax, not logic simplification)
