# Project Architecture & Code Rules

## Core Principle

Features contain business intent. Integrations contain technical details. Controllers orchestrate. Nothing else.

## Feature-Based Structure

Code is organized by feature (vertical slice). New functionality MUST be added inside an existing feature or a new feature directory. We DO NOT organize code by technical layers (no global `app/Services/`, `app/Helpers/`, `app/Utils/` directories).

A feature owns its controllers, actions, services, models, data classes, requests, and policies.

## Routing

All routes belong directly in `routes/web.php` (or `routes/api.php` for API routes). Do not create separate route files per feature. Group related routes using `Route::middleware()` and `Route::prefix()` within the main route file.

## Controllers

Controllers must remain thin. They delegate to domain-level Actions and Services — they do not contain business logic themselves. Commands and jobs follow the same principle. Eloquent models must not contain business workflows either.

Prefer invokable controllers (single `__invoke` method) when a controller handles exactly one action. Use the `__invoke` method instead of named action methods. Only use resource-style controllers when a full CRUD set naturally belongs together and even then, consider whether individual invokable controllers are clearer.

```php
class StoreUserController extends Controller
{
    public function __invoke(StoreUserRequest $request): RedirectResponse
    {
        // ...
    }
}
```

## Code Style

Prefer OOP method calls over procedural helper functions:

- `redirect()->route('name')` over `to_route('name')`
- `response()->json()` over `response()` helper
- Throw typed exceptions (`throw new HttpException(403)`, `abort(403)`) over conditional helpers (`abort_if`, `abort_unless`, `throw_if`, `throw_unless`)

## Actions

Actions are domain-level classes that orchestrate complex workflows by combining multiple tasks (e.g., creating a user involves validation, persistence, notification, and logging).

- Follow the command pattern with a single public `handle()` method.
- Use the `Action` suffix in class names (e.g., `CreateUserAction`, `UpdateProjectAction`).
- Use constructor dependency injection for services and dependencies.
- Use `handle()` method parameters to pass runtime data.
- `handle()` may return a value and may have side effects.
- No cross-feature mutation without going through an Action.

```php
class CreateUserAction
{
    public function __construct(
        private UserService $userService,
        private NotificationService $notificationService,
    ) {}

    public function handle(UserData $data): User
    {
        $user = $this->userService->store($data);

        $this->notificationService->sendWelcome($user);

        Log::info('User created', ['user_id' => $user->id]);

        return $user;
    }
}
```

## Services

Services are domain-level classes with multiple public methods that live inside their owning feature. They typically align with a model but don't have to. Each method does one simple thing (e.g., `store`, `update`, `delete`).

- Methods handle database persistence and return values.
- Methods must not have side effects other than database persistence (no notifications, no logging, no events).
- There must be no global `app/Services/` directory — services belong to their feature.

```php
class UserService
{
    public function store(UserData $data): User
    {
        return User::create($data->toArray());
    }

    public function update(User $user, UserData $data): User
    {
        $user->update($data->toArray());

        return $user->refresh();
    }
}
```

## Data Classes

Use `spatie/laravel-data` for all data transfer objects. The `spatie/laravel-typescript-transformer` package is used to generate TypeScript types for the frontend.

Prefer `Data::from()` and `Data::collect()` factory methods over manual constructor calls (`new Data(...)`) when creating data objects from models or other sources. When the source requires custom mapping (e.g., derived properties, relationship data, or type transformations), add a magical creation method (`fromModel`, `fromUser`, etc.) on the data class itself. This keeps mapping logic centralized in the data class rather than scattered across controllers.

Each data class must have at most one magical creation method per input type. Magical methods are matched by parameter type-hint, and when multiple methods accept the same type, the first one declared in the class wins — which is fragile and bug-prone. Use distinct type-hints per method (e.g., `fromUser(User $user)` and `fromArray(array $data)`, not `fromUser(User $user)` and `fromModel(User $user)`).

```php
class UserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone = null,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            name: $user->name,
            email: $user->email,
            phone: $user->profile?->phone,
        );
    }
}

// In controllers — use factory methods:
$userData = UserData::from($user);                           // single object
$collection = UserData::collect($users, DataCollection::class); // collection
```

## Request Objects & Data Extraction

- Request objects are only used inside controllers — never pass them to Actions or Services.
- When 4 or fewer properties are needed, extract data directly from the request.
- When more than 4 properties are needed, create a Data class from the request.
- Data classes must be created via a factory method on the request class itself.

```php
// In the FormRequest class
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            // ...
        ];
    }

    public function toData(): UserData
    {
        return UserData::from($this->validated());
    }
}

// In the controller — few properties, extract directly
public function update(UpdateNameRequest $request, User $user): RedirectResponse
{
    $this->userService->updateName($user, $request->validated('name'));

    return back();
}

// In the controller — many properties, use Data class
public function store(StoreUserRequest $request): RedirectResponse
{
    $this->createUserAction->handle($request->toData());

    return redirect()->route('users.index');
}
```

## Authorization

Authorization is always handled in the controller using Gates and Policies. Never use the `authorize()` method on FormRequest classes.

```php
public function update(UpdateUserRequest $request, User $user): RedirectResponse
{
    Gate::authorize('update', $user);

    // ...
}
```

## Feature Boundaries

- Features may not directly depend on other feature internals.
- If cross-feature interaction is required, use an Action or a defined Contract (interface).
- No reaching into another feature's models or private classes.

## Integration Layer

All external systems (APIs, payment providers, email providers, LLMs, etc.) must live in `app/Integrations/`.

- Features define Contracts (interfaces) for what they need.
- Integrations implement those Contracts.
- Features must not directly import integration implementations.
- Integrations must not contain business logic.
- Integrations must not depend on Features.
- Use `Gateway`, `Client`, or `Adapter` suffixes for integration classes.

Allowed dependency direction:

```
Feature -> Contract -> Integration
```

Not allowed:

```
Feature -> Integration implementation
Integration -> Feature
Feature A -> Feature B internals
```

## Forbidden Patterns

The following global directories are forbidden:

- `app/Services/` (use feature-scoped services instead)
- `app/Helpers/`
- `app/Utils/`
- Shared business logic outside a feature

If something feels "shared", it likely belongs to a feature, to Integrations, or is true infrastructure.

## Naming Rules

- Use `Action` suffix for business operations (e.g., `CreateProjectAction`).
- Use `Gateway`, `Client`, or `Adapter` for integrations.
- Avoid generic names like `Manager`, `Handler`, `Processor`.
- Names must reflect domain language.

## Code Quality Checks

After completing any task that involves PHP code, you MUST run the following composer scripts in order:

1. `vendor/bin/sail composer rector` — Applies automated code upgrades and refactoring rules.
2. `vendor/bin/sail composer pint` — Fixes code style to match project conventions.
3. `vendor/bin/sail composer analyse` — Runs PHPStan static analysis.

All three must pass cleanly. Fix any issues they surface before considering the task complete.
