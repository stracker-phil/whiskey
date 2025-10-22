# Changes from PHP 7.4 to PHP 8.0

## Constructor Property Promotion

Usage:

Impact: Cleaner and shorter code, less boilerplate.

## Type-hint for `mixed`

Usage:

Impact: Clear intention vs. "forgot to type-hint".

## Use of `str_contains`

Usage: 1

Before: `strpos( $haystack, $needle ) !== false`
After: `str_contains( $haystack, $needle )`

Impact: Better code understanding (clearer intent).

## Named Arguments

Usage:

Impact: Improved readability and code understanding.
