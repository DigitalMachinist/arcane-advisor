# Project Conventions

Cross-surface, cross-language, and documentation conventions for this repo. For PHP code-level mechanics (class layout, identifier naming within PHP, patterns, enforcement toolchain), see `style-guide.md`.

## Naming across surfaces

Different surfaces use different casing — translation happens at one boundary, always the same one.

- **camelCase** at every authored and wire-facing surface:
  - YAML frontmatter keys in `database/spells/*.yaml`
  - Request and response JSON on `POST /api/consult`
  - Enum string values stored in the database
- **snake_case** only at the MySQL column level (Laravel default).
- Translation between camelCase and snake_case happens at the **Eloquent model boundary** — use `$casts`, accessors, and `$attribute` conversion. Controllers and actions never see snake_case keys.
- Slugs are lowercase, hyphenated: `fireball`, `mage-armor`.

Identifier mechanics *within* a single language (PHP class = PascalCase, method = camelCase, DB column = snake_case, route URI = kebab-case) live in `style-guide.md` Section 4.

## Documentation file naming

Three categories of doc, each with its own lifecycle rules.

### Point-in-time artifacts

Filename format: `YYYY-MM-DD-##-description.md`. The `##` is a within-day sequence (`01`, `02`, …) so same-day artifacts stay deterministically ordered. These accumulate; old ones are rarely edited.

- `docs/sessions/` — records of chat sessions (only when requested).
- `docs/notes/` — research notes, audit results, historical design docs and implementation plans that have been executed, working documents that don't fit another category.

### Living references

Filename format: descriptive non-dated kebab-case (e.g. `api-consult.md`, `implementation-plan.md`). These are the current truth, edited in place, with stable linkable names.

- `docs/specs/` — feature specifications. Numeric-prefixed for build ordering (`00-index.md`, `01-prompt-box-and-landing.md`).
- `docs/plans/` — implementation plans (`implementation-plan.md`, `checklist.md`).
- `docs/schemas/` — data schemas and API contracts.
- `docs/` — permanent guides (this file, `style-guide.md`, `testing-strategy.md`).

### Mockups (`docs/mockups/`)

A living-reference directory with its own structure because visual design artifacts don't map cleanly to either of the rules above. HTML files are rendered in-browser for review.

- `spec-NN-canonical.html` — the canonical mockup for a numbered spec. Edited in place; the visual source of truth when prose and mockup disagree.
- `components/<name>.html` — canonical anatomy for a reusable component (e.g. `spell-card.html`, `whimsy-dial.html`).
- `archive/<name>.html` — historical design variants, kept for reference. Naming inside `archive/` is intentionally flexible (letter-prefixed iteration series like `A-open-tome.html` … `Z-sigil-orbit.html`, or descriptive variant tags like `spec-07-variation-A.html`).

## Pull request convention

### Title format

```
[Stage B / PR 2.2] Vocabulary enums
```

Stage letter + PR number matching `docs/plans/implementation-plan.md`, followed by the plain-English name from the plan. No `feat:` prefix.

### Body structure

```markdown
## What
One paragraph. What was built and any non-obvious decisions made.

## Spec / plan reference
- Stage X, PR Y.Z — per `docs/plans/implementation-plan.md`
- Relevant schema or spec docs consulted

## Tests added
- `tests/path/to/FooTest.php` — what it covers

## Gate status
- [x] `composer run check` green
- [x] All new tests pass
- [ ] Rebased on latest epic branch tip

## Decisions / deviations
Any place the implementation diverged from the plan, and why. Omit section if none.

## Unblocks
Which PRs can now start or proceed.
```

**Gate status convention:** checked boxes were verified by the agent before opening the PR; unchecked boxes require human or post-merge verification (e.g. rebase on latest epic branch tip).
