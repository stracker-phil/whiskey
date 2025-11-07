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

---

### Branch `php-7.4` - Baseline Implementation

#### Core Plugin Setup

- [x] Autoloader setup (PSR-4 using composer)
- [x] Main bootstrap class with DI

#### Registry System (Hook-Based)

- [x] Implement `RecipeRegistry` class
- [x] Implement `IngredientRegistry` class
- [x] Add `whiskey:register_recipe` hook
- [x] Add `whiskey:register_ingredient` hook
- [x] Storage and retrieval for both registries
- [x] Basic validation

#### Ingredient Architecture

- [x] Create `Ingredient` base class
- [x] Define method signatures: `validate()`, `execute()`
- [x] Create `ExecutionResult` class for type-safe responses
- [x] Create `RecipeExecutor` for ingredient orchestration

#### Prepare Ingredients (Proof of Concept)

- [x] `SetHomepageIngredient` class
- [x] Basic validation logic
- [x] Execute method returning ExecutionResult
- [x] Provide 10 ingredients

#### REST API Controller

- [x] Register REST namespace (`whiskey/v1`)
- [x] `GET /recipes` - List all recipes
- [x] `GET /recipe/{name}` - Get recipe details
- [x] `POST /recipe/{name}/apply` - Execute recipe
- [x] `GET /ingredients` - List all ingredients
- [x] `GET /ingredient/{name}` - Get ingredient details
- [x] `GET /status` - Plugin status
- [x] Unified response pattern (plural endpoints return names, singular return full data)
- [x] Permission callbacks
- [x] Response formatting (success/error)
- [x] WP-CLI commands (unified with REST API pattern)

#### Testing Setup

- [x] PHPUnit configuration
- [x] Custom WordPress function stubs (add_action, do_action, add_filter, apply_filters)
- [x] Test Main class (100% coverage)
- [x] Test RecipeExecutor (100% coverage)
- [x] PHP stubs for IDE support (wordpress-stubs, wp-cli-stubs)
- [x] Testing guidelines documentation (tests/TESTING.md)
- [x] Test IngredientRegistry
- [x] Test RecipeRegistry
- [x] Test individual ingredients
- [x] Test REST endpoints
- [x] Test CLI commands

#### Sample Recipes

- [x] WordPress shop setup recipe
- [x] WooCommerce US store recipe

#### Documentation

- [x] Inline docblocks throughout codebase
- [x] README with usage examples
- [x] REST API endpoint documentation
- [x] WP-CLI command documentation
- [x] CLAUDE.md for AI assistant context
- [x] Capture baseline complexity metrics (need 10+ ingredients total)
- [x] Document baseline in `docs/baseline-7.4.md`

---

### Branch `php-8.0` - Modern Syntax

#### Constructor Promotion

- [x] Refactor: All class constructor properties (Main, Registries, RestController, RecipeExecutor)
- [x] Document changes

#### Match Expressions

- [x] Refactor: ExecutionStrategy, IngredientCategory, WhiskeyTool, ValidationCode
- [x] Document changes

#### Annotate `mixed` type

- [x] Refactor Ingredients
- [x] Document changes

#### Named Arguments

- [x] Refactor: ExecutionResult creation
- [x] Refactor: ProcessImagesIngredient
- [x] Document changes

#### Union Types

- [x] Refactor: Ingredients
- [x] Document changes

#### Additional Features

- [x] Use `str_contains()`, `str_starts_with()`, `str_ends_with()` in ingredients
- [x] Document changes

#### Documentation

- [x] Pass all tests
- [x] Finish the before/after documentation in `docs/changes-8.0.md`
- [ ] Capture new complexity metrics

---

### Branch `php-8.1` - Type Safety

#### Enums for Categories

- [ ] Create `IngredientCategory` enum (WordPress, WooCommerce, PayPal, etc.)
- [ ] Refactor: Replace `const CATEGORY` strings with enum
- [ ] Refactor: Registry category filtering
- [ ] Document changes

#### Readonly Properties

- [ ] Refactor: ExecutionResult as readonly class
- [ ] Refactor: Ingredient constructor properties as readonly
- [ ] Refactor: Registry properties as readonly where appropriate
- [ ] Document changes

#### Never Return Type

- [ ] Refactor: Error handling methods in ingredients
- [ ] Document changes

#### Final Class Constants

- [ ] Refactor: Ingredient CATEGORY constants as final
- [ ] Refactor: REST namespace constant as final
- [ ] Document changes

#### Additional Features

- [ ] Use `array_is_list()` in validation where applicable
- [ ] Document changes

#### Documentation

- [ ] Pass all tests
- [ ] Finish the before/after documentation in `docs/changes-8.1.md`
- [ ] Capture new complexity metrics

---

### Branch `php-8.2` - Immutability

#### Readonly Classes

- [ ] Refactor: ExecutionResult as readonly class
- [ ] Refactor: Simple ingredient classes as readonly
- [ ] Document changes

#### DNF Types

- [ ] Review ingredient validate() signatures for DNF type opportunities
- [ ] Review ingredient execute() return types
- [ ] Document changes

#### Additional Features

- [ ] Use `true`/`false`/`null` types in ingredient validation
- [ ] Document changes

#### Documentation

- [ ] Pass all tests
- [ ] Finish the before/after documentation in `docs/changes-8.2.md`
- [ ] Capture new complexity metrics

---

### Branch `php-8.3` - Final Polish

#### Typed Constants

- [ ] Add types to all ingredient CATEGORY constants
- [ ] Add types to REST namespace constant
- [ ] Document changes

#### Override Attribute

- [ ] Add `#[Override]` to ingredient validate() implementations
- [ ] Add `#[Override]` to ingredient execute() implementations
- [ ] Document changes

#### Additional Features

- [ ] Use `json_validate()` for recipe config validation
- [ ] Use anonymous readonly classes if applicable
- [ ] Document changes

#### Documentation

- [ ] Pass all tests
- [ ] Finish the before/after documentation in `docs/changes-8.3.md`
- [ ] Capture new complexity metrics

#### Final Report and Evaluation

- [ ] Document 20% complexity reduction achievement
- [ ] Prepare blog post content

### Success Criteria Tracking

- [ ] **Criterion 1**: Functional plugin across 5 PHP versions
- [ ] **Criterion 2**: Document 15+ PHP improvements with examples
- [ ] **Criterion 3**: Achieve 20% complexity reduction (7.4 → 8.3)
- [ ] **Criterion 4**: Excellent unit test coverage (90% methods)
- [ ] **Criterion 5**: Comprehensive blog post published

### Metrics to Track

- Lines of code per version
- Cyclomatic complexity per version
- Number of classes/methods (ingredients provide more classes to measure)
- Number of ingredients implemented
- Test coverage percentage
- Documentation completeness

### Nice to haves

- [ ] Use **wpdi** for DI
- [ ] MCP Server integration via WordPress MCP Adapter
- [x] WP-CLI command support
- [x] Recipe dependency management
