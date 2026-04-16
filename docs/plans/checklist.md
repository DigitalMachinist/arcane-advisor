# Arcane Advisor — Implementation Checklist

Tracks completion across all stages. Check items only when the PR is merged and `composer run check` is green on the merge commit. Red-test commits do not count as complete. Each PR's required tests are enumerated in `implementation-plan.md`.

## Stage A — Phase 0: Skeleton

- [ ] PR 0.1 — Scaffold from laravel-13-template
- [ ] PR 0.2 — Vue 3 SPA + Pinia + Vue Router + Vite
- [ ] PR 0.3 — Tailwind 4 via `@tailwindcss/vite`
- [ ] PR 0.4 — Docker Compose: MySQL 8.4 + Redis 7
- [ ] PR 0.5 — `composer run check` aggregate
- [ ] PR 0.6 — Validation hooks (Cowork PostToolUse + git pre-commit)
- [ ] PR 0.7 — `LlmClient` interface + `FixtureClient` + `CloudflareClient` stub
- [ ] PR 0.8 — `POST /api/consult` stub route, Controller, Action
- [ ] PR 0.9 — Architectural boundary tests
- [ ] PR 0.10 — Docs, `.env.example`, README

## Stage B — Spec 2: Spell database

- [x] PR 2.1 — YAML schema lock + fixture spells
- [x] PR 2.2 — Vocabulary enums (11 string-backed)
- [ ] PR 2.3 — Migrations, Eloquent models, factories
- [ ] PR 2.4 — `SpellsImportAction` + `spells:import` command
- [ ] PR 2.5 — `SpellRepository` read API
- [ ] PR 2.6 — `SpellsScrapeAction` + `spells:scrape` command
- [ ] PR 2.7 — `SpellsExtractAction` + `spells:extract` command
- [ ] PR 2.8 — `SpellsReviewAction` + `spells:review` walker
- [ ] PR 2.9 — Full wizard corpus populated + reviewed

## Stage C — Spec 3: Personality + embeddings

- [ ] PR 3.1 — `personality_blurb` + `embedding` columns + cosine helper
- [ ] PR 3.2 — `SpellsEnrichAction` + `spells:enrich` command
- [ ] PR 3.3 — Full wizard corpus enriched

## Stage D — Spec 4: Recommendation engine (headless)

- [ ] PR 4.1 — Prompt embedding + candidate ranking
- [ ] PR 4.2 — Per-card explanation pipeline
- [ ] PR 4.3 — `ConsultAction::execute()` wired end-to-end
- [ ] PR 4.4 — Round caching + history rehydration
- [ ] PR 4.5 — Error taxonomy + fallback

## Stage E — Spec 1: Landing + prompt UI

- [ ] PR 1.1 — Landing route + prompt input
- [ ] PR 1.2 — Results route (minimal)
- [ ] PR 1.3 — Loading + error states

## Stage F — Spec 6: Whimsy dial

- [ ] PR 6.1 — Whimsy dial component + CSS cascade
- [ ] PR 6.2 — Dial value threaded through `/api/consult`
- [ ] PR 6.3 — Prompt + blurb conditioning on whimsy

## Stage G — Spec 5: Card rendering

- [ ] PR 5.1 — `SpellCard` component against fixture payload
- [ ] PR 5.2 — Blurb + explanation rendering
- [ ] PR 5.3 — Whimsy-conditioned flourishes
- [ ] PR 5.4 — Responsive layout

## Stage H — Spec 7: Conversation mode

- [ ] PR 7.1 — Client-side roundId history store
- [ ] PR 7.2 — Follow-up UI on results view
- [ ] PR 7.3 — Classifier + `answer`-type response
- [ ] PR 7.4 — Engine consumes conversation history

## Stage I — Spec 8: Sourcebook selector

- [ ] PR 8.1 — Sourcebook multi-select component
- [ ] PR 8.2 — Selection threaded through `/api/consult`
- [ ] PR 8.3 — Empty-result handling

## Cross-cutting quality gates (asserted every PR via `composer run check`)

- [ ] Larastan level 6 clean
- [ ] Pint + Rector diff-free
- [ ] `tests/Arch/*` boundary tests green
- [ ] No live network in the Pest suite
- [ ] Vitest coverage of every SPA component touched
- [ ] `npm run build` green
