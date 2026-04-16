# Session: Docker Local Infrastructure

**Date:** 2026-03-29

## Goal

Add Docker containers for MySQL and Redis to support local Laravel development, with PHP/artisan/Vite running natively on macOS.

## Decision Log

- **Approach chosen:** Plain `docker-compose.yml` (Option A) over Laravel Sail (too heavy for infrastructure-only use) and standalone `docker run` commands (too manual).
- **Services:** MySQL 8.4, Redis 7 (Alpine), both bound to `127.0.0.1` only.
- **Composer script naming:** `docker:start` / `docker:stop` (user requested `docker:start` over initially proposed `docker:up`).
- **Redis adoption:** Switched cache, queue, and session drivers from `database` to `redis` since Redis is now available.
- **`--wait` flag:** Used `docker compose up -d --wait` instead of a manual poll loop — Docker Compose handles health check waiting natively.

## What Was Built

| Commit | Description |
|---|---|
| `7985e94` | Created `docker-compose.yml` with MySQL 8.4 + Redis 7, health checks, named volumes |
| `c1ce238` | Updated `.env.example` — DB password, cache/queue/session switched to Redis |
| `664319c` | Added `docker:start` and `docker:stop` Composer scripts |
| `b0deebd` | Updated `dev` and `setup` scripts to call `docker:start` first |
| `bf03fea` | Updated `CLAUDE.md` with Docker commands and architecture |

## Files Changed

- `docker-compose.yml` — Created (MySQL + Redis services, named volumes, health checks)
- `.env.example` — DB_PASSWORD=password, CACHE_STORE/QUEUE_CONNECTION/SESSION_DRIVER=redis
- `.env` — Same changes (gitignored)
- `composer.json` — Added `docker:start`/`docker:stop`, updated `dev`/`setup` to start Docker first
- `CLAUDE.md` — Added Docker commands section, updated architecture description

## Artifacts

- **Spec:** `docs/notes/2026-03-29-02-docker-local-infrastructure-spec.md`
- **Implementation plan:** `docs/notes/2026-03-29-03-docker-local-infrastructure-plan.md`

## Post-Session Note

Running `composer run dev` requires `npm install` first (node_modules must exist for Vite). The `composer run setup` script handles this automatically since it includes `npm install` before `npm run build`.
