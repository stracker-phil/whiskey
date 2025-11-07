# Changes from PHP 7.4 to PHP 8.0

New patterns implemented in code:

- Constructor Property Promotion
- Match expression
- Type-hint for `mixed`
- Union Types
- Named Arguments
- Use of `str_contains`

---

## Constructor Property Promotion

Usage: **7** classes
> 🔎 Search for `__construct\(\s*p` (using regex)

### Before

```php
class RestController {
    /**
     * @param WhiskeyTool[] $tools
     */
    private array $tools;
    
    /**
     * @param WhiskeyTool[] $tools
     */
    public function __construct( array $tools ) {
        $this->tools = $tools;
    }
}
```

### After

```php
class RestController {
    /**
     * @param WhiskeyTool[] $tools
     */
    public function __construct( private array $tools ) {
    }
}
```

### Impact

- Much cleaner and shorter code, less boilerplate

---

### Match expression

Usage: **6** uses
> 🔎 Search for `match (`

### Before

```php
protected function get_http_code( Exception $e ): int {
    $message_lower = strtolower( $e->getMessage() );

    if ( str_contains( $message_lower, 'not found' ) ) {
        return 404;
    }

    if ( str_contains( $message_lower, 'unauthorized' ) ) {
        return 401;
    }

    if ( str_contains( $message_lower, 'forbidden' ) ) {
        return 403;
    }

    if ( str_contains( $message_lower, 'invalid' ) ) {
        return 400;
    }

    if ( str_contains( $message_lower, 'conflict' ) ) {
        return 409;
    }

    return 500;
}
```

### After

```php
protected function get_http_code( Exception $e ): int {
    $message_lower = strtolower( $e->getMessage() );

    return match ( true ) {
        str_contains( $message_lower, 'not found' ) => 404,
        str_contains( $message_lower, 'unauthorized' ) => 401,
        str_contains( $message_lower, 'forbidden' ) => 403,
        str_contains( $message_lower, 'invalid' ) => 400,
        str_contains( $message_lower, 'conflict' ) => 409,
        default => 500,
    };
}
```

### Impact

- Concise syntax and one less nesting level (compared to switch)
- More predictable: Fall-through not possible, strict comparison

---

## Type-hint for `mixed`

Usage: **20** uses
> 🔎 Search for `: mixed|mixed \$` (regex, except comments & string literals)

### Before

```php
/**
 * @param mixed $value
 */
public function validate( $value ): ValidationResult
```

### After

```php
public function validate( mixed $value ): ValidationResult
```

### Impact

- Clear intention vs. "forgot to type-hint"
- No comment required to annotate param or return type

---

## Union types

Usage: **6** uses
> 🔎 Search for `\b\w+\|\w+\b` (regex, except comments & string literals)

### Before

```php
/**
 * @param string|int $value
 */
private function page_id_from_value( $value ): int
```

### After

```php
private function page_id_from_value( string|int $value ): int
```

### Impact

- Type safety

---

## Named Arguments

Usage: **51** uses in 17 files

- 46 `new ExecutionResult` constructors
- 5 `ProcessImagesIngredient::create_image_size()` calls

> 🔎 Search for `\w+: ["'\[\s]*\$?\w+` (regex, except comments & string literals)

### Before

```php
$gallery_data = $this->create_image_size(
    $editor,
    $file,
    'whiskey_og',
    1200,
    630,
    85,
    true,
    'jpg',
    [ 'x' => 'center', 'y' => 'top' ]
);
```

### After

```php
$gallery_data = $this->create_image_size(
    editor: $editor,
    original_file: $file,
    size_name: 'whiskey_og',
    width: 1200,
    height: 630,
    crop: true,
    crop_position: [ 'x' => 'center', 'y' => 'top' ]
);
```

### Impact

- Improved readability, code documentation and readability
- Omit unused optional arguments from function call

---

## Use of `str_contains`

Usage: **5** uses
> 🔎 Search for `str_contains(`

### Before

```php
if ( strpos( $haystack, $needle ) !== false )
```

### After

```php
if ( str_contains( $haystack, $needle ) )
```

### Impact

- Clear code intent, better understanding

---

## Unused patterns

- No opportunity to use `str_starts_with()` or `str_ends_with()`
