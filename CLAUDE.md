# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Fresh Laravel 13 application (PHP 8.3+) with Tailwind CSS 4 and Vite. No custom domain logic yet — ready for feature development.

## IMPORTANT RULES

- Always write specifications to describe new features when developing new features before planning work
- Always refer to relevant documentation in `docs/` and `docs/specs/` to provide context for writing new specifications
- Always review the specification yourself, report the results, and don't move forward with implementation planning until the specification is approved
- Always plan out the implementation in a plan file before implementing
- Always make use of superpowers (especially brainstorming) when planning features
- Always build implementation plans using a TDD-approach and plan out the critical path for an agent swarm development process
- Always plan to perform several passes of reviews and self-correction at the end of implementation before considering the implmentation complete
- Always review the implementation plan yourself, report the results, and don't move forward with implementation until the plan is approved
- If an implementation step encounters problems due to system configuration that require the user's attention, always stop and ask for their intervention rather than working around the problem
- If there is any risk of context/conversation compacting when performing an operation, always warn the user first rather than executing the instructions without confirmation
- After resolving conflicts, always rerun all tests to confirm there are no regression failures
- When beginning work on a new feature, build it in a branch so a PR can be created from it and merged into main when it is approved
- NEVER make commits to or push to the `main` branch. Assume `main` is branch protected and that pushes to main will always fail.

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

Tests use **Pest v4** as the test framework. The default test suite runs against an **in-memory SQLite database** (configured in phpunit.xml). Tests tagged with `->group('mysql')` are excluded by default and require a running MySQL instance.

### Code Style
```bash
composer run lint          # Fix code style (Laravel Pint)
composer run lint:check    # Check style without fixing
composer run analysis      # Run static analysis (Larastan/PHPStan)
composer run check         # Run all checks: Pint + Rector + Larastan + tests + type coverage
```

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

- **Database**: MySQL 8.4 (`laravel_13_template`) in Docker, with Redis for cache, queue, and sessions
- **Infrastructure**: `docker-compose.yml` runs MySQL + Redis; PHP/artisan/Vite run natively on macOS
- **Frontend**: Tailwind CSS 4 via `@tailwindcss/vite` plugin, entry points in `resources/css/app.css` and `resources/js/app.js`
- **Routing**: `routes/web.php` (HTTP), `routes/console.php` (Artisan commands); health check at `/up`
- **Bootstrap**: `bootstrap/app.php` configures routing, middleware, and exceptions; `bootstrap/providers.php` registers service providers
- **User model** uses PHP 8 attributes (`#[Fillable]`, `#[Hidden]`) instead of property arrays

## Local Hosting for Development

- Local server runs at: http://localhost:8000
- Vite development server runs at: http://localhost:5173

## Documentation

### Chat Sessions

- Store records of chat sessions in `docs/sessions/` (but only when requested by the user)
- Use date-prefixed filenames: `YYYY-MM-DD-short-description.md`

### Specifications

- Store implementation plans (current and historical) in `docs/specs/`
- Use date-prefixed filenames: `YYYY-MM-DD-short-description.md`

### Implementation Plans

- Store implementation plans (current and historical) in `docs/plans/`
- Use date-prefixed filenames: `YYYY-MM-DD-short-description.md`

### Permanent Documentation

- Store user-targeted and technical documentation in `docs/`
- Use descriptive non-dated file names in kebab-case: `project-architecture.md`

### Notes

- Store documents that guide implementations or aid development but don't fit another category in `docs/notes/`
- This includes things like research notes, audit results, and other working documents that aren't chat sessions, permanent documentation, plans, or specs
- Use date-prefixed filenames: `YYYY-MM-DD-short-description.md`

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
- PHP 8.3+ feature usage