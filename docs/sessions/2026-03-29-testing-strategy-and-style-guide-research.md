# Testing Strategy & Style Guide Research Session

**Date:** 2026-03-29
**Branch:** `feature/testing-strategy-improvements`
**PR:** https://github.com/DigitalMachinist/laravel-13-template/pull/1

---

## What We Did

### 1. Deep Research Phase

Dispatched 5 parallel research agents to investigate Laravel testing best practices and code style guidelines (2025-2026 community consensus from Spatie, laravel.io, Laracasts):

1. **testing-best-practices** — Laravel testing philosophy, what to test, community consensus
2. **pest-testing-tooling** — Pest PHP v4, architectural testing, mutation testing, CI integration
3. **code-style-conventions** — Spatie's style guide, PSR/PER standards, Laravel naming conventions, PHP 8.3+ patterns
4. **style-enforcement-tooling** — Pint configuration, PHPStan/Larastan, Rector, CI pipeline setup
5. **testing-antipatterns** — What NOT to test, over-mocking, testing boundaries, database testing strategies

Produced three raw research notes in `docs/notes/`:
- `2026-03-29-laravel-testing-anti-patterns-research.md`
- `2026-03-29-laravel-php-style-conventions-research.md`
- `2026-03-29-php-laravel-style-enforcement-static-analysis-research.md`

### 2. Two Deliverable Documents

Synthesized research into:
- **`docs/testing-strategy.md`** — What to test, what not to test, mocking strategy, coverage targets, anti-patterns, Pest conventions
- **`docs/style-guide.md`** — Naming conventions, class structure, Pint/Larastan/Rector tooling, PHP 8.3+ patterns, code organization

### 3. Brainstormed Testing Strategy Improvements

Focused on two concerns:
- **Switching to Pest** as the sole developer-facing test framework (PHPUnit hidden as internal engine)
- **MySQL/SQLite compatibility** — chose a hybrid approach (option C from brainstorming):
  - Reference documentation (MySQL vs SQLite feature tables)
  - Pest arch tests for static detection of MySQL-only patterns
  - Dual-database CI pipeline (SQLite on every push, MySQL on PRs)

Decided on categorization approach (option C): tests needing MySQL get tagged with `->group('mysql')`, excluded from the default SQLite suite, and run in CI against real MySQL.

Design spec written to `docs/specs/2026-03-29-testing-strategy-improvements-design.md`.

### 4. Implementation (8 Tasks, Subagent-Driven)

Built on branch `feature/testing-strategy-improvements` using the subagent-driven-development skill (10 commits):

| Task | What | Commits |
|---|---|---|
| 1 | Installed Pest v4 + plugins | `b131185` |
| 2 | Converted example tests to Pest closure syntax, cleaned up Pest.php | `2d4b57b` |
| 3 | Configured `phpunit.xml` to exclude `mysql` group by default | `8ee07e9` |
| 4 | Created `tests/Arch/ArchitectureTest.php` (7 structural rules) | `b5f2dae` |
| 5 | Created `tests/Arch/DatabaseCompatibilityTest.php` (3 MySQL-only pattern detectors) | `2cc0a2e` |
| 6 | Created `.github/workflows/tests.yml` (dual-database CI) | `aef3442` |
| 7 | Rewrote `docs/testing-strategy.md` per spec | `af5d0f6` |
| 8 | Updated `CLAUDE.md` with Pest commands and testing strategy reference | `b924de6` |

Additional commits:
- `7e67752` — Added `composer run lint` / `lint:check` scripts, fixed Pint style issues
- `91c775a` — Used context7 to resolve Pest version issues

**Notable discovery:** Plan specified Pest v3, but PHPUnit 12 (required by Laravel 13) needs Pest v4. Pest v4 was installed correctly; documentation updated to reference Pest without a version number.

### 5. Other Changes

- Set up Context7 MCP server (`.mcp.json`)
- Created PR: https://github.com/DigitalMachinist/laravel-13-template/pull/1

---

## Key Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Test framework | Pest v4 (sole interface, PHPUnit hidden) | Laravel community standard, cleaner syntax, arch/mutation testing built in |
| MySQL/SQLite strategy | Hybrid (docs + arch tests + dual CI) | Fast local feedback from arch tests, CI catches runtime differences |
| MySQL test handling | `->group('mysql')` categorization | Clean separation, SQLite suite stays fast, MySQL tests explicit |
| Arch test presets | `php()` + `security()` only (no `laravel()` or `strict types` yet) | `laravel()` preset not validated, strict types needs Pint config first |
| Style enforcement tools | Pint + Larastan + Rector (recommended, not yet installed) | Complementary tools with no overlap |

---

## Artifacts Produced

| File | Type |
|---|---|
| `docs/testing-strategy.md` | Permanent documentation |
| `docs/style-guide.md` | Permanent documentation (first draft) |
| `docs/specs/2026-03-29-testing-strategy-improvements-design.md` | Design spec |
| `docs/plans/2026-03-29-testing-strategy-improvements.md` | Implementation plan |
| `docs/notes/2026-03-29-laravel-testing-anti-patterns-research.md` | Research notes |
| `docs/notes/2026-03-29-laravel-php-style-conventions-research.md` | Research notes |
| `docs/notes/2026-03-29-php-laravel-style-enforcement-static-analysis-research.md` | Research notes |
| `tests/Arch/ArchitectureTest.php` | 7 arch tests |
| `tests/Arch/DatabaseCompatibilityTest.php` | 3 compatibility tests |
| `.github/workflows/tests.yml` | CI workflow |
| `.mcp.json` | Context7 MCP server config |

---

## Open Items

- **Style guide** (`docs/style-guide.md`) is a first draft from research — needs brainstorming/refinement before being considered final
- **Style guide tooling** (Larastan, Rector, `pint.json` with strict rules) is documented but not yet installed
- **CLAUDE.md Style Guide section** still says TODO — pending style guide approval
- **`arch()->preset()->laravel()`** and **strict types arch rule** deferred until style guide tooling is in place
