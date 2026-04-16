# Project Conventions

## Controllers and Actions

- Controllers are thin HTTP wrappers exposing a single `__invoke` method.
- Actions hold the business logic and expose a single `execute` method.
- Controllers call Actions; Actions never call Controllers.
- Never collapse a Controller and its Action into one class.
- See `style-guide.md` for the full single-action controller and Action patterns.

## Naming Conventions

- **PascalCase** for class names.
- **camelCase** at all authored and wire-facing surfaces: YAML frontmatter keys, API request/response JSON, enum string values.
- **snake_case** at DB columns and migrations (Laravel default).
- Translation between the two happens at the Eloquent model boundary.
- Slugs are lowercase, hyphenated: `fireball`, `mage-armor`.
- For full style rules see `style-guide.md`.
