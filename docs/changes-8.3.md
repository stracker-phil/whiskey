# Changes from PHP 8.2 to PHP 8.3

New patterns implemented in code:

- Typed Class Constants
- Override Attribute

---

## Typed Class Constants

Usage: **19** classes, **72** constants
> 🔎 Search for `\sconst \w` (regex)

### Before

```php
abstract class Ingredient {
    public const NAME = '';
    public const CATEGORY = IngredientCategory::General;
    public const DESCRIPTION = '';
}

class RestController {
    private const NAMESPACE = 'whiskey/v1';
}
```

### After

```php
abstract class Ingredient {
    public const string NAME = '';
    public const IngredientCategory CATEGORY = IngredientCategory::General;
    public const string DESCRIPTION = '';
}

class RestController {
    private const string NAMESPACE = 'whiskey/v1';
}
```

### Impact

- Type safety. Impossible to accidentally assign wrong type to constants
- Static analysis. Better IDE support and compile-time checking

---

## #[Override] Attribute

Usage: **6** tool classes, **8** method overrides
> 🔎 Search for `#\[\\Override\]` (regex)

### Before

```php
class ApplyRecipeTool extends WhiskeyTool {
    protected function format_rest_success( array $data ): WP_REST_Response {
        // Custom REST response formatting
    }

    protected function format_cli_output( array $data ): void {
        // Custom CLI output formatting
    }
}
```

### After

```php
class ApplyRecipeTool extends WhiskeyTool {
    #[\Override]
    protected function format_rest_success( array $data ): WP_REST_Response {
        // Custom REST response formatting
    }

    #[\Override]
    protected function format_cli_output( array $data ): void {
        // Custom CLI output formatting
    }
}
```

### Impact

- Typo protection. Prevents silent bugs from method name typos (e.g., `format_cli_ouput` would fail)
- Refactoring safety. Catches errors if parent method signature changes or gets removed

Note: Only applied to optional method overrides, not abstract method implementations where PHP already enforces signature matching.
