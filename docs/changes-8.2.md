# Changes from PHP 8.1 to PHP 8.2

New patterns implemented in code:

- Readonly Classes
- DNF Types (Nullable Type Notation)
- Standalone true/false types

---

## Readonly Classes

Usage: **2** classes
> 🔎 Search for `\breadonly class\b` (regex, except comments & string literals)

### Before

```php
class ExecutionResult {
    public function __construct(
        private readonly bool $success,
        private readonly string $message,
        private readonly array $data = []
    ) {
    }
}
```

### After

```php
readonly class ExecutionResult {
    public function __construct(
        private bool $success,
        private string $message,
        private array $data = []
    ) {
    }
}
```

### Impact

- Cleaner syntax: Single `readonly` keyword instead of repeating on each property
- Enforces immutability at class level - impossible to add mutable properties
- Self-documenting: Signals this is an immutable value object

---

## DNF Types (Nullable Type Notation)

Usage: **3** files, **7** parameters
> 🔎 Search for `\w+\|null` (regex, except comments & string literals)

### Before

```php
public static function from_string( ?string $value ): string

private function create_image_size(
    int $width,
    ?int $height = null,
    ?bool $crop = false
): bool|string
```

### After

```php
public static function from_string( string|null $value ): string

private function create_image_size(
    int $width,
    int|null $height = null,
    bool|null $crop = false
): bool|string
```

### Impact

- Consistent syntax: All union types use `Type|null` notation
- Modern convention: Aligns with PHP 8.0+ union type syntax

---

## Standalone true/false Types

Usage: **2** functions
> 🔎 Search for `:\s*(true|false)\s*\{` (regex)

### Before

```php
public function permission_callback(): bool

private function create_image_size( ...args ): bool|string
```

### After

```php
public function permission_callback(): true

private function create_image_size( ...args ): false|string
```

### Impact

- Maximum type safety: Impossible to accidentally return wrong boolean value
- Self-documenting: Clear what boolean value is returned on each branch

---
