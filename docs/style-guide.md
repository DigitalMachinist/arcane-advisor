# Style Guide

A prescriptive style guide for this Laravel 13 / PHP 8.3+ application. Rules are enforced by automated tools wherever possible. Convention-only rules are enforced through code review.

---

## 1. Enforcement Toolchain

Four tools enforce style and correctness automatically. Run them all at once with `composer run check`.

### Tools

| Tool | What It Enforces | How to Run |
|---|---|---|
| **Pint** | Code formatting, strict types, comparison operators, class ordering, trailing commas, PHPDoc cleanup | `composer run lint` (fix) / `composer run lint:check` (check only) |
| **Rector** | Dead code removal, early returns, type tightening, PHP 8.3 modernization, Laravel-specific refactoring | `vendor/bin/rector process` (fix) / `vendor/bin/rector process --dry-run` (check only) |
| **Larastan** | Static analysis at level 6 — type safety, undefined methods, incorrect return types | `composer run analysis` |
| **Pest** | Architectural rules, type coverage, mutation testing | `php artisan test` / `./vendor/bin/pest --type-coverage --min=100` / `./vendor/bin/pest --mutate` |

### Combined Check

```bash
composer run check
```

This runs Pint, Rector (dry-run), Larastan, the full test suite, and type coverage in sequence. Run it before pushing.

### Enforcement Matrix

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

Rules marked **Convention (code review)** are not machine-enforced. Reviewers must check these manually.

### Configuration Files

- Pint: `pint.json`
- Rector: `rector.php`
- Larastan: `phpstan.neon`
- Arch tests: `tests/Arch/ArchitectureTest.php`, `tests/Arch/DatabaseCompatibilityTest.php`

Do not duplicate tool config in this document. The config files are the source of truth.

---

## 2. General PHP Conventions

### `declare(strict_types=1)` on Every File (Pint)

Every PHP file begins with a strict types declaration. No exceptions.

```php
<?php

declare(strict_types=1);
```

### Full Type Declarations

All parameters, return types, and properties must have explicit type declarations. Type coverage is enforced at 100% by Pest.

```php
public function calculateDiscount(Order $order, float $rate): Money
{
    // ...
}
```

### Early Returns Over `else` (Rector)

Reduce nesting by returning early. Rector enforces this automatically.

```php
// Good
public function getDiscount(User $user): float
{
    if (! $user->isActive()) {
        return 0.0;
    }

    if ($user->isVip()) {
        return 0.20;
    }

    return 0.05;
}

// Bad
public function getDiscount(User $user): float
{
    if ($user->isActive()) {
        if ($user->isVip()) {
            return 0.20;
        } else {
            return 0.05;
        }
    } else {
        return 0.0;
    }
}
```

### `match` Over `switch`

Use `match` for simple value mapping. Use `switch` only when arms contain multiple statements with side effects.

```php
// Good: match for value mapping
$label = match ($status) {
    OrderStatus::Pending => 'Awaiting Payment',
    OrderStatus::Shipped => 'On Its Way',
    OrderStatus::Delivered => 'Delivered',
};

// Acceptable: switch when arms have multiple statements with side effects
switch ($event->type) {
    case 'order.placed':
        $this->notifyWarehouse($event);
        $this->chargePayment($event);
        break;
    case 'order.cancelled':
        $this->refundPayment($event);
        $this->restoreInventory($event);
        break;
}
```

### String Conventions

Single quotes for simple strings. Double quotes with `{$var}` for interpolation. `sprintf` for complex formatting.

```php
// Simple string
$status = 'active';

// Interpolation
$greeting = "Hello, {$user->name}";

// Complex formatting
$message = sprintf('Order #%d totals %s for %s', $order->id, $total, $user->email);
```

### PHPDoc: Only When Types Cannot Express It (Pint)

Pint removes superfluous PHPDoc automatically. Write PHPDoc only for generics, complex array shapes, or "why" explanations.

```php
// Good: generics that types cannot express
/** @return Collection<int, User> */
public function getActiveUsers(): Collection
{
    // ...
}

// Good: complex array shape
/** @param array{name: string, roles: list<string>} $data */
public function createUser(array $data): User
{
    // ...
}

// Bad: duplicates the type signature
/** @param string $name */
public function setName(string $name): void
{
    // ...
}
```

### Comments Explain "Why", Not "What"

Write self-documenting code. Comments exist only to explain reasoning that the code cannot convey.

```php
// Good: explains why
// Stripe requires amounts in cents, not dollars.
$amount = (int) ($order->total * 100);

// Bad: describes what the code already says
// Multiply total by 100
$amount = (int) ($order->total * 100);
```

---

## 3. Array Formatting

Every array with two or more items gets one item per line with a trailing comma. This is a convention enforced through code review.

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
```

Nested arrays expand vertically:

```php
$config = [
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
    ],
    'cache' => [
        'driver' => 'redis',
        'prefix' => 'app',
    ],
];
```

Single-item arrays: inline is allowed, multi-line is preferred.

```php
// Allowed
'status' => ['required'],

// Also acceptable
'status' => [
    'required',
],
```

---

## 4. Naming Conventions

### Classes

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

### Methods and Variables

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

### Database

| Element | Convention | Example |
|---|---|---|
| Table name | Plural snake_case | `users`, `order_items` |
| Pivot table | Singular alphabetical snake_case | `order_product`, `post_tag` |
| Column name | Singular snake_case | `first_name`, `is_active` |
| Foreign key | Singular table + `_id` | `user_id`, `order_item_id` |
| Boolean columns | `is_`/`has_` prefix | `is_active`, `has_verified_email` |
| Date columns | `_at` suffix | `published_at`, `expires_at` |
| Migration file | Timestamp + action + description | `create_users_table`, `add_phone_to_users_table` |

### Routes

| Element | Convention | Example |
|---|---|---|
| URI | Plural kebab-case | `/blog-posts`, `/order-items` |
| Route name | Dot-separated | `blog-posts.index`, `users.show` |
| Route parameter | Singular camelCase | `{user}`, `{blogPost}` |
| Nested resources | Parent/child | `/posts/{post}/comments` |

### Config and Environment

| Element | Convention | Example |
|---|---|---|
| Config file | Lowercase snake_case | `config/database.php` |
| Config key | snake_case with dots | `database.connections.mysql.host` |
| Environment variable | UPPER_SNAKE_CASE | `APP_NAME`, `DB_HOST` |

---

## 5. Class Structure

Pint's `ordered_class_elements` rule enforces the following order. See `pint.json` for the exact configuration.

1. **Traits** (`use`)
2. **Constants** (public, protected, private)
3. **Properties** (public, protected, private)
4. **Constructor**
5. **Destructor**
6. **Magic methods**
7. **Abstract methods** (public, protected)
8. **Static methods** (public, protected, private)
9. **Public methods**
10. **Protected methods**
11. **Private methods**

```php
class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    protected $fillable = ['user_id', 'total'];

    private float $cachedDiscount = 0.0;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public function __toString(): string
    {
        return "Order #{$this->id}";
    }

    public static function pending(): Builder
    {
        return static::where('status', self::STATUS_PENDING);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateTotal(): Money
    {
        // ...
    }

    protected function applyDiscount(float $rate): void
    {
        // ...
    }

    private function resetCache(): void
    {
        // ...
    }
}
```

---

## 6. Controllers

### Single-Action Only

Every controller has exactly one public method: `__invoke()`. No multi-action resource controllers. This is a convention enforced through code review.

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\View\View;

class IndexPostsController extends Controller
{
    public function __invoke(): View
    {
        $posts = Post::query()
            ->latest()
            ->paginate()
        ;

        return view('posts.index', compact('posts'));
    }
}
```

### Directory Structure

Group controllers by resource:

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

### Route Registration

```php
Route::get('/posts', IndexPostsController::class)->name('posts.index');
Route::get('/posts/create', CreatePostController::class)->name('posts.create');
Route::post('/posts', StorePostController::class)->name('posts.store');
Route::get('/posts/{post}', ShowPostController::class)->name('posts.show');
Route::get('/posts/{post}/edit', EditPostController::class)->name('posts.edit');
Route::put('/posts/{post}', UpdatePostController::class)->name('posts.update');
Route::delete('/posts/{post}', DestroyPostController::class)->name('posts.destroy');
```

### Thin Controllers

Controllers handle HTTP concerns only: accept a request, delegate to an action or service, return a response. No business logic in controllers.

```php
// Good: delegates to an action
public function __invoke(StorePostRequest $request, CreatePost $createPost): RedirectResponse
{
    $post = $createPost->execute($request->validated());

    return redirect()->route('posts.show', $post);
}

// Bad: business logic in the controller
public function __invoke(StorePostRequest $request): RedirectResponse
{
    $post = Post::create($request->validated());
    $post->tags()->sync($request->input('tags'));
    event(new PostCreated($post));
    Cache::forget('posts.index');

    return redirect()->route('posts.show', $post);
}
```

---

## 7. Models

### Property and Method Ordering

Organize model internals in this order:

1. **Traits** (`use HasFactory`, `use SoftDeletes`)
2. **Constants**
3. **PHP attributes** (`#[Fillable]`, `#[Hidden]`, `#[ScopedBy]`)
4. **Property overrides** (`$table`, `$connection`, `$primaryKey`, `$keyType`, `$incrementing`)
5. **Timestamp/casting configuration** (`$timestamps`, `$dateFormat`)
6. **Casts** (`protected function casts(): array`)
7. **Relationships** (ordered by type, see below)
8. **Scopes**
9. **Accessors and mutators** (Attribute methods)
10. **Query helpers** (custom query methods)
11. **Business logic methods**
12. **Lifecycle hooks** (booted/boot methods)

### PHP Attributes Preferred

Use PHP 8 attributes instead of property arrays where supported by the framework:

```php
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[ScopedBy([ActiveScope::class])]
class User extends Model
{
    use HasFactory;
    use Notifiable;
}
```

### Relationship Ordering

Order relationships by type:

1. `belongsTo`
2. `hasOne`
3. `hasMany`
4. `belongsToMany`
5. `morphOne` / `morphMany` / `morphTo` / `morphToMany` / `morphedByMany`

```php
// belongsTo first
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}

// Then hasMany
public function comments(): HasMany
{
    return $this->hasMany(Comment::class);
}

// Then belongsToMany
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class);
}
```

---

## 8. Services and Actions

### Actions

An action is a single-purpose class with one public method: `execute()`. Name it as a verb phrase describing what it does.

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

class CreateUser
{
    public function execute(CreateUserData $data): User
    {
        return User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
        ]);
    }
}
```

### Services

A service composes multiple actions into a feature-level API. Name it `{Feature}Service`.

```php
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

### When to Use Which

- **Action only**: simple operations (create a record, send an email, generate a report)
- **Service composing actions**: multi-step workflows (user registration, order placement)
- **No repository pattern**: query models directly in actions and services. Eloquent is the query layer.

---

## 9. Form Requests

### Array Syntax for Rules

Always use array syntax, never pipe-delimited strings.

```php
// Good
public function rules(): array
{
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
            Rule::unique('users')->ignore($this->user),
        ],
    ];
}

// Bad
public function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email',
    ];
}
```

### Always Implement `authorize()`

Every form request must have an explicit `authorize()` method, even if it just returns `true`.

```php
public function authorize(): bool
{
    return $this->user()->can('update', $this->post);
}
```

### Naming Convention

- `Store{Model}Request` for creation: `StoreUserRequest`, `StorePostRequest`
- `Update{Model}Request` for updates: `UpdateUserRequest`, `UpdatePostRequest`

Pest arch tests enforce the `Request` suffix and `FormRequest` base class (see `tests/Arch/ArchitectureTest.php`).

---

## 10. Migrations

### Column Ordering

Order columns within a migration in this sequence:

1. **Primary key** (`$table->id()`)
2. **Foreign keys** (`$table->foreignId('user_id')->constrained()`)
3. **String/text columns** (`$table->string()`, `$table->text()`)
4. **Numeric columns** (`$table->integer()`, `$table->decimal()`)
5. **Boolean columns** (`$table->boolean()`)
6. **Date/time columns** (`$table->date()`, `$table->dateTime()`)
7. **JSON columns** (`$table->json()`)
8. **Timestamps** (`$table->timestamps()`)
9. **Soft deletes** (`$table->softDeletes()`)

```php
Schema::create('orders', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
    $table->string('reference', 32)->unique();
    $table->text('notes')->nullable();
    $table->unsignedInteger('quantity');
    $table->decimal('total', 10, 2);
    $table->boolean('is_paid')->default(false);
    $table->dateTime('shipped_at')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### Migration Naming

Use descriptive action-based names: `create_orders_table`, `add_phone_to_users_table`, `drop_legacy_tokens_table`.

---

## 11. Routes

### Resource-Style Naming

Use standard resource naming even with single-action controllers. Routes should read like REST endpoints.

```php
Route::get('/posts', IndexPostsController::class)->name('posts.index');
Route::get('/posts/{post}', ShowPostController::class)->name('posts.show');
Route::post('/posts', StorePostController::class)->name('posts.store');
```

### Grouped Routes with Middleware

```php
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/posts', IndexPostsController::class)->name('posts.index');
    Route::get('/posts/create', CreatePostController::class)->name('posts.create');
    Route::post('/posts', StorePostController::class)->name('posts.store');
});
```

### URI Conventions

- Plural kebab-case: `/blog-posts`, `/order-items`
- Dot-separated names: `blog-posts.index`, `users.show`
- Singular camelCase parameters: `{user}`, `{blogPost}`
- Nested resources follow parent/child: `/posts/{post}/comments`

---

## 12. Blade and Views

### Component File Naming

Kebab-case for all component files:

```
resources/views/components/
    alert-banner.blade.php
    form-input.blade.php
    nav-link.blade.php
```

### Use Components, Not Includes

Prefer `<x-component />` over `@include`.

```blade
{{-- Good --}}
<x-alert-banner type="success" :message="$message" />

{{-- Bad --}}
@include('partials.alert-banner', ['type' => 'success', 'message' => $message])
```

### Layout Structure

```
resources/views/
    layouts/
        app.blade.php
        guest.blade.php
    components/
        alert-banner.blade.php
        form-input.blade.php
    posts/
        index.blade.php
        show.blade.php
        create.blade.php
```

### No PHP Logic in Templates

Blade templates render data. They do not compute it. Move all logic to controllers, view models, or computed properties.

```blade
{{-- Good: data prepared in the controller --}}
<span>{{ $formattedTotal }}</span>

{{-- Bad: business logic in the template --}}
<span>${{ number_format($order->items->sum('price') * (1 + $taxRate), 2) }}</span>
```

---

## 13. PHP 8.3+ Features

Use modern PHP features. Rector automatically upgrades older patterns to PHP 8.3 equivalents.

### Enums

Use backed enums for values stored in the database or exposed via API.

```php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}
```

### Readonly Classes

Use for DTOs and value objects that should be immutable after construction.

```php
readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
```

### Constructor Promotion

Promote constructor parameters to properties. Use `readonly` for injected dependencies.

```php
class CreateUser
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly Hasher $hasher,
    ) {}
}
```

### First-Class Callables

Use the `...` syntax instead of string-based callables or closure wrappers.

```php
// Good
$names = $users->map($this->formatName(...));

// Bad
$names = $users->map([$this, 'formatName']);
$names = $users->map(function (User $user) {
    return $this->formatName($user);
});
```

### Match Expressions

Use `match` instead of `switch` for value mapping (see Section 2).

```php
$icon = match ($status) {
    'success' => 'check-circle',
    'warning' => 'exclamation-triangle',
    'error' => 'x-circle',
    default => 'information-circle',
};
```

### Named Arguments

Use named arguments to improve readability when calling functions with multiple parameters, especially when skipping optional ones.

```php
// Good: clear what each argument means
dispatch(new ProcessOrder(
    orderId: $order->id,
    priority: Priority::High,
    notifyCustomer: true,
));

// Good: skipping optional parameters
$collection = collect($items)->sortBy(
    callback: fn (Item $item): string => $item->name,
    descending: true,
);
```
