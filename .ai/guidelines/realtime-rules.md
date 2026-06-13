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

The payload is deliberately thin: enough to phrase a toast, decide what to reload, and suppress the originating surface's own echo. It MUST NOT carry record fields (no email, role, timestamps, etc.).

```php
// app/Broadcasting/Data/ResourceChangedData.php
#[TypeScript]
class ResourceChangedData extends Data
{
    public function __construct(
        public ResourceAction $action,
        public int $id,          // affected record id
        public string $label,    // human label for the toast, e.g. the user's name
        public int $actorId,     // who did it — used for the toast label
        public string $actorName,
        public ?string $origin = null, // the initiating surface's client id — used to suppress its own echo; null for non-web surfaces (e.g. MCP)
    ) {}
}
```

## Events

Each feature owns a single `{Model}Changed` event in its `Events/` directory carrying a `ResourceAction` — one event per model, not one per verb, so the frontend subscribes once and switches on the action.

- Implement `ShouldBroadcastNow`. These signals are tiny, so dispatch them synchronously in the request rather than queuing: it guarantees the broadcast fires immediately (no waiting on a busy or absent queue worker) and the only cost — a fast local publish to Reverb — is negligible for a five-field payload. Reserve queued `ShouldBroadcast` for heavy or high-volume events. If a mutation is ever wrapped in a DB transaction, also implement `ShouldDispatchAfterCommit` so the broadcast waits for the commit.
- Broadcast on a **private** channel.
- Set `broadcastAs()` to a stable name so the frontend listener does not depend on the class namespace.

```php
// app/Features/UserManagement/Events/UserChanged.php
class UserChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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

Broadcasting is a **side effect**, so it belongs in the **Action**, never the Service (Services persist only — see the backend rules). Dispatch after the Service has persisted, and pass the acting user — and the originating surface's `$origin` — in as parameters rather than reading global state.

Dispatch with `Event::dispatch` (`{Model}Changed::dispatch(...)`), not the `broadcast()` helper: the dispatcher auto-broadcasts events implementing `ShouldBroadcast*` (so the behaviour is identical), and unlike `broadcast()` it can be asserted with `Event::fake()` in tests.

The `$origin` is threaded straight through from the caller: web controllers pass `$request->header('X-Client-Id')`; non-web surfaces (MCP tools, console commands, jobs) pass nothing, leaving it `null` so every browser is notified.

```php
public function handle(UserData $data, User $actor, ?string $origin = null): User
{
    $user = $this->userService->store($data);

    UserChanged::dispatch(new ResourceChangedData(
        action: ResourceAction::Created,
        id: $user->id,
        label: $user->name,
        actorId: $actor->id,
        actorName: $actor->name,
        origin: $origin,
    ));

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

## Suppress the Originating Surface's Own Echo

The surface that made the change already saw the result of its own request and must not get a toast or reload. Suppress on the **origin**, not the user: each web SPA tab generates a stable client id (`lib/client-id.ts`) that an `http.onRequest` interceptor in `app.tsx` attaches as the `X-Client-Id` header on every request. Controllers echo it into `ResourceChangedData::$origin`, and the hook drops the event only when `payload.origin === clientId`.

Suppressing on `actorId` (the user) is wrong once the app has more than one surface: the same user acting over MCP — or in a second browser tab — shares the actor id, so an `actorId` check would silence a browser that never saw the change. Keying on the originating client id keeps the initiating tab quiet while every other tab and surface (MCP carries a `null` origin) updates.

Do **not** rely on `->toOthers()`: Inertia v3 uses its own XHR client (not axios), so Echo's automatic `X-Socket-ID` header is absent and `toOthers()` will not work without extra plumbing. The `X-Client-Id` header is our own equivalent and is robust across surfaces.

## Frontend Hook

All subscribe/suppress/toast/reload logic lives in one reusable hook (`hooks/use-realtime-resource.ts`), so pages stay skinny and add real-time with one line. Use `@laravel/echo-react`'s `useEcho`; the event name is the `broadcastAs()` value with a leading dot.

```ts
useRealtimeResource({
    channel: 'users',
    event: '.UserChanged',
    only: ['users'],          // Inertia partial-reload keys for THIS page
    mode: 'auto',             // 'auto' | 'ask'
});
```

Origin self-suppression is handled inside the hook via the shared client id — pages pass nothing for it.

The payload type is the generated `App.Broadcasting.Data.ResourceChangedData` — never hand-write it.

## Presence (Optional)

For genuine co-editing awareness, layer `useEchoPresence` on the per-record channel (`user.{id}`) to show who else is viewing/editing and warn before a conflict happens. Add this to edit pages where concurrent edits are likely.

## Checklist for a Real-Time CRUD Feature

1. `{Model}Changed` event in the feature's `Events/`, private channel, `broadcastAs()` set.
2. The create/update/delete **Actions** dispatch it with a `ResourceChangedData` signal.
3. Channel authorization added to `routes/channels.php` against the matching policy.
4. Subscribing pages call `useRealtimeResource` with their own `only` keys and the right `mode`.
5. Origin self-suppression verified: the initiating tab gets no self-toast, while the actor's *other* tabs and non-web surfaces (MCP) do update.
