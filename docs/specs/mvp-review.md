# MVP Spec Review — specs 1–8 + master

> A cross-spec audit tracking the state of the MVP bundle. The original audit covered specs 1–7 and found three critical gaps, six cross-spec inconsistencies, and a long list of unasked MVP concerns. This file tracks what's been resolved and what remains.
>
> Last updated: 2026-04-14. See [00-index.md](00-index.md) for the canonical spec map.

---

## Status Summary

- **Critical gaps identified in the original audit:** 4. **Resolved: 3. Deferred: 1 (LLM provider — to implementation planning).**
- **Cross-spec inconsistencies identified:** 7. **Resolved: 7.**
- **Unasked MVP concerns:** ~30. **Addressed in the spec phase: audience, accessibility baseline, small UX polish items. Deferred to a future spec: example prompts / first-run onboarding. Deferred to implementation planning: error taxonomy, storage inventory, LLM stack, testing strategy, framework / API contract, data seeding. Out of scope for the personal-use MVP: rate limiting, moderation, cost controls, analytics, observability, licensing, shareable URLs, i18n, long-transcript scroll.**

The spec set is complete for the personal-use MVP. Every originally unanswered concern now has a clear disposition: resolved, deferred to a named phase, deferred to a named future spec, or out of scope for personal use.

---

## 1. Critical gaps — status

### 1.1 Spec 5 (Results & Spell Cards) — ✅ Resolved

Spec 5 now exists at [05-results-and-spell-cards.md](05-results-and-spell-cards.md). It defers to the component sheets (`spell-card.html`, `set-message.html`, `error-card.html`) as the visual source of truth and adds the set-level decisions the sheets didn't express: three cards per set, single vertical column, parchment-skeleton load, inline expansion, dedicated error card, sourcebook code in the meta line.

### 1.2 Sourcebook Selector — ✅ Resolved

Spec 8 now exists at [08-sourcebook-selector.md](08-sourcebook-selector.md). Header-level icon + count control, five books enabled by default, at-least-one enforced, localStorage persistence. The three-letter codes (PHB/XGE/TCE/FTD/SCC) are locked as canonical in the master spec and used everywhere downstream. Spec 2's mermaid, which previously pointed at a non-existent "Spec 6: Sourcebook Selector," now correctly points at Spec 8.

### 1.3 LLM provider / integration — ⏸ Deferred to implementation planning

Unchanged from the original audit. Neither provider, streaming posture, nor prompt-versioning approach is specified in any spec. By agreement (2026-04-14), this is out of scope for the spec phase and is a load-bearing decision for implementation planning. Downstream implications to track when it's picked up:

- Streaming vs. blocking changes Spec 5's loading section (currently assumes non-streaming: three skeletons resolve together).
- Error taxonomy and retry budget are referenced by Spec 7 but unowned.
- Prompt templates and versioning affect Spec 4's parse/explain steps and every recommendation output.
- Cost budget per conversation caps the round-limit design but is not currently quantified.

### 1.4 Dial layout drift between Specs 6 and 7 — ✅ Resolved

Spec 6 was rewritten to the linear five-rune row design locked in Spec 7. The casting circle is now framed as an atmospheric companion in both specs. The polar-arc mock is archived at `mockups/archive/spec-06-canonical-polar-arc.html`; `mockups/spec-06-canonical.html` is the linear row. The `whimsy-dial.html` component sheet matches.

---

## 2. Cross-spec inconsistencies — status

| # | Issue | Status |
|---|---|---|
| 2.1 | Sourcebook codes (master: "Fizban / Strixhaven"; Spec 2: "FTD / SCC") | ✅ Resolved — three-letter codes locked in master (line 197), used canonically everywhere. |
| 2.2 | "5 results" vs "5 rounds" naming collision | ✅ Resolved — Spec 5 commits to three cards per set (not five), removing the collision entirely. |
| 2.3 | Rules Q&A output (Spec 4 "cards optional" vs Spec 7 "no cards") | ✅ Resolved — Spec 4 now states **no spell cards follow** for rules questions; Spec 5 mirrors this in its set-level variants table. |
| 2.4 | Spec numbering map (three different "Spec 6"s) | ✅ Resolved — [00-index.md](00-index.md) is the canonical map. Mermaid diagrams across Specs 2, 3, 4 updated to match. |
| 2.5 | Spec 4 treats conversation mode as "future" | ✅ Resolved — Spec 4 now names Spec 7 as the active consumer in its overview, step 1, and out-of-scope section. |
| 2.6 | Stale-dial indicator (Spec 6 says none; Spec 7 has one) | ✅ Resolved — Spec 6's "No stale-dial indicator" Resolved Decision explicitly cross-references Spec 7 as the owner of the conversation-context hint. |
| 2.7 | localStorage ownership scattered across specs | ✅ Mostly resolved — Spec 1 (theme), Spec 6 (dial), Spec 8 (sourcebooks) each say they use localStorage. Spec 7 forbids it for conversation history. No single "storage inventory" spec, but each control now names its own mechanism. Low priority for MVP. |

---

## 3. Unasked MVP concerns — status

Grouped by disposition. None of these are spec-internal contradictions; they are decisions none of the specs originally owned.

### ✅ Resolved in the spec phase

- **Audience.** Personal use. Locked in master spec §MVP Audience (2026-04-14). Public release is planned separately as its own phase. Rate limiting, moderation, cost controls, analytics, observability, and licensing are therefore out of scope for this phase; they become load-bearing only if audience changes.
- **Accessibility baseline.** WCAG 2.1 AA, keyboard-complete, blanket `prefers-reduced-motion` respect, casting circle decorative (`aria-hidden`), focus stays on the prompt dock after submit with `aria-live` turn announcement. Defined in master spec §Accessibility Baseline (2026-04-14) and inherited by Specs 1, 5, 6, 7, 8.
- **Sourcebook canonical format.** Slash-delimited (`PHB/XGE/TCE/FTD/SCC`) everywhere. Master spec, Spec 2, Spec 8 aligned (2026-04-14).

### ⏸ Deferred to implementation planning

- **Error taxonomy.** Specs know how errors should be expressed and handled (dedicated error card per Spec 5, retry affordance, live-region announcement). The exact classes — timeout vs. upstream vs. rejected vs. rate-limited — and their structured shape are implementation-phase concerns. Spec 7 references the categories by name so the handlers exist; their precise membership is deferred.
- **Storage inventory.** Each control names its own storage: theme (Spec 1), dial (Spec 6), sourcebooks (Spec 8) in `localStorage`; conversation (Spec 7) session-only in memory. Whether this consolidates into a single storage module, how keys are namespaced, and migration posture are implementation concerns.
- **LLM provider / streaming / prompt-versioning.** See §1.3.
- **Spell data storage.** YAML-for-authoring vs. DB-at-runtime. Implementation planning.

### Out of scope for personal-use MVP (revisit if audience changes)

- **Authentication / accounts.** Anonymous-only assumed. Header reserves a login slot (Spec 1) but nothing implements it.
- **Rate limiting, moderation, cost controls, analytics, observability, licensing/publication.** All gated by audience; see §MVP Audience.
- **Content refresh cadence.** No scheduled review for LLM-derived tags yet.

### Remaining small UX items — resolved

- **URL / shareable state.** ✅ Not required for MVP (2026-04-14). Landing and conversation URLs do not need to be bookmarkable or linkable in the personal-use phase.
- **First-run onboarding / example prompts.** ⏭ Deferred to a future spec (2026-04-14). Example prompts will be authored into their own spec rather than folded into an existing one. Listed as a future spec in [00-index.md](00-index.md).
- **Header contents.** ✅ No nav links in the MVP header (2026-04-14). The header carries the theme toggle, the sourcebook selector (Spec 8), and the reserved — but unimplemented — login slot called out in Spec 1. Nothing else.
- **"New conversation" placement.** ✅ Resolved by the Spec 7 canonical mock (`mockups/spec-07-canonical.html` and its Spec 8 extension). The button sits in the top-right stack above the rounds label.
- **Internationalization.** ✅ English-only for the personal-use MVP (2026-04-14). Revisit with any public-release phase.
- **Long-transcript scroll behaviour.** ✅ Not needed (2026-04-14). The Spec 7 five-round cap keeps transcripts short enough that sticky headers, jump-to-turn, or other navigation aids are unnecessary.

### Engineering — deferred to implementation planning

- **Testing strategy.** ⏸ Implementation planning (2026-04-14). Non-trivial for LLM-driven pipelines; the strategy is chosen alongside the LLM stack.
- **Framework choices / API contract.** ⏸ Implementation planning (2026-04-14). Laravel + Vue + Tailwind named in master; the precise contract and DB schema are locked in during build planning.
- **Data seeding for dev/test.** ⏸ Implementation planning (2026-04-14). Local-development story for spell data lives with the stack choice.

---

## 4. Shortest path to "ready to build"

Original audit flagged three load-bearing decisions. Status:

1. ~~Write (or fold-into-7) Spec 5.~~ ✅ Done.
2. ~~Pick an audience for MVP.~~ ✅ Personal use (master spec §MVP Audience).
3. **Pick the LLM stack** — deferred to implementation planning.

With audience and accessibility resolved, the specs are structurally complete. Remaining open items are either deferred to implementation planning (§3 ⏸), out of scope for personal use (§3 out-of-scope), or minor polish (§3 remaining).

---

## 5. What looks good

Retained from the original audit, and reinforced by the cleanup round:

- **Data-layer split** (Spec 2 mechanical vs. Spec 3 personality) is clean and extensible.
- **Spec 4's diversify-not-top-N** is the right instinct for the product's feel.
- **Spec 7's round cap** serves three purposes (product focus + cost + rate limit) with one mechanism.
- **Component sheets as source of truth for Spec 5** — the spell-card / set-message / error-card sheets predated Spec 5's prose and are canonical for that spec. This is a Spec-5-specific arrangement, not a project-wide convention; other specs may lead with prose and have their sheets follow (e.g. Spec 8's sheet is being authored after the prose).
- **00-index.md** itself pins every spec number to its canonical title and future specs to their next-available number; removes a whole class of drift possible over the previous audit.
