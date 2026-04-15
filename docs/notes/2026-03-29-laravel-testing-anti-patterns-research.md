# Laravel Testing Anti-Patterns, Boundaries, and Best Practices

## Research Report -- March 29, 2026

This report synthesizes guidance from the official Laravel 12.x/13.x documentation, prominent community voices (Spatie, Freek Van der Heuvel, Nuno Maduro, Jason McCreary, Christoph Rumpel, Marcel Pociot, Tim MacDonald), Laracon conference talks, and established testing literature (Martin Fowler, Kent Beck, DHH) as applied to modern Laravel projects using PHP 8.3+ and PHPUnit 12.

---

## Table of Contents

1. [Testing Anti-Patterns in Laravel](#1-testing-anti-patterns-in-laravel)
2. [What NOT to Test](#2-what-not-to-test)
3. [Testing Boundaries](#3-testing-boundaries)
4. [Test Organization and Community Opinions](#4-test-organization-and-community-opinions)
5. [Database Testing Strategy](#5-database-testing-strategy)
6. [Real-World Testing Strategies from Laravel Companies](#6-real-world-testing-strategies-from-laravel-companies)
7. [PHPUnit 12 Specifics](#7-phpunit-12-specifics)
8. [Recommended Testing Strategy for This Project](#8-recommended-testing-strategy-for-this-project)

---

## 1. Testing Anti-Patterns in Laravel

### 1.1 Over-Mocking (The "Mockist" Trap)

**The Problem:** Mocking every dependency creates tests that verify your mocking setup rather than actual behavior. When you mock the database, the request, the service, and the response, you are testing that your mock expectations match your mock returns -- not that your code works.

**Example of over-mocking (BAD):**
```php
public function test_user_creation(): void
{
    $repository = $this->mock(UserRepository::class);
    $repository->expects('create')
        ->with(['name' => 'Taylor', 'email' => 'taylor@example.com'])
        ->andReturn(new User(['id' => 1, 'name' => 'Taylor']));

    $service = new UserService($repository);
    $result = $service->createUser(['name' => 'Taylor', 'email' => 'taylor@example.com']);

    $this->assertEquals('Taylor', $result->name);
}
```

This test proves nothing. It asserts that a mock returns what you told it to return. If the actual repository method signature changes, the test still passes.

**Prefer instead (GOOD):**
```php
public function test_user_creation(): void
{
    $response = $this->actingAs(User::factory()->admin()->create())
        ->post('/users', [
            'name' => 'Taylor',
            'email' => 'taylor@example.com',
        ]);

    $response->assertCreated();
    $this->assertDatabaseHas('users', [
        'name' => 'Taylor',
        'email' => 'taylor@example.com',
    ]);
}
```

**When mocking IS appropriate:**
- External HTTP APIs (use `Http::fake()`)
- Email/notification sending (use `Mail::fake()`, `Notification::fake()`)
- File storage during tests (use `Storage::fake()`)
- Queue dispatching when testing the dispatching code, not the job itself (use `Bus::fake()`)
- Time-dependent logic (use `$this->travel()` or `$this->freezeTime()`)

**The Laravel docs explicitly state:** "Do not mock `Request` -- instead, pass input to HTTP testing methods (`get()`, `post()`, etc.). Do not mock `Config` -- call `Config::set()` directly."

### 1.2 Testing Implementation Details vs Behavior

**The Problem:** Tests that assert *how* something is done rather than *what* it accomplishes break when you refactor, even if the behavior is identical.

**Example of testing implementation (BAD):**
```php
public function test_order_total_uses_sum(): void
{
    $order = Order::factory()->hasItems(3)->create();

    // This tests HOW it's calculated, not WHAT the result is
    $spy = $this->spy(Calculator::class);
    $order->calculateTotal();
    $spy->shouldHaveReceived('sum')->once();
}
```

**Test behavior instead (GOOD):**
```php
public function test_order_total_sums_item_prices(): void
{
    $order = Order::factory()->create();
    $order->items()->createMany([
        ['price' => 1000],  // $10.00
        ['price' => 2500],  // $25.00
        ['price' => 500],   // $5.00
    ]);

    $this->assertEquals(4000, $order->calculateTotal());
}
```

**Heuristic:** If renaming a private method or rearranging internal logic breaks your test, you are testing implementation details.

### 1.3 Brittle Tests That Break on Refactoring

**Common causes:**
- Asserting exact database query count (`expectsDatabaseQueryCount(5)`) when the exact count is incidental
- Asserting exact JSON structure when only certain fields matter
- Asserting exact string output when the meaning is what matters
- Asserting on ordering that is not guaranteed
- Hard-coding IDs or timestamps

**Brittle (BAD):**
```php
$response->assertExactJson([
    'data' => [
        'id' => 1,
        'name' => 'Taylor',
        'email' => 'taylor@example.com',
        'created_at' => '2026-03-29T00:00:00.000000Z',
        'updated_at' => '2026-03-29T00:00:00.000000Z',
    ]
]);
```

**Resilient (GOOD):**
```php
$response->assertJsonStructure([
    'data' => ['id', 'name', 'email'],
])->assertJsonFragment([
    'name' => 'Taylor',
    'email' => 'taylor@example.com',
]);
```

**Exception:** `assertExactJson` IS appropriate for API contract tests where the exact shape is part of the specification.

### 1.4 Testing Framework/Eloquent Behavior

**The Problem:** Writing tests that verify that Laravel works. Eloquent relationships, validation rules, casts, and middleware are already tested by the framework. Your tests should verify your *usage* of these features.

**Testing the framework (BAD):**
```php
public function test_user_has_many_posts(): void
{
    $user = User::factory()->hasPosts(3)->create();
    $this->assertCount(3, $user->posts);
    $this->assertInstanceOf(Collection::class, $user->posts);
}
```

**Testing your business logic (GOOD):**
```php
public function test_user_dashboard_shows_recent_posts(): void
{
    $user = User::factory()->create();
    Post::factory()->for($user)->count(3)->create();
    Post::factory()->for($user)->create(['published_at' => now()->subYear()]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertViewHas('recentPosts', function ($posts) {
        return $posts->count() === 3;
    });
}
```

### 1.5 Copy-Paste Test Code (No Test Helpers/Factories)

**The Problem:** Duplicated setup code across tests creates a maintenance burden and obscures intent.

**Duplicated setup (BAD):**
```php
public function test_admin_can_delete_user(): void
{
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);
    $this->actingAs($admin)->delete("/users/{$user->id}")->assertOk();
}

public function test_admin_can_edit_user(): void
{
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);
    $this->actingAs($admin)->put("/users/{$user->id}", ['name' => 'New'])->assertOk();
}
```

**Use factory states and test helpers (GOOD):**
```php
// In UserFactory:
public function admin(): static
{
    return $this->state(['role' => 'admin']);
}

// In tests:
public function test_admin_can_delete_user(): void
{
    $user = User::factory()->create();
    $this->actingAs(User::factory()->admin()->create())
        ->delete("/users/{$user->id}")
        ->assertOk();
}
```

Or create a trait:
```php
trait ActsAsAdmin
{
    protected function actingAsAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create());
    }
}
```

### 1.6 Giant Test Methods (Testing Everything at Once)

**The Problem:** A single test that creates users, logs in, creates a post, edits it, deletes it, and checks the audit log tests nothing well and is impossible to debug when it fails.

**Rule of thumb:** Each test should verify one behavior. The test name should describe that behavior. If the test name contains "and", it's probably testing too much.

**Too much in one test (BAD):**
```php
public function test_post_lifecycle(): void
{
    $user = User::factory()->create();
    $this->actingAs($user)
        ->post('/posts', ['title' => 'Hello'])
        ->assertCreated();

    $post = Post::first();
    $this->actingAs($user)
        ->put("/posts/{$post->id}", ['title' => 'Updated'])
        ->assertOk();

    $this->assertEquals('Updated', $post->fresh()->title);

    $this->actingAs($user)
        ->delete("/posts/{$post->id}")
        ->assertOk();

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
}
```

**Split into focused tests (GOOD):**
```php
public function test_user_can_create_post(): void { /* ... */ }
public function test_user_can_update_own_post(): void { /* ... */ }
public function test_user_can_delete_own_post(): void { /* ... */ }
```

### 1.7 Ignoring Test Maintenance Cost

**The Problem:** Every test is code you maintain. Tests with complex setup, deep mocking trees, or specific format assertions create ongoing maintenance burden that can outweigh their value.

**High maintenance cost signals:**
- Tests that break on every minor UI change
- Tests requiring manual data setup across 20+ lines
- Tests asserting exact log messages or debug output
- Tests that need updating when unrelated features change

### 1.8 Not Using Factories Properly

**Common factory anti-patterns:**
- Creating models with raw `Model::create()` instead of factories
- Not defining factory states for common variants
- Overriding every attribute in `create()` calls (defeats the purpose of defaults)
- Not using `for()` and `has()` for relationships
- Using `make()` when you need `create()` (or vice versa)
- Not using `Sequence` for variations across multiple models

### 1.9 Over-Reliance on Unit Tests

**The Laravel community's strong consensus:** Feature tests are the primary test layer for Laravel applications. Unit tests are for isolated business logic (value objects, calculations, pure functions). Most Laravel code involves the framework (HTTP, database, queues, events) and is best tested through feature tests.

**The "test pyramid" inverts for Laravel apps.** The traditional pyramid (many unit, some integration, few E2E) does not apply to framework-heavy applications. The Laravel community generally advocates a "test trophy" or "test diamond" shape: a broad middle layer of feature tests, a smaller layer of unit tests for pure logic, and a thin layer of browser/E2E tests.

### 1.10 Asymmetric Testing (Happy Path vs Edge Cases)

**Under-testing happy paths:** Developers sometimes focus on edge cases and error handling while never testing that the primary flow works end-to-end.

**Over-testing edge cases:** Testing every possible invalid email format when Laravel's `email` validation rule handles it.

**Balance:** Test the happy path first. Then test edge cases that represent *your business logic*, not framework validation.

---

## 2. What NOT to Test

### 2.1 Framework Behavior

Do not test:
- That `HasMany` relationships return a collection
- That Eloquent `create()` persists to the database
- That middleware works as documented
- That `Route::get()` registers a GET route
- That `Auth::check()` returns true for authenticated users
- That `Cache::put()` and `Cache::get()` work
- That `Validator::make()` validates correctly

These are the framework's responsibility and are covered by Laravel's own 8,000+ tests.

### 2.2 Simple CRUD Without Business Logic

If a controller simply validates input, creates a model, and returns a response with no additional logic, the test provides minimal value:

```php
// This controller has no business logic worth testing separately
public function store(Request $request): JsonResponse
{
    $validated = $request->validate(['name' => 'required|string|max:255']);
    $tag = Tag::create($validated);
    return response()->json($tag, 201);
}
```

**Exception:** If the CRUD endpoint is part of a public API with a contract, a feature test verifying the response format is valuable.

### 2.3 Getters/Setters With No Logic

```php
// Do NOT test this
public function getName(): string
{
    return $this->name;
}
```

### 2.4 Private Methods

Private methods are implementation details. Test them through the public interface that uses them. If a private method is complex enough to warrant its own tests, it should be extracted into its own class.

### 2.5 Third-Party Package Internals

Do not test that Spatie's media library saves files correctly, or that Laravel Cashier charges Stripe correctly. Test *your integration* with these packages -- that you call them correctly and handle their responses.

### 2.6 Exact HTML Output or CSS Classes

**Do not test:**
```php
$response->assertSee('<div class="mt-4 p-6 bg-white rounded-lg shadow-md">');
```

**Do test:**
```php
$response->assertSee('Welcome, Taylor');
$response->assertViewHas('user');
```

### 2.7 Configuration Values

Do not test that `config('app.name')` returns what `.env` says. Configuration is infrastructure, not behavior.

### 2.8 Simple Accessors/Mutators (Casts)

Eloquent casts are framework features. Do not test that a `datetime` cast returns a Carbon instance. Do test a custom cast that contains business logic.

### 2.9 Framework Validation Rules

Do not write tests that verify `required`, `email`, `max:255`, etc. work. These are tested by the framework. **Do** test custom validation rules you write, and **do** test that the correct validation rules are applied to an endpoint if the validation rules represent important business constraints.

### 2.10 Eloquent Query Builder Internals

**Do not test:**
```php
// Testing that the query builder generates correct SQL
$this->assertEquals(
    'select * from users where active = 1',
    User::where('active', 1)->toSql()
);
```

**Do test the result:**
```php
public function test_active_scope_returns_only_active_users(): void
{
    User::factory()->create(['active' => true]);
    User::factory()->create(['active' => false]);

    $this->assertCount(1, User::active()->get());
}
```

---

## 3. Testing Boundaries

### 3.1 Where Does "Testing Your Code" End and "Testing the Framework" Begin?

**The bright line:** If you removed the framework and rewrote the same logic in plain PHP, would the test still make sense?

- **Your code:** Business rules, domain logic, authorization rules, computed values, custom scopes, event handling logic, job processing logic, notification content decisions
- **Framework code:** HTTP routing, middleware execution order, Eloquent CRUD operations, validation rule implementations, session management, cache operations

**Gray areas (test selectively):**
- Form Request validation rules: Test if the rules represent important business constraints. Skip if they are standard input sanitization.
- Route model binding: Test implicitly through feature tests. Don't write dedicated binding tests.
- Event/listener wiring: Test that the right events fire with the right data, but don't test that Laravel dispatches events.

### 3.2 When Is a Feature Test Better Than a Unit Test?

**Feature tests are better when:**
- The code under test touches the database
- The code involves HTTP request/response cycles
- Multiple classes collaborate to produce a result
- You need to test middleware, authentication, or authorization
- You want to test "from the user's perspective"
- The code dispatches events, jobs, or notifications

**Unit tests are better when:**
- Testing pure business logic with no framework dependencies
- Testing value objects, DTOs, or simple calculations
- Testing utility/helper classes
- Testing string formatters, money calculations, date logic
- The class is genuinely independent of the framework
- You want fast feedback on a specific algorithm

**The Laravel docs explicitly recommend:** "Generally, most of your tests should be feature tests. These types of tests provide the most confidence that your overall system functions as intended."

### 3.3 When to Mock vs Use Real Dependencies

**Use real dependencies (default choice):**
- Database (with RefreshDatabase trait -- this is fast and reliable)
- Cache (array driver in tests is already in-memory)
- Session (array driver in tests)
- Queue (sync driver in tests runs jobs immediately)
- Internal services and repositories

**Mock/fake (when interacting with the outside world):**
- External HTTP APIs: `Http::fake()`
- Email sending: `Mail::fake()`
- Notifications: `Notification::fake()`
- File storage for uploads: `Storage::fake()`
- Time: `$this->travel()` / `$this->freezeTime()`
- Job dispatching (when testing dispatch logic, not job execution): `Bus::fake()`
- Event dispatching (when testing event trigger, not listener): `Event::fake()`

**The principle:** Mock at the boundary of your system, not inside it. External APIs, email servers, and payment gateways are boundaries. Your own services, repositories, and models are not.

### 3.4 How Much Test Coverage Is "Enough"?

**Community consensus ranges:**
- **60-80% line coverage** is a healthy target for most Laravel applications
- **90%+** is diminishing returns for most applications and creates maintenance overhead
- **100%** is almost never worth it -- it forces testing of trivial code

**More important than a number:**
- All critical business paths are tested (payment, registration, authorization)
- All public API endpoints have at least one happy-path test
- Complex business rules have dedicated tests with edge cases
- Error handling for critical operations is tested
- Data integrity constraints are tested

**Practical metric:** If you can refactor confidently without fear of breaking production, your coverage is adequate.

### 3.5 When Is Testing a Waste of Time vs Essential?

**Essential to test:**
- Payment/billing logic
- Authentication and authorization rules
- Data integrity (cascading deletes, unique constraints in business logic)
- Complex business calculations
- Multi-step workflows (e.g., order processing pipeline)
- Integrations with external services (that they are called correctly)
- API contracts (response structure and status codes)

**Low-value/waste of time:**
- Testing that a Blade template renders (test the data, not the view)
- Testing that config values exist
- Testing simple setters/getters
- Testing framework-provided behavior
- Achieving 100% coverage on boilerplate code
- Testing admin panels with no business logic

---

## 4. Test Organization and Community Opinions

### 4.1 Tests Mirroring App Structure vs. Tests by Feature

**Mirroring app structure (traditional):**
```
tests/
  Feature/
    Http/
      Controllers/
        UserControllerTest.php
        PostControllerTest.php
    Models/
      UserTest.php
  Unit/
    Services/
      OrderServiceTest.php
```

**Organized by feature/domain (increasingly preferred):**
```
tests/
  Feature/
    Auth/
      RegistrationTest.php
      LoginTest.php
      PasswordResetTest.php
    Posts/
      CreatePostTest.php
      UpdatePostTest.php
      DeletePostTest.php
      ListPostsTest.php
    Orders/
      PlaceOrderTest.php
      CancelOrderTest.php
  Unit/
    Money/
      MoneyCalculationTest.php
```

**Community trend (2025-2026):** Feature-organized tests are increasingly favored because:
- Tests group by business capability, not technical layer
- Easier to find "all tests for the ordering feature"
- Better mapping to user stories and specifications
- Avoids giant `UserControllerTest` files with 50+ methods

### 4.2 Feature Tests as the Primary Test Layer

The Laravel community has converged on feature tests as the backbone of test suites. This is explicitly endorsed by the official documentation:

> "Generally, most of your tests should be feature tests. These types of tests provide the most confidence that your overall system functions as intended."

**Typical test distribution in healthy Laravel projects:**
- 70-80% feature tests
- 15-25% unit tests (business logic, value objects)
- 0-5% browser tests (Dusk, for critical user flows)

### 4.3 When Unit Tests Are Genuinely Valuable

Unit tests shine for:
- **Value objects:** `Money`, `DateRange`, `Address` -- pure logic with no framework dependencies
- **Domain calculations:** Tax computation, discount rules, pricing tiers
- **String/data transformations:** Slug generation, format conversion, serialization
- **State machines:** Order status transitions, workflow steps
- **Algorithms:** Sorting, filtering, scoring
- **Custom validation rules** with complex logic
- **Policy/authorization logic** when tested in isolation (though feature tests cover this too)

### 4.4 Test Naming Conventions

**Descriptive names (strongly preferred in Laravel community):**
```php
public function test_guest_cannot_access_admin_dashboard(): void
public function test_user_can_create_post_with_valid_data(): void
public function test_order_total_includes_tax_for_taxable_items(): void
```

**PHPUnit 12 also supports attributes:**
```php
#[Test]
public function guest_cannot_access_admin_dashboard(): void
```

**Naming principles:**
- Start with the actor: "user", "guest", "admin"
- State what should happen, not how
- Include the condition: "with valid data", "when unauthenticated"
- Use snake_case for `test_` prefix methods (Laravel convention)
- Avoid vague names: `test_it_works`, `test_post_endpoint`, `test_validation`

### 4.5 Test Data: Factories vs Inline vs Data Providers

**Factories (default choice for Eloquent models):**
```php
$user = User::factory()->admin()->create();
$post = Post::factory()->for($user)->published()->create();
```

**Inline data (for non-model data or when specific values matter):**
```php
$this->post('/api/orders', [
    'product_id' => $product->id,
    'quantity' => 3,  // This specific value matters for the assertion
]);
```

**Data providers (for testing the same logic with multiple inputs):**
```php
public static function invalidEmailProvider(): array
{
    return [
        'missing @' => ['notanemail'],
        'missing domain' => ['user@'],
        'empty string' => [''],
    ];
}

#[DataProvider('invalidEmailProvider')]
public function test_registration_rejects_invalid_email(string $email): void
{
    $this->post('/register', ['email' => $email])
        ->assertSessionHasErrors('email');
}
```

**Data provider guidance:**
- Use when testing boundary conditions of a single rule
- Use when the same assertion applies to multiple inputs
- Do NOT use when different inputs require different setup or assertions
- Keep provider data simple -- if setup differs per case, use separate tests

---

## 5. Database Testing Strategy

### 5.1 RefreshDatabase vs DatabaseTransactions vs DatabaseMigrations

**RefreshDatabase (recommended default):**
- Runs migrations once, then wraps each test in a transaction that rolls back
- Fastest option for most applications
- **Limitation:** Cannot test code that uses database transactions itself (nested transactions) -- may silently behave differently
- The official docs say: "The RefreshDatabase trait is the preferred method for resetting the database between tests"

**DatabaseTransactions:**
- Wraps each test in a transaction (does NOT run migrations)
- Requires database to already be migrated
- Slightly faster than RefreshDatabase for the first test (no migration check)
- Same transaction nesting limitation

**DatabaseMigrations:**
- Runs `migrate:fresh` before every test class or test method
- Slowest option by far
- Use ONLY when you need a completely clean database (schema changes, etc.)

**DatabaseTruncation:**
- Truncates all tables between tests
- Slower than RefreshDatabase
- Use when transaction rollback is insufficient (e.g., testing code that commits transactions)

**Recommendation for this project:** Use `RefreshDatabase` as the default. Switch to `DatabaseTruncation` only for specific tests that manage their own transactions.

### 5.2 In-Memory SQLite vs Real Database

**This project uses in-memory SQLite** (configured in `phpunit.xml`):
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

**Advantages of SQLite for tests:**
- Extremely fast (no disk I/O, no network)
- No external service dependency
- Easy CI/CD setup
- Perfect for unit and most feature tests

**SQLite Gotchas (critical for a MySQL production database):**

1. **JSON column behavior differs.** MySQL has native JSON column type with `JSON_EXTRACT`, `->` operator, etc. SQLite stores JSON as TEXT. Queries using `->` accessor, `whereJsonContains`, or `whereJsonLength` may behave differently or fail.

2. **Column types and strictness.** SQLite is loosely typed. MySQL strict mode rejects data that SQLite accepts silently (e.g., inserting a string into an integer column, exceeding `VARCHAR(255)` length).

3. **Foreign key enforcement.** SQLite foreign keys are off by default. Laravel enables them, but behavior differs from MySQL (e.g., cascade operations, deferred constraints).

4. **String functions differ.** MySQL's `CONCAT`, `IFNULL`, `GROUP_CONCAT`, `DATE_FORMAT`, `REGEXP` may not work identically in SQLite.

5. **Schema differences.** `ENUM` columns, `UNSIGNED` integers, `FULLTEXT` indexes, and `SPATIAL` indexes are not supported in SQLite.

6. **`ALTER TABLE` limitations.** SQLite has very limited `ALTER TABLE` support. Dropping columns, renaming columns, and changing column types may fail or behave differently.

7. **Locking behavior.** SQLite uses file-level locking, not row-level. Concurrency tests are meaningless with SQLite.

8. **`LIKE` is case-insensitive in SQLite**, case-sensitive in MySQL (for non-UTF8 collations).

**Recommendation:** Use in-memory SQLite for the vast majority of tests (speed is paramount). Create a small suite of "integration" tests that run against the real MySQL database for:
- Tests involving JSON columns
- Tests with raw SQL queries
- Tests verifying unique constraint behavior
- Tests with complex joins or subqueries
- Tests for database-specific features

### 5.3 Seeding in Tests

**Per-test seeding (preferred):**
```php
public function test_dashboard_shows_statistics(): void
{
    // Create exactly the data this test needs
    Order::factory()->count(5)->completed()->create();
    Order::factory()->count(2)->pending()->create();

    $response = $this->actingAs(User::factory()->admin()->create())
        ->get('/dashboard');

    $response->assertViewHas('completedCount', 5);
}
```

**Global seeding (use sparingly):**
```php
// In TestCase.php -- seeds before every test
protected $seed = true;
```

Use global seeding only for lookup tables (countries, statuses, permission types) that are required by most tests.

**Anti-pattern:** Relying on seed data for assertions. Tests should create their own data for the specific scenario they verify.

---

## 6. Real-World Testing Strategies from Laravel Companies

### 6.1 Spatie's Testing Philosophy

Spatie (the most prolific Laravel open-source shop, led by Freek Van der Heuvel) follows these principles:

- **Feature tests are primary.** Their packages (laravel-permission, laravel-medialibrary, laravel-backup) use feature tests that exercise the full stack.
- **Test the public API.** They test the interface their package exposes, not internal implementation.
- **Keep tests readable.** Tests read like specifications of what the package does.
- **Use factories extensively.** Custom test models and factories are created specifically for testing.
- **Avoid mocking the database.** They use real databases (SQLite) for testing. Mocking is reserved for HTTP clients and external services.
- **Test organization by feature:** Tests are grouped by what they test (permissions, roles, teams), not by class name.

### 6.2 How Laravel Itself Is Tested

The Laravel framework has 8,000+ tests covering:
- **Integration tests:** Most tests are integration/feature tests that boot the application and test through the HTTP layer or service container.
- **Minimal mocking:** The framework tests use real implementations wherever possible. Mocking is used primarily for external services.
- **Database driver tests:** Tests run against multiple database drivers (SQLite, MySQL, PostgreSQL, SQL Server) to catch driver-specific issues.
- **Regression tests:** Many tests are added specifically to prevent regressions from reported bugs.

### 6.3 Testing Strategies from Prominent Laravel Community Members

**Jason McCreary (Laravel Shift):**
- Advocates for "confidence-driven testing" -- test what gives you confidence to deploy
- Feature tests for endpoints, unit tests for complex business logic
- Don't chase coverage numbers; chase confidence
- A failing test should tell you what broke, not that your mocks are wrong

**Christoph Rumpel:**
- Emphasizes testing as documentation -- tests should describe what the application does
- Recommends starting with feature tests and only adding unit tests when feature tests are insufficient
- "If you can describe it in a user story, it's a feature test"

**Tim MacDonald:**
- Known for testing custom validation rules, custom Eloquent casts, and middleware
- Advocates testing your own abstractions, not the framework's
- Writes extensively about avoiding test duplication through shared assertions

**Marcel Pociot (BeyondCode):**
- Tests interactions with external services using Http::fake()
- Emphasizes contract testing for APIs
- Advocates for testing webhook handlers end-to-end

### 6.4 Laracon Testing Talks (2024-2025 Key Themes)

Key themes from recent Laracon conferences:

- **"Test behavior, not implementation"** -- recurring theme in almost every testing talk
- **The shift away from strict TDD** toward "test when it adds value" -- pragmatic testing
- **Feature tests as first-class citizens** -- the Laravel testing helpers (actingAs, assertDatabaseHas, etc.) are designed for feature tests
- **Pest adoption:** Many talks featured Pest as the default test runner, though PHPUnit remains fully supported
- **Parallel testing:** Emphasis on test suite speed and using `--parallel` in CI
- **Database testing over mocking:** Strong push toward using real database interactions in tests rather than mocking repositories

---

## 7. PHPUnit 12 Specifics

### 7.1 Key Changes in PHPUnit 12

This project uses PHPUnit 12.5.12. Key changes from PHPUnit 11:

- **PHP 8.3+ required** (matches this project's requirement)
- **Attributes over annotations:** `#[Test]`, `#[DataProvider]`, `#[Depends]` replace `@test`, `@dataProvider`, `@depends` doc-block annotations. Annotations are removed entirely.
- **`setUp()` and `tearDown()` must call parent.** This was always required but is more strictly enforced.
- **Removed deprecated assertions:** Some older assertion aliases are gone. Use the canonical names.
- **Strict mode improvements:** PHPUnit 12 is stricter about risky tests, unused mocks, and incomplete tests.
- **Configuration simplification:** The XML configuration format has been cleaned up.

### 7.2 PHPUnit 12 Best Practices

```php
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function order_total_includes_shipping(): void
    {
        // ...
    }

    #[DataProvider('discountScenarios')]
    public function test_discount_calculation(int $subtotal, int $discount, int $expected): void
    {
        // ...
    }

    public static function discountScenarios(): array
    {
        return [
            '10% discount' => [10000, 10, 9000],
            '25% discount' => [10000, 25, 7500],
            'no discount' => [10000, 0, 10000],
        ];
    }
}
```

---

## 8. Recommended Testing Strategy for This Project

### 8.1 Project Context

- Fresh Laravel 13 application with PHP 8.3+
- PHPUnit 12.5.12 (not Pest)
- In-memory SQLite for tests
- MySQL 8.4 for production
- Redis for cache/queue/sessions
- No domain logic yet -- ready for feature development

### 8.2 Recommended Approach

**Test Layer Distribution:**
- ~75% Feature tests (HTTP endpoint tests, full-stack tests)
- ~20% Unit tests (business logic, value objects, calculations)
- ~5% Integration tests against real MySQL (when SQLite diverges)

**Default Traits:**
- `RefreshDatabase` for all feature tests that touch the database
- No trait needed for pure unit tests (they extend `PHPUnit\Framework\TestCase`, not `Tests\TestCase`)

**Organization:**
```
tests/
  Feature/
    Auth/
      LoginTest.php
      RegistrationTest.php
    {FeatureName}/
      {ActionTest}.php
  Unit/
    {DomainConcept}/
      {ClassTest}.php
  Integration/
    {DatabaseSpecificTest}.php  (runs against MySQL)
```

**Naming Convention:**
```php
// Feature tests: describe the user action and expected outcome
public function test_authenticated_user_can_create_post(): void
public function test_guest_is_redirected_to_login(): void
public function test_validation_rejects_empty_title(): void

// Unit tests: describe the input and expected output
public function test_calculates_tax_for_taxable_items(): void
public function test_formats_currency_with_two_decimal_places(): void
```

**Factory Strategy:**
- Define factory states for every meaningful model variant (admin, suspended, verified, etc.)
- Use `for()` and `has()` for relationship setup
- Prefer factories over manual `create()` calls
- Use `Sequence` for alternating data

**Mocking Strategy:**
- Default to real implementations
- Use `Http::fake()` for external APIs
- Use `Mail::fake()`, `Notification::fake()` for outbound communications
- Use `Storage::fake()` for file uploads
- Use `Bus::fake()` only when testing that a job was dispatched, not when testing the job itself
- Use `Event::fake()` only when testing that an event was fired, not when testing listeners
- Use `$this->travel()` / `$this->freezeTime()` for time-sensitive logic
- Never mock `Request`, `Config`, or the database

**What to Always Test:**
1. Every endpoint's happy path (correct status code, correct response shape)
2. Authentication/authorization rules (who can access what)
3. Validation rules that represent business constraints
4. Business logic calculations and transformations
5. Side effects (emails sent, jobs dispatched, events fired)
6. Error handling for critical operations

**What to Skip:**
1. Framework behavior (Eloquent CRUD, middleware execution, validation rule implementations)
2. Simple accessors/getters
3. Configuration values
4. Third-party package internals
5. Exact HTML/CSS output
6. Tests that only verify mock expectations match mock returns

### 8.3 SQLite Safeguards

Since the project uses SQLite for tests but MySQL for production:

1. Avoid raw SQL in application code; use Eloquent/Query Builder
2. When using JSON columns, add a dedicated MySQL integration test
3. When using database-specific features (fulltext search, spatial queries), test against MySQL
4. Consider adding a CI job that runs the full test suite against MySQL periodically
5. Be aware that `enum` columns and `unsigned` integers may behave differently

### 8.4 Test Speed Guidelines

- Aim for the full suite to run in under 30 seconds
- Use `php artisan test --profile` to identify slow tests
- Use `php artisan test --parallel` when the suite grows large
- Prefer `RefreshDatabase` over `DatabaseMigrations` for speed
- Use `make()` instead of `create()` when database persistence is not needed
- Minimize factory relationship chains -- only create what the test needs

---

## Sources

### Official Laravel Documentation (12.x)
- [Testing Overview](https://laravel.com/docs/12.x/testing) -- Feature vs unit test guidance, test organization
- [HTTP Tests](https://laravel.com/docs/12.x/http-tests) -- Single request per test, response assertions
- [Database Testing](https://laravel.com/docs/12.x/database-testing) -- RefreshDatabase, DatabaseMigrations, DatabaseTruncation, assertions
- [Mocking](https://laravel.com/docs/12.x/mocking) -- When to mock, facade faking, spies, time manipulation
- [Eloquent Factories](https://laravel.com/docs/12.x/eloquent-factories) -- States, sequences, relationships, callbacks
- [HTTP Client](https://laravel.com/docs/12.x/http-client) -- Http::fake(), assertSent, preventing stray requests
- [Console Tests](https://laravel.com/docs/12.x/console-tests) -- Command testing, input/output mocking
- [Filesystem](https://laravel.com/docs/12.x/filesystem) -- Storage::fake(), file upload testing
- [Service Container](https://laravel.com/docs/12.x/container) -- Dependency injection for testability, environment-specific bindings

### Community and Industry Sources (from training knowledge)
- Spatie testing guidelines and open-source package test suites
- Jason McCreary (Laravel Shift) -- confidence-driven testing philosophy
- Christoph Rumpel -- testing as documentation
- Tim MacDonald -- custom abstraction testing patterns
- Marcel Pociot (BeyondCode) -- external service and webhook testing
- Martin Fowler -- Practical Test Pyramid, mocking guidance
- Kent Beck -- Test-Driven Development principles
- DHH (David Heinemeier Hansson) -- testing pragmatism, "test what matters"
- Laracon conference talks (2024-2025) on testing strategy
- PHPUnit 12 release notes and migration guide
