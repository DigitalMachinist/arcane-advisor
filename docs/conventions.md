# Project Conventions

Cross-surface, cross-language, and documentation conventions for this repo. For PHP code-level mechanics (class layout, identifier naming within PHP, patterns, enforcement toolchain), see `style-guide.md`.

## Database model conventions

These apply to every Eloquent model and its migration in this project. The mechanics (column types, cast syntax) belong here because they are project decisions, not PHP style rules.

### Primary key

Use `$table->id()` (auto-increment `BIGINT UNSIGNED`). Never expose the raw integer `id` to users — it is internal only.

### Public identifier (UUID)

Every entity that can be referenced in a URL or API response carries a `uuid` column alongside the primary key:

```php
$table->id();
$table->uuid('uuid')->unique();
```

- Generated on create via `Str::uuid()` in the model's `booted()` hook (or a factory state).
- Cast to `string` — no special UUID cast needed.
- Used in all external references (route parameters, API payloads, client-side links).
- The integer `id` must not appear in routes, API responses, or client-facing output.

### Timestamps

Store timestamps as `BIGINT` unix milliseconds, not MySQL `TIMESTAMP`/`DATETIME` columns. Cast them to `Carbon` via a custom cast so application code works with `Carbon` throughout.

```php
// Migration
$table->unsignedBigInteger('created_at_ms');
$table->unsignedBigInteger('updated_at_ms')->nullable();

// Model
public $timestamps = false;

protected function casts(): array
{
    return [
        'created_at_ms' => UnixMillisecondsCast::class,
        'updated_at_ms' => UnixMillisecondsCast::class,
    ];
}
```

Do **not** use `$table->timestamps()` — that produces MySQL `TIMESTAMP` columns that have timezone and range limitations. The `UnixMillisecondsCast` lives at `app/Casts/UnixMillisecondsCast.php` and converts between `int` (ms since epoch) and `Carbon`.

### Column ordering in migrations

Apply these three conventions within the standard column ordering from `style-guide.md` Section 10:

1. `$table->id()` — internal PK, always first
2. `$table->uuid('uuid')->unique()` — public identifier, immediately after PK
3. _(other columns per style-guide ordering)_
4. `$table->unsignedBigInteger('created_at_ms')` and `$table->unsignedBigInteger('updated_at_ms')->nullable()` — in place of `$table->timestamps()`

---

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
