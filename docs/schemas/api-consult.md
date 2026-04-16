# `POST /api/consult` — Request / Response Schema

Single endpoint the product exposes. Handles both initial prompts and conversation follow-ups. Server is stateless regarding user sessions but maintains a short-lived Redis cache of prior rounds so the client sends IDs rather than full payloads.

## Request

```json
{
  "prompt": "I want something to keep the party safe while they rest",
  "whimsy": "Balanced",
  "sourcebooks": ["phb", "xge"],
  "history": ["01HMZ7A3K4...", "01HMZ7F9P2..."]
}
```

| Field         | Type                   | Required | Notes                                                                                  |
| ------------- | ---------------------- | -------- | -------------------------------------------------------------------------------------- |
| `prompt`      | string                 | yes      | Non-empty. Max length 500 chars (landing or follow-up).                                |
| `whimsy`      | enum string            | yes      | One of `"Tactical"`, `"Balanced"`, `"Creative"`, `"Theatrical"`, `"Chaotic"`.          |
| `sourcebooks` | string[]               | yes      | Non-empty. Sourcebook codes (e.g. `"phb"`, `"xge"`, `"tce"`). Canonical list TBD.       |
| `history`     | string[]               | yes      | Ordered list of `roundId`s from prior turns. `[]` for a first-turn request. Max 5.     |

Validation failures return `type: "validation"` (see Error shape below).

## Response envelope

Every successful response:

```json
{
  "data": { ... },
  "meta": {
    "requestId": "01HMZ...",
    "modelVersion": "gemma-4-26b-a4b-it",
    "timingMs": 1240
  }
}
```

The `meta` envelope is present from day one but carries non-functional diagnostics. Clients may ignore it.

## `data` shapes

`data.type` is the discriminator. Two successful variants exist.

### Recommendations

```json
{
  "type": "recommendations",
  "roundId": "01HMZ...",
  "round": 1,
  "recommendations": [ SpellCard, SpellCard, SpellCard ],
  "message": null
}
```

- Exactly three `SpellCard`s.
- `message` is reserved for an optional framing line (e.g. "Here are three safer options for your rest scenario"); may be `null`.

### Answer (rules / reference Q&A)

```json
{
  "type": "answer",
  "roundId": "01HMZ...",
  "round": 2,
  "message": "Fireball requires you to see the point of origin, so casting it from around a corner isn't allowed.",
  "referencedSpells": ["fireball"]
}
```

- `referencedSpells` is `string[]` of spell slugs the answer talks about; may be empty.
- The engine classifies each turn's prompt as recommendations-vs-answer. Classifier lands in Stage H; Stage D assumes every response is `type: "recommendations"`.

## `SpellCard` shape

```json
{
  "slug": "fireball",
  "name": "Fireball",
  "level": 3,
  "school": "Evocation",
  "castingTime": "1 action",
  "range": "150 feet",
  "components": {
    "verbal": true,
    "somatic": true,
    "material": "a tiny ball of bat guano and sulfur"
  },
  "duration": "Instantaneous",
  "concentration": false,
  "classes": ["wizard", "sorcerer"],
  "damage": [{ "dice": "8d6", "type": "fire" }],
  "conditions": [],
  "sources": [{ "code": "phb", "page": 241 }],
  "blurb": "A classic opener — loud, bright, and impossible to ignore.",
  "explanation": "You asked for something that protects during a rest; Fireball doesn't fit, but its sibling Alarm does — see card 2."
}
```

Tags (combat roles, utilities, targeting, action economy) are stored on the server but not exposed on the card by default.

## Round caching

Every `recommendations` and `answer` response writes a round record to Redis under `consult:round:{roundId}` with TTL = 2 hours. The cached record stores all inputs and outputs for complete conversation reconstruction:

```json
{
  "prompt": "...",
  "whimsy": "Balanced",
  "sourcebooks": ["phb", "xge"],
  "response": { /* the full data shape above */ }
}
```

A flat keyspace (`consult:round:{uuid}`) is used. No user scoping — cross-user exposure is not a concern for this product.

On each request, the server rehydrates every `roundId` in `history` from Redis to build the conversation context. Any miss triggers `HistoryExpired` (see below).

## Error shape

Errors share the envelope but `data.type` signals the class:

```json
{
  "data": {
    "type": "timeout" | "upstreamUnavailable" | "validation" | "historyExpired" | "unknown",
    "message": "human-readable summary",
    "details": { /* optional per-type payload */ }
  },
  "meta": { ... }
}
```

| Type                  | HTTP status | When                                                         |
| --------------------- | ----------- | ------------------------------------------------------------ |
| `validation`          | 422         | Request body fails validation (missing prompt, bad whimsy, history too long, etc.). |
| `historyExpired`      | 410         | One or more `roundId`s in `history` missing from Redis.      |
| `timeout`             | 504         | Upstream LLM call exceeded configured timeout.               |
| `upstreamUnavailable` | 503         | Upstream LLM returned non-2xx or connection failed.          |
| `unknown`             | 500         | Unhandled exception. `details` does not leak stack traces.   |

`validation` `details` includes a field-by-field error map compatible with Laravel's default validator output.

## Stage mapping

- **Stage D** implements the envelope, `recommendations` responses, round caching, and all error types except the classifier-gated `answer` path.
- **Stage H** adds the classifier that routes to either `recommendations` or `answer`, plus the `answer` response shape and `referencedSpells` field.

## Canonical examples

Single-turn request:

```json
{ "prompt": "something dramatic for a boss fight", "whimsy": "Theatrical", "sourcebooks": ["phb", "xge"], "history": [] }
```

Follow-up request (Stage H):

```json
{ "prompt": "what about for roleplay instead?", "whimsy": "Theatrical", "sourcebooks": ["phb", "xge"], "history": ["01HMZ...round1"] }
```

Follow-up rules question (Stage H):

```json
{ "prompt": "does the second one require concentration?", "whimsy": "Theatrical", "sourcebooks": ["phb", "xge"], "history": ["01HMZ...round1", "01HMZ...round2"] }
```
