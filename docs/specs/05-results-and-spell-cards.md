# Spec 5: Results & Spell Cards

> See [spec.md](../spec.md) for the product overview. This spec is the output surface for [Spec 4](04-recommendation-logic.md) (recommendation logic) — it defines how the recommendation set is rendered to the user. It consumes mechanical fields from [Spec 2](02-spell-data-mechanical.md), personality tags and justifications from [Spec 3](03-spell-data-personality.md), and the sourcebook code committed by [Spec 8](08-sourcebook-selector.md). The in-conversation rendering of these same cards is governed by [Spec 7](07-conversation-mode.md).

> **The component sheets under `specs/mockups/components/` are the source of truth for every visual and state-level detail in this spec.** `spell-card.html`, `set-message.html`, and `error-card.html` were thoroughly designed during the Spec 7 work and their states, structure, colour, type, and affordances are canonical. This spec is a prose companion that names the pieces, describes set-level behaviour (how many cards, how they load, how they transition), and captures decisions the sheets don't express. Where this prose and a sheet disagree about a visual, structural, or interaction detail, **the sheet wins**; the prose is updated to match.

---

## Overview

When the user submits a query, the recommendation logic returns a set of spells with justifications and tags. This spec is about turning that data structure into a surface the user actually wants to read — parchment-textured cards, stacked in a calm vertical column, each one a clearly legible unit of advice.

The register is in-world: the wizard is handing you their opinion. The page is not a search results list. Cards are the primary unit; the set-level banner is a secondary grace note when context is needed.

---

## The Set

### Set Size

A standard recommendation set is **three spell cards**. Three gives the recommendation logic room to produce a spread (typically: a safe pick, an on-theme pick, a wildcard, though Spec 4 owns the composition) without overwhelming the user. The user gets to compare without scrolling a wall of spells.

Some query types do not return three cards. See Set-Level Variants below.

### Set-Level Variants

| Query outcome | Rendering |
|---|---|
| Standard recommendation | Three spell cards, vertical column |
| "No exact match" fallback | Italic set banner ("Not an exact match — closest viable options") above three cards, each card also carrying its own per-card inexact-match banner |
| Rules question (see [Spec 4](04-recommendation-logic.md#rules-questions-disguised-as-recommendations) and [Spec 7](07-conversation-mode.md#5-rules--meta-qa)) | Italic set banner only. No cards follow. |
| Error | A single error card replaces the set. No banner, no spell cards. |
| Empty result (shouldn't occur — Spec 4 guarantees a non-empty set) | Treated as error; show the error card. |

---

## Layout

### Desktop

Cards render in a **single vertical column**, full-width relative to the content column (~680px wide, matching the component sheet). Each card is a complete, self-contained unit; the user reads top-to-bottom, one card at a time.

The content column is centred beneath the landing chrome — the casting circle, dock, and dial remain visible above the results. The first card appears below the submitted prompt (now the first turn of a transcript) with comfortable vertical breathing room.

A set-level banner (when present) sits directly above the first card, inset to the same width, with its own modest top margin from the turn's user prompt.

### Tablet

Unchanged from desktop — single column, slightly narrower content width (~600px). The casting circle and dock adjust per Spec 1; the card column remains the single-focus stack.

### Mobile

Single column, edge-padded (~16px gutter). Cards shrink the stat grid to 2×2 if needed, tighten internal padding, and drop any decorative accent that competes with the content. Expansion and action buttons remain tap-sized (≥44px).

---

## Transition from Landing

Immediately after submit, the landing page **does not swap** to a dedicated results view. Instead:

1. The dock, casting circle, and dial remain in place.
2. The submitted prompt becomes the first user turn in a transcript (see [Spec 7](07-conversation-mode.md)). The submitted prompt's scrap animates into its transcript-turn position above the results area.
3. Three **parchment skeletons** render immediately in the card column — card-shaped placeholders at the same size as a real card, with subtle pulse on the title / stat-grid / why-block regions.
4. When the LLM response arrives, the three skeletons are replaced by the three real cards in a single coordinated fade-in (no streaming, no per-card staggered arrival for MVP).

From that first submission onward, the page is in conversation mode. Subsequent queries append further turns below.

### Loading State

The parchment skeletons are in-world — they read as a scroll being unrolled, not as a spinner. They pulse gently in the same candlelit amber tone as the rest of the chrome. A small arcane progress cue (a circle of runes slowly lighting in sequence, or similar — ambient, not prominent) may sit above the skeletons if the wait exceeds a short threshold (e.g. 1.5s). Exact micro-interaction is an implementation detail.

No central-page spinner. No blocking overlay. The dial and dock stay interactive throughout.

### Error Handling

The canonical rendering is `components/error-card.html` — appearance, copy tone, and retry affordance live there. Behaviour:

- Skeletons are replaced by the error card.
- The error card carries an in-world message ("The candles guttered — the oracle lost its grip on the request"), a brief explanation of what went wrong when known, and a **Retry** button that re-submits the same query bundle.
- The user's prompt remains in place above; they can also edit the prompt in the dock and submit anew.
- Errors do not replace prior transcript turns — if this is the Nth turn, only this turn shows the error card; previous turns remain intact.

---

## Spell Card Anatomy

The canonical anatomy is `specs/mockups/components/spell-card.html`. The summary below is an index into that sheet, not a competing source.

Every spell card — landing, transcript, inexact-match, anchored — shares a single skeleton. State differences are additive: banners, badges, and anchor lines decorate the base card but never restructure it.

### Baseline Components

From top to bottom (as rendered in the sheet):

1. **Optional anchor line** ("Inspired by your mention of ice") — appears only when the card is produced by a "More like this" refinement or a keyword pin. Small, secondary weight, sits above the title.
2. **Optional per-card banner** ("Not an exact match — closest viable option") — full-width within the card, inline warning tone, used when the recommendation set is soft-fail.
3. **Card head** — the spell title (display serif, prominent) and the meta line beside/below it. The meta line reads: `Lvl {level} · {school} · {sourcebook code}`.
4. **Stat grid** — 4 cells: Cast, Range, Components, Duration. 2×2 on narrow viewports, 4×1 on wide.
5. **Stat badges** (optional) — Concentration (warm tone) and Ritual (green tone) when applicable. Rendered between the stat grid and the "why" block.
6. **"Why this spell"** — a short justification from the recommendation logic (Spec 4), led by the "Why this spell" label. One to three sentences. The source of the card's flavour.
7. **Tags** — up to four personality tag pills (Spec 3). If Spec 4 returns more than four, Spec 5 truncates to the first four; the rest are not displayed. Pills are low-contrast parchment chips.
8. **Card foot** — a small action row with `More like this` on the left and a `Read full description ▾` expand affordance on the right.

### Sourcebook Code Placement

The three-letter sourcebook code (PHB/XGE/TCE/FTD/SCC) sits in the meta line next to level and school, separated by the same `·` divider: `Lvl 2 · Conjuration · PHB`. Small, quiet, co-located with the other cataloguing metadata. This is the single canonical placement.

### Expansion

The `Read full description ▾` affordance expands the card **inline**. On click:

- The chevron rotates 180°.
- The card grows downward, revealing the spell's full rules text (preserved verbatim from Spec 2) in a distinct, slightly recessed text block beneath the tags.
- Subsequent cards are pushed down by the height of the expanded description. No overlay, no modal.
- The affordance label flips to `Collapse ▴`.
- Multiple cards can be expanded at once; each card's expansion state is independent.

Expansion does not refetch; the full description is delivered with the card payload.

### Card Interactions

- **More like this.** Submits a new query anchored to this card. The new query becomes a new transcript turn; the resulting cards carry the "Inspired by {spell name}" anchor line. Exact shape of the anchored prompt is Spec 4's job.
- **Expand / collapse.** See above.
- **Click elsewhere on the card.** No-op. The card is not itself a link; only its affordances are interactive. Keeps the reading experience calm.

### Card States

Every state a card can take is isolated in `specs/mockups/components/spell-card.html`. That sheet is canonical; the list below is a locator, not a replacement:

| State | Sheet section | Notes |
|---|---|---|
| Default · Collapsed | State 1 | The baseline. |
| With Concentration (+ Ritual) | State 2 | Warm / green stat badges. |
| Anchored · "Inspired by" | State 3 | Anchor line above title. From refinement queries. |
| Not an Exact Match | State 4 | Inexact-match banner at top of card. |
| Expanded | State 5+ | Full rules text revealed inline. |

When a new state is needed (new query type, new failure mode), add it to the sheet first, then note it here.

---

## Set-Level Message

For set-wide context (inexact match, rules answer, no-match explanation, error-adjacent soft messages), a **set-level italic banner** sits above the card column. Canonical rendering: `components/set-message.html` — that sheet owns the exact type, tint, inset, and tone.

Shape at a glance: a one- or two-line italic message on a lightly-tinted parchment strip, inset to the card column's width. No icon, no button. This is the wizard speaking about the set as a whole before presenting it.

Examples:

- "Not an exact match — these are the closest the oracle could bring back."
- "That's a rules question, not a spellcraft one." (followed by a short rules answer, no cards)
- "Sifting through rarely-used spells — you asked for something unusual."

The set-level banner is distinct from the per-card inexact-match banner. Both can coexist in a single turn; they play different roles.

---

## Relationship to Personality Tags and "Why This Spell"

Tags and the "why this spell" blurb are the clearest surface manifestation of the Spec 3 personality layer. Small editorial rules:

- Tags are lowercase, at most two words per tag, rendered as parchment pills. Maximum four per card.
- The "why this spell" blurb is one to three sentences, written by the recommendation logic (Spec 4) in a human voice. It does not reiterate the mechanical stats; it explains *why this spell for this query*.
- Neither tags nor the blurb may contradict the full description (Spec 2). If the blurb says "cheap and non-lethal" the description must support it.
- Tags are not independently clickable in MVP. They are descriptive labels, not filters. See [Spec 3](03-spell-data-personality.md#out-of-scope-for-this-spec).

---

## Relationship to Conversation Mode

From the first query onward the page is a transcript. Every rendered card lives inside a transcript turn. [Spec 7](07-conversation-mode.md) owns turn composition, stacking, and dial-change hints; Spec 5 owns the cards themselves and the set-level banner that introduces them.

In transcript context:

- Cards retain full interactivity (expand, More like this).
- "More like this" creates a new turn, not a sibling set within the existing turn.
- The set-level banner and card set are laid out identically to the landing-page case.

---

## Accessibility

Inherits the [master spec's Accessibility Baseline](../spec.md#accessibility-baseline). Spell-card / set-level refinements:

- Each spell card is an `<article>` with an accessible name derived from the spell's title. The card's interactive affordances (expand/collapse, "More like this") are real `<button>`s with clear labels, not divs-as-buttons.
- The expand/collapse button exposes `aria-expanded` and controls the hidden description region via `aria-controls`. Inline expansion does not move focus; the button stays focused so keyboard users can collapse with the same key that expanded.
- The meta line (`Lvl 2 · Conjuration · PHB`) is read as a single accessible string per card, with the `·` delimiter spoken as a pause rather than a punctuation name (achieved via separator spans and `aria-label` composition).
- The reasoning blockquote's "why this spell" preamble is always present in the DOM so screen-reader users hear the framing before the rationale.
- The set-level italic banner has `role="note"` (or equivalent) and is placed in the reading order immediately before the set it introduces.
- The dedicated error card is announced through the Spec 7 transcript live region and includes a focusable "Retry" button placed first in the card's tab order.
- The parchment-skeleton loading state is purely decorative; assistive tech hears only the live-region announcement that the turn is loading.

---

## Out of Scope for This Spec

- **Recommendation logic.** What gets recommended and why is [Spec 4](04-recommendation-logic.md). Spec 5 only renders the output.
- **Combo bundling.** Suggested spell combos (e.g. Grease + Fire Bolt) are a future concept; not in MVP.
- **Pinning / favourites.** No "save this spell" affordance in MVP.
- **Spell comparison view.** No side-by-side compare-two-spells mode.
- **Tag-based filtering / browse.** Tags are descriptive, not interactive (see [Spec 3](03-spell-data-personality.md)).
- **Direct share / export.** No "copy card as text" or "share link" affordance in MVP.
- **Reasoning trace display beyond "why this spell".** Spec 4's full reasoning trace is diagnostic; users see only the curated blurb.
- **Dial-change hints in transcript.** Owned by [Spec 7](07-conversation-mode.md).

---

## Resolved Design Decisions

- **Three cards per standard set.** Owned by Spec 5 as the rendering target; Spec 4 produces three when possible.
- **Single vertical column.** One card width, full attention per card. No grid.
- **Landing chrome persists after submit.** Dock, dial, and casting circle remain. The page becomes a transcript from the first submission on.
- **Parchment skeletons during load.** Three card-shaped placeholders pulse until the set arrives; replaced together, not streamed.
- **Sourcebook code in the meta line.** `Lvl 2 · Conjuration · PHB`. Single canonical placement.
- **Inline expansion.** The card grows downward; no modal. Multiple cards can be expanded independently.
- **Max four personality tags per card.** If Spec 4 returns more, Spec 5 truncates to the first four.
- **Set-level italic banner for set-wide context.** Used for inexact match, rules answers, and similar. Separate from per-card inexact-match banners.
- **Error card replaces the set.** Per-card errors are not a concept; recommendation failure is treated at the set level.
- **Retry replays the same query bundle.** Dial, sourcebooks, prompt text, and conversation context are unchanged by the retry.
- **Component sheets are the source of truth.** `spell-card.html`, `set-message.html`, and `error-card.html` were thoroughly designed during Spec 7. Their states, structure, type, colour, and affordances are canonical. Prose defers to the sheets in every case; disagreement means the prose is wrong and gets updated.
