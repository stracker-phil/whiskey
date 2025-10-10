# Roadmap

## Branch Structure

Each PHP version branch is **self-contained** with working code + accumulated documentation:
```
main                    # Project overview
php-7.4                 # Baseline (no docs/ yet)
php-8.0                 # + docs/changes-from-7.4.md
php-8.1                 # + docs/changes-from-8.0.md (carries forward 7.4 doc)
php-8.2                 # + docs/changes-from-8.1.md (carries forward all)
php-8.3                 # + docs/changes-from-8.2.md (complete history)
```

**Each branch has:** `src/`, `tests/`, `README.md`, and growing `docs/` folder

**docs/changes-from-X.md contains:** PHP features used, code refactorings, complexity gains

---

## Implementation Roadmap

### Branch `main`
- [x] Create repo for the project
- [x] Add README, ROADMAP and LICENSE
- [x] Plugin header and activation (no logic)

### Branch `php-7.4` - Baseline Implementation

#### Core Plugin Setup
- [ ] Autoloader setup (PSR-4)
- [ ] Define plugin constants (version, path, url)

#### Recipe Registry (Hook System)
- [ ] Implement `RecipeRegistry` class
- [ ] Add `whiskey:register_recipe` hook
- [ ] Recipe storage and retrieval
- [ ] Basic recipe validation

#### Handler Architecture
- [ ] Create `RecipeHandlerInterface`
- [ ] Define handler method signatures: `validate()`, `execute()`
- [ ] Implement factory pattern for handler selection

#### First Handler: PayPal (Proof of Concept)
- [ ] `PayPalHandler` class
- [ ] Define the recipe schema for PayPal
- [ ] Basic validation logic
- [ ] Execute: clear_transients operation
- [ ] Execute: verify_connection operation

#### REST API Controller
- [ ] Register REST namespace (`whiskey/v1`)
- [ ] `GET /recipes` - List all recipes
- [ ] `GET /recipe/{name}` - Get recipe details
- [ ] `POST /recipe/{name}/execute` - Execute recipe
- [ ] `GET /status` - Plugin status
- [ ] Permission callbacks (manage_options)
- [ ] Response formatting (success/error)

#### Additional Handlers
- [ ] `WooCommerceHandler` skeleton
- [ ] `WordPressHandler` skeleton

#### Testing Setup
- [ ] PHPUnit configuration
- [ ] Test RecipeRegistry
- [ ] Test PayPalHandler
- [ ] Test REST endpoints

#### Documentation
- [ ] Inline @explain comments
- [ ] README with usage examples
- [ ] Capture baseline complexity metrics
- [ ] Document baseline in `docs/baseline-7.4.md`

### Branch `php-8.0` - Modern Syntax

#### Constructor Promotion
- [ ] Refactor: Handler classes constructor properties
- [ ] Refactor: Config objects as value objects
- [ ] Document before/after in `docs/changes-from-7.4.md`

#### Match Expressions
- [ ] Refactor: Handler factory (switch → match)
- [ ] Refactor: Recipe type validation
- [ ] Refactor: Status code mapping
- [ ] Document examples

#### Named Arguments
- [ ] Refactor: REST response building
- [ ] Refactor: Handler instantiation
- [ ] Document readability improvements

#### Union Types
- [ ] Refactor: Handler method return types
- [ ] Refactor: Config value types
- [ ] Document type safety gains

#### Nullsafe Operator
- [ ] Refactor: Config access chains
- [ ] Refactor: Optional parameter handling
- [ ] Document null safety improvements

#### Additional Features
- [ ] Use `str_contains()`, `str_starts_with()`, `str_ends_with()`
- [ ] Update tests for new syntax
- [ ] Measure complexity reduction

### Branch `php-8.1` - Type Safety

#### Enums for Recipe Types
- [ ] Create `RecipeType` enum (PayPal, WooCommerce, WordPress)
- [ ] Refactor: Registry type validation
- [ ] Refactor: Handler factory type checking
- [ ] Document before/after in `docs/changes-from-8.0.md`

#### Readonly Properties
- [ ] Refactor: Config classes as immutable DTOs
- [ ] Refactor: Handler constructor properties
- [ ] Document immutability benefits

#### Never Return Type
- [ ] Refactor: Error handling methods
- [ ] Document exhaustive validation

#### Final Class Constants
- [ ] Refactor: Config key constants
- [ ] Refactor: Status code constants

#### Additional Features
- [ ] Use `array_is_list()` where applicable
- [ ] Update tests
- [ ] Measure complexity reduction

### Branch `php-8.2` - Immutability

#### Readonly Classes
- [ ] Refactor: All config DTOs as readonly classes
- [ ] Refactor: Recipe data objects
- [ ] Document before/after in `docs/changes-from-8.1.md`
- [ ] Document architectural improvements

#### DNF Types
- [ ] Review handler signatures for DNF type opportunities
- [ ] Document type precision gains

#### Additional Features
- [ ] Use `true`/`false`/`null` types where beneficial
- [ ] Update tests
- [ ] Measure complexity reduction

### Branch `php-8.3` - Final Polish

#### Typed Constants
- [ ] Add types to all class constants
- [ ] Document type safety in `docs/changes-from-8.2.md`

#### Override Attribute
- [ ] Add `#[Override]` to handler implementations
- [ ] Document inheritance clarity

#### Additional Features
- [ ] Use `json_validate()` for config validation
- [ ] Use anonymous readonly classes if applicable
- [ ] Update tests
- [ ] Measure final complexity reduction

#### Final Documentation
- [ ] Complete evolution story across all `docs/changes-from-*.md`
- [ ] Document 20% complexity reduction achievement
- [ ] Prepare blog post content

### Success Criteria Tracking

- [ ] **Criterion 1**: Functional plugin across 5 PHP versions
- [ ] **Criterion 2**: Document 15+ PHP improvements with examples
- [ ] **Criterion 3**: Achieve 20% complexity reduction (7.4 → 8.3)
- [ ] **Criterion 4**: 100% unit test coverage
- [ ] **Criterion 5**: Comprehensive blog post published

### Metrics to Track

- Lines of code per version
- Cyclomatic complexity per version
- Number of classes/methods
- Test coverage percentage
- Documentation completeness
