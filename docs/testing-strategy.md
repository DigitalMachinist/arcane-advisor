# Testing Strategy

A practical guide for testing this Laravel 13 / PHP 8.4+ application, informed by 2025-2026 community consensus from Spatie, Laravel core team, Laracasts, and prominent community voices.

---

## Philosophy

**Test behavior, not implementation.** If you refactor internals and the test breaks, the test was too coupled to implementation details.

**Feature tests are the primary test layer.** Laravel's official documentation states: "Most of your tests should be feature tests — they provide the most confidence that your system as a whole is functioning as intended."

**Mock at the boundary, not inside.** Use real implementations for your own code (services, models, database). Mock only external boundaries (APIs, email, file storage, time).

**One request per test.** The Laravel docs explicitly recommend this: "Each test should only make one request to your application."

---

## Test Framework: Pest

This project uses **Pest** as its test framework. Pest is built on PHPUnit (100% compatible, same `phpunit.xml`) and is the Laravel community's preferred testing framework, endorsed by the Laravel core team. All tests use Pest's closure-based syntax exclusively.

Key capabilities:

- **Architectural testing** via `arch()` — enforce structural rules without writing traditional tests
- **Mutation testing** via `--mutate` — verify tests actually catch regressions
- **Type coverage** via `--type-coverage` — ensure complete type declarations across the codebase
- **Parallel execution** via `--parallel` — speed up large test suites

### Commands Reference

```bash
php artisan test                              # Run all tests (primary command)
php artisan test --filter=CreatePostTest       # Run a specific test file
php artisan test tests/Feature/               # Run only feature tests
php artisan test tests/Unit/                  # Run only unit tests
php artisan test tests/Arch/                  # Run only architectural tests
php artisan test --group=mysql                # Run MySQL-dependent tests (requires MySQL)
./vendor/bin/pest --type-coverage --min=100   # Check type coverage
./vendor/bin/pest --mutate                    # Run mutation testing
./vendor/bin/pest --parallel                  # Run tests in parallel
./vendor/bin/pest --profile                   # Identify slow tests
```

---

## Test Distribution

| Layer | Proportion | What | Framework Context |
|-------|-----------|------|-------------------|
| Feature tests | ~75% | HTTP endpoints, full request lifecycle | Pest + RefreshDatabase |
| Unit tests | ~20% | Pure business logic, value objects, calculations | Pest, no framework boot |
| Architectural tests | ~5% | Structural rules, dependency constraints | Pest `arch()` |

Browser tests (Dusk) should be added only when JavaScript-driven UI requires it.

---

## What to Test

### Always Test

- **Every HTTP endpoint's happy path** — correct status code, correct response shape/data
- **Authentication rules** — unauthenticated users are rejected appropriately
- **Authorization rules** — users cannot access/modify resources they shouldn't (test both allowed AND denied)
- **Validation rules that represent business constraints** — test with both valid and invalid data
- **Business logic calculations and transformations** — edge cases, boundary conditions
- **Side effects** — that the right jobs, events, notifications, and mail are dispatched with correct data
- **Database state changes** — use `assertDatabaseHas()`, `assertDatabaseMissing()`, `assertSoftDeleted()`
- **Custom scopes** — they encode business logic and should be verified against real data
- **API response contracts** — JSON structure, pagination format, error response consistency
- **Complex relationships** — only when they encode business logic (e.g., filtered/constrained relationships)
- **Factory smoke tests** — that `Model::factory()->create()` produces a valid model

### Never Test

- **Framework behavior** — don't test that `HasMany` returns a collection, that `auth` middleware redirects, or that Eloquent `create()` persists to the database. Laravel has 8,000+ tests for this.
- **Simple getters/setters with no logic** — if it just returns `$this->name`, skip it
- **Private methods directly** — test through the public interface. If a private method is complex enough for its own test, extract it into its own class.
- **Third-party package internals** — test your integration with them, not that they work as documented
- **Exact HTML output or CSS classes** — use `assertSee('Welcome, Taylor')` not `assertSee('<div class="mt-4">')`
- **Configuration values** — config is infrastructure, not behavior
- **Simple Eloquent casts** — don't test that `'datetime'` returns Carbon. Do test custom casts with business logic.
- **Framework validation rule implementations** — don't test that `'required'` rejects empty strings. Test that YOUR endpoint applies the correct rules.
- **Query builder SQL** — test the result, not the generated query

### Low-Value (Skip Unless Business-Critical)

- Simple CRUD with zero business logic (a controller that validates, creates, and returns)
- Admin panels with no custom business rules
- Asserting exact log messages (too brittle)
- Testing the same flow multiple ways (one good feature test beats a feature test + controller unit test + request test)

---

## Test Organization

### Directory Structure

Organize tests by feature/domain, not by mirroring app structure:

```
tests/
├── Arch/
│   ├── ArchitectureTest.php          # Structural rules
│   └── DatabaseCompatibilityTest.php # MySQL/SQLite compatibility checks
├── Feature/
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   ├── RegistrationTest.php
│   │   └── PasswordResetTest.php
│   ├── Posts/
│   │   ├── CreatePostTest.php
│   │   ├── UpdatePostTest.php
│   │   ├── DeletePostTest.php
│   │   └── ListPostsTest.php
│   └── Orders/
│       ├── PlaceOrderTest.php
│       └── CancelOrderTest.php
├── Unit/
│   ├── Money/
│   │   └── MoneyCalculationTest.php
│   └── Services/
│       └── TaxCalculatorTest.php
├── Pest.php
└── TestCase.php
```

### Naming Conventions

```php
test('authenticated user can create a post', function () { ... });
test('guest is redirected to login', function () { ... });
test('validation rejects empty title', function () { ... });
it('calculates tax for taxable items', function () { ... });
```

Name tests as sentences describing who does what and what happens. Include the actor, action, and condition.

---

## Database Testing Strategy

### Default: RefreshDatabase + In-Memory SQLite

The project uses in-memory SQLite (`phpunit.xml`) with `RefreshDatabase`. This is the fastest option and is appropriate for most tests.

```php
uses(RefreshDatabase::class);

test('user can create a post', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/posts', ['title' => 'Hello World', 'body' => 'Content here'])
        ->assertCreated();

    $this->assertDatabaseHas('posts', ['title' => 'Hello World', 'user_id' => $user->id]);
});
```

### Factory Best Practices

- **Always use factories** — never raw `Model::create()` or `DB::insert()`
- **Override only what matters** — let factory defaults handle the rest
- **Define factory states** for common variants: `->admin()`, `->suspended()`, `->published()`
- **Use relationship methods**: `->for($user)`, `->has(Post::factory()->count(3))`
- **Use `make()` when you don't need persistence**, `create()` when you do
- **Use `recycle()`** to reuse related models instead of creating duplicates

```php
// Good: only specify what matters for THIS test
$user = User::factory()->create(['email' => 'specific@test.com']);

// Good: factory states for variants
$admin = User::factory()->admin()->create();
$post = Post::factory()->for($admin)->published()->create();

// Good: relationship factories
$user = User::factory()->has(Post::factory()->count(3))->create();
```

---

## Database Compatibility

Production uses MySQL 8.4 while the default test database is in-memory SQLite. Most tests run fine on SQLite, but certain MySQL-specific features behave differently or are unsupported. This section provides a comprehensive reference for understanding and managing those differences.

### MySQL vs SQLite Feature Reference

#### High Risk

These features will cause test failures or silently produce wrong results on SQLite.

| MySQL Feature | SQLite Behavior | Detectable Statically |
|---|---|---|
| JSON columns (`->`, `whereJsonContains`, `whereJsonLength`) | Stored as TEXT, operators may fail or differ | Yes — arch test flags it |
| `ENUM` columns | Stored as TEXT, no constraint enforcement | Yes — in migrations |
| Strict mode (type coercion) | Silently accepts wrong types | No — rely on MySQL CI job |
| `FULLTEXT` indexes | Not supported | Yes — arch test flags it |
| `REGEXP` / `RLIKE` | Different syntax/behavior | Partially — in raw SQL |

#### Medium Risk

These features differ but may not cause immediate failures — they produce subtly wrong results.

| MySQL Feature | SQLite Behavior |
|---|---|
| `GROUP_CONCAT` / `JSON_ARRAYAGG` | Different function names |
| `DATE_FORMAT`, `YEAR()`, `MONTH()` | Not available natively |
| `UNSIGNED` integers | Ignored |
| Row-level locking (`FOR UPDATE`) | File-level locking only |
| `ALTER TABLE` (drop/rename columns) | Very limited support |
| Case-sensitive `LIKE` | SQLite `LIKE` is case-insensitive |

#### Low Risk

These features are absent in SQLite but are unlikely to appear in typical application code.

| MySQL Feature | SQLite Behavior |
|---|---|
| Spatial/GIS types | Not supported |
| Stored procedures | Not supported |
| Multiple character sets/collations | Limited |
| Partitioning | Not supported |

### Test Groups: SQLite vs MySQL

Tests are divided into two groups based on database requirements:

- **Default (no tag):** Runs on SQLite. This is the vast majority of tests.
- **`mysql` group:** Requires a running MySQL instance. Use for tests that exercise MySQL-specific features.

#### Tagging Convention

```php
// Default — runs on SQLite, no tag needed
test('user can create a post', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/posts', ['title' => 'Hello World', 'body' => 'Content here'])
        ->assertCreated();
});

// MySQL-dependent — tag with ->group('mysql')
test('full-text search returns matching posts', function () {
    // This test requires MySQL FULLTEXT indexes
    // ...
})->group('mysql');
```

The `mysql` group is excluded from the default test suite in `phpunit.xml`, so running `php artisan test` only executes SQLite-compatible tests. To run MySQL tests explicitly:

```bash
php artisan test --group=mysql
```

#### When to Tag a Test as MySQL

Tag a test with `->group('mysql')` when it:

- Uses `whereJsonContains`, `whereJsonLength`, or JSON `->` operators on the database
- Relies on `FULLTEXT` indexes or MySQL full-text search
- Uses raw SQL with MySQL-specific functions (`GROUP_CONCAT`, `DATE_FORMAT`, `YEAR()`, etc.)
- Tests row-level locking (`FOR UPDATE`, `LOCK IN SHARE MODE`)
- Depends on MySQL strict mode behavior (type coercion, truncation)
- Uses `REGEXP` or `RLIKE` in queries

When in doubt, check the High Risk table above. If the feature appears there, tag the test.

### Architectural Enforcement

The architectural test suite in `tests/Arch/DatabaseCompatibilityTest.php` automatically detects usage of certain MySQL-specific features in application code (such as `whereJsonContains` and `whereJsonLength`), as well as MySQL-only patterns in raw SQL calls. This provides an early warning when code that may not work on SQLite is introduced without proper test tagging.

See the [Architectural Testing](#architectural-testing) section for details on both test files.

---

## Mocking Strategy

### Use Real Implementations (Default)

| Dependency | Approach |
|---|---|
| Database | Real via RefreshDatabase + SQLite |
| Cache | Array driver (in-memory, configured in phpunit.xml) |
| Session | Array driver (configured in phpunit.xml) |
| Queue | Sync driver (jobs execute immediately) |
| Internal services/actions | Real instances |
| Form requests, middleware, policies | Real via HTTP tests |

### Use Fakes (External Boundaries Only)

| Boundary | Fake | Example |
|---|---|---|
| External HTTP APIs | `Http::fake()` | Third-party payment, weather API |
| Email | `Mail::fake()` | `Mail::assertSent(OrderConfirmation::class)` |
| Notifications | `Notification::fake()` | `Notification::assertSentTo($user, ...)` |
| File storage | `Storage::fake()` | File uploads, `Storage::assertExists(...)` |
| Queue (dispatch testing) | `Bus::fake()` | `Bus::assertDispatched(ProcessPayment::class)` |
| Events (fire testing) | `Event::fake()` | `Event::assertDispatched(OrderPlaced::class)` |
| Time | `$this->travel()` / `$this->freezeTime()` | Subscription expiry, scheduling |

### Never Mock

- `Request` — use HTTP test methods (`$this->get()`, `$this->post()`)
- `Config` — use `Config::set()` directly
- The database/Eloquent — use real data with factories
- The class under test

### Pattern: Dispatch + Handle Separately

```php
// Test 1: Verify the job is dispatched
test('placing order dispatches payment job', function () {
    Bus::fake();

    $this->actingAs($user)->post('/orders', $orderData);

    Bus::assertDispatched(ProcessPayment::class, fn ($job) =>
        $job->amount === 4000
    );
});

// Test 2: Verify the job's logic (separate test)
test('process payment job charges the user', function () {
    $user = User::factory()->create();
    $job = new ProcessPayment($user, 4000);
    $job->handle();

    $this->assertDatabaseHas('payments', ['user_id' => $user->id, 'amount' => 4000]);
});
```

---

## Architectural Testing

Architectural tests use Pest's `arch()` function to enforce structural rules without writing traditional test cases. They live in `tests/Arch/` and are organized into two files:

### Structure Rules (`tests/Arch/ArchitectureTest.php`)

Enforces code quality presets and naming/inheritance conventions:

```php
arch()->preset()->php();
arch()->preset()->security();

arch('no debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('controllers have Controller suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('models extend Eloquent Model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('form requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request')
    ->toExtend('Illuminate\Foundation\Http\FormRequest');

arch('jobs implement ShouldQueue')
    ->expect('App\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');
```

### Database Compatibility Rules (`tests/Arch/DatabaseCompatibilityTest.php`)

Detects usage of MySQL-specific features (such as `whereJsonContains`, `whereJsonLength`, and MySQL-only raw SQL patterns) that would fail or produce incorrect results on SQLite. See the [Database Compatibility](#database-compatibility) section for the full feature reference and tagging conventions.

---

## Coverage Strategy

### Targets

| Metric | Target | Enforcement |
|---|---|---|
| Line coverage | 80%+ | `--coverage --min=80` in CI |
| Type coverage | 100% | `--type-coverage --min=100` (achievable on fresh project) |
| Mutation score (business logic) | 70%+ | `--mutate --min=70 --class=App\\Services,App\\Models,App\\Actions` |

### Prerequisites

- **Coverage driver**: Mutation testing and line coverage require a coverage driver (Xdebug or PCOV). PCOV is recommended for CI as it's faster. Install via `pecl install pcov` or enable Xdebug in coverage mode.

### Running Coverage

```bash
# Line coverage (requires Xdebug or PCOV)
./vendor/bin/pest --coverage --min=80

# Type coverage (requires pestphp/pest-plugin-type-coverage)
./vendor/bin/pest --type-coverage --min=100

# Mutation testing on business logic only (requires Xdebug or PCOV)
./vendor/bin/pest --mutate --parallel --min=70 --class=App\\Services,App\\Models,App\\Actions

# Find slow tests
./vendor/bin/pest --profile
```

### Scoping Mutation Testing with `covers()` and `mutates()`

Pest's mutation testing engine needs to know which code each test covers. Use `covers()` or `mutates()` at the top of test files to link tests to the classes they exercise. Without these, `--mutate` won't generate mutations for your classes.

- **`covers()`** — scopes both mutation testing AND code coverage reports
- **`mutates()`** — scopes mutation testing only (no effect on coverage reports)

```php
// At the top of a test file — tells Pest which class(es) these tests cover
covers(OrderService::class);

test('placing an order calculates total correctly', function () {
    $service = new OrderService();
    $total = $service->calculateTotal([...]);

    expect($total)->toBe(4200);
});
```

You can also use `--class` and `--ignore` CLI flags to scope mutations without modifying test files:

```bash
# Scope to specific namespaces
./vendor/bin/pest --mutate --class=App\\Services,App\\Models

# Exclude namespaces
./vendor/bin/pest --mutate --ignore=App\\Http\\Requests

# Generate mutations for ALL classes (resource-intensive)
./vendor/bin/pest --mutate --everything --parallel --covered-only
```

### What Coverage Numbers Mean

- **Line coverage** is a lower bound — 80% coverage doesn't mean 80% quality
- **Mutation testing** measures whether tests actually catch changes — a better quality signal
- **Type coverage** at 100% prevents missing type declarations from creeping in
- A codebase with 70% line coverage and 80% MSI is better tested than 95% line coverage with 40% MSI

---

## Testing Patterns Quick Reference

### CRUD Endpoint Testing Checklist

For each resource endpoint, test:

- [ ] Happy path (valid data, correct status code, correct response)
- [ ] Unauthenticated access is rejected
- [ ] Unauthorized access is rejected (user can't modify others' resources)
- [ ] Validation errors returned for invalid input
- [ ] Database state changes verified (`assertDatabaseHas`, `assertDatabaseMissing`)
- [ ] Side effects triggered (events, jobs, notifications, mail)
- [ ] Edge cases (empty state, pagination boundary, soft deletes)

### Common Assertions

```php
// HTTP
$response->assertOk();                    // 200
$response->assertCreated();               // 201
$response->assertNoContent();             // 204
$response->assertRedirect('/dashboard');
$response->assertForbidden();             // 403
$response->assertNotFound();              // 404
$response->assertUnprocessable();         // 422

// JSON
$response->assertJsonStructure(['data' => ['id', 'name']]);
$response->assertJsonPath('data.name', 'Taylor');
$response->assertJsonCount(3, 'data');

// Database
$this->assertDatabaseHas('users', ['email' => 'taylor@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);
$this->assertDatabaseCount('users', 5);
$this->assertSoftDeleted('posts', ['id' => $post->id]);

// Views
$response->assertViewIs('users.index');
$response->assertViewHas('users');
$response->assertSee('Welcome, Taylor');

// Auth
$this->assertAuthenticated();
$this->assertGuest();

// Validation
$response->assertSessionHasErrors(['email']);
$response->assertInvalid(['email']);
```

---

## Test Speed Guidelines

- Target: full suite under 30 seconds
- Use `--parallel` when suite grows beyond 30s
- Use `--profile` to identify slow tests
- Prefer `RefreshDatabase` over `DatabaseMigrations`
- Use `make()` instead of `create()` when persistence isn't needed
- Only create the data each test actually needs

---

## Anti-Patterns to Avoid

| Anti-Pattern | Why It's Bad | Do This Instead |
|---|---|---|
| Mocking everything | Tests verify mock setup, not behavior | Use real implementations; mock only boundaries |
| Testing implementation details | Breaks on refactoring even when behavior is correct | Test inputs and outputs, not internal calls |
| Giant test methods | Impossible to debug; unclear what's being tested | One behavior per test |
| Asserting exact JSON/HTML | Breaks on every minor change | Assert structure and key values |
| Hard-coding IDs/timestamps | Brittle, order-dependent | Use factories and relative assertions |
| Testing framework code | Zero value; already tested by Laravel | Test YOUR code's behavior |
| Testing same flow multiple ways | Maintenance burden with no extra confidence | One well-written feature test is enough |
| Over-specifying factory data | Obscures test intent | Only set attributes that matter for the test |

---

## CI Integration

### Dual-Database Pipeline

The CI pipeline runs two jobs to cover both SQLite (fast, default) and MySQL (full compatibility):

| Trigger | Job | Database | What Runs |
|---|---|---|---|
| Every push to `main` | `sqlite-tests` | SQLite (in-memory) | Default test suite + arch tests + type coverage + Pint |
| Every PR | `mysql-tests` | MySQL 8.4 | Full suite including `mysql` group |

See `.github/workflows/tests.yml` for the full workflow configuration.
