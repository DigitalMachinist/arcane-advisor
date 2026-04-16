# Arcane Advisor

A spell recommendation engine for D&D 5e wizards. Describe your predicament in plain language — a collapsing mine, a hostile dinner party, a dragon that just noticed you — and receive a curated shortlist of wizard spells with written explanations for why each one fits.

## How It Works

The wizard's study has one door: a prompt box. Type what's happening at the table, and the system parses your situation into tactical goals, stylistic cues, and hard constraints, then scores every wizard spell in the corpus on both mechanical fit and personality fit. A **Whimsy Dial** with five positions (Tactical through Chaotic) shifts the blend between practical picks and delightfully unexpected ones. Results arrive as spell cards — parchment-textured, expandable, each carrying a short "why this spell" explanation written in the voice of a knowledgeable friend.

Follow-up queries refine, amend, or riff on prior results in a short conversation (up to five rounds). A sourcebook selector lets you limit recommendations to the books your table actually uses.

## Stack

| Layer | Technology |
|-------|------------|
| API | Laravel 13 (PHP 8.3+) |
| Frontend | Vue 3 SPA, Pinia, Vue Router, Tailwind CSS 4, Vite |
| Database | MySQL 8.4, Redis 7 (Docker) |
| LLM | Cloudflare Workers AI — `@cf/google/gemma-4-26b-a4b-it` (completions), `@cf/baai/bge-base-en-v1.5` (embeddings) |

## Prerequisites

- PHP 8.3+
- Node.js 18+
- Docker & Docker Compose
- Composer

## Quick Start

```bash
git clone https://github.com/DigitalMachinist/arcane-advisor.git
cd arcane-advisor

# Install dependencies, generate app key, start Docker, run migrations, build frontend
composer run setup

# Start the dev server, queue worker, log monitor, and Vite
composer run dev
```

The app serves at `http://localhost:8000`. Vite runs at `http://localhost:5173`.

## Development

`composer run check` is the single validation gate. It runs Pint, Rector, Larastan, Pest, type coverage, Vitest, and `npm run build`. Run it before pushing anything.

```bash
composer run check          # Everything — the only command you need
composer run docker:start   # Start MySQL + Redis containers
composer run docker:stop    # Stop containers (data persists)
```

## Documentation

The project is thoroughly specced and planned. Start here:

- `CLAUDE.md` — rules, commands, and conventions for working in this repo
- `docs/specs/00-index.md` — canonical map and dependency graph for all 8 feature specs
- `docs/plans/implementation-plan.md` — build order (Stages A–I), per-PR test lists
- `docs/notes/checklist.md` — current completion status
- `docs/style-guide.md` — code style and architectural patterns
- `docs/testing-strategy.md` — test philosophy and coverage expectations
- `docs/conventions.md` — naming rules and process conventions
