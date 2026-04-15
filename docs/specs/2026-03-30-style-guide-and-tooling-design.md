# Style Guide & Tooling Design Spec

**Date:** 2026-03-30
**Branch:** `feature/style-guide`
**Status:** Draft

---

## Overview

Establish the project's code style guide, configure enforcement tooling (Pint, Larastan, Rector), add unified composer commands, and update the CI pipeline — all delivered as a single coherent unit.

This spec covers two coupled deliverables:
1. **`docs/style-guide.md`** — the authoritative style reference for developers
2. **Tooling & CI** — configs, composer commands, and GitHub Actions to enforce the rules

---

## Style Guide Rules

### General PHP Conventions

- **Strict types** on every PHP file: `declare(strict_types=1)`
- **Full type declarations** on all parameters, return types, and properties
- **Early returns** over `else` — reduce nesting
- **`match` over `switch`** for simple value mapping; `switch` only when arms have multiple statements with side effects
- **Single quotes** for simple strings, **double quotes** with `{$var}` interpolation, `sprintf` for complex formatting
- **PHPDoc only when types can't express it** — generics (`Collection<int, User>`), complex arrays, or "why" explanations. No PHPDoc that duplicates the type signature.
- **No comments that describe "what"** — write self-documenting code. Comments explain "why" only.

### Array Formatting

- **Every array item on its own line**, including nested arrays expanded vertically
- **Trailing commas** on all multi-line arrays, arguments, parameters, and match arms
- Single-item arrays may be inline but multi-line is preferred

```php
// Required for 2+ items
return [
    'name' => [
        'required',
        'string',
        'max:255',
    ],
    'email' => [
        'required',
        'string',
        'email',
    ],
];

// Single-item: inline allowed, multi-line preferred
'status' => ['required'],
```

### Controllers: Single-Action Only

Every controller is an invokable `__invoke()` controller. No resource controllers, no multi-method controllers.

A CRUD resource becomes separate controller files:

```
app/Http/Controllers/Posts/
    IndexPostsController.php
    CreatePostController.php
    StorePostController.php
    ShowPostController.php
    EditPostController.php
    UpdatePostController.php
    DestroyPostController.php
```

Naming convention: **Verb + Model + Controller** (plural where the action returns multiple).

Routes reference the class directly:

```php
Route::get('/posts', IndexPostsController::class)->name('posts.index');
Route::get('/posts/create', CreatePostController::class)->name('posts.create');
Route::post('/posts', StorePostController::class)->name('posts.store');
Route::get('/posts/{post}', ShowPostController::class)->name('posts.show');
Route::get('/posts/{post}/edit', EditPostController::class)->name('posts.edit');
Route::put('/posts/{post}', UpdatePostController::class)->name('posts.update');
Route::delete('/posts/{post}', DestroyPostController::class)->name('posts.destroy');
```

Controllers must be thin — delegate business logic to actions or services.

### Services and Actions

- **Actions** — single-purpose classes with an `execute()` method for atomic business operations. Named as verb phrases: `CreateUser`, `ProcessPayment`.
- **Services** — compose multiple actions into a feature's API surface. Named `{Feature}Service`. Used when a feature has enough surface area that developers benefit from seeing all capabilities in one place.
- **Simple cases** — controllers inject and call actions directly without a service wrapper.
- **Repository pattern** — not used. Eloquent directly, actions for business logic.

```php
// Service composes actions into a feature API
class UserService
{
    public function __construct(
        private readonly CreateUser $createUser,
        private readonly SendWelcomeEmail $sendWelcomeEmail,
        private readonly ProvisionDefaultWorkspace $provisionWorkspace,
    ) {}

    public function register(CreateUserData $data): User
    {
        $user = $this->createUser->execute($data);
        $this->sendWelcomeEmail->execute($user);
        $this->provisionWorkspace->execute($user);

        return $user;
    }
}
```

### Class Structure Ordering

Enforced by Pint's `ordered_class_elements` rule:

1. Traits (`use`)
2. Constants (public, protected, private)
3. Properties (public, protected, private)
4. Constructor
5. Destructor
6. Magic methods
7. Abstract methods (public, protected)
8. Static methods (public, protected, private)
9. Public methods
10. Protected methods
11. Private methods

### Model Conventions

Property and method ordering within models:

1. Traits
2. Constants
3. Configuration properties (`$table`, etc.)
4. Mass assignment (`$fillable` or PHP attributes)
5. Hidden/visible
6. Casts (method)
7. Default attributes
8. Relationships (ordered: `belongsTo`, `hasOne`, `hasMany`, `belongsToMany`, `morph*`)
9. Scopes
10. Accessors and Mutators
11. Custom methods
12. Boot/booted methods

Use PHP attributes (`#[Fillable]`, `#[Hidden]`, `#[ScopedBy]`) where supported.

### Naming Conventions

#### Classes

| Type | Convention | Example |
|---|---|---|
| Model | Singular PascalCase | `User`, `BlogPost`, `OrderItem` |
| Controller | Verb + Model + `Controller` | `IndexPostsController`, `StorePostController` |
| Form Request | `Store`/`Update` + Model + `Request` | `StoreUserRequest`, `UpdatePostRequest` |
| Resource | Model + `Resource` | `UserResource`, `PostResource` |
| Collection | Model + `Collection` | `UserCollection` |
| Policy | Model + `Policy` | `UserPolicy`, `PostPolicy` |
| Event | Past tense | `OrderShipped`, `UserRegistered` |
| Listener | Action phrase | `SendShipmentNotification`, `UpdateInventory` |
| Job | Verb phrase | `ProcessPodcast`, `SendWelcomeEmail` |
| Notification | Past tense/descriptor | `InvoicePaid`, `PasswordReset` |
| Middleware | `Ensure` prefix or descriptor | `EnsureTokenIsValid` |
| Exception | Descriptor + `Exception` | `InvalidOrderException` |
| Trait | Adjective or `Has`/`Can` prefix | `Notifiable`, `HasFactory` |
| Interface | Adjective or capability | `Authenticatable`, `ShouldQueue` |
| Enum | Singular PascalCase | `OrderStatus`, `UserRole` |
| Action | Verb phrase | `CreateUser`, `ProcessPayment` |
| Service | Feature + `Service` | `UserService`, `OrderService` |
| DTO | Concept + `Data` | `UserData`, `CreateUserData` |

#### Methods and Variables

| Context | Convention | Example |
|---|---|---|
| General method | camelCase verb phrase | `calculateTotal()`, `sendEmail()` |
| Relationship (singular) | camelCase singular | `user()`, `phone()` |
| Relationship (plural) | camelCase plural | `comments()`, `posts()` |
| Scope | `scope` + PascalCase | `scopeActive()`, `scopePublished()` |
| Boolean method | `is`/`has`/`can`/`should` prefix | `isAdmin()`, `hasPermission()` |
| General variable | camelCase | `$userName`, `$totalAmount` |
| Boolean variable | `is`/`has`/`can`/`should` prefix | `$isActive`, `$hasAccess` |
| Collection/array | Plural | `$users`, `$orderItems` |
| Single model | Singular | `$user`, `$orderItem` |

#### Database

| Element | Convention | Example |
|---|---|---|
| Table name | Plural snake_case | `users`, `order_items` |
| Pivot table | Singular alphabetical snake_case | `order_product`, `post_tag` |
| Column name | Singular snake_case | `first_name`, `is_active` |
| Foreign key | Singular table + `_id` | `user_id`, `order_item_id` |
| Boolean columns | `is_`/`has_` prefix | `is_active`, `has_verified_email` |
| Date columns | `_at` suffix | `published_at`, `expires_at` |
| Migration file | Timestamp + action + description | `create_users_table`, `add_phone_to_users_table` |

#### Routes

| Element | Convention | Example |
|---|---|---|
| URI | Plural kebab-case | `/blog-posts`, `/order-items` |
| Route name | Dot-separated | `blog-posts.index`, `users.show` |
| Route parameter | Singular camelCase | `{user}`, `{blogPost}` |
| Nested resources | Parent/child | `/posts/{post}/comments` |

#### Config and Environment

| Element | Convention | Example |
|---|---|---|
| Config file | Lowercase snake_case | `config/database.php` |
| Config key | snake_case with dots | `database.connections.mysql.host` |
| Environment variable | UPPER_SNAKE_CASE | `APP_NAME`, `DB_HOST` |

### Form Request Conventions

- **Array syntax** for validation rules (not pipe-delimited strings)
- Always implement `authorize()` even if it returns `true`
- Validation lives in Form Requests, not controllers
- Naming: `Store{Model}Request` / `Update{Model}Request`

### Migration Conventions

Column ordering in migrations:

1. Primary key (`$table->id()`)
2. Foreign keys
3. String/text columns
4. Numeric columns
5. Boolean columns
6. Date/time columns
7. JSON columns
8. `$table->timestamps()`
9. `$table->softDeletes()`

### Route Conventions

- Resource-style routes even with single-action controllers (maintain RESTful naming)
- Grouped routes with shared middleware
- Kebab-case URIs, dot-separated route names
- Single-action controllers referenced by class: `Route::get('/posts', IndexPostsController::class)`

### Blade and View Conventions

- Component files: kebab-case (`form-input.blade.php`)
- Component usage: `<x-form-input />`, `<x-ui.card />`
- Layouts: `layouts/app.blade.php`, `layouts/guest.blade.php`
- Blade components over `@include` where possible
- No PHP logic in Blade templates — prepare data in controllers/view composers

### PHP 8.3+ Features to Use

- **Enums** for fixed sets of related values (backed by string or int)
- **Readonly classes** for DTOs and value objects
- **Constructor property promotion** with `readonly` for injected dependencies
- **First-class callable syntax**: `$users->map($this->formatUser(...))`
- **`match` expressions** for value mapping
- **Named arguments** when improving readability of calls with many parameters

---

## Enforcement: Tooling vs Convention

| Rule | Enforced By |
|---|---|
| Code formatting (indentation, spacing, braces) | Pint |
| `declare(strict_types=1)` | Pint |
| `===` over `==` | Pint |
| Strict params (`in_array` etc.) | Pint |
| `void` return types | Pint |
| Class element ordering | Pint |
| Trailing commas | Pint |
| No superfluous PHPDoc | Pint |
| Dead code removal | Rector |
| Early returns | Rector |
| Type declaration tightening | Rector |
| PHP 8.3 modernization | Rector |
| Laravel-specific modernization | Rector (driftingly/rector-laravel) |
| Type safety (level 6) | Larastan |
| No debugging functions | Pest arch test |
| Controller suffix | Pest arch test |
| Model extends Eloquent | Pest arch test |
| Form Request extends/suffix | Pest arch test |
| Jobs implement ShouldQueue | Pest arch test |
| MySQL feature detection | Pest arch test |
| Type coverage 100% | Pest `--type-coverage` |
| Mutation score 70%+ (business logic) | Pest `--mutate` |
| Array formatting (one item per line) | Convention (code review) |
| Single-action controllers | Convention (code review) |
| Naming conventions | Convention (code review) |
| Service/Action patterns | Convention (code review) |
| Model property ordering | Convention (code review) |
| Migration column ordering | Convention (code review) |

---

## Tooling Configuration

### Pint (`pint.json`)

```json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "strict_comparison": true,
        "strict_param": true,
        "void_return": true,
        "nullable_type_declaration_for_default_null_value": true,
        "no_superfluous_phpdoc_tags": {
            "allow_mixed": true,
            "remove_inheritdoc": true
        },
        "ordered_class_elements": {
            "order": [
                "use_trait",
                "case",
                "constant_public",
                "constant_protected",
                "constant_private",
                "property_public",
                "property_protected",
                "property_private",
                "construct",
                "destruct",
                "magic",
                "phpunit",
                "method_public_abstract",
                "method_protected_abstract",
                "method_public_static",
                "method_protected_static",
                "method_private_static",
                "method_public",
                "method_protected",
                "method_private"
            ]
        },
        "trailing_comma_in_multiline": {
            "elements": [
                "arguments",
                "arrays",
                "match",
                "parameters"
            ]
        },
        "single_line_empty_body": true,
        "simplified_null_return": true,
        "array_indentation": true,
        "method_chaining_indentation": true,
        "multiline_whitespace_before_semicolons": {
            "strategy": "new_line_for_chained_calls"
        }
    }
}
```

### Larastan (`phpstan.neon`)

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/

    level: 6

    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
```

### Rector (`rector.php`)

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/bootstrap/cache',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
    ])
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    );
```

Note: `driftingly/rector-laravel` provides Laravel-specific rule sets. Its integration with `rector.php` will be determined during implementation based on the package's current API.

### Composer Commands

Existing commands (`lint`, `lint:check`, `test`) are preserved. New additions: `analysis` and `check`.

```json
{
    "scripts": {
        "lint": ["./vendor/bin/pint"],
        "lint:check": ["./vendor/bin/pint --test"],
        "analysis": ["vendor/bin/phpstan analyse"],
        "test": [
            "@php artisan config:clear --ansi",
            "@php artisan test"
        ],
        "check": [
            "./vendor/bin/pint --test",
            "vendor/bin/rector process --dry-run",
            "vendor/bin/phpstan analyse",
            "@php artisan config:clear --ansi",
            "@php artisan test",
            "./vendor/bin/pest --type-coverage --min=100"
        ]
    }
}
```

---

## CI Pipeline

### Trigger Matrix

| Job | push to main | PR to main |
|---|---|---|
| Quality checks (Pint + Rector + Larastan + tests + type coverage) | Yes | Yes |
| MySQL tests | No | Yes |
| Mutation testing (scoped to business logic) | No | Yes |

### Quality Checks Job

Steps (in order):
1. Setup PHP 8.3 with pcov
2. Cache + install Composer dependencies
3. Prepare environment (.env + key)
4. Pint check (`./vendor/bin/pint --test`)
5. Rector dry-run (`vendor/bin/rector process --dry-run`)
6. Larastan (`vendor/bin/phpstan analyse`)
7. Tests (`php artisan test`)
8. Type coverage (`./vendor/bin/pest --type-coverage --min=100`)

### MySQL Tests Job (PRs only)

Unchanged from current workflow. Runs `php artisan test --group=mysql` against MySQL 8.4 service container.

### Mutation Testing Job (PRs only)

Separate job running:
```bash
./vendor/bin/pest --mutate --parallel --covered-only --min=70 --class=App\\Services,App\\Models,App\\Actions
```

Requires pcov. Runs in parallel with the quality checks and MySQL jobs. The `--class` targets are the namespaces where business logic will live — the command runs cleanly even when those namespaces don't yet contain classes (it simply has nothing to mutate).

### Branch Protection

Main branch uses GitHub branch protection. All CI jobs must pass before merge.

---

## Style Guide Document Structure

The `docs/style-guide.md` file will be organized as:

1. **Enforcement Toolchain** — tools, what they enforce, how to run them, what's convention/code review
2. **General PHP Conventions** — strict types, type declarations, early returns, match, strings, comments
3. **Array Formatting** — one item per line, nested expansion, trailing commas
4. **Naming Conventions** — classes, methods, variables, database, routes, config
5. **Class Structure** — ordering rules
6. **Controllers** — single-action only, naming, routing
7. **Models** — property/method ordering, PHP attributes, relationships
8. **Services and Actions** — when to use each, composition pattern
9. **Form Requests** — array syntax, naming, authorize()
10. **Migrations** — column ordering
11. **Routes** — conventions, grouping
12. **Blade and Views** — component conventions
13. **PHP 8.3+ Features** — enums, readonly, constructor promotion, first-class callables

Each section marks which rules are tool-enforced vs convention. Tooling config details live in the config files themselves and are referenced, not duplicated.

---

## Additional Deliverables

- **Update `CLAUDE.md`** — replace the `## Style Guide` TODO section with a reference to `docs/style-guide.md` and add the new composer commands (`analysis`, `check`) to the Common Commands section

---

## Out of Scope

- **Rector as a periodic batch tool** — not applicable; enforced from day one via CI
- **Line coverage in CI** — add later when the test suite has substance
- **`arch()->preset()->laravel()`** — defer until validated with the current codebase
- **Strict types arch rule** — Pint handles this via `declare_strict_types`
- **Dusk/browser testing** — add only when JavaScript-driven UI requires it
- **Database compatibility detection** — already handled by existing arch tests
