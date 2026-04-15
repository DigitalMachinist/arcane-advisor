# Style Guide & Tooling Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Establish the project's code style guide, configure enforcement tooling (Pint, Larastan, Rector), add unified composer commands, and update the CI pipeline.

**Architecture:** Single-branch delivery on `feature/style-guide`. Install and configure three enforcement tools (Pint strict rules, Larastan level 6, Rector with code quality sets), add composer commands to orchestrate them, update GitHub Actions CI to run all checks, and write the authoritative style guide document. The existing codebase must pass all tools cleanly before committing each config.

**Tech Stack:** Laravel 13, PHP 8.3+, Laravel Pint, Larastan (PHPStan), Rector, Pest v4, GitHub Actions

**Spec:** `docs/specs/2026-03-30-style-guide-and-tooling-design.md`

---

## File Map

| Action | File | Responsibility |
|---|---|---|
| Create | `pint.json` | Pint strict rules on Laravel preset |
| Create | `phpstan.neon` | Larastan level 6 configuration |
| Create | `rector.php` | Rector code quality + PHP 8.3 rule sets |
| Modify | `composer.json` | Add dev dependencies + `analysis` and `check` scripts |
| Modify | `.github/workflows/tests.yml` | Add Rector, Larastan, mutation testing jobs |
| Create | `docs/style-guide.md` | Authoritative style reference |
| Modify | `CLAUDE.md` | Replace Style Guide TODO, add new commands |

---

### Task 1: Configure Pint Strict Rules

**Files:**
- Create: `pint.json`

- [ ] **Step 1: Create `pint.json`**

Create `pint.json` in the project root:

```json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "strict_comparison": true,
        "strict_param": true,
        "void_return": true,
        "nullable_type_declaration_for_default_null_value": true,
        "no_superfluous_phpdoc_tags": {
            "allow_mixed": true,
            "remove_inheritdoc": true
        },
        "ordered_class_elements": {
            "order": [
                "use_trait",
                "case",
                "constant_public",
                "constant_protected",
                "constant_private",
                "property_public",
                "property_protected",
                "property_private",
                "construct",
                "destruct",
                "magic",
                "phpunit",
                "method_public_abstract",
                "method_protected_abstract",
                "method_public_static",
                "method_protected_static",
                "method_private_static",
                "method_public",
                "method_protected",
                "method_private"
            ]
        },
        "trailing_comma_in_multiline": {
            "elements": [
                "arguments",
                "arrays",
                "match",
                "parameters"
            ]
        },
        "single_line_empty_body": true,
        "simplified_null_return": true,
        "array_indentation": true,
        "method_chaining_indentation": true,
        "multiline_whitespace_before_semicolons": {
            "strategy": "new_line_for_chained_calls"
        }
    }
}
```

- [ ] **Step 2: Run Pint to auto-fix the entire codebase**

Run: `./vendor/bin/pint`

This will apply the new strict rules to all existing PHP files (adding `declare(strict_types=1)`, converting `==` to `===`, adding `void` return types, etc.). Review the output to confirm it only touches expected files under `app/`, `config/`, `database/`, `routes/`, `tests/`.

- [ ] **Step 3: Run Pint check to verify clean state**

Run: `./vendor/bin/pint --test`

Expected: 0 files with style issues.

- [ ] **Step 4: Run existing tests to verify Pint changes don't break anything**

Run: `php artisan test`

Expected: All tests pass. The `declare_strict_types`, `strict_comparison`, and `strict_param` rules change runtime behavior, so tests must confirm nothing broke.

- [ ] **Step 5: Commit**

```bash
git add pint.json app/ config/ database/ routes/ tests/ bootstrap/
git commit -m "Add Pint strict rules and apply to codebase"
```

Stage `pint.json` plus any PHP files Pint modified. The commit message covers both the config and the auto-fixed files.

---

### Task 2: Install and Configure Larastan

**Files:**
- Create: `phpstan.neon`
- Modify: `composer.json` (via composer require)

- [ ] **Step 1: Install Larastan**

Run: `composer require --dev larastan/larastan`

This installs PHPStan and the Larastan extension. Verify it appears in `composer.json` under `require-dev`.

- [ ] **Step 2: Create `phpstan.neon`**

Create `phpstan.neon` in the project root:

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/

    level: 6

    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
```

- [ ] **Step 3: Run Larastan to verify clean analysis**

Run: `vendor/bin/phpstan analyse`

Expected: "No errors" at level 6. If there are errors, fix them in the relevant `app/` files before proceeding. Common issues on a fresh Laravel app at level 6: missing return types, untyped parameters. These should already be resolved by Pint's `void_return` rule from Task 1, but verify.

- [ ] **Step 4: Run tests to confirm nothing changed**

Run: `php artisan test`

Expected: All tests pass. Larastan is analysis-only — it doesn't change code — but confirm the test suite is still green.

- [ ] **Step 5: Commit**

```bash
git add phpstan.neon composer.json composer.lock
git commit -m "Install Larastan and configure PHPStan at level 6"
```

---

### Task 3: Install and Configure Rector

**Files:**
- Create: `rector.php`
- Modify: `composer.json` (via composer require)

- [ ] **Step 1: Install Rector and Laravel Rector**

Run: `composer require --dev rector/rector driftingly/rector-laravel`

Verify both appear in `composer.json` under `require-dev`.

- [ ] **Step 2: Create `rector.php`**

Create `rector.php` in the project root. Check the `driftingly/rector-laravel` package's README or source to determine how to integrate its Laravel rule sets (the API may use `->withSets()` or `->withConfiguredRule()`). The base config:

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/bootstrap/cache',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
    ])
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
```

Add the `driftingly/rector-laravel` integration based on what you find in its docs. If the package provides a prepared set or a `withSets()` call, add it. If it requires individual rule configuration, add the most commonly recommended rules.

- [ ] **Step 3: Run Rector dry-run to preview changes**

Run: `vendor/bin/rector process --dry-run`

Review the output. Rector may suggest changes to existing files (early returns, type tightening, dead code removal). These are expected and safe on a fresh codebase.

- [ ] **Step 4: Apply Rector changes**

Run: `vendor/bin/rector process`

This applies all suggested changes.

- [ ] **Step 5: Run Pint to format Rector's output**

Run: `./vendor/bin/pint`

Rector changes may not match Pint's formatting. Run Pint after Rector to ensure consistent formatting. This is the standard execution order: Rector -> Pint.

- [ ] **Step 6: Verify Rector dry-run is now clean**

Run: `vendor/bin/rector process --dry-run`

Expected: No changes suggested (Rector is idempotent after a full run).

- [ ] **Step 7: Run Pint check to verify clean state**

Run: `./vendor/bin/pint --test`

Expected: 0 files with style issues.

- [ ] **Step 8: Run Larastan to verify no new issues**

Run: `vendor/bin/phpstan analyse`

Expected: No errors. Rector's type tightening could theoretically introduce Larastan issues, so verify.

- [ ] **Step 9: Run tests**

Run: `php artisan test`

Expected: All tests pass. Rector's `earlyReturn` and `codeQuality` sets restructure code, so tests confirm behavior is preserved.

- [ ] **Step 10: Commit**

```bash
git add rector.php composer.json composer.lock app/ config/ database/ routes/ tests/
git commit -m "Install Rector and apply code quality rules to codebase"
```

Stage `rector.php`, dependency changes, and any PHP files Rector modified.

---

### Task 4: Add Composer Commands

**Files:**
- Modify: `composer.json:36-61` (scripts section)

- [ ] **Step 1: Add `analysis` and `check` scripts to `composer.json`**

Add two new scripts to the `"scripts"` section of `composer.json`. The existing `lint`, `lint:check`, and `test` scripts are preserved unchanged. Add after the existing `"lint:check"` entry:

```json
"analysis": [
    "vendor/bin/phpstan analyse"
],
```

Add after the existing `"docker:stop"` entry (or at the end of the custom scripts, before the lifecycle hooks):

```json
"check": [
    "./vendor/bin/pint --test",
    "vendor/bin/rector process --dry-run",
    "vendor/bin/phpstan analyse",
    "@php artisan config:clear --ansi",
    "@php artisan test",
    "./vendor/bin/pest --type-coverage --min=100"
],
```

- [ ] **Step 2: Test `composer run analysis`**

Run: `composer run analysis`

Expected: Larastan runs and reports no errors.

- [ ] **Step 3: Test `composer run check`**

Run: `composer run check`

Expected: All 6 steps run sequentially and pass: Pint check, Rector dry-run, Larastan, config clear, tests, type coverage.

- [ ] **Step 4: Commit**

```bash
git add composer.json
git commit -m "Add composer analysis and check commands"
```

---

### Task 5: Update CI Pipeline

**Files:**
- Modify: `.github/workflows/tests.yml`

- [ ] **Step 1: Update the workflow file**

Replace the entire contents of `.github/workflows/tests.yml` with:

```yaml
name: Tests

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  quality-checks:
    name: Quality Checks
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

      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run Pint (code style)
        run: ./vendor/bin/pint --test

      - name: Run Rector (code quality)
        run: vendor/bin/rector process --dry-run

      - name: Run Larastan (static analysis)
        run: vendor/bin/phpstan analyse

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

      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run full test suite against MySQL
        run: php artisan test --group=mysql
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: testing
          DB_USERNAME: root
          DB_PASSWORD: password

  mutation-tests:
    name: Mutation Tests
    runs-on: ubuntu-latest
    if: github.event_name == 'pull_request'

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

      - name: Prepare environment
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run mutation tests
        run: ./vendor/bin/pest --mutate --parallel --covered-only --min=70 --class=App\\Services,App\\Models,App\\Actions
```

Key changes from the existing workflow:
- Renamed `sqlite-tests` job to `quality-checks`
- Added Rector dry-run step and Larastan step to `quality-checks`
- Added new `mutation-tests` job (PR only)
- MySQL tests job unchanged

- [ ] **Step 2: Validate YAML syntax**

Run: `python3 -c "import yaml; yaml.safe_load(open('.github/workflows/tests.yml'))"`

Expected: No output (valid YAML). If Python/PyYAML isn't available, use: `ruby -ryaml -e "YAML.safe_load(File.read('.github/workflows/tests.yml'))"`

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/tests.yml
git commit -m "Add Rector, Larastan, and mutation testing to CI pipeline"
```

---

### Task 6: Write the Style Guide Document

**Files:**
- Create: `docs/style-guide.md`

- [ ] **Step 1: Create `docs/style-guide.md`**

Write the complete style guide document following the structure defined in the spec (`docs/specs/2026-03-30-style-guide-and-tooling-design.md`, "Style Guide Document Structure" section). The document must cover all 13 sections:

1. **Enforcement Toolchain** — List all tools (Pint, Larastan, Rector, Pest arch tests) with what each enforces, how to run them, and the `composer run check` command. Include the "Enforced by" table from the spec's "Enforcement: Tooling vs Convention" section. Note which rules are convention-only (code review).

2. **General PHP Conventions** — `declare(strict_types=1)` on all files (Pint-enforced), full type declarations, early returns over `else`, `match` over `switch`, string quoting rules, PHPDoc-only-when-needed rule, comments-explain-why rule.

3. **Array Formatting** — Every array item on its own line. Nested arrays expanded vertically. Trailing commas. Single-item inline allowed but multi-line preferred. Include the code example from the spec.

4. **Naming Conventions** — All six naming tables from the spec: Classes, Methods and Variables, Database, Routes, Config and Environment.

5. **Class Structure** — The 11-item ordering list from the spec (traits -> constants -> properties -> constructor -> ... -> private methods). Note: enforced by Pint's `ordered_class_elements` rule.

6. **Controllers** — Single-action only. Every controller uses `__invoke()`. Include the CRUD directory structure example and the route registration example from the spec. Note: controllers must be thin, delegate to actions/services.

7. **Models** — The 12-item property/method ordering list. PHP attributes (`#[Fillable]`, `#[Hidden]`, `#[ScopedBy]`) preferred where supported. Include relationship ordering (belongsTo -> hasOne -> hasMany -> belongsToMany -> morph*).

8. **Services and Actions** — Actions: single-purpose, `execute()` method, verb-phrase names. Services: compose actions into feature API, `{Feature}Service` naming. Simple cases use actions directly. No repository pattern. Include the `UserService` code example from the spec.

9. **Form Requests** — Array syntax for rules, always implement `authorize()`, naming convention `Store{Model}Request` / `Update{Model}Request`.

10. **Migrations** — Column ordering (9 items: primary key -> foreign keys -> ... -> softDeletes).

11. **Routes** — Resource-style naming even with single-action controllers, grouped routes with middleware, kebab-case URIs, dot-separated names.

12. **Blade and Views** — Kebab-case component files, `<x-component />` usage, layouts directory, components over `@include`, no PHP logic in templates.

13. **PHP 8.3+ Features** — Enums, readonly classes, constructor promotion, first-class callables, match expressions, named arguments.

The tone should be direct and prescriptive (like the testing strategy doc), with code examples for each major convention. Mark tool-enforced rules with the tool name in parentheses. Do NOT duplicate the full tool config files — reference them by filename.

- [ ] **Step 2: Review the document against the spec**

Read through `docs/style-guide.md` and cross-reference against every section of `docs/specs/2026-03-30-style-guide-and-tooling-design.md` under "Style Guide Rules". Confirm no rules are missing, no rules contradict, and all code examples match the conventions described.

- [ ] **Step 3: Commit**

```bash
git add docs/style-guide.md
git commit -m "Add style guide document"
```

---

### Task 7: Update CLAUDE.md

**Files:**
- Modify: `CLAUDE.md:58-62` (Code Style section)
- Modify: `CLAUDE.md:126-128` (Style Guide section)

- [ ] **Step 1: Add new commands to Code Style section**

In `CLAUDE.md`, replace the Code Style section (lines 58-62):

```markdown
### Code Style
```bash
composer run lint          # Fix code style (Laravel Pint)
composer run lint:check    # Check style without fixing
```
```

With:

```markdown
### Code Style
```bash
composer run lint          # Fix code style (Laravel Pint)
composer run lint:check    # Check style without fixing
composer run analysis      # Run static analysis (Larastan/PHPStan)
composer run check         # Run all checks: Pint + Rector + Larastan + tests + type coverage
```
```

- [ ] **Step 2: Replace Style Guide TODO section**

In `CLAUDE.md`, replace the Style Guide section (lines 126-128):

```markdown
## Style Guide

TODO
```

With:

```markdown
## Style Guide

See `docs/style-guide.md` for the complete style guide, including:
- Enforcement toolchain (Pint, Larastan, Rector, arch tests)
- Single-action controller convention
- Service and Action patterns
- Array formatting, naming conventions, class structure
- PHP 8.3+ feature usage
```

- [ ] **Step 3: Commit**

```bash
git add CLAUDE.md
git commit -m "Update CLAUDE.md with style guide reference and new commands"
```

---

### Task 8: Final Verification

**Files:** None (verification only)

- [ ] **Step 1: Run the full check suite**

Run: `composer run check`

Expected: All checks pass — Pint, Rector dry-run, Larastan, tests, type coverage.

- [ ] **Step 2: Run Pint check**

Run: `./vendor/bin/pint --test`

Expected: 0 style issues.

- [ ] **Step 3: Run Rector dry-run**

Run: `vendor/bin/rector process --dry-run`

Expected: No changes suggested.

- [ ] **Step 4: Run Larastan**

Run: `vendor/bin/phpstan analyse`

Expected: No errors.

- [ ] **Step 5: Run all tests**

Run: `php artisan test`

Expected: All tests pass.

- [ ] **Step 6: Run type coverage**

Run: `./vendor/bin/pest --type-coverage --min=100`

Expected: 100% type coverage.

- [ ] **Step 7: Review git log for clean commit history**

Run: `git log --oneline main..HEAD`

Expected: 8+ commits (1 spec commit + 7 task commits), clean and logical progression.

- [ ] **Step 8: Verify all deliverables exist**

Check these files exist and are non-empty:
- `pint.json`
- `phpstan.neon`
- `rector.php`
- `docs/style-guide.md`
- `.github/workflows/tests.yml` (updated)
- `composer.json` (updated with `analysis` and `check` scripts)
- `CLAUDE.md` (updated with style guide reference and new commands)
