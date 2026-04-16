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

Two categories of doc, with different lifecycle rules.

### Point-in-time artifacts

Filename format: `YYYY-MM-DD-##-description.md`. These accumulate; old ones are rarely edited.

- `docs/sessions/` — records of chat sessions (only when requested).
- `docs/mocks/` — design mockups and related artifacts.
- `docs/notes/` — research notes, audit results, working documents that don't fit another category.

### Living references

Filename format: descriptive non-dated kebab-case (e.g. `api-consult.md`, `implementation-plan.md`). These are the current truth, edited in place, with stable linkable names.

- `docs/specs/` — feature specifications. Numeric-prefixed for build ordering (`00-index.md`, `01-prompt-box-and-landing.md`).
- `docs/plans/` — implementation plans.
- `docs/schemas/` — data schemas and API contracts.
- `docs/` — permanent guides (this file, `style-guide.md`, `testing-strategy.md`).
