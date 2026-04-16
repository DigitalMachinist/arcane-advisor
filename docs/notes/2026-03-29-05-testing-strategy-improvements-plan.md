# Testing Strategy Improvements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Switch to Pest as the sole developer-facing test framework and implement a MySQL/SQLite compatibility enforcement strategy.

**Architecture:** Install Pest v3 on top of PHPUnit, convert existing tests, add architectural tests for database compatibility enforcement, configure phpunit.xml for mysql group exclusion, create a dual-database CI workflow, and update all documentation.

**Tech Stack:** Pest v3, pestphp/pest-plugin-laravel, pestphp/pest-plugin-type-coverage, PHPUnit 12 (internal), GitHub Actions

**Spec:** `docs/notes/2026-03-29-04-testing-strategy-improvements-spec.md`

---

## File Structure

**Create:**
- `tests/Pest.php` — Pest configuration (base TestCase, shared traits)
- `tests/Arch/ArchitectureTest.php` — Structural arch rules (presets, naming, etc.)
- `tests/Arch/DatabaseCompatibilityTest.php` — MySQL/SQLite compatibility enforcement
- `.github/workflows/tests.yml` — Dual-database CI pipeline

**Modify:**
- `composer.json` — Pest dependencies added via composer commands
- `phpunit.xml` — Add mysql group exclusion
- `tests/Feature/ExampleTest.php` — Converted to Pest syntax by `--drift`
- `tests/Unit/ExampleTest.php` — Converted to Pest syntax by `--drift`
- `docs/testing-strategy.md` — Rewritten per spec
- `CLAUDE.md` — Updated Testing section and commands

**Unchanged:**
- `tests/TestCase.php` — Stays as-is; referenced by `tests/Pest.php`

---

### Task 1: Install Pest and Plugins

**Files:**
- Modify: `composer.json` (via composer commands)
- Create: `tests/Pest.php`

- [ ] **Step 1: Install Pest v3 and the Laravel plugin**

```bash
composer require pestphp/pest --dev --with-all-dependencies
composer require pestphp/pest-plugin-laravel --dev
```

- [ ] **Step 2: Install the type coverage plugin**

```bash
composer require pestphp/pest-plugin-type-coverage --dev
```

- [ ] **Step 3: Initialize Pest**

```bash
./vendor/bin/pest --init
```

This creates `tests/Pest.php`. Verify the file exists:

```bash
cat tests/Pest.php
```

Expected: A file containing `uses(Tests\TestCase::class)->in('Feature');` or similar.

- [ ] **Step 4: Verify Pest runs the existing PHPUnit tests as-is**

```bash
php artisan test
```

Expected: 2 tests pass (Pest runs PHPUnit-style tests without conversion).

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock tests/Pest.php
git commit -m "Install Pest v3 as test framework

Add pestphp/pest, pest-plugin-laravel, and pest-plugin-type-coverage.
Initialize Pest with tests/Pest.php configuration."
```

---

### Task 2: Convert Existing Tests to Pest Syntax

**Files:**
- Modify: `tests/Feature/ExampleTest.php`
- Modify: `tests/Unit/ExampleTest.php`

- [ ] **Step 1: Run Pest drift to auto-convert tests**

```bash
./vendor/bin/pest --drift
```

This converts the two PHPUnit class-based tests to Pest closure syntax.

- [ ] **Step 2: Verify the converted Feature test**

Read `tests/Feature/ExampleTest.php`. It should now look like:

```php
<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
```

If `--drift` didn't produce clean output, manually write the file to match the above.

- [ ] **Step 3: Verify the converted Unit test**

Read `tests/Unit/ExampleTest.php`. It should now look like:

```php
<?php

test('that true is true', function () {
    expect(true)->toBeTrue();
});
```

If `--drift` didn't produce clean output, manually write the file to match the above.

- [ ] **Step 4: Run tests to confirm they pass**

```bash
php artisan test
```

Expected: 2 tests pass, all in Pest closure syntax.

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php
git commit -m "Convert existing tests to Pest syntax

Use pest --drift to convert PHPUnit class-based tests to Pest closures."
```

---

### Task 3: Configure phpunit.xml for MySQL Group Exclusion

**Files:**
- Modify: `phpunit.xml`

- [ ] **Step 1: Add mysql group exclusion to phpunit.xml**

Add a `<groups>` block inside the `<phpunit>` element, after the `<testsuites>` block and before `<source>`:

```xml
    <groups>
        <exclude>
            <group>mysql</group>
        </exclude>
    </groups>
```

The full `phpunit.xml` should now be:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <groups>
        <exclude>
            <group>mysql</group>
        </exclude>
    </groups>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="BROADCAST_CONNECTION" value="null"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="DB_URL" value=""/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
        <env name="NIGHTWATCH_ENABLED" value="false"/>
    </php>
</phpunit>
```

- [ ] **Step 2: Verify tests still pass**

```bash
php artisan test
```

Expected: 2 tests pass (no mysql-group tests exist yet, so nothing changes).

- [ ] **Step 3: Commit**

```bash
git add phpunit.xml
git commit -m "Exclude mysql test group from default test suite

Tests tagged with ->group('mysql') will only run when explicitly
invoked with --group=mysql against a real MySQL database."
```

---

### Task 4: Create Architectural Tests — Structure Rules

**Files:**
- Create: `tests/Arch/ArchitectureTest.php`

- [ ] **Step 1: Create the tests/Arch directory**

```bash
mkdir -p tests/Arch
```

- [ ] **Step 2: Write the architectural test file**

Create `tests/Arch/ArchitectureTest.php`:

```php
<?php

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

Note: We intentionally omit `arch()->preset()->laravel()` and the `strict types` rule for now. The `laravel` preset may enforce opinions we haven't validated yet, and strict types requires running Pint with `declare_strict_types` first (a style guide task, not a testing task). These can be added once the style guide tooling is in place.

- [ ] **Step 3: Run the arch tests**

```bash
php artisan test tests/Arch
```

Expected: All arch tests pass. The `App\Http\Controllers`, `App\Http\Requests`, and `App\Jobs` namespaces may be empty — Pest arch tests pass for empty namespaces (no violations found).

- [ ] **Step 4: Commit**

```bash
git add tests/Arch/ArchitectureTest.php
git commit -m "Add architectural tests for structural code rules

Enforce: no debug functions, controller/model/request/job naming
and inheritance conventions via Pest arch() tests."
```

---

### Task 5: Create Architectural Tests — Database Compatibility

**Files:**
- Create: `tests/Arch/DatabaseCompatibilityTest.php`

- [ ] **Step 1: Write the database compatibility arch test file**

Create `tests/Arch/DatabaseCompatibilityTest.php`:

```php
<?php

/**
 * Database Compatibility Tests
 *
 * These tests detect usage of MySQL-specific features that are not supported
 * by SQLite (our default test database). Code using these features must be
 * tagged with ->group('mysql') so it runs against MySQL in CI.
 *
 * See docs/testing-strategy.md "Database Compatibility" for the full reference.
 */

arch('no whereJsonContains in app code')
    ->expect('App')
    ->not->toUse(['whereJsonContains']);

arch('no whereJsonLength in app code')
    ->expect('App')
    ->not->toUse(['whereJsonLength']);

/**
 * Scan for MySQL-only SQL patterns in raw database calls.
 *
 * This test greps the application source for DB::raw(), DB::statement(),
 * and DB::unprepared() calls containing MySQL-specific functions that
 * do not work in SQLite.
 */
test('no MySQL-only SQL in raw database calls', function () {
    $mysqlPatterns = [
        'FULLTEXT',
        'GROUP_CONCAT',
        'JSON_ARRAYAGG',
        'JSON_OBJECT',
        'DATE_FORMAT',
        'YEAR(',
        'MONTH(',
        'DAY(',
        'REGEXP',
        'RLIKE',
        'FOR UPDATE',
        'LOCK IN SHARE MODE',
    ];

    $appPath = base_path('app');
    $violations = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($appPath, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($file->getPathname());

        // Only check files that use raw SQL
        if (! preg_match('/DB::(raw|statement|unprepared|select)\s*\(/', $contents)) {
            continue;
        }

        foreach ($mysqlPatterns as $pattern) {
            if (stripos($contents, $pattern) !== false) {
                $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
                $violations[] = "{$relativePath} contains MySQL-only pattern: {$pattern}";
            }
        }
    }

    expect($violations)
        ->toBeEmpty()
        ->when(
            count($violations) > 0,
            fn ($expectation) => $expectation->and(implode("\n", $violations))
                ->toBe('No MySQL-only patterns should exist in app code. Tag related tests with ->group(\'mysql\').')
        );
});
```

- [ ] **Step 2: Run the database compatibility tests**

```bash
php artisan test tests/Arch/DatabaseCompatibilityTest.php
```

Expected: All tests pass (no MySQL-specific code exists yet in the fresh project).

- [ ] **Step 3: Commit**

```bash
git add tests/Arch/DatabaseCompatibilityTest.php
git commit -m "Add database compatibility arch tests

Detect MySQL-only features (JSON queries, raw SQL patterns) that would
fail against the SQLite test database. Flags violations so developers
know to tag tests with ->group('mysql')."
```

---

### Task 6: Create GitHub Actions CI Workflow

**Files:**
- Create: `.github/workflows/tests.yml`

- [ ] **Step 1: Create the .github/workflows directory**

```bash
mkdir -p .github/workflows
```

- [ ] **Step 2: Write the CI workflow file**

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  sqlite-tests:
    name: SQLite Tests
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo_sqlite, pcov
          coverage: pcov

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run Pint (code style)
        run: ./vendor/bin/pint --test

      - name: Run tests
        run: php artisan test

      - name: Run type coverage
        run: ./vendor/bin/pest --type-coverage --min=100

  mysql-tests:
    name: MySQL Tests
    runs-on: ubuntu-latest
    if: github.event_name == 'pull_request'

    services:
      mysql:
        image: mysql:8.4
        env:
          MYSQL_DATABASE: testing
          MYSQL_ROOT_PASSWORD: password
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo_mysql, pcov
          coverage: pcov

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run full test suite against MySQL
        run: php artisan test --group=mysql
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password
```

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/tests.yml
git commit -m "Add GitHub Actions CI workflow

SQLite tests run on every push (fast). MySQL tests run on PRs only
to catch dialect-specific issues in mysql-grouped tests."
```

---

### Task 7: Update the Testing Strategy Document

**Files:**
- Modify: `docs/testing-strategy.md`

This is the largest task — rewriting the document to reflect all changes from the spec. The key changes:

1. Test Framework section presents Pest as established (remove install/migration steps)
2. All code examples use Pest syntax exclusively (remove PHPUnit fallback mention)
3. New "Database Compatibility" section with reference tables and group convention
4. Architectural Tests section expanded with database compatibility rules
5. CI section updated to show dual-database pipeline
6. Commands reference updated

- [ ] **Step 1: Rewrite the Test Framework section**

Replace lines 19-42 (from `## Test Framework: Pest PHP` through the `---`) with:

```markdown
## Test Framework: Pest PHP

This project uses **Pest v3** as its test framework. Pest is the Laravel community's preferred testing tool, endorsed by the Laravel core team. It provides:

- Readable closure-based syntax with less boilerplate than PHPUnit classes
- Architectural testing via `arch()` for enforcing structural rules
- Mutation testing via `--mutate` for verifying test quality
- Type coverage via `--type-coverage` for enforcing type declarations
- Parallel test execution via `--parallel`

Pest runs on top of PHPUnit internally and reads `phpunit.xml` for configuration. Developers interact only with Pest — never with PHPUnit directly.

### Commands

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
```

- [ ] **Step 2: Remove the PHPUnit fallback from Naming Conventions**

In the "Naming Conventions" subsection under "Test Organization", remove lines 137-141 (the PHPUnit fallback block):

```markdown
**PHPUnit fallback (if needed):**
```php
public function test_authenticated_user_can_create_post(): void { ... }
```
```

- [ ] **Step 3: Replace the "SQLite vs MySQL Gotchas" subsection with a full "Database Compatibility" section**

Replace lines 166-180 (from `### SQLite vs MySQL Gotchas` through the `**Recommendation:**` line) with the new section. Place it after the "Factory Best Practices" subsection (after line 201):

```markdown
---

## Database Compatibility

This project uses **in-memory SQLite** for fast local tests and **MySQL 8.4** in production. Most tests run against SQLite. Tests that require MySQL-specific features are tagged and run separately in CI.

### MySQL vs SQLite Feature Reference

#### High Risk (commonly used in Laravel apps)

| MySQL Feature | SQLite Behavior | Detectable Statically |
|---|---|---|
| JSON columns (`->`, `whereJsonContains`, `whereJsonLength`) | Stored as TEXT, operators may fail or differ | Yes — arch test flags it |
| `ENUM` columns | Stored as TEXT, no constraint enforcement | Yes — in migrations |
| Strict mode (type coercion) | Silently accepts wrong types | No — rely on MySQL CI job |
| `FULLTEXT` indexes | Not supported | Yes — arch test flags it |
| `REGEXP` / `RLIKE` | Different syntax/behavior | Partially — in raw SQL |

#### Medium Risk

| MySQL Feature | SQLite Behavior |
|---|---|
| `GROUP_CONCAT` / `JSON_ARRAYAGG` | Different function names |
| `DATE_FORMAT`, `YEAR()`, `MONTH()` | Not available natively |
| `UNSIGNED` integers | Ignored |
| Row-level locking (`FOR UPDATE`) | File-level locking only |
| `ALTER TABLE` (drop/rename columns) | Very limited support |
| Case-sensitive `LIKE` | SQLite `LIKE` is case-insensitive |

#### Low Risk (rare in typical Laravel apps)

| MySQL Feature | SQLite Behavior |
|---|---|
| Spatial/GIS types | Not supported |
| Stored procedures | Not supported |
| Multiple character sets/collations | Limited |
| Partitioning | Not supported |

### Test Groups: SQLite vs MySQL

Tests are split into two groups:

- **Default (no tag)** — runs against SQLite. Fast, local-first. This is ~95% of tests.
- **`mysql` group** — tests requiring MySQL-specific features. Skipped by default, runs in CI.

Tag a test for MySQL when it uses any feature from the reference tables above:

```php
test('full-text search returns matching products', function () {
    // Uses FULLTEXT index — not supported by SQLite
})->group('mysql');
```

The `phpunit.xml` excludes the `mysql` group by default, so `php artisan test` always runs the fast SQLite suite. To run MySQL tests locally:

```bash
# Requires Docker MySQL to be running (composer run docker:start)
php artisan test --group=mysql
```

### When to Tag a Test as MySQL

Tag with `->group('mysql')` when your test:
- Uses `whereJsonContains`, `whereJsonLength`, or JSON `->` operator queries
- Relies on `FULLTEXT` search
- Uses `DB::raw()` with MySQL-specific SQL functions
- Tests behavior that depends on MySQL strict mode (type rejection)
- Tests row-level locking or transaction isolation

Do NOT tag when:
- You're using standard Eloquent methods (even on JSON cast columns — casts work in SQLite)
- You're testing business logic that doesn't touch MySQL-specific query features
- You're unsure — leave it untagged and let the arch tests or MySQL CI job catch issues

### Architectural Enforcement

The `tests/Arch/DatabaseCompatibilityTest.php` file automatically flags:
- Usage of `whereJsonContains` or `whereJsonLength` in `App\` code
- MySQL-only SQL patterns in `DB::raw()` / `DB::statement()` calls (FULLTEXT, GROUP_CONCAT, REGEXP, FOR UPDATE, etc.)

These arch tests run as part of the default suite and provide immediate feedback when MySQL-only patterns are introduced.
```

- [ ] **Step 4: Update the Architectural Testing section**

Replace lines 263-304 (the entire "## Architectural Testing" section) with:

```markdown
## Architectural Testing

Architectural tests enforce structural rules about the codebase via Pest's `arch()` function. They run as part of the default test suite.

### Structure Rules (`tests/Arch/ArchitectureTest.php`)

```php
arch()->preset()->php();       // No die, var_dump, etc.
arch()->preset()->security();  // No eval, exec, insecure hash functions

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

See the [Database Compatibility](#database-compatibility) section for details. These tests flag MySQL-only patterns in application code so developers know to tag related tests with `->group('mysql')`.
```

- [ ] **Step 5: Update the CI Integration section**

Replace lines 422-470 (the entire "## CI Integration" section) with:

```markdown
## CI Integration

The project uses a dual-database CI strategy:

| Trigger | Job | Database | What Runs |
|---|---|---|---|
| Every push to `main` | `sqlite-tests` | SQLite (in-memory) | Default test suite + arch tests + type coverage + Pint |
| Every PR | `mysql-tests` | MySQL 8.4 | Full suite including `mysql` group |

The SQLite job is fast (~30s) and catches most issues. The MySQL job runs on PRs only to verify dialect-specific code.

See `.github/workflows/tests.yml` for the full workflow configuration.
```

- [ ] **Step 6: Run all tests to verify nothing broke**

```bash
php artisan test
```

Expected: All tests pass (feature, unit, and arch tests).

- [ ] **Step 7: Commit**

```bash
git add docs/testing-strategy.md
git commit -m "Update testing strategy for Pest and database compatibility

Rewrite to present Pest as established framework, convert all examples
to Pest syntax, add MySQL/SQLite compatibility reference and test group
documentation, update CI section for dual-database pipeline."
```

---

### Task 8: Update CLAUDE.md

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Update the Testing commands section**

Replace the Testing subsection in Common Commands (lines 43-52) with:

```markdown
### Testing
```bash
php artisan test                              # Run all tests (Pest, SQLite)
php artisan test --filter=CreatePostTest       # Run a specific test file
php artisan test tests/Feature/               # Run only feature tests
php artisan test tests/Unit/                  # Run only unit tests
php artisan test tests/Arch/                  # Run only architectural tests
php artisan test --group=mysql                # Run MySQL-dependent tests (requires Docker MySQL)
./vendor/bin/pest --type-coverage --min=100   # Check type coverage
./vendor/bin/pest --mutate                    # Run mutation testing
./vendor/bin/pest --profile                   # Identify slow tests
```

Tests use **Pest v3** as the test framework. The default test suite runs against an **in-memory SQLite database** (configured in phpunit.xml). Tests tagged with `->group('mysql')` are excluded by default and require a running MySQL instance.
```

- [ ] **Step 2: Update the Testing Strategy section**

Replace the Testing Strategy section (lines 114-116) with:

```markdown
## Testing Strategy

See `docs/testing-strategy.md` for the complete testing strategy, including:
- What to test and what not to test
- Pest conventions and test organization
- MySQL vs SQLite database compatibility guide
- Mocking strategy and coverage targets
```

- [ ] **Step 3: Run tests one final time**

```bash
php artisan test
```

Expected: All tests pass.

- [ ] **Step 4: Commit**

```bash
git add CLAUDE.md
git commit -m "Update CLAUDE.md with Pest commands and testing strategy reference

Replace PHPUnit references with Pest commands, add mysql group
documentation, link to full testing strategy doc."
```

---

## Task Summary

| Task | Description | Files |
|---|---|---|
| 1 | Install Pest and plugins | `composer.json`, `tests/Pest.php` |
| 2 | Convert existing tests to Pest syntax | `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php` |
| 3 | Configure phpunit.xml mysql group exclusion | `phpunit.xml` |
| 4 | Create architectural tests — structure rules | `tests/Arch/ArchitectureTest.php` |
| 5 | Create architectural tests — database compatibility | `tests/Arch/DatabaseCompatibilityTest.php` |
| 6 | Create GitHub Actions CI workflow | `.github/workflows/tests.yml` |
| 7 | Update testing strategy document | `docs/testing-strategy.md` |
| 8 | Update CLAUDE.md | `CLAUDE.md` |
