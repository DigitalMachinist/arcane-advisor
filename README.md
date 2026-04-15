# Laravel 13 Template

A modern Laravel 13 starter template configured for AI-assisted development with Claude Code. This template includes a complete testing setup (Pest), static analysis (Larastan), code formatting (Pint), automated refactoring (Rector), and Docker infrastructure for MySQL and Redis.

## Philosophy

This Laravel 13 template is my default starting point for working on a brand new Laravel project using Claude Code. My goal with this configuration is to:

- **Automate alignment guidance** — Run tests, linters, and style guides to help the LLM guide itself toward alignment
- **Self-document through tooling** — Enforce structured specifications and implementation plans to enrich context
- **Enforce a disciplined workflow** — Follow SPEC → PLAN → TESTS → CODE with checkpoints to empower human decision-making at critical times

The result is a codebase that evolves with high confidence, clear intent, and built-in guardrails.

## Quick Start

### Prerequisites

- PHP 8.3+
- Node.js 18+ (for npm/Vite)
- Docker & Docker Compose (for MySQL 8.4 and Redis 7)
- Composer

### Clone & Install

```bash
git clone https://github.com/DigitalMachinist/laravel-13-template.git
cd laravel-13-template

# Run the setup script (installs dependencies, generates key, starts Docker, runs migrations)
composer run setup
```

The setup script will:
1. Install PHP dependencies via Composer
2. Generate an application key
3. Start MySQL and Redis containers (with health checks)
4. Run database migrations
5. Install Node dependencies
6. Build frontend assets

### Local Development

Start the development server, queue worker, logs, and Vite in one command:

```bash
composer run dev
```

This runs concurrently:
- PHP development server at `http://localhost:8000`
- Queue worker (for job processing)
- Log monitor (Pail)
- Vite dev server at `http://localhost:5173`

### Docker Services

Start/stop MySQL and Redis containers:

```bash
# Start containers (with MySQL readiness check)
composer run docker:start

# Stop containers (preserves data in named volumes)
composer run docker:stop

# Reset containers and delete all data
docker compose down -v
```

## Testing

This project uses **Pest v4** with architectural and mutation testing. Tests run against an **in-memory SQLite database** by default; tests requiring a real MySQL instance are tagged with `->group('mysql')`.

### Run Tests

```bash
# Run all tests (SQLite, fast)
php artisan test

# Run a specific test file
php artisan test --filter=CreatePostTest

# Run only feature tests
php artisan test tests/Feature/

# Run only unit tests
php artisan test tests/Unit/

# Run only architectural tests
php artisan test tests/Arch/

# Run MySQL-dependent tests (requires Docker MySQL running)
php artisan test --group=mysql

# Check type coverage (requires 100%)
./vendor/bin/pest --type-coverage --min=100

# Run mutation testing (scoped to business logic)
./vendor/bin/pest --mutate

# Find slow tests
./vendor/bin/pest --profile
```

### What Gets Tested

- **Feature tests** — User-facing workflows, HTTP requests, integrations
- **Unit tests** — Isolated business logic, utility functions
- **Arch tests** — Structural rules (no circular dependencies, proper namespacing, etc.)
- **Database compatibility** — MySQL-specific patterns are detected before CI

See `docs/testing-strategy.md` for the complete testing philosophy and coverage targets.

## Code Quality & Style

All code style and quality checks are automated. Run them locally before pushing:

```bash
# Fix code style violations
composer run lint

# Check style without fixing
composer run lint:check

# Run static analysis (PHPStan/Larastan at level 6)
composer run analysis

# Run ALL checks: Pint + Rector + Larastan + tests + type coverage
composer run check
```

### Style Guide

See `docs/style-guide.md` for detailed rules on:
- Single-action controllers (`__invoke()` only)
- Array formatting conventions
- Service and Action patterns
- PHP 8.3+ feature usage
- Naming conventions and class structure

### Tools

| Tool | Purpose | Enforced |
|------|---------|----------|
| **Pest v4** | Testing framework with architectural & mutation testing | ✓ Tests must pass |
| **Pint** | Code style / formatting | ✓ Automated, `--test` in CI |
| **Larastan** | Static analysis (PHPStan + Laravel) at level 6 | ✓ No errors allowed |
| **Rector** | Automated code quality refactoring | ✓ `--dry-run` in CI |

## Architecture

- **Database**: MySQL 8.4 in Docker, with Redis for cache, queue, and sessions
- **Frontend**: Tailwind CSS 4 via `@tailwindcss/vite`, with Vite build tooling
- **Infrastructure**: `docker-compose.yml` runs MySQL + Redis; PHP/artisan/Vite run natively
- **Routing**: `routes/web.php` (HTTP), `routes/console.php` (Artisan commands)
- **Bootstrap**: `bootstrap/app.php` configures routing, middleware, exceptions

See `docs/project-architecture.md` for detailed architecture documentation.

## Workflow

This template encourages a structured development workflow:

1. **SPEC** — Write a specification describing the feature (`docs/specs/`)
2. **PLAN** — Create an implementation plan with concrete steps (`docs/plans/`)
3. **TESTS** — Write tests first, then implementation (TDD approach)
4. **CODE** — Implement code to make tests pass
5. **REVIEW** — Verify implementation against spec and plan

See `CLAUDE.md` for detailed guidance when working with Claude Code on this project.

## Documentation

- `docs/testing-strategy.md` — What to test, what not to test, mocking strategy
- `docs/style-guide.md` — Code style rules, conventions, enforcement toolchain
- `docs/project-architecture.md` — System architecture and design decisions
- `docs/specs/` — Feature specifications (date-prefixed)
- `docs/plans/` — Implementation plans (date-prefixed)
- `docs/sessions/` — Chat session records (date-prefixed)
- `docs/notes/` — Research notes and working documents

## Common Tasks

### Add a New Feature

```bash
# 1. Write a specification (docs/specs/YYYY-MM-DD-feature-name.md)
# 2. Create an implementation plan (docs/plans/YYYY-MM-DD-feature-name.md)
# 3. Write tests first
# 4. Implement code to pass tests
# 5. Run composer run check to verify everything
php artisan test
composer run check
```

### Reset Database

```bash
# Fresh migration with seeders
php artisan migrate:fresh --seed
```

### Production Build

```bash
# Build frontend assets for production
npm run build
```

## License

This template is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Learning Resources

- [Laravel 13 Documentation](https://laravel.com/docs)
- [Pest Testing](https://pestphp.com)
- [Larastan / PHPStan](https://larastan.com)
- [Laravel Pint](https://laravel.com/docs/pint)
- [Rector](https://getrector.org)
