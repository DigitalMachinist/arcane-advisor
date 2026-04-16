# Arcane Advisor

Your wizard knows 300+ spells. You remember maybe twelve. Arcane Advisor bridges the gap — not just by finding the *right* spell, but by helping you discover the surprising, clever, and wildly entertaining ones you'd never think to prepare.

Type what's happening at the table in plain language. A collapsing mine, a diplomatic dinner gone sideways, a dragon that just noticed you, or "I want to do something absurd with Prestidigitation." The system doesn't just solve problems — it invites you to play with the entire spell list in ways that make the table laugh, gasp, or slow-clap.

## How It Works

A **Whimsy Dial** with five positions — Tactical through Chaotic — is the soul of the engine. Crank it low for the mechanically optimal pick. Crank it high and discover that *Wall of Force* makes an excellent toboggan, or that *Programmed Illusion* can rickroll a lich. Every recommendation arrives as a spell card with a short "why this spell" explanation written in the voice of a knowledgeable friend who's also a little bit unhinged.

Under the hood, the system parses your prompt into tactical goals, stylistic cues, and hard constraints, then scores every wizard spell on both mechanical fit and personality fit — weighted by wherever you've set the dial. Follow-up queries refine, riff on, or gleefully derail prior results across a short conversation (up to five rounds). A sourcebook selector keeps recommendations honest to the books your table actually uses.

## Stack

| Layer | Technology |
|-------|------------|
| API | Laravel 13 (PHP 8.4+) |
| Frontend | Vue 3 SPA, Pinia, Vue Router, Tailwind CSS 4, Vite |
| Database | MySQL 8.4, Redis 7 (Docker) |
| LLM | Cloudflare Workers AI — `@cf/google/gemma-4-26b-a4b-it` (completions), `@cf/baai/bge-base-en-v1.5` (embeddings) |

## Prerequisites

- PHP 8.4+ (with `php8.4-xdebug` for `composer run check:ci` — mutation testing needs a coverage driver)
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

Two validation gates, split for iteration speed:

- `composer run check` — local fast gate. Pint, Larastan, Pest (default suite), Vitest. Runs in ~1–2 min on WSL; use this for pre-commit iteration.
- `composer run check:ci` — CI gate. Adds Rector, `npm run build`, the Pest `build` group (Vite manifest assertions), and Pest `--type-coverage --min=100`. Runs automatically on every push/PR through `.github/workflows/check.yml`.

```bash
composer run check          # Local iteration
composer run check:ci       # Full CI-parity gate
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
