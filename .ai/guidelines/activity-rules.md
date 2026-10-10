# Activity Log & Impersonation Rules

## Core Principle

The application can always prove who did what: who, when, through which surface, from where, and what changed. The log is written explicitly from Actions, is append-only down to the database, and survives the deletion of whoever or whatever it describes.

## Where Things Live

- `app/Infrastructure/Activity/` is **infrastructure** every feature may use, like `app/Infrastructure/Broadcasting/`: `Data/ChangeContext`, `Services/ActivityRecorder`, `Models/Activity`, `Enums/{ActivityEvent,ActivitySource}` and `Data/ActivityData`.
- `app/Features/Activity/` is the **feature** for the admin surface: the searchable log page (`/activity`) and its policy (`ActivityPolicy`, registered explicitly). Other features import only the infrastructure, never the feature.
- `ActivityRecorder` is a Service that writes the log. That is the one infrastructure exception to "Services have no side effects": recording is what it is for. Feature Services still never log.

## The ChangeContext

Every Action that mutates data receives one `ChangeContext` instead of loose `User $actor` / `?string $origin` parameters. The activity log and the co-working signal both read from it, so they always name the same person.

| Caller | Factory |
|---|---|
| Web controller | `ChangeContext::fromRequest($request)` |
| Guest flow (accepting an invitation, resetting a password) | `ChangeContext::fromRequest($request, actor: $user)` |
| MCP tool | `ChangeContext::fromMcpRequest($request)` |
| Seeder, command, job | `ChangeContext::system()` |

The context carries the actor, the source (`web`, `mcp`, `system`), the tab's `origin` (for echo suppression only, never stored), the **impersonator** while the session impersonates someone, and the IP address and user agent.

## What Gets Logged

Every create, update and delete, and every other change someone may later need to account for, is logged — in the **Action**, never in a model (no model events or observers), a controller or a feature Service:

```php
// app/Features/UserManagement/Actions/UpdateUserAction.php
public function handle(User $user, string $name, string $email, Role $role, ChangeContext $context): User
{
    $before = ['name' => $user->name, 'email' => $user->email, 'role' => $user->getRoleNames()->first()];

    $user = $this->userManagementService->update($user, $name, $email, $role);

    $changes = ActivityRecorder::diff($before, ['name' => $user->name, 'email' => $user->email, 'role' => $role->value]);

    if ($changes !== []) {
        $this->activityRecorder->record($context, ActivityEvent::Updated, $user, $changes);
    }

    UserChanged::dispatch(ResourceChangedData::fromContext($context, ResourceAction::Updated, $user->id, $user->name));

    return $user;
}
```

- `ActivityRecorder::record($context, $event, $subject, $changes = [])`. The subject is null only when there is nothing to point at, such as a failed login for an unknown email address.
- Build `$changes` with `ActivityRecorder::diff($before, $after)` (`['field' => ['old' => …, 'new' => …]]`). An update with an empty diff writes no entry, but still sends the record's signal. A create is a diff from `[]`.
- A hard delete logs `ActivityRecorder::snapshot([...])` **before** deleting, so the log still says what was removed.
- Log domain values (role names, enum values), not internal ids or model instances.
- `ActivityEvent` is the closed list of event keys. A new kind of event adds a case **and** its label under `activity.events.{value}` in `lang/en/activity.php`. Never pass a free-form string.
- `subject_type` stores a short alias, never a class name. The morph map in `AppServiceProvider::configureMorphMap()` is **enforced**, so logging a model without an alias fails loudly. Add the alias when a model is first logged, and add its label under `activity.subjects.{alias}`.

### Authentication

Login, logout, failed logins, throttling and two-factor changes are owned by Fortify and the framework, so `App\Features\Auth\Listeners\RecordAuthenticationActivity` records them from their events. Password resets, password changes and registration go through our own Actions and log there. Only the `web` guard counts; an API token (MCP) does not log in.

## Sensitive Fields

Passwords, 2FA secrets, recovery codes and tokens never enter the log, not even as an old or new value. `ActivityRecorder::SENSITIVE_FIELDS` plus every key ending in `_token` or `_secret` is dropped at any depth, including inside a snapshot. Name a new secret column so it is caught, or add it to `SENSITIVE_FIELDS` with a test.

## Append-Only, Except for Privacy

There is no route, tool, feature Service or policy ability to update or delete an entry. A Postgres trigger refuses every `UPDATE` and `DELETE` on `activities`, with one exception: `App\Infrastructure\Activity\Services\ActivityPrivacy`, the only code that may change the log, and only to honour the terms in `config/privacy.php`. It marks its own transaction (`SET LOCAL app.activity_privacy_maintenance`) and switches the mark off again when done. Never set that mark anywhere else.

- `causer_id` and `impersonator_id` have no foreign key, and `causer_name` / `impersonator_name` are snapshots, so a deleted user keeps their entries.
- **Deleting a user is a soft delete.** They can no longer log in, their sessions and API tokens are revoked, and they can be restored (`users.restore`) for `privacy.anonymize_deleted_users_after_days`. Then `users:anonymize` (scheduled daily) runs `AnonymizeUserAction`: the account becomes "Deleted user #id", and `ActivityPrivacy::anonymizeUser()` replaces their name where they acted or impersonated, clears those requests' IP and user agent, and scrubs the `ActivityPrivacy::PERSONAL_FIELDS` (`name`, `email`) in every entry about them. The entries themselves stay, and the anonymization is logged too.
- **`activity:prune`** (scheduled daily) clears IP addresses, user agents and the email addresses typed into failed logins for unknown accounts after `privacy.activity_network_details_days`, and deletes entries after `privacy.activity_retention_days` when that is set (default: kept forever).
- A new field holding personal data that can end up in `changes` (a phone number, an address) goes into `ActivityPrivacy::PERSONAL_FIELDS`, with a test that anonymizing scrubs it.
- A test that expects the trigger to refuse a statement wraps each attempt in its own `DB::transaction()` (a savepoint); otherwise the aborted transaction makes every later statement fail for the wrong reason.

## Visibility

The full log on `/activity` needs the `view activity` permission. A record's own page may show its timeline (`Activity::query()->forSubject($model)`), but only to who may read the log: entries carry IP addresses and user agents.

## Impersonation

Impersonating wraps the real user's session instead of replacing it:

- `ImpersonateController` logs `impersonation_started` (actor: the admin, subject: the target), switches the session user and stores `['impersonator_id', 'impersonated_id']` under `ChangeContext::IMPERSONATION_SESSION_KEY`.
- While it lasts, every entry has the target as causer and the admin as **impersonator**; the co-working toast reads "Jane, impersonated by Piet".
- `StopImpersonatingController` and `LogoutController` (which replaces Fortify's logout route) log `impersonation_stopped` with the admin as actor and hand the session back.
- Switching never calls `login()` or `logout()`: a login would be recorded as the target logging in, a logout would cycle remember tokens. The session id is migrated, the CSRF token renewed and the previous user's password confirmation dropped.
- `UserPolicy::impersonate` refuses yourself and anyone who may impersonate too, so an administrator's account is never borrowed. Impersonations do not nest.
- `RestrictImpersonation` (web middleware) refuses the target's profile, password and two-factor routes while impersonating, and ends the session when the impersonator was deleted meanwhile. Add a new account-security route to `RestrictImpersonation::BLOCKED_ROUTES`.
- MCP has no impersonation: a token always acts as its own user.

## Testing

Per Action, assert the entry and its fields (event, causer, source, changes) for the web and, where a tool exists, for MCP (`source` = `mcp`). Assert that sensitive fields are absent and that a no-op update writes nothing.
