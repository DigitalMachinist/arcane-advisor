# Testing Strategy Improvements — Design Spec

**Date:** 2026-03-29
**Status:** Approved
**Scope:** Switch to Pest as sole test interface; document and enforce MySQL/SQLite compatibility

---

## Overview

Two changes to the project's testing infrastructure:

1. **Pest as the sole developer-facing test framework** — PHPUnit remains as an internal engine but developers never interact with it directly.
2. **MySQL vs SQLite compatibility strategy** — a hybrid approach combining documentation, static arch tests, and a dual-database CI pipeline.

---

## 1. Pest as Sole Test Interface

### What Changes

- Install Pest v3 + plugins (`pestphp/pest-plugin-laravel`, `pestphp/pest-plugin-type-coverage`)
- Run `--init` to create `tests/Pest.php`
- Run `--drift` to convert the two existing example tests to Pest closure syntax
- `phpunit.xml` stays (Pest reads it) but documentation never references `phpunit` directly
- All test examples in docs use Pest `test()` / `it()` syntax exclusively

### Commands

| Command | Purpose |
|---|---|
| `php artisan test` | Primary command (Laravel auto-delegates to Pest when installed) |
| `composer run test` | Convenience alias (already calls `php artisan test`) |
| `./vendor/bin/pest --mutate` | Pest-specific flags (mutation testing, type coverage, arch) |
| `./vendor/bin/pest --type-coverage` | Type coverage analysis |
| `./vendor/bin/pest --arch` | Run architectural tests only |

### What Developers See

- `php artisan test` is the standard command — works exactly as before, just runs Pest
- All test files use Pest closure syntax
- `tests/Pest.php` configures the base TestCase and shared traits
- PHPUnit exists only as an invisible dependency

---

## 2. MySQL vs SQLite Compatibility

### Approach: Hybrid (Documentation + Static Detection + Dual CI)

Three layers of protection:

1. **Reference documentation** — a table in the testing strategy doc listing MySQL features SQLite doesn't support, organized by risk level
2. **Pest architectural tests** — static rules that flag MySQL-only patterns in application code, providing fast local feedback
3. **Dual-database CI** — SQLite for speed on every push, MySQL for correctness on every PR

### Feature Difference Reference

#### High Risk (commonly used in Laravel apps)

| MySQL Feature | SQLite Behavior | Detectable Statically |
|---|---|---|
| JSON columns (`->`, `whereJsonContains`, `whereJsonLength`) | Stored as TEXT, operators may fail or differ | Yes |
| `ENUM` columns | Stored as TEXT, no constraint enforcement | Yes (in migrations) |
| Strict mode (type coercion) | Silently accepts wrong types | No |
| `FULLTEXT` indexes | Not supported | Yes |
| `REGEXP` / `RLIKE` | Different syntax/behavior | Partially (in raw SQL) |

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

### Test Categorization

**Two groups:**

- **Default (no tag)** — runs against SQLite. The fast, local-first suite. ~95% of tests.
- **`mysql` group** — tests exercising MySQL-specific features. Skipped in default SQLite run.

**Tagging convention:**

```php
test('full-text search returns matching products', function () {
    // uses FULLTEXT index
})->group('mysql');
```

**`phpunit.xml` default exclusion:**

Configure `phpunit.xml` to exclude the `mysql` group by default so `php artisan test` skips MySQL-dependent tests automatically.

**Running MySQL tests:**

```bash
php artisan test                          # SQLite suite (excludes mysql group)
php artisan test --group=mysql            # MySQL-dependent tests only
```

### Architectural Test Enforcement

A `tests/Arch/DatabaseCompatibilityTest.php` that flags MySQL-only patterns:

- `arch()` rules that flag usage of `whereJsonContains`, `whereJsonLength`, `fullText()` etc. in application code
- Grep-based Pest tests that scan for MySQL-only SQL patterns in `DB::raw()` / `DB::statement()` calls
- These tests give fast local feedback — developers know immediately if they've introduced a MySQL dependency

### CI Pipeline

| Trigger | What Runs | Database |
|---|---|---|
| Every push | Default test suite + arch tests | SQLite (in-memory) |
| Every PR | Full test suite including `mysql` group | MySQL 8.4 |

---

## 3. Changes to Testing Strategy Document

The existing `docs/testing-strategy.md` will be updated:

1. **Test Framework section** — rewritten to present Pest as established (not a recommendation). Remove installation/migration steps.
2. **All code examples** — converted to Pest `test()`/`it()` syntax. No PHPUnit class-based examples.
3. **New section: "Database Compatibility"** — the MySQL/SQLite reference table, `mysql` group convention, decision guide for when to tag.
4. **Architectural Tests section** — expanded to include database compatibility enforcement rules.
5. **CI Integration section** — updated to show dual-database pipeline.
6. **Commands Reference** — `php artisan test` as primary, `./vendor/bin/pest` for Pest-specific flags.
7. **Philosophy, what to test/not test, mocking, coverage, anti-patterns** — unchanged.

---

## Out of Scope

- Actually installing Pest or running migrations (that's the implementation plan)
- Writing the CI GitHub Actions workflow file (implementation)
- Modifying `composer.json` or `phpunit.xml` (implementation)
- Changes to the style guide document
