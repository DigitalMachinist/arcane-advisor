# Project Conventions

## Naming Conventions

- **PascalCase** for class names.
- **camelCase** at all authored and wire-facing surfaces: YAML frontmatter keys, API request/response JSON, enum string values.
- **snake_case** at DB columns and migrations (Laravel default).
- Translation between the two happens at the Eloquent model boundary.
- Slugs are lowercase, hyphenated: `fireball`, `mage-armor`.
- For full style rules see `style-guide.md`.
