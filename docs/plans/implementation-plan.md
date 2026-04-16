# Arcane Advisor — Implementation Plan

## Stack and conventions (locked)

- Laravel 13 + PHP 8.4+ via DigitalMachinist/laravel-13-template
- Vue 3 SPA + Pinia + Vue Router, mounted via Vite on top of the Laravel API
- Tailwind 4 via `@tailwindcss/vite`
- MySQL 8.4 + Redis 7 (Docker, template defaults); in-memory SQLite for Pest
- Pest v4, Larastan level 6, Pint, Rector, Vitest, `npm run build` — all rolled under `composer run check`
- CI: GitHub Actions; branch protection on `main` requires `composer run check` to pass before merge
- Cloudflare Workers AI: `@cf/google/gemma-4-26b-a4b-it` (swappable) for completions, `@cf/baai/bge-base-en-v1.5` for embeddings
- Pre-computed embeddings stored as BLOB on `spells`, cosine similarity in PHP
- Controllers are thin HTTP (`__invoke`) and delegate to Actions (`execute`) for business logic; they are never collapsed
- TDD red-green: SPEC → PLAN → failing tests → implementation → refactor; no production code before a failing test exists
- Small, internally-consistent PRs: one complete feature each, small enough that an agent ships without context compaction
- Repo location: `D&D Pyrrin/arcane-advisor/`; specs at `arcane-advisor/docs/specs/`; plans at `arcane-advisor/docs/plans/`

## Build order

The order chases a working end-to-end recommendation as early as possible. Whimsy dial lands just after the first UI so the app's identity shows up before the card polish work. Sourcebook selector defers to the end.

- Stage A — Phase 0: Skeleton
- Stage B — Spec 2: Spell database
- Stage C — Spec 3: Personality + embeddings
- Stage D — Spec 4: Recommendation engine (headless)
- Stage E — Spec 1: Landing + prompt UI
- Stage F — Spec 6: Whimsy dial
- Stage G — Spec 5: Card rendering
- Stage H — Spec 7: Conversation mode
- Stage I — Spec 8: Sourcebook selector

## Invariants preserved by the ordering

1. Spec 4's engine accepts `whimsy` and `sourcebooks` parameters from Stage D onward, with constant defaults. Stages F and I replace the constants with UI-driven values without touching the engine.
2. Whimsy is not threaded into DB schema or the shape of prompt templates until Stage F; keep it out of the corpus until it is real.
3. Every stage ends with `composer run check` green.
4. Each PR's first commit is failing tests only. Implementation commits follow.

## Test grain convention

Each PR below lists the concrete tests it must make pass. Tests are named as Pest/Vitest files; one name per logical test class or spec file. A PR is complete only when every listed test is green under `composer run check`.

---

## Stage A — Phase 0: Skeleton

Outcome: a Laravel + Vue skeleton that boots, has `composer run check` wired end-to-end, exposes a stub `/api/consult`, and binds a fixture `LlmClient` by default.

### PR 0.1 — Scaffold from laravel-13-template

The template already provides: `CLAUDE.md` with project rules, `composer run check` (Pint + Rector + Larastan + tests + type coverage), Docker MySQL + Redis, Tailwind 4, Pest v4, `docs/style-guide.md`, `docs/testing-strategy.md`, and branch workflow conventions. This PR verifies the scaffold works out of the box; subsequent Phase 0 PRs extend it.

Tests to make pass:
- `tests/Feature/HealthcheckTest.php` — `GET /up` returns 200
- `tests/Feature/HomeRouteTest.php` — `GET /` returns 200 and the Vue mount point
- `tests/Unit/SmokeTest.php` — a trivial `expect(true)->toBeTrue()` runs under Pest v4

### PR 0.2 — Vue 3 SPA + Pinia + Vue Router + Vite

Tests to make pass:
- `resources/js/tests/App.spec.ts` — Vitest mounts `<App />` with router and asserts landing route renders
- `resources/js/tests/store.spec.ts` — Pinia store instantiates and mutates state
- CI step: `npm run build` exits zero

### PR 0.3 — Tailwind 4 via `@tailwindcss/vite`

Tests to make pass:
- `resources/js/tests/tailwind.spec.ts` — a component with a Tailwind utility class renders with the class applied
- Vite build output includes generated CSS (asserted via file-existence test)

### PR 0.4 — Docker Compose: MySQL 8.4 + Redis 7

Tests to make pass:
- `tests/Feature/DatabaseConnectivityTest.php` — default connection resolves; a trivial migration applies and rolls back cleanly
- `tests/Feature/RedisConnectivityTest.php` — `Redis::ping()` returns PONG
- `tests/Feature/SqliteInMemoryTest.php` — Pest uses in-memory SQLite when `APP_ENV=testing`

### PR 0.5 — Extend `composer run check` + GitHub Actions

The template's `composer run check` already runs Pint + Rector + Larastan + Pest + type coverage. This PR extends it to also run Vitest and `npm run build`, and adds the GitHub Actions workflow.

Tests to make pass:
- GitHub Actions workflow at `.github/workflows/check.yml` runs `composer run check` on every push and PR; green badge required
- `composer run check` runs Pest, Larastan (level 6), Pint, Rector (dry run), Vitest, and `npm run build` — all zero-exit
- `tests/Meta/ComposerCheckContentsTest.php` — parses `composer.json`'s `scripts.check` and asserts each required tool is invoked (including `npx vitest run` and `npm run build`)
- `tests/Meta/CiWorkflowExistsTest.php` — asserts `.github/workflows/check.yml` exists and invokes `composer run check`

### PR 0.6 — Validation hooks

Tests to make pass:
- `tests/Meta/PreCommitHookInstalledTest.php` — asserts `.git/hooks/pre-commit` exists and invokes `composer run check`
- `tests/Meta/CoworkHookConfiguredTest.php` — asserts the Cowork PostToolUse hook config file is present and targets `composer run check`

### PR 0.7 — `LlmClient` interface + `FixtureClient` + `CloudflareClient` stub

Tests to make pass:
- `tests/Unit/Llm/LlmClientContractTest.php` — both implementations satisfy the `LlmClient` interface (method signatures, return types)
- `tests/Unit/Llm/FixtureClientTest.php` — given a fixture key, returns the canned `LlmResponse`; unknown key throws `FixtureNotFoundException`
- `tests/Unit/Llm/CloudflareClientTest.php` — stub returns `UnsupportedOperationException` for now, interface-conformant
- `tests/Unit/Llm/LlmClientBindingTest.php` — container resolves `LlmClient` based on `config('llm.driver')`; defaults to `fixture`

### PR 0.8 — `POST /api/consult` stub route, Controller, Action

Tests to make pass:
- `tests/Feature/ConsultRouteTest.php` — `POST /api/consult` returns 200 with the stub shape
- `tests/Feature/ConsultValidationTest.php` — missing `prompt` returns 422
- `tests/Unit/Http/ConsultControllerTest.php` — controller delegates to `ConsultAction::execute()` with the validated request payload and does no business logic
- `tests/Unit/Actions/ConsultActionStubTest.php` — action returns the fixture stub payload

### PR 0.9 — Architectural boundary tests

Tests to make pass:
- `tests/Arch/ControllersAreThinTest.php` — controllers expose only `__invoke` and never instantiate domain services directly
- `tests/Arch/ActionsExposeExecuteTest.php` — every class under `app/Domain/**/Actions` exposes a single public `execute`
- `tests/Arch/DomainHasNoHttpImportsTest.php` — classes under `app/Domain` do not import `Illuminate\Http\*`
- `tests/Arch/NoLiveHttpInTestsTest.php` — test suite contains no un-faked `Http::` calls

### PR 0.10 — Augment `CLAUDE.md`, `.env.example`, README

The template already provides a comprehensive `CLAUDE.md`. This PR augments it for Arcane Advisor:
- Update project overview to describe Arcane Advisor (D&D 5e wizard spell recommendation engine), Vue 3 SPA, Cloudflare Workers AI
- Add explicit Controllers-vs-Actions rule and scoped-decisions-stay-scoped rule to the important rules section
- Add camelCase/snake_case boundary convention (YAML + API = camelCase; DB = snake_case; translation at Eloquent boundary)
- Add index entries pointing to `docs/plans/implementation-plan.md`, `docs/plans/checklist.md`, `docs/schemas/api-consult.md`, `docs/schemas/spell-yaml.md`, `docs/schemas/enums.md`
- Update file naming conventions across all doc categories:
  - **Point-in-time artifacts** (sessions, mocks, notes): date-prefixed (`YYYY-MM-DD-##-description.md`) — these accumulate; old ones are rarely edited
  - **Living references** (specs, plans, schemas, guides): descriptive non-dated kebab-case (`api-consult.md`, `implementation-plan.md`) — these are the current truth, edited in place, with stable linkable names
  - **Specs** additionally use numeric prefixes for build ordering (`00-index.md`, `01-landing-prompt.md`)

Tests to make pass:
- `tests/Meta/EnvExampleDefaultsTest.php` — `.env.example` sets `LLM_DRIVER=fixture`
- `tests/Meta/ReadmeMentionsCheckCommandTest.php` — README references `composer run check` as the single validation entry point
- `tests/Meta/ClaudeMdPointsToDocsTest.php` — `CLAUDE.md` references `implementation-plan.md`, `checklist.md`, and all three schema docs

---

## Stage B — Spec 2: Spell database

Forks locked: per-type link tables for tags; PHP string-backed enums for vocabularies; build the `spells:review` walker.

Outcome: a MySQL-backed wizard corpus authored as YAML, populated via a scrape → extract → review → import pipeline, queryable via `SpellRepository`.

### PR 2.1 — YAML schema lock + fixture spells

Tests to make pass:
- `tests/Unit/Domain/Spells/YamlSchemaTest.php` — each of 3 hand-authored fixture YAMLs validates; per-failure-mode fixtures (missing key, bad enum, wrong type) are rejected with specific error messages
- `tests/Unit/Domain/Spells/YamlLoaderTest.php` — loader returns a typed DTO, not a raw array

### PR 2.2 — Vocabulary enums

Defines the canonical enum vocabularies. `ActionEconomy` and `DurationCategory` are produced by import parsers (PR 2.4), not authored — but the enums themselves live in this PR.

Tests to make pass:
- `tests/Unit/Domain/Spells/Enums/VocabularyEnumTest.php` — for each enum (School, SourceCode, SourceClass, Qualifier, DamageType, Condition, Targeting, AreaShape, AbilityScore, AttackRoll, ActionEconomy, DurationCategory, CombatRole, OutOfCombatUtility): known values present, `::from()` rejects unknown, `::tryFrom()` returns null for unknown, `cases()` count matches expected
- `tests/Arch/EnumsAreStringBackedTest.php` — every enum under `app/Domain/Spells/Enums` is string-backed

### PR 2.3 — Migrations, Eloquent models, factories

Tests to make pass:
- `tests/Feature/Domain/Spells/SpellModelTest.php` — factory creates a valid spell; timestamps populate; enum casts round-trip
- `tests/Feature/Domain/Spells/SpellSourcesRelationshipTest.php` — many-to-one; cascade-delete of spell removes sources
- `tests/Feature/Domain/Spells/SpellClassAvailabilityRelationshipTest.php` — pivot semantics hold
- `tests/Feature/Domain/Spells/SpellDamageRelationshipTest.php`
- `tests/Feature/Domain/Spells/SpellConditionsRelationshipTest.php`
- `tests/Feature/Domain/Spells/SpellTargetingsRelationshipTest.php`
- `tests/Feature/Domain/Spells/SpellCombatRolesRelationshipTest.php`
- `tests/Feature/Domain/Spells/SpellUtilitiesRelationshipTest.php`
- `tests/Feature/Domain/Spells/SpellActionEconomiesRelationshipTest.php`
- `tests/Arch/DomainModelsNoHttpTest.php` — models under `app/Domain/Spells/Models` don't import HTTP classes

### PR 2.4 — `SpellsImportAction` + `spells:import` command

Includes the two pure parsers (`CastingTimeParser`, `DurationParser`) that derive `actionEconomy` and `durationCategory` from authored free-text fields during import.

Tests to make pass:
- `tests/Unit/Domain/Spells/Parsers/CastingTimeParserTest.php` — exhaustive mapping table: `"1 action"` → `action`, `"1 bonus action"` → `bonusAction`, `"1 reaction, ..."` → `reaction`, `"1 minute"` → `minute`, `"10 minutes"` → `tenMinutes`, `"1 hour"` → `hour`, fallback → `longer`
- `tests/Unit/Domain/Spells/Parsers/DurationParserTest.php` — strips `"Concentration, "` prefix then maps: `"Instantaneous"` → `instantaneous`, `"Until dispelled"` → `untilDispelled`, `"Permanent"` → `permanent`, time-valued strings → `timed`
- `tests/Feature/Domain/Spells/Actions/SpellsImportActionTest.php` — import from fixture YAML directory populates all tables correctly; `action_economy` and `duration_category` columns populated by the parsers; re-running is idempotent (no duplicate rows); invalid YAML aborts the whole import transactionally (no partial state)
- `tests/Feature/Console/SpellsImportCommandTest.php` — exit code 0 on success, non-zero on failure; summary output lists imported count
- `tests/Unit/Http/SpellsImportCommandDelegatesTest.php` — command only calls the Action and formats output

### PR 2.5 — `SpellRepository` read API

Tests to make pass:
- `tests/Feature/Domain/Spells/SpellRepositoryTest.php` — `findByLevel`, `findBySchool`, `findByDamageType`, `findByCondition`, `findByTag` each return correct rows; eager-loads relationships per the declared shape
- `tests/Feature/Domain/Spells/SpellRepositoryNoNPlusOneTest.php` — Laravel query log shows bounded query count under representative workloads

### PR 2.6 — `SpellsScrapeAction` + `spells:scrape` command

Starts from the index page at `https://dnd5e.wikidot.com/spells:wizard`, extracts per-spell links, then fetches each detail page. Respectful-scrape rules apply.

Tests to make pass:
- `tests/Feature/Domain/Spells/Actions/SpellsWizardIndexParseTest.php` — given a recorded copy of the wizard index page, parser returns the full set of spell slugs
- `tests/Feature/Domain/Spells/Actions/SpellsScrapeActionTest.php` — using recorded HTML fixtures under `tests/Fixtures/scrape/`, each fixture yields a raw-record array with expected fields; output files written to `storage/app/spells/raw/`
- `tests/Feature/Domain/Spells/Actions/ScraperRespectfulnessTest.php` — HTTP client sends a User-Agent and honors a configured delay between requests
- `tests/Feature/Console/SpellsScrapeCommandTest.php` — respects `--dry-run`; exit codes correct

### PR 2.7 — `SpellsExtractAction` + `spells:extract` command

Tests to make pass:
- `tests/Feature/Domain/Spells/Actions/SpellsExtractActionTest.php` — given fixture raw JSON and a fixture `LlmClient` returning canned structured output, Action writes YAML matching the PR 2.1 schema. Covers: damage spell, conditions spell, cantrip, concentration spell
- `tests/Unit/Domain/Spells/ExtractionPromptTest.php` — prompt template at `resources/prompts/spell-extraction.txt` renders with expected slots filled
- `tests/Feature/Console/SpellsExtractCommandTest.php` — command exit codes and summary output

### PR 2.8 — `SpellsReviewAction` + `spells:review` walker

Review state is tracked in a sidecar `database/spells/.reviewed-slugs` file, one slug per line. YAML files themselves stay pure spell data.

Tests to make pass:
- `tests/Feature/Domain/Spells/Actions/SpellsReviewActionTest.php` — walker presents one spell at a time via an injected IO double; approve appends the slug to `.reviewed-slugs`, edit rewrites the YAML in place, skip leaves both the file and the sidecar unchanged
- `tests/Feature/Domain/Spells/Actions/SpellsReviewResumeTest.php` — re-running skips slugs already in `.reviewed-slugs`
- `tests/Feature/Console/SpellsReviewCommandTest.php` — command wires Laravel Prompts to the Action without leaking IO concerns

### PR 2.9 — Full wizard corpus populated + reviewed

Tests to make pass:
- `tests/Feature/Domain/Spells/WizardCorpusCompletenessTest.php` — every PHB wizard spell slug is present under `database/spells/*.yaml` and validates under `YamlSchemaTest`
- `tests/Feature/Domain/Spells/WizardCorpusReviewedTest.php` — every authored slug appears in `database/spells/.reviewed-slugs`
- `tests/Feature/Domain/Spells/CorpusImportsCleanTest.php` — running `spells:import` against the full corpus populates the DB without errors

---

## Stage C — Spec 3: Personality + embeddings

Outcome: every spell carries a `personality_blurb` and a pre-computed embedding BLOB; cosine similarity helper is in place.

### PR 3.1 — `personality_blurb` + `embedding` columns + cosine helper

Tests to make pass:
- `tests/Feature/Domain/Spells/EnrichmentSchemaTest.php` — migration adds both columns with correct types; BLOB round-trips a float vector
- `tests/Unit/Domain/Search/CosineSimilarityTest.php` — identical vectors score 1.0; orthogonal score 0.0; opposite score -1.0; malformed input throws

### PR 3.2 — `SpellsEnrichAction` + `spells:enrich` command

Tests to make pass:
- `tests/Feature/Domain/Spells/Actions/SpellsEnrichActionTest.php` — given fixture LLM + fixture embeddings, populates both columns for all spells; idempotent; re-running with `--force` overwrites
- `tests/Feature/Console/SpellsEnrichCommandTest.php` — exit codes and summary output
- `tests/Unit/Domain/Spells/BlurbPromptTest.php` — prompt template renders with expected slots

### PR 3.3 — Full wizard corpus enriched

Tests to make pass:
- `tests/Feature/Domain/Spells/WizardCorpusEnrichedTest.php` — every wizard spell in the DB has a non-null `personality_blurb` and a non-empty `embedding`

---

## Stage D — Spec 4: Recommendation engine (headless)

Outcome: `POST /api/consult` returns three real spells with LLM-generated explanations, following the envelope defined in `docs/schemas/api-consult.md`. Every response carries a `roundId` and a Redis-cached record of the turn. `whimsy` and `sourcebooks` parameters accepted with constant defaults. The classifier and `answer`-type responses land in Stage H; Stage D assumes every successful response is `type: "recommendations"`.

### PR 4.1 — Prompt embedding + candidate ranking

Tests to make pass:
- `tests/Feature/Domain/Recommend/EmbedPromptActionTest.php` — given a prompt, calls `LlmClient::embed()` and returns a float vector
- `tests/Feature/Domain/Recommend/RankCandidatesActionTest.php` — returns top-K spells by cosine similarity against prompt embedding; respects `class=wizard` filter
- `tests/Feature/Domain/Recommend/SourcebookFilterTest.php` — respects `sourcebooks` parameter; default "all" returns everything

### PR 4.2 — Per-card explanation pipeline

Tests to make pass:
- `tests/Feature/Domain/Recommend/ExplainCardActionTest.php` — given a spell + prompt context + whimsy, returns structured explanation via fixture LLM
- `tests/Unit/Domain/Recommend/ExplanationPromptTest.php` — prompt template renders with slots filled

### PR 4.3 — `ConsultAction::execute()` wired end-to-end

Tests to make pass:
- `tests/Feature/Http/ConsultActionRealPipelineTest.php` — request with a prompt returns exactly three spells with explanations against fixture LLM + real DB; response matches `docs/schemas/api-consult.md` envelope
- `tests/Feature/Http/ConsultParametersTest.php` — `whimsy` and `sourcebooks` parameters accepted; defaults applied when absent
- `tests/Feature/Http/ConsultEmptyCorpusTest.php` — returns a well-formed empty result when filters exclude all spells
- `tests/Feature/Http/ConsultEnvelopeMetaTest.php` — `meta.requestId`, `meta.modelVersion`, `meta.timingMs` present on every response

### PR 4.4 — Round caching + history rehydration

Tests to make pass:
- `tests/Feature/Http/ConsultRoundIdEmittedTest.php` — every successful response includes a UUIDv7 `roundId`
- `tests/Feature/Domain/Recommend/RoundCacheTest.php` — `RoundCache::put()` stores `{prompt, whimsy, sourcebooks, response}` under `consult:round:{uuid}` with 2-hour TTL; `RoundCache::get()` returns stored record; missing keys return null
- `tests/Feature/Http/ConsultHistoryRehydrationTest.php` — request with `history` of valid roundIds rebuilds conversation context from Redis and passes it to the engine
- `tests/Feature/Http/ConsultHistoryExpiredTest.php` — any missing roundId in `history` yields `type: "historyExpired"` with HTTP 410

### PR 4.5 — Error taxonomy + fallback

Tests to make pass:
- `tests/Feature/Http/ConsultTimeoutTest.php` — fixture client raising timeout yields `type: "timeout"` with HTTP 504
- `tests/Feature/Http/ConsultUpstreamUnavailableTest.php` — non-2xx upstream yields `type: "upstreamUnavailable"` with HTTP 503
- `tests/Feature/Http/ConsultValidationFailureTest.php` — bad payload yields `type: "validation"` with HTTP 422 and a field-by-field error map
- `tests/Feature/Http/ConsultUnknownErrorTest.php` — unexpected exception yields `type: "unknown"` with HTTP 500; stack trace not leaked

---

## Stage E — Spec 1: Landing + prompt UI

Outcome: user types a prompt, sees three real spells rendered as minimal list or raw JSON.

### PR 1.1 — Landing route + prompt input

Tests to make pass:
- `resources/js/tests/routes/landing.spec.ts` — landing renders a prompt input and submit button
- `resources/js/tests/stores/consultStore.spec.ts` — store submits the prompt, holds loading state, stores the response

### PR 1.2 — Results route (minimal)

Tests to make pass:
- `resources/js/tests/routes/results.spec.ts` — given a stored response, renders three entries with name and explanation
- `resources/js/tests/routes/resultsEmpty.spec.ts` — empty response renders a "no matches" state

### PR 1.3 — Loading + error states

Tests to make pass:
- `resources/js/tests/stores/consultErrorStates.spec.ts` — each of Timeout, UpstreamUnavailable, Validation, Unknown maps to a user-facing message
- `resources/js/tests/routes/loadingState.spec.ts` — loading spinner visible while request in-flight

---

## Stage F — Spec 6: Whimsy dial

Outcome: hard-coded whimsy default replaced by a live dial; CSS cascade wired; engine honors client-supplied value. The five locked levels are Tactical, Balanced, Creative, Theatrical, Chaotic (see specs for per-level semantics). "Balanced" is the default applied from Stage D until this stage wires the dial.

### PR 6.1 — Whimsy dial component + CSS cascade

Tests to make pass:
- `resources/js/tests/components/WhimsyDial.spec.ts` — dial renders, value changes update a Pinia store
- `resources/js/tests/whimsyCascade.spec.ts` — setting store whimsy updates `data-whimsy` on `<body>` and resolves expected CSS variable values

### PR 6.2 — Dial value threaded through `/api/consult`

Tests to make pass:
- `resources/js/tests/stores/consultStoreWhimsy.spec.ts` — submit sends the current whimsy value in the payload
- `tests/Feature/Http/ConsultHonorsWhimsyTest.php` — engine receives and applies the supplied whimsy, overriding the default

### PR 6.3 — Prompt + blurb conditioning on whimsy

Tests to make pass:
- `tests/Unit/Domain/Recommend/WhimsyConditionedPromptTest.php` — prompt template selects the expected variant per whimsy level
- `tests/Feature/Domain/Recommend/BlurbSelectionByWhimsyTest.php` — blurb presentation differs across whimsy levels against fixture data

---

## Stage G — Spec 5: Card rendering

Outcome: minimal list is replaced with proper spell cards showing all mechanical and flavor data.

### PR 5.1 — `SpellCard` component against fixture payload

Tests to make pass:
- `resources/js/tests/components/SpellCard.spec.ts` — renders name, level, school, casting time, range, components, duration, concentration pip
- `resources/js/tests/components/SpellCardDamage.spec.ts` — damage rolls render when present
- `resources/js/tests/components/SpellCardClassChips.spec.ts` — class chips render for each class availability

### PR 5.2 — Blurb + explanation rendering

Tests to make pass:
- `resources/js/tests/components/SpellCardBlurb.spec.ts` — blurb renders with whimsy-conditioned styling
- `resources/js/tests/components/SpellCardExplanation.spec.ts` — per-card explanation from the engine renders

### PR 5.3 — Whimsy-conditioned flourishes

Tests to make pass:
- `resources/js/tests/components/SpellCardFlourishes.spec.ts` — each whimsy level toggles the expected decorative classes
- `resources/js/tests/routes/resultsWithCards.spec.ts` — full results route renders three `SpellCard`s end-to-end

### PR 5.4 — Responsive layout

Tests to make pass:
- `resources/js/tests/components/SpellCardResponsive.spec.ts` — breakpoint classes applied; snapshot stable across viewports

---

## Stage H — Spec 7: Conversation mode

Outcome: multi-round follow-ups work. Client carries only the list of prior `roundId`s; server rehydrates full turn context from the Redis round cache (which Stage D already populates). A classifier routes each turn to either `recommendations` or `answer` responses. 5-round cap enforced server-side.

### PR 7.1 — Client-side roundId history store

Tests to make pass:
- `resources/js/tests/stores/conversationStore.spec.ts` — store appends each response's `roundId` to the in-memory history; clears on new session; renders the visible transcript from responses it has observed (no re-fetch)
- `resources/js/tests/stores/conversationCapClientTest.spec.ts` — client surfaces cap-reached state after 5 rounds

### PR 7.2 — Follow-up UI on results view

Tests to make pass:
- `resources/js/tests/routes/resultsFollowup.spec.ts` — follow-up input visible after a response; submitting sends the current `history: roundId[]`
- `resources/js/tests/routes/resultsFollowupCap.spec.ts` — input disabled with message when cap reached
- `resources/js/tests/routes/resultsHistoryExpired.spec.ts` — 410 `historyExpired` response surfaces a "start a fresh consultation" prompt

### PR 7.3 — Classifier + `answer`-type response

Tests to make pass:
- `tests/Feature/Domain/Recommend/IntentClassifierTest.php` — recommendation-shaped prompts classify as `recommendations`; rules/reference prompts classify as `answer`; against fixture LLM
- `tests/Feature/Http/ConsultAnswerResponseTest.php` — rules question yields `type: "answer"` with `message` and `referencedSpells`
- `resources/js/tests/components/AnswerBubble.spec.ts` — answer renders as a chat bubble with linkified spell references

### PR 7.4 — Engine consumes conversation history

Tests to make pass:
- `tests/Feature/Http/ConsultWithHistoryTest.php` — request with prior `roundId`s rehydrates from Redis and conditions both embedding and explanation passes on the rehydrated context
- `tests/Feature/Http/ConsultHistoryCapServerTest.php` — server returns 422 when `history.length > 5`
- `tests/Feature/Http/ConsultStatelessTest.php` — identical request+history pairs produce deterministic output against fixture client

---

## Stage I — Spec 8: Sourcebook selector

Outcome: the hard-coded "all sourcebooks" default is replaced by a live multi-select.

### PR 8.1 — Sourcebook multi-select component

Tests to make pass:
- `resources/js/tests/components/SourcebookSelector.spec.ts` — renders available sourcebooks, default = all selected, toggles update a Pinia store
- `resources/js/tests/stores/sourcebookStore.spec.ts` — empty selection treated as "all" per product rule, or surfaces an empty-state UI per final decision

### PR 8.2 — Selection threaded through `/api/consult`

Tests to make pass:
- `resources/js/tests/stores/consultStoreSourcebooks.spec.ts` — submit includes current selection in the payload
- `tests/Feature/Http/ConsultHonorsSourcebooksTest.php` — engine receives and filters on supplied sourcebooks, overriding the default

### PR 8.3 — Empty-result handling

Tests to make pass:
- `tests/Feature/Http/ConsultSourcebooksExcludeAllTest.php` — selection that excludes every spell returns a well-formed empty result
- `resources/js/tests/routes/resultsEmptySourcebook.spec.ts` — UI renders a specific "widen your sourcebooks" message for this case

---

## Cross-cutting quality gates

Enforced at every PR via `composer run check`:

- Larastan level 6 clean
- Pint + Rector diff-free
- `tests/Arch/*` boundary tests green
- No live network in the Pest suite
- Vitest coverage of every SPA component touched
- `npm run build` green
