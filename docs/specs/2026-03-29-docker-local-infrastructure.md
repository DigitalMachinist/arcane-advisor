# Docker Local Infrastructure

Date: 2026-03-29

## Goal

Run MySQL and Redis in Docker containers for local development. PHP, artisan, and Vite remain native on macOS.

## docker-compose.yml

Two services at the project root, both bound to `127.0.0.1`:

### MySQL 8.4

- Image: `mysql:8.4`
- Port: `127.0.0.1:3306:3306`
- Environment:
  - `MYSQL_DATABASE=laravel_13_template`
  - `MYSQL_ROOT_PASSWORD=password`
  - `MYSQL_ALLOW_EMPTY_PASSWORD=no`
- Volume: `mysql-data:/var/lib/mysql` (named, persists across restarts)
- Health check: `mysqladmin ping -h localhost`

### Redis 7

- Image: `redis:7-alpine`
- Port: `127.0.0.1:6379:6379`
- Volume: `redis-data:/data` (named, persists across restarts)
- Health check: `redis-cli ping`

## .env.example Changes

- `DB_PASSWORD=password` (match MYSQL_ROOT_PASSWORD)
- `CACHE_STORE=redis` (was `database`)
- `QUEUE_CONNECTION=redis` (was `database`)
- `SESSION_DRIVER=redis` (was `database`)

## Composer Script Changes

### New: `docker:start`

Runs `docker compose up -d`, then waits for MySQL health check to pass (poll loop, max ~30 seconds). Ensures migrations can run immediately after.

### New: `docker:stop`

Runs `docker compose down`. Preserves named volumes so data survives restarts.

### Updated: `dev`

Calls `docker:start` first, then runs the existing concurrent command (server, queue, logs, vite).

### Updated: `setup`

Calls `docker:start` (with MySQL readiness wait) before `php artisan migrate --force`.

## What Does Not Change

- `phpunit.xml` — tests use in-memory SQLite, independent of Docker
- PHP/artisan/Vite run natively on macOS
- `.env` remains gitignored; developers can override ports/passwords locally

## Implementation Steps

1. Create `docker-compose.yml` at project root
2. Update `.env.example` with new defaults
3. Update `.env` to match (if present)
4. Add `docker:start` and `docker:stop` scripts to `composer.json`
5. Update `dev` script to call `docker:start` first
6. Update `setup` script to call `docker:start` before migrations
7. Update `CLAUDE.md` with new Docker commands
