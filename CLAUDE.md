# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Arcane Advisor is a D&D 5e wizard spell recommendation engine. Users describe a situation in natural language; the system returns ranked spell picks with per-card explanations tuned by a five-position "Whimsy Dial." Stack: Laravel 13 API (PHP 8.4+), Vue 3 SPA (Pinia + Vue Router) via Vite, Tailwind CSS 4, MySQL 8.4, Redis 7. LLM: Cloudflare Workers AI — `@cf/google/gemma-4-26b-a4b-it` for completions, `@cf/baai/bge-base-en-v1.5` for embeddings. See `docs/plans/implementation-plan.md` for the full build order (Stages A–I).

## IMPORTANT RULES

- Always write specifications to describe new features when developing new features before planning work
- Always refer to relevant documentation in `docs/` and `docs/specs/` to provide context for writing new specifications
- Always review the specification yourself, report the results, and don't move forward with implementation planning until the specification is approved
- Always plan out the implementation in a plan file before implementing
- When writing implementation plans, organize them into self-consistent and complete shippable units that are small enough for a Claude context window to execute on
- Always make use of superpowers (especially brainstorming) when planning features
- Always build implementation plans using a TDD-approach and plan out the critical path for an agent swarm development process
- Always plan to perform several passes of reviews and self-correction at the end of implementation before considering the implmentation complete
- Always review the implementation plan yourself, report the results, and don't move forward with implementation until the plan is approved
- If an implementation step encounters problems due to system configuration that require the user's attention, always stop and ask for their intervention rather than working around the problem
- If there is any risk of context/conversation compacting when performing an operation, always warn the user first rather than executing the instructions without confirmation
- After resolving conflicts, always rerun all tests to confirm there are no regression failures
- When beginning work on a new feature, build it in a branch so a PR can be created from it and merged into main when it is approved
- NEVER make commits to or push to the `main` branch. Assume `main` is branch protected and that pushes to main will always fail.

## Conventions

See `docs/conventions.md` for project work and process conventions, naming rules, and legacy issues to keep in mind when producing work.

## Common Commands

When making tool calls to execute commands defined in package.json, prefer using them as written in this section.
ALWAYS prefer to use commands in this format unless you have a good reason not to, and if so you should confirm with me.

### Development
```bash
composer run dev          # Runs server, queue worker, log monitor (pail), and Vite concurrently
php artisan serve         # Run just the web server
npm run dev               # Run just the Vite dev server
```

### Docker (MySQL + Redis)
```bash
composer run docker:start  # Start MySQL and Redis containers (waits for healthy)
composer run docker:stop   # Stop containers (data persists in named volumes)
docker compose down -v     # Stop containers AND delete all data
```

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

Tests use **Pest v4** as the test framework. The default test suite runs against an **in-memory SQLite database** (configured in phpunit.xml). Tests tagged with `->group('mysql')`, `->group('redis')`, or `->group('build')` are excluded by default and require a running service / built frontend.

### Code Style and Gates
```bash
composer run lint          # Fix code style (Laravel Pint)
composer run lint:check    # Check style without fixing
composer run analysis      # Run static analysis (Larastan/PHPStan)
composer run check         # Local fast gate: Pint + Larastan + Pest + Vitest
composer run check:ci      # Full CI gate: adds Rector, npm run build, build-group tests, pest --type-coverage
```

The split exists so `composer run check` can stay fast enough for pre-commit iteration. `composer run check:ci` is what `.github/workflows/check.yml` runs on every push/PR.

### Database
```bash
php artisan migrate               # Run migrations
php artisan migrate:fresh --seed  # Reset DB and seed
php artisan db:seed               # Run seeders
```

### Build
```bash
npm run build             # Production build (Vite)
```

## Architecture

- **Database**: MySQL 8.4 (`arcane_advisor`) in Docker, with Redis for cache, queue, and sessions
- **Infrastructure**: `docker-compose.yml` runs MySQL + Redis; PHP/artisan/Vite run natively on macOS
- **Frontend**: Tailwind CSS 4 via `@tailwindcss/vite` plugin, entry points in `resources/css/app.css` and `resources/js/app.js`
- **Routing**: `routes/web.php` (HTTP), `routes/console.php` (Artisan commands); health check at `/up`
- **Bootstrap**: `bootstrap/app.php` configures routing, middleware, and exceptions; `bootstrap/providers.php` registers service providers
- **User model** uses PHP 8 attributes (`#[Fillable]`, `#[Hidden]`) instead of property arrays

## Local Hosting for Development

- Local server runs at: http://localhost:8000
- Vite development server runs at: http://localhost:5173

## Documentation

### Point-in-Time Artifacts

Date-prefixed filenames (`YYYY-MM-DD-##-description.md`). These accumulate; old ones are rarely edited.

- **Chat Sessions** (`docs/sessions/`) — records of chat sessions (only when requested). Format: `YYYY-MM-DD-short-description.md`.
- **Mocks** (`docs/mocks/`) — design mockups and related artifacts.
- **Notes** (`docs/notes/`) — research notes, audit results, working documents that don't fit another category.

### Living References

Descriptive non-dated kebab-case filenames (e.g. `api-consult.md`, `implementation-plan.md`). These are the current truth, edited in place, with stable linkable names.

- **Specs** (`docs/specs/`) — feature specifications. Use numeric prefixes for build ordering (`00-index.md`, `01-prompt-box-and-landing.md`).
- **Plans** (`docs/plans/`) — implementation plans.
- **Schemas** (`docs/schemas/`) — data schemas and API contracts.
- **Guides** (`docs/`) — permanent documentation (style guide, testing strategy). Descriptive kebab-case names.

## Key References

- `docs/specs/00-index.md` — canonical spec map and dependency graph for all 8 feature specs
- `docs/plans/implementation-plan.md` — full build order (Stages A–I), per-PR test lists, locked conventions
- `docs/schemas/` — API envelope (`api-consult.md`), spell YAML structure (`spell-yaml.md`), enum vocabularies (`enums.md`)

## Current Status

Track and update progress in `docs/notes/checklist.md`. Keep it current as PRs are completed.

## Testing Strategy

See `docs/testing-strategy.md` for the complete testing strategy, including:
- What to test and what not to test
- Pest conventions and test organization
- MySQL vs SQLite database compatibility guide
- Mocking strategy and coverage targets

## Style Guide

See `docs/style-guide.md` for the complete style guide, including:
- Enforcement toolchain (Pint, Larastan, Rector, arch tests)
- Single-action controller convention
- Service and Action patterns
- Array formatting, naming conventions, class structure
- PHP 8.4+ feature usage
