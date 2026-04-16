# Enum Vocabularies

Canonical value lists for every enum referenced by the spell YAML schema and the API. All enum values are **camelCase strings** (per `docs/plans/implementation-plan.md` naming convention); backed in PHP as string-backed enums under `app/Domain/Spells/Enums`.

## Authored vs. derived

Most enums are **authored** — values appear directly in YAML frontmatter. Two are **derived** by the import action from authored free-text fields:

- `ActionEconomy` — produced from `castingTime` by `CastingTimeParser`
- `DurationCategory` — produced from `duration` by `DurationParser`

The enum types exist in both cases; only the authoring story differs.

---

## School

Arcane and divine schools of magic. Bounded by 5e rules.

Values: `abjuration`, `conjuration`, `divination`, `enchantment`, `evocation`, `illusion`, `necromancy`, `transmutation`

## SourceCode

Sourcebooks in scope for the v1 wizard corpus.

Values: `phb`, `xge`, `tce`, `scag`, `ftd`, `aag`

## SourceClass

Classes that can learn a spell. Every spell in our corpus includes `wizard`; other values flag cross-class availability.

Values: `wizard`, `sorcerer`, `cleric`, `druid`, `bard`, `paladin`, `ranger`, `warlock`, `artificer`

## Qualifier

Cross-cutting authored flags. Array shape is extensible; v1 vocabulary is minimal.

Values: `concentration`, `ritual`

## DamageType

Bounded by 5e rules.

Values: `acid`, `bludgeoning`, `cold`, `fire`, `force`, `lightning`, `necrotic`, `piercing`, `poison`, `psychic`, `radiant`, `slashing`, `thunder`

## Condition

Bounded by 5e rules. Exhaustion is included as a single value; the engine ignores exhaustion *level* for classification purposes.

Values: `blinded`, `charmed`, `deafened`, `exhaustion`, `frightened`, `grappled`, `incapacitated`, `invisible`, `paralyzed`, `petrified`, `poisoned`, `prone`, `restrained`, `stunned`, `unconscious`

## Targeting

How the caster selects what the spell affects.

Values: `point`, `self`, `creature`, `creatures`, `area`, `touch`

## AreaShape

The region an area spell occupies. Nullable — non-area spells use `null`.

Values: `sphere`, `cube`, `cone`, `line`, `cylinder`, `wall`, `null`

## AbilityScore

Used for `savingThrow.ability`. Bounded by 5e rules.

Values: `strength`, `dexterity`, `constitution`, `intelligence`, `wisdom`, `charisma`

## AttackRoll

Type of attack roll for spells that use one. Nullable — save-based or no-roll spells use `null`.

Values: `melee`, `ranged`, `null`

## ActionEconomy (derived)

Casting-time category. Produced by `CastingTimeParser` from the authored `castingTime` string.

Values: `action`, `bonusAction`, `reaction`, `minute`, `tenMinutes`, `hour`, `longer`

Mapping rules:
- `"1 action"` → `action`
- `"1 bonus action"` → `bonusAction`
- `"1 reaction, …"` → `reaction`
- `"1 minute"` → `minute`
- `"10 minutes"` → `tenMinutes`
- `"1 hour"` → `hour`
- anything else → `longer`

## DurationCategory (derived)

Duration time-shape category. Produced by `DurationParser` from the authored `duration` string. Concentration is **not** represented here — it lives in `qualifiers`.

Values: `instantaneous`, `timed`, `untilDispelled`, `permanent`

Mapping rules (after stripping any leading `"Concentration, "` prefix):
- `"Instantaneous"` → `instantaneous`
- `"Until dispelled"` → `untilDispelled`
- `"Permanent"` → `permanent`
- anything with a time value → `timed`

---

## CombatRole

Editorial tags describing what role a spell plays in combat. A spell can carry zero, one, or many — a spell like Wall of Fire is `areaDamage` + `control`; Greater Invisibility is `obfuscate` + `escape`. Multi-tag authoring is the norm, not the exception.

Values:

| Value                | Meaning                                                                                   | Example spells                                                     |
| -------------------- | ----------------------------------------------------------------------------------------- | ------------------------------------------------------------------ |
| `areaDamage`         | Damage multiple targets in a region                                                       | Fireball, Cloudkill, Ice Storm                                     |
| `singleTargetDamage` | Focused damage on one target                                                              | Chromatic Orb, Disintegrate, Scorching Ray                         |
| `sustainedDamage`    | Damage ticking over multiple rounds, typically concentration                              | Flaming Sphere, Moonbeam, Cloud of Daggers                         |
| `control`            | Deny position, action, or targets outright                                                | Hold Person, Web, Wall of Force, Hypnotic Pattern                  |
| `debuff`             | Direct debilitating effect on enemies — stat penalties, imposed conditions                | Bane, Bestow Curse, Slow, Blindness/Deafness                       |
| `hinder`             | Get in the way of enemies' efforts without directly debilitating them                     | Grease, Entangle, Wall of Stone as obstacle                        |
| `buff`               | Numerical or mechanical boost on allies                                                   | Bless, Haste, Enlarge                                              |
| `expedite`           | Speed up or grant extra actions/movement to allies                                        | Haste, Expeditious Retreat                                         |
| `defend`             | Reduce or prevent incoming damage to self or allies                                       | Shield, Absorb Elements, Mage Armor, Stoneskin                     |
| `heal`               | Restore HP or remove combat-relevant conditions                                           | Vampiric Touch, Healing Word (if cross-class)                      |
| `move`               | Reposition self or allies                                                                 | Misty Step, Dimension Door, Fly in combat                          |
| `escape`             | Get out of a bad spot specifically — disengage, teleport away, break grapples             | Misty Step (also `move`), Dimension Door, Expeditious Retreat      |
| `summon`             | Create combat allies                                                                      | Find Familiar, Conjure Elemental, Summon X                         |
| `counter`            | Shut down enemy magic                                                                     | Counterspell, Dispel Magic                                         |
| `transform`          | Change the physical form of a creature or object in combat                                | Polymorph, Enlarge/Reduce, Gaseous Form                            |
| `obfuscate`          | Make attackers miss or target wrong via perception — covers blur, invisibility, darkness  | Blur, Mirror Image, Greater Invisibility, Darkness                 |
| `deceive`            | Create a false impression — make something appear to be something other than it is        | Silent Image, Phantasmal Force, Disguise Self (in combat)          |
| `sense`              | Detect or perceive otherwise-hidden information during combat                             | See Invisibility, True Seeing, Detect Magic (in combat)            |
| `alert`              | Notify of threats or changes in the battlefield                                           | Arcane Eye, Clairvoyance (in combat)                               |
| `communicate`        | Convey information to allies during combat                                                | Message, Telepathic Bond                                           |

Guidance for authors:
- `debuff` vs `hinder`: `debuff` directly debilitates a target (Bane imposes -1d4); `hinder` obstructs without touching the target itself (Grease makes the floor slippery). Many spells are both.
- `move` vs `escape`: `move` is any repositioning; `escape` specifically addresses getting out of a bad spot. Misty Step is both.
- `defend` vs `obfuscate`: `defend` reduces damage taken (Shield, Stoneskin); `obfuscate` makes enemies target wrong (Blur, Invisibility). Some spells do both.
- `obfuscate` vs `deceive`: `obfuscate` hides the truth (can't see me / can't see anything); `deceive` creates false information (sees something that isn't there / believes something untrue). Invisibility is `obfuscate`; Silent Image is `deceive`. Many illusions are both.

## OutOfCombatUtility

Editorial tags describing what non-combat actions a spell performs. Same multi-tag philosophy as `combatRoles`. Verb-form values mirror the shape of player prompts ("I need to sneak past the guards" → `obfuscate`; "I need to convince the noble" → `influence`), which primes the matcher toward spells that express those actions as solutions.

Values:

| Value         | Meaning                                                                 | Example spells                                                  |
| ------------- | ----------------------------------------------------------------------- | --------------------------------------------------------------- |
| `explore`     | Overcome terrain, vision, or environmental obstacles                    | Light, Feather Fall, Water Breathing, Levitate                  |
| `influence`   | Sway a creature's feelings, opinions, or commands                       | Charm Person, Suggestion, Friends                               |
| `deceive`     | Create a false impression — object, voice, identity, memory             | Disguise Self, Silent Image, Modify Memory, Seeming             |
| `obfuscate`   | Hide self, ally, or truth from detection                                | Invisibility, Nondetection, Pass Without Trace, Silence         |
| `communicate` | Remote messaging across distance                                        | Message, Sending, Telepathic Bond                               |
| `travel`      | Long-distance or cross-planar movement                                  | Teleport, Dimension Door, Tree Stride, Plane Shift              |
| `learn`       | Gather facts — divination, detection, identification                    | Detect Magic, Identify, Locate Object, Divination, Commune      |
| `create`      | Bring new objects, creatures, food, or shelter into being               | Unseen Servant, Mage Hand, Create Food and Water, Find Familiar |
| `shape`       | Alter existing materials or terrain at scale                            | Mold Earth, Shape Water, Fabricate, Stone Shape, Move Earth     |
| `heal`        | Restore HP, cure conditions, remove curses (out of combat)              | Lesser Restoration, Greater Restoration, Remove Curse           |
| `ward`        | Prep-time protective magic, traps, and alarms                           | Alarm, Glyph of Warding, Leomund's Tiny Hut, Private Sanctum    |

Guidance for authors:
- `obfuscate` vs `deceive` (same distinction as in CombatRole): `obfuscate` hides what is actually there (Invisibility, Pass Without Trace); `deceive` projects what isn't there (Silent Image, Disguise Self). Some spells are both.
- `create` vs `shape`: `create` produces something new from magical nothing (Unseen Servant, Create Food and Water); `shape` alters existing matter (Mold Earth, Stone Shape, Fabricate).
- `influence` is narrow — sway a mind directly. Illusions that convince through deception go in `deceive`, not `influence`.
- `heal` on this enum means out-of-combat use (Lesser Restoration curing a disease); combat healing is tagged on `combatRoles`.

## Authoring escape hatch

Spells that fit no tag in a given array use an empty array, not a placeholder value. A spell with `combatRoles: []` simply doesn't participate in combat-role matching. This keeps the vocabulary clean.

## Total enum count

Fourteen enums ship in PR 2.2:

1. School
2. SourceCode
3. SourceClass
4. Qualifier
5. DamageType
6. Condition
7. Targeting
8. AreaShape
9. AbilityScore
10. AttackRoll
11. ActionEconomy (derived)
12. DurationCategory (derived)
13. CombatRole
14. OutOfCombatUtility
