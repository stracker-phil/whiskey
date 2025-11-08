# Changes from PHP 8.0 to PHP 8.1

New patterns implemented in code:

- Enums
- Readonly Properties
- Final class constants
- First-class callable syntax
- Use of `array_is_list`

---

## Enums

Usage: **2** enums
> 🔎 Search for `\benum\b` (regex, except comments & string literals)

### Before

```php
class ValidationCode {
    public const VALID                   = 'valid';
    public const INVALID_TYPE            = 'invalid_type';
    public const MISSING_REQUIRED_KEY    = 'missing_required_key';
    public const INVALID_ARRAY_STRUCTURE = 'invalid_array_structure';
    public const INVALID_FORMAT          = 'invalid_format';
    public const INVALID_VALUE           = 'invalid_value';
    
    public static function get_message( string $result, $context = null ): string {
        return match ( $result ) {
            self::VALID => 'Validation passed',
            self::INVALID_TYPE => sprintf( 'Invalid type: expected %s', $context ?? 'unknown' ),
            self::MISSING_REQUIRED_KEY => sprintf( 'Missing required key: %s', $context ?? 'unknown' ),
            self::INVALID_ARRAY_STRUCTURE => 'Invalid array structure',
            self::INVALID_FORMAT => sprintf( 'Invalid format: %s', $context ?? 'unknown' ),
            self::INVALID_VALUE => sprintf( 'Invalid value: %s', $context ?? 'unknown' ),
            default => 'Unknown validation error',
    }
}
```

### After

Note, that I switched from UPPER_CASE to PascalCase constants, as this is the general PSR convention for enums.

```php
enum ValidationCode: string {
    case Valid                 = 'valid';
    case InvalidType           = 'invalid_type';
    case MissingRequiredKey    = 'missing_required_key';
    case InvalidArrayStructure = 'invalid_array_structure';
    case InvalidFormat         = 'invalid_format';
    case InvalidValue          = 'invalid_value';

    public function get_message( mixed $context = null ): string {
        return match ( $this ) {
            self::Valid => 'Validation passed',
            self::InvalidType => sprintf( 'Invalid type: expected %s', $context ?? 'unknown' ),
            self::MissingRequiredKey => sprintf( 'Missing required key: %s', $context ?? 'unknown' ),
            self::InvalidArrayStructure => 'Invalid array structure',
            self::InvalidFormat => sprintf( 'Invalid format: %s', $context ?? 'unknown' ),
            self::InvalidValue => sprintf( 'Invalid value: %s', $context ?? 'unknown' ),
        };
    }
}
```

### Impact

- Type safety: Only defined enum values are allowed
- More expressive and self documenting
- Separation of concerns

---

## Readonly Properties

Usage: **7** constructors
> 🔎 Search for `\sreadonly\s` (regex, except comments & string literals)

### Before

```php
class ExecutionResult {
    public function __construct(
        private bool $success,
        private string $message,
        private array $data = []
    ) {
    }
}
```

### After

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

### Impact

- Guarantees immutability (no accidental modification)
- Code documents which properties are one-time configuration

---

## Final class constants

Usage: **3** constants
> 🔎 Search for `\bfinal\s(public|protected)\sconst\b` (regex, except comments & string literals)

### Before

```php
class ExecutionStrategy {
	public const SEQUENTIAL        = 'sequential';
	public const CONTINUE_ON_ERROR = 'continue_on_error';
	public const DRY_RUN           = 'dry_run';
}
```

### After

```php
class ExecutionStrategy {
	final public const SEQUENTIAL        = 'sequential';
	final public const CONTINUE_ON_ERROR = 'continue_on_error';
	final public const DRY_RUN           = 'dry_run';
}
```

### Impact

- Enforce immutable constants (child classes cannot override the values)

---

## First-class callable syntax

Usage: **3** uses
> 🔎 Search for `\$this->\w+\(\s*\.{3}\s*\)` (regex, except comments & string literals)

### Before

```php
// Passing method as callable using array syntax
register_rest_route(
    $namespace,
    $config['path'],
    [
        'methods'             => $config['method'],
        'callback'            => [ $this, 'handle_rest' ],
        'permission_callback' => $permission_callback,
        'args'                => $config['args'] ?? [],
    ]
);
```

### After

```php
// Using first-class callable syntax
register_rest_route(
    $namespace,
    $config['path'],
    [
        'methods'             => $config['method'],
        'callback'            => $this->handle_rest( ... ),
        'permission_callback' => $permission_callback,
        'args'                => $config['args'] ?? [],
    ]
);
```

### Impact

- Cleaner, more explicit syntax for creating callables
- Better IDE support and refactoring capabilities
- Creates a proper Closure object instead of array representation

---

## Use of `array_is_list`

Usage: **1** use
> 🔎 Search for `array_is_list(`

### Before

```php
// Manual check if array is sequential (0-indexed list)
if ( array_keys( $array ) !== range( 0, count( $array ) - 1 ) ) {
    return false;
}
```

### After

```php
// Built-in function for checking sequential arrays
if ( ! array_is_list( $array ) ) {
    return false;
}
```

### Impact

- Much clearer intent of the code, shorter syntax
- Better performance (native implementation)

---

## Unused patterns

- No opportunity to use `never` return type, and `new` in initializers
