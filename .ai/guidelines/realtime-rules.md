# Real-Time & Co-Working Rules

## Core Principle

Broadcast a **signal**, not a payload. Reverb tells clients *that* something changed; Inertia re-fetches *what* changed. The authoritative, policy-filtered data always comes from the controller — never from the socket.

This keeps a single source of truth, avoids re-implementing authorization on the broadcast path, and means a record the recipient may not see is never leaked over a channel.

## When This Applies

Any CRUD feature where two people could be looking at the same data at once (lists, dashboards, detail/edit pages) MUST broadcast its create/update/delete mutations so other viewers are kept in sync. If a feature mutates data that another open page could be showing, it is not complete until it broadcasts.

## Data Flow

```
Actor saves ──HTTP──▶ Action ──persist (Service)──▶ broadcast signal
                                                          │ Reverb
        other clients ◀── "user 42 updated by Jane" ──────┘
                          │
                          └─▶ toast + router.reload({ only: ['users'] })
                                                  │ HTTP (Inertia)
                                                  └─▶ controller re-runs → fresh, authorized props
```

## Shared Broadcasting Infrastructure

Real-time scaffolding is true infrastructure, not feature business logic, and lives in `app/Broadcasting/`:

- `ResourceAction` enum — `Created`, `Updated`, `Deleted` (TitleCase keys).
- `ResourceChangedData` — the single, generic `#[TypeScript]` signal payload reused by every feature.

The payload is deliberately thin: enough to phrase a toast, decide what to reload, and suppress the actor's own echo. It MUST NOT carry record fields (no email, role, timestamps, etc.).

```php
// app/Broadcasting/Data/ResourceChangedData.php
#[TypeScript]
class ResourceChangedData extends Data
{
    public function __construct(
        public ResourceAction $action,
        public int $id,          // affected record id
        public string $label,    // human label for the toast, e.g. the user's name
        public int $actorId,     // who did it — used to suppress the actor's own toast/reload
        public string $actorName,
    ) {}
}
```

## Events

Each feature owns a single `{Model}Changed` event in its `Events/` directory carrying a `ResourceAction` — one event per model, not one per verb, so the frontend subscribes once and switches on the action.

- Implement `ShouldBroadcast` (queued — ensure a queue worker runs).
- Broadcast on a **private** channel.
- Set `broadcastAs()` to a stable name so the frontend listener does not depend on the class namespace.

```php
// app/Features/UserManagement/Events/UserChanged.php
class UserChanged implements ShouldBroadcast
{
    public function __construct(public ResourceChangedData $payload) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('users');
    }

    public function broadcastAs(): string
    {
        return 'UserChanged';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return $this->payload->toArray();
    }
}
```

## Dispatching

Broadcasting is a **side effect**, so it belongs in the **Action**, never the Service (Services persist only — see the backend rules). Dispatch after the Service has persisted.

```php
public function handle(UserData $data): User
{
    $user = $this->userService->store($data);

    broadcast(new UserChanged(new ResourceChangedData(
        action: ResourceAction::Created,
        id: $user->id,
        label: $user->name,
        actorId: auth()->id(),
        actorName: auth()->user()->name,
    )));

    return $user;
}
```

## Channels & Authorization

Two granularities, both private, authorized in `routes/channels.php` against the same policy the corresponding page uses:

- **Collection channel** — `{resource-plural}` (e.g. `users`) — for anyone on a page that lists or summarizes the resource. Authorize with `viewAny`.
- **Per-record channel** — `{resource-singular}.{id}` (e.g. `user.{id}`) — for anyone viewing or editing one record. Authorize with `view`. This is where same-record conflict handling and presence belong.

```php
Broadcast::channel('users', fn (User $user) => $user->can('viewAny', User::class));
Broadcast::channel('user.{id}', fn (User $user, int $id) => $user->can('view', User::findOrFail($id)));
```

## Reloading Data

Refresh through Inertia partial reloads — never mutate page props from the socket directly:

```ts
router.reload({ only: ['users'] });
```

Each subscribing page passes the `only` keys *it* cares about, so one signal can refresh a list on one page and a counter on another.

## Hot Reload vs. Ask

The strategy is a property of the consuming view, not the event:

| Situation | Strategy |
| --- | --- |
| List, dashboard, read-only detail (no unsaved local state) | **Auto** — `router.reload({ only })` + an informational toast |
| A form with dirty fields, or editing the same record that changed | **Ask** — a toast with a "Reload" action; never clobber unsaved input |

For a same-record conflict (someone saved the record you are editing), make it prominent (`toast.warning` or an inline banner) because reloading discards the user's edits.

## Suppress the Actor's Own Echo

The person who made the change already saw the result of their own request and must not get a toast or reload. Suppress on the client by comparing `actorId` to the current user id.

Do **not** rely on `->toOthers()`: Inertia v3 uses its own XHR client (not axios), so Echo's automatic `X-Socket-ID` header is absent and `toOthers()` will not work without extra plumbing. The `actorId` check is robust and needs none.

## Frontend Hook

All subscribe/suppress/toast/reload logic lives in one reusable hook (`hooks/use-realtime-resource.ts`), so pages stay skinny and add real-time with one line. Use `@laravel/echo-react`'s `useEcho`; the event name is the `broadcastAs()` value with a leading dot.

```ts
useRealtimeResource({
    channel: 'users',
    event: '.UserChanged',
    only: ['users'],          // Inertia partial-reload keys for THIS page
    mode: 'auto',             // 'auto' | 'ask'
    currentUserId,
});
```

The payload type is the generated `App.Broadcasting.Data.ResourceChangedData` — never hand-write it.

## Presence (Optional)

For genuine co-editing awareness, layer `useEchoPresence` on the per-record channel (`user.{id}`) to show who else is viewing/editing and warn before a conflict happens. Add this to edit pages where concurrent edits are likely.

## Checklist for a Real-Time CRUD Feature

1. `{Model}Changed` event in the feature's `Events/`, private channel, `broadcastAs()` set.
2. The create/update/delete **Actions** dispatch it with a `ResourceChangedData` signal.
3. Channel authorization added to `routes/channels.php` against the matching policy.
4. Subscribing pages call `useRealtimeResource` with their own `only` keys and the right `mode`.
5. Actor self-suppression verified (no self-toast on the initiating client).
