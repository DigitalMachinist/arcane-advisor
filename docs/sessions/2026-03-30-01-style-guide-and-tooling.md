# Style Guide & Tooling Session

**Date:** 2026-03-30
**Branch:** `feature/style-guide`
**PR:** https://github.com/DigitalMachinist/laravel-13-template/pull/2

---

## What We Did

### 1. Brainstormed Style Guide Improvements

Starting from the first-draft style guide (produced in the testing strategy session), incorporated user feedback:

- **Single-action controllers only** — no resource controllers, every controller uses `__invoke()`
- **Array formatting** — every array item on its own line, nested arrays expanded vertically (convention, not tool-enforced)
- **Services as feature API facades** — services compose multiple actions into a documented feature surface; simple cases use actions directly
- **Composer commands** — `composer run analysis` (PHPStan) and `composer run check` (all checks)
- **CI pipeline** — add Rector, Larastan, and mutation testing to GitHub Actions
- **Dangling semicolons** — removed `multiline_whitespace_before_semicolons` rule after user feedback that semicolons on their own line hurt readability

### 2. Designed the Spec

Wrote `docs/notes/2026-03-30-01-style-guide-and-tooling-spec.md` covering:
- All style guide rules with changes from the draft
- Enforcement matrix (tool-enforced vs convention)
- Tooling configuration (Pint, Larastan, Rector)
- Composer commands
- CI pipeline (trigger matrix, job structure)

### 3. Wrote the Implementation Plan

Created `docs/notes/2026-03-30-02-style-guide-and-tooling-plan.md` with 8 tasks:
1. Configure Pint strict rules
2. Install and configure Larastan
3. Install and configure Rector
4. Add composer commands
5. Update CI pipeline
6. Write style guide document
7. Update CLAUDE.md
8. Final verification

### 4. Implemented via Subagent-Driven Development

Dispatched fresh subagents per task with two-stage review (spec compliance + code quality) for the complex tasks.

| Task | What | Commits |
|---|---|---|
| 1 | Created `pint.json` with strict rules, auto-fixed 29 PHP files | `b8b1537` |
| 2 | Installed Larastan v3.9.3 (PHPStan 2.x), created `phpstan.neon` at level 6 | `011ec2e` |
| 3 | Installed Rector v2.3.9 + rector-laravel v2.2.0, created `rector.php`, applied changes to 7 files | `149b804` |
| 4 | Added `analysis` and `check` scripts to `composer.json` | `41fed57` |
| 5 | Updated `.github/workflows/tests.yml` with Rector, Larastan, mutation testing | `fc26761` |
| 6 | Wrote `docs/style-guide.md` (13 sections, 2,752 words) | `ccf9d28` |
| 7 | Updated `CLAUDE.md` with style guide reference and new commands | `2a6b403` |
| 8 | Final verification — all checks pass | (verification only) |

### 5. Post-Implementation Fixes

- **Mutation tests CI failure** — `--min=70` fails when no business logic classes exist yet (0 mutations = 0% score). Fixed by treating "No mutations created" as a pass.
- **Dangling semicolons** — User disliked `multiline_whitespace_before_semicolons` moving semicolons to their own line. Removed the rule from `pint.json`.
- **`->not` chain formatting** — Split `->not` onto its own line in arch tests for readability.
- **Multi-item array expansion** — Expanded inline multi-item arrays in arch tests to one item per line.

---

## Key Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Controller style | Single-action only (`__invoke()`) | Keeps each controller laser-focused; acceptable trade-off of more files |
| Services pattern | Compose actions into feature API | Services are the "table of contents" for a feature's capabilities |
| Array formatting enforcement | Convention (code review) | No standard PHP tooling enforces one-item-per-line; automated options too complex for the benefit |
| Dangling semicolons | Removed rule | `new_line_for_chained_calls` strategy hurts readability |
| Rector in CI | Enforced from day one (`--dry-run`) | Fresh codebase has no legacy backlog; prevents drift |
| Mutation testing in CI | PR-only, scoped to business logic | Fast when project is small; easier to enforce from day one than retrofit |
| Mutation tests empty case | Treat "No mutations created" as pass | Avoids false failure when no business logic classes exist yet |
| PHPStan 2.x parameters | Removed deprecated params | `checkMissingIterableValueType` and `checkGenericClassInNonGenericObjectType` don't exist in PHPStan 2.x |
| Larastan level | Level 6 | Enforces type declarations exist; appropriate for fresh project |
| Rector Laravel sets | 5 explicit sets | CODE_QUALITY, COLLECTION, IF_HELPERS, FACADE_ALIASES_TO_FULL_NAMES, TYPE_DECLARATIONS |

---

## Artifacts Produced

| File | Type |
|---|---|
| `docs/style-guide.md` | Permanent documentation (13-section style reference) |
| `docs/notes/2026-03-30-01-style-guide-and-tooling-spec.md` | Design spec |
| `docs/notes/2026-03-30-02-style-guide-and-tooling-plan.md` | Implementation plan |
| `pint.json` | Pint strict rules config |
| `phpstan.neon` | Larastan level 6 config |
| `rector.php` | Rector code quality + Laravel rules config |
| `composer.json` | Updated with `analysis` and `check` scripts |
| `.github/workflows/tests.yml` | Updated CI with Rector, Larastan, mutation testing |
| `CLAUDE.md` | Updated with style guide reference and new commands |

---

## Tools Installed

| Tool | Version | Purpose |
|---|---|---|
| Larastan | v3.9.3 (PHPStan 2.1.44) | Static analysis with Laravel support |
| Rector | v2.3.9 | Automated code quality transformations |
| rector-laravel | v2.2.0 | Laravel-specific Rector rules |

---

## CI Pipeline (Final)

| Job | Trigger | Steps |
|---|---|---|
| Quality Checks | push to main + PRs | Pint check, Rector dry-run, Larastan, tests, type coverage |
| MySQL Tests | PRs only | `php artisan test --group=mysql` against MySQL 8.4 |
| Mutation Tests | PRs only | `--mutate --parallel --covered-only --min=70` scoped to business logic |

---

## Composer Commands (Final)

| Command | Purpose |
|---|---|
| `composer run lint` | Fix code style (Pint) |
| `composer run lint:check` | Check style without fixing |
| `composer run analysis` | Run static analysis (Larastan/PHPStan) |
| `composer run test` | Clear config + run tests |
| `composer run check` | All checks: Pint + Rector + Larastan + tests + type coverage |
