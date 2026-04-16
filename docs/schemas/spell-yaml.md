# Spell YAML Schema

One YAML file per spell, named by slug: `database/spells/{slug}.yaml`. The file uses frontmatter + markdown body. Frontmatter carries all structured fields; body is the human-readable spell description rendered as markdown.

## Naming and casing

- All field names are **camelCase** throughout authored surfaces (YAML, API JSON).
- DB columns follow Laravel's default snake_case; Eloquent models translate at the boundary.
- Slugs are lowercase, hyphenated: `fireball`, `mage-armor`, `tashas-hideous-laughter`.

## Required fields

Every spell YAML includes every frontmatter key. Fields that don't apply to a given spell are explicitly null or empty, never omitted. This keeps `YamlSchemaTest` simple and makes diffs uniform.

## Frontmatter shape

```yaml
slug: string                    # lowercase, hyphenated, matches filename
name: string                    # display name
level: int                      # 0 for cantrips, 1-9 for leveled spells
school: School                  # enum
castingTime: string             # free text ("1 action", "10 minutes", "1 reaction, which you take when...")
range: string                   # free text ("150 feet", "Self", "Touch", "Self (30-foot cone)")
components:
  verbal: bool
  somatic: bool
  material: string | null       # null if no material component; otherwise the component text
duration: string                # free text display form ("Instantaneous", "Concentration, up to 1 hour")
qualifiers: Qualifier[]         # extensible authored array; v1 vocabulary: concentration, ritual
classes: SourceClass[]          # classes that can learn this spell; "wizard" always present for our corpus
damage: DamageEntry[]           # empty if no damage; multiple entries for multi-type damage
conditions: Condition[]         # empty if spell imposes no conditions
targeting: Targeting            # enum: point, self, creature, creatures, area, touch
areaShape: AreaShape | null     # null if not an area spell
areaSize: string | null         # free text ("20 feet", "60-foot cone")
savingThrow:                    # null if no save
  ability: AbilityScore
attackRoll: AttackRoll | null   # null if no attack roll; otherwise melee/ranged
combatRoles: CombatRole[]       # in-combat editorial tags; empty allowed
utilities: OutOfCombatUtility[] # out-of-combat editorial tags; empty allowed
sources:                        # at least one entry
  - code: SourceCode
    page: int
personalityBlurb: string        # short flavor text generated in Stage C
```

## Derived (not authored) fields

Two enums are populated by the import action from authored free-text fields and stored on the DB row, but never appear in YAML:

- `actionEconomy` (`ActionEconomy` enum) — derived from `castingTime` by `CastingTimeParser`. Mapping: `"1 action"` → `action`, `"1 bonus action"` → `bonusAction`, `"1 reaction, ..."` → `reaction`, `"1 minute"` → `minute`, `"10 minutes"` → `tenMinutes`, `"1 hour"` → `hour`, anything else → `longer`.
- `durationCategory` (`DurationCategory` enum) — derived from `duration` by `DurationParser`. Concentration is **not** part of this enum; it lives in `qualifiers`. Mapping: leading `"Concentration, "` is stripped first, then: `"Instantaneous"` → `instantaneous`, `"Until dispelled"` → `untilDispelled`, `"Permanent"` → `permanent`, anything with a time value → `timed`.

Authors do not stamp these. The `SpellsImportAction` writes them to `action_economy` and `duration_category` columns at import time.

## DamageEntry

```yaml
dice: string                    # "8d6", "1d4+1"
type: DamageType                # enum
```

## Qualifiers

Cross-cutting authored flags. v1 vocabulary:

- `concentration` — spell requires concentration. Engine uses this for combo/conflict detection (one concentration spell at a time, conflicts with party concentration, breaks on damage, etc.). Note that `durationCategory` does not duplicate this signal — it captures only the time shape (`timed`, `untilDispelled`, etc.) after the concentration prefix is stripped.
- `ritual` — spell can be cast as a ritual (10 minutes added, no slot cost).

The array shape is preserved for forward extension; new qualifiers can be added by extending the `Qualifier` enum without schema churn.

## What structured fields encode vs. what lives in prose

The frontmatter encodes **classification surface** — the fields the Stage D engine uses to match prompts to spells. Resolution mechanics (upcast scaling, save-for-half, attack bonuses, exact targeting rules) live in the markdown body as authored prose. Rule-of-thumb: if it affects "does this spell answer the user's prompt?" it's a structured field; if it affects "how do I actually run this spell at the table?" it's in the body.

## Combat vs. utility tagging

`combatRoles` and `utilities` are the two editorial classification arrays that drive matching. `combatRoles` answers "what role does this spell play in a combat encounter?" (e.g. `areaDamage`, `control`, `defense`). `utilities` answers "what purposes does it serve outside combat?" (e.g. `exploration`, `social`, `investigation`). A spell can have entries in both, one, or neither.

## Body

The markdown body contains the full spell description as authored/scraped. Supported: headings, paragraphs, emphasis, inline code, tables, lists. Nothing in the body is machine-parsed — it's for display.

## Nullability convention

Use explicit `null` (YAML `null` or `~`) rather than omitting the key. Example:

```yaml
attackRoll: null
savingThrow: null
areaShape: null
```

## Review tracking

Review state lives in `database/spells/.reviewed-slugs`, one slug per line. The `spells:review` walker appends to this file on approval and reads it on startup to skip already-reviewed spells. YAML files themselves contain no process metadata — they're pure spell data.

## Canonical examples

See `database/spells/fireball.yaml` (damage + area), `database/spells/mage-hand.yaml` (cantrip, utility, no damage), and `database/spells/alarm.yaml` (ritual, no damage, long duration) for reference shapes exercising every null/empty combination. These also serve as PR 2.1's test fixtures.
