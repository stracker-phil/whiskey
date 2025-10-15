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

#### First Ingredients (Proof of Concept)

- [x] `SetHomepageIngredient` class
- [x] Basic validation logic
- [x] Execute method returning ExecutionResult

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

#### WP-CLI Controller

- [x] `wp whiskey recipes` - List all recipes
- [x] `wp whiskey recipe <n>` - Get recipe details
- [x] `wp whiskey apply <n>` - Execute recipe with --dry-run support
- [x] `wp whiskey ingredients` - List all ingredients
- [x] `wp whiskey ingredient <n>` - Get ingredient details
- [x] `wp whiskey status` - Plugin status
- [x] Unified with REST API pattern

#### Tool-Based Architecture Refactoring

- [x] Create `WhiskeyTool` base class (handles REST/CLI registration and error handling)
- [x] Extract behavior into self-contained tool classes:
  - [x] `ListRecipesTool` - List all recipes
  - [x] `ShowRecipeTool` - Show recipe details
  - [x] `ApplyRecipeTool` - Execute recipes with validation
  - [x] `ListIngredientsTool` - List all ingredients
  - [x] `ShowIngredientTool` - Show ingredient details
  - [x] `StatusTool` - Plugin status
- [x] Refactor `RestController` to thin registration layer (210 lines → 45 lines, 78% reduction)
- [x] Refactor `CliController` to thin registration layer (339 lines → 35 lines, 90% reduction)
- [x] Update DI in `whiskey.php` to instantiate tools
- [x] Benefits achieved:
  - Single source of truth for business logic (no REST/CLI duplication)
  - Easy to add new features (just create a tool class)
  - Clear separation of concerns (registration vs logic)
  - Perfect foundation for PHP evolution demos

#### Additional Ingredients

- [x] WordPress ingredients (4/5 operations completed)
  - [x] `SetHomepageIngredient`
  - [x] `UpdatePermalinksIngredient`
  - [x] `CreateShopPagesIngredient` (with modular template system)
  - [x] `SetMenuItemsIngredient`
  - [ ] `SetActiveTheme`
- [ ] WooCommerce ingredients (0/5 operations)
  - [ ] `WooCommerceCountryIngredient`
  - [ ] `WooCommerceCurrencyIngredient`
  - [ ] `WooCommerceShippingIngredient`
  - [ ] Additional as needed
- [ ] PayPal ingredients (0/5 operations)
  - [ ] `PayPalMerchantIngredient`
  - [ ] `PayPalOnboardingIngredient`
  - [ ] `PayPalSettingsIngredient`
  - [ ] `PayPalPaymentMethodsIngredient`
  - [ ] Additional as needed

#### Testing Setup

- [x] PHPUnit configuration
- [x] Custom WordPress function stubs (add_action, do_action, add_filter, apply_filters)
- [x] Test Main class (100% coverage)
- [x] Test RecipeExecutor (100% coverage)
- [x] PHP stubs for IDE support (wordpress-stubs, wp-cli-stubs)
- [x] Testing guidelines documentation (tests/TESTING.md)
- [ ] Test IngredientRegistry
- [ ] Test RecipeRegistry
- [ ] Test individual ingredients
- [ ] Test REST endpoints
- [ ] Test CLI commands

#### Sample Recipes

- [ ] WordPress shop setup recipe
- [ ] WooCommerce US store recipe
- [ ] PayPal sandbox recipe

#### Documentation

- [x] Inline docblocks throughout codebase
- [x] README with usage examples
- [x] REST API endpoint documentation
- [x] WP-CLI command documentation
- [x] CLAUDE.md for AI assistant context
- [ ] Capture baseline complexity metrics (need 15+ ingredients total)
- [ ] Document baseline in `docs/baseline-7.4.md`

### Branch `php-8.0` - Modern Syntax

#### Constructor Promotion

- [ ] Refactor: All class constructor properties (Main, Registries, RestController, RecipeExecutor)
- [ ] Refactor: Ingredient classes with state
- [ ] Document before/after in `docs/changes-from-7.4.md`

#### Match Expressions

- [ ] Refactor: RecipeExecutor ingredient instantiation patterns (if applicable)
- [ ] Refactor: Status code mapping in RestController
- [ ] Document examples

#### Named Arguments

- [ ] Refactor: REST response building
- [ ] Refactor: Ingredient instantiation
- [ ] Refactor: ExecutionResult creation
- [ ] Document readability improvements

#### Union Types

- [ ] Refactor: Ingredient validate() parameter types
- [ ] Refactor: Ingredient execute() parameter types
- [ ] Document type safety gains

#### Nullsafe Operator

- [ ] Refactor: Registry access chains
- [ ] Refactor: Optional parameter handling in ingredients
- [ ] Document null safety improvements

#### Additional Features

- [ ] Use `str_contains()`, `str_starts_with()`, `str_ends_with()` in ingredients
- [ ] Update tests for new syntax
- [ ] Measure complexity reduction

### Branch `php-8.1` - Type Safety

#### Enums for Categories

- [ ] Create `IngredientCategory` enum (WordPress, WooCommerce, PayPal, etc.)
- [ ] Refactor: Replace `const CATEGORY` strings with enum
- [ ] Refactor: Registry category filtering
- [ ] Document before/after in `docs/changes-from-8.0.md`

#### Readonly Properties

- [ ] Refactor: ExecutionResult as readonly class
- [ ] Refactor: Ingredient constructor properties as readonly
- [ ] Refactor: Registry properties as readonly where appropriate
- [ ] Document immutability benefits

#### Never Return Type

- [ ] Refactor: Error handling methods in ingredients
- [ ] Document exhaustive validation

#### Final Class Constants

- [ ] Refactor: Ingredient CATEGORY constants as final
- [ ] Refactor: REST namespace constant as final

#### Additional Features

- [ ] Use `array_is_list()` in validation where applicable
- [ ] Update tests
- [ ] Measure complexity reduction

### Branch `php-8.2` - Immutability

#### Readonly Classes

- [ ] Refactor: ExecutionResult as readonly class
- [ ] Refactor: Simple ingredient classes as readonly
- [ ] Document before/after in `docs/changes-from-8.1.md`
- [ ] Document architectural improvements

#### DNF Types

- [ ] Review ingredient validate() signatures for DNF type opportunities
- [ ] Review ingredient execute() return types
- [ ] Document type precision gains

#### Additional Features

- [ ] Use `true`/`false`/`null` types in ingredient validation
- [ ] Update tests
- [ ] Measure complexity reduction

### Branch `php-8.3` - Final Polish

#### Typed Constants

- [ ] Add types to all ingredient CATEGORY constants
- [ ] Add types to REST namespace constant
- [ ] Document type safety in `docs/changes-from-8.2.md`

#### Override Attribute

- [ ] Add `#[Override]` to ingredient validate() implementations
- [ ] Add `#[Override]` to ingredient execute() implementations
- [ ] Document inheritance clarity

#### Additional Features

- [ ] Use `json_validate()` for recipe config validation
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
- Number of classes/methods (ingredients provide more classes to measure)
- Number of ingredients implemented
- Test coverage percentage
- Documentation completeness

### Nice to haves

- [ ] Use **wpdi** for DI
- [ ] MCP Server integration via WordPress MCP Adapter
- [x] WP-CLI command support
- [ ] Recipe dependency management
