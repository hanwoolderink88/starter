# Field Collaboration Rules

## Core Principle

Text fields of a record can be edited **together, one editor per field at a time**. Focusing a field claims it; typing saves it a second after the last keystroke; leaving it (blur, Escape, navigating away, closing the tab) releases it. Everyone else sees who is editing and follows the saved text.

The value always lives in the model's own column. Collaboration adds a lock per field (`field_locks`) and, for long texts, a history (`field_revisions`). Nothing else about the model changes.

## Making a Model Collaborative

Implement `App\Infrastructure\Collaboration\Contracts\CollaborativeModel`, register the model in the morph map, and give it a policy with `view` and `update`:

```php
public function collaborativeFields(): array
{
    return [
        'name' => new CollaborativeField(['required', 'string', 'max:255'], label: 'roles.form.name'),
        'description' => new CollaborativeField(['nullable', 'string', 'max:1000'], label: 'roles.form.description', multiline: true),
    ];
}

public function collaborationChannels(): array
{
    return ["role.{$this->id}", 'roles']; // the record's channel and its list's channel
}
```

- `rules` validate every save, exactly as the field would be validated in a form. Every save also gets `max:collaboration.max_length`.
- `label` names the field in validation messages (translation key or text).
- `multiline: true` makes it a textarea with a history of revisions that can be compared and restored. Single-line fields have no history; the activity log covers them.

Pass the fields to the edit page with `CollaborativeFieldData::forModel($model, $viewer, $request->header('X-Client-Id'))` and render each with `<CollaborativeField>`. Keep the page current with `useCollaborativeFieldSignals({ channel, only })`.

## Rules

- **Remove collaborative fields from the record's form request and update Action.** The form's Save stores the remaining fields only; the field and the form must never both write the same column.
- **Autosave does not bump the record version** (optimistic locking). A form open for the other fields stays valid while someone types in a field.
- **One activity entry per editing session**, with the value before and after the session — never one per autosave. A session closes on release, or on expiry (`collaboration:close-expired-sessions`, every minute) on behalf of its holder.
- **The first revision of a long text is the text from before collaboration** (author System), so its history is complete.
- **Revisions are append-only** (Postgres trigger). Only privacy maintenance may update them, to anonymize author names.
- **Restoring a revision is only possible while nobody holds the field.** It appends a new revision and logs `revision_restored`.
- Broadcasts are signals only (`CollaborativeFieldChanged`), as in the Real-Time rules. The tab that holds a field ignores incoming props for that field.

## Timing

`config/collaboration.php`: heartbeat every 15 s; a lock without heartbeat expires after 45 s (connection lost); a session without typing ends after 120 s. The frontend constants in `use-collaborative-field.ts` mirror these.
