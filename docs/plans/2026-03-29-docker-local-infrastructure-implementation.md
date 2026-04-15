# Docker Local Infrastructure — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `docker-compose.yml` with MySQL 8.4 and Redis 7 for local development, with Composer scripts to start/stop services and updated `.env` defaults to use Redis for cache/queue/session.

**Architecture:** Infrastructure-only Docker — PHP, artisan, and Vite stay native on macOS. A `docker-compose.yml` at the project root defines two services (MySQL, Redis) with named volumes for persistence. Composer scripts wrap `docker compose` commands and include a MySQL readiness wait so migrations can run immediately.

**Tech Stack:** Docker Compose, MySQL 8.4, Redis 7 (Alpine)

---

### File Map

| File | Action | Responsibility |
|---|---|---|
| `docker-compose.yml` | Create | Define MySQL + Redis services, volumes, health checks |
| `.env.example` | Modify | Update DB_PASSWORD, CACHE_STORE, QUEUE_CONNECTION, SESSION_DRIVER |
| `.env` | Modify | Match `.env.example` changes |
| `composer.json` | Modify | Add `docker:start`, `docker:stop`; update `dev` and `setup` scripts |
| `CLAUDE.md` | Modify | Add Docker commands section |

---

### Task 1: Create `docker-compose.yml`

**Files:**
- Create: `docker-compose.yml`

- [ ] **Step 1: Create the compose file**

```yaml
services:
  mysql:
    image: mysql:8.4
    ports:
      - "127.0.0.1:3306:3306"
    environment:
      MYSQL_DATABASE: laravel_13_template
      MYSQL_ROOT_PASSWORD: password
      MYSQL_ALLOW_EMPTY_PASSWORD: "no"
    volumes:
      - mysql-data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      timeout: 5s
      retries: 10

  redis:
    image: redis:7-alpine
    ports:
      - "127.0.0.1:6379:6379"
    volumes:
      - redis-data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 5s
      timeout: 5s
      retries: 10

volumes:
  mysql-data:
  redis-data:
```

- [ ] **Step 2: Verify compose file is valid**

Run: `docker compose config --quiet`
Expected: No output (exit code 0, meaning the file is valid)

- [ ] **Step 3: Commit**

```bash
git add docker-compose.yml
git commit -m "Add docker-compose.yml with MySQL 8.4 and Redis 7"
```

---

### Task 2: Update `.env.example` and `.env`

**Files:**
- Modify: `.env.example`
- Modify: `.env`

- [ ] **Step 1: Update `.env.example`**

Change these four lines:

| Line | From | To |
|---|---|---|
| `DB_PASSWORD=` | `DB_PASSWORD=` | `DB_PASSWORD=password` |
| `SESSION_DRIVER=database` | `SESSION_DRIVER=database` | `SESSION_DRIVER=redis` |
| `QUEUE_CONNECTION=database` | `QUEUE_CONNECTION=database` | `QUEUE_CONNECTION=redis` |
| `CACHE_STORE=database` | `CACHE_STORE=database` | `CACHE_STORE=redis` |

- [ ] **Step 2: Update `.env` with the same changes**

Apply the same four changes to `.env`.

- [ ] **Step 3: Commit**

```bash
git add .env.example
git commit -m "Update .env.example: set DB password and switch cache/queue/session to Redis"
```

Note: `.env` is gitignored so only `.env.example` is committed.

---

### Task 3: Add `docker:start` and `docker:stop` Composer scripts

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Add `docker:start` script**

Add to the `"scripts"` section of `composer.json`:

```json
"docker:start": [
    "Composer\\Config::disableProcessTimeout",
    "docker compose up -d --wait"
]
```

The `--wait` flag blocks until all services with health checks are healthy (MySQL and Redis both have health checks defined in the compose file). This replaces the need for a manual poll loop — Docker Compose handles the wait natively.

- [ ] **Step 2: Add `docker:stop` script**

Add to the `"scripts"` section of `composer.json`:

```json
"docker:stop": [
    "docker compose down"
]
```

- [ ] **Step 3: Verify both scripts work**

Run: `composer run docker:start`
Expected: Docker pulls images (first run), starts containers, waits for health checks, then returns.

Run: `docker compose ps`
Expected: Both `mysql` and `redis` services show as "healthy".

Run: `composer run docker:stop`
Expected: Containers stop. `docker compose ps` shows no running containers.

- [ ] **Step 4: Commit**

```bash
git add composer.json
git commit -m "Add docker:start and docker:stop Composer scripts"
```

---

### Task 4: Update `dev` and `setup` Composer scripts

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Update the `dev` script**

Change the `"dev"` script from:

```json
"dev": [
    "Composer\\Config::disableProcessTimeout",
    "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite --kill-others"
]
```

To:

```json
"dev": [
    "Composer\\Config::disableProcessTimeout",
    "@composer docker:start",
    "npx concurrently -c \"#93c5fd,#c4b5fd,#fb7185,#fdba74\" \"php artisan serve\" \"php artisan queue:listen --tries=1 --timeout=0\" \"php artisan pail --timeout=0\" \"npm run dev\" --names=server,queue,logs,vite --kill-others"
]
```

- [ ] **Step 2: Update the `setup` script**

Change the `"setup"` script from:

```json
"setup": [
    "composer install",
    "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
    "@php artisan key:generate",
    "@php artisan migrate --force",
    "npm install",
    "npm run build"
]
```

To:

```json
"setup": [
    "composer install",
    "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
    "@php artisan key:generate",
    "@composer docker:start",
    "@php artisan migrate --force",
    "npm install",
    "npm run build"
]
```

The `@composer docker:start` call (which uses `--wait`) is placed before `migrate --force` so MySQL is healthy before migrations run.

- [ ] **Step 3: Verify `dev` boots everything**

Run: `composer run docker:stop` (clean slate)
Run: `composer run dev`
Expected: Docker containers start first, then server/queue/logs/vite launch concurrently. Ctrl+C to stop the dev processes (Docker containers remain running in background).

- [ ] **Step 4: Commit**

```bash
git add composer.json
git commit -m "Update dev and setup scripts to start Docker services first"
```

---

### Task 5: Update `CLAUDE.md`

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Add Docker section and update Architecture**

Add a Docker section under Common Commands:

```markdown
### Docker (MySQL + Redis)
```bash
composer run docker:start  # Start MySQL and Redis containers (waits for healthy)
composer run docker:stop   # Stop containers (data persists in named volumes)
docker compose down -v     # Stop containers AND delete all data
```
```

Update the Architecture section's Database line from:

```
- **Database**: MySQL (`laravel_13_template`), with database-driven sessions, cache, and queue
```

To:

```
- **Database**: MySQL 8.4 (`laravel_13_template`) in Docker, with Redis for cache, queue, and sessions
- **Infrastructure**: `docker-compose.yml` runs MySQL + Redis; PHP/artisan/Vite run natively on macOS
```

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "Update CLAUDE.md with Docker commands and architecture"
```
