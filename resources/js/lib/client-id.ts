/**
 * A stable identifier for this browser tab's Inertia SPA instance.
 *
 * Sent as the `X-Client-Id` header on every request (see `app.tsx`) so the
 * server can echo it back on co-working broadcasts. The tab that initiated a
 * change suppresses its own echo by matching this id — see
 * `use-realtime-resource`. It is regenerated on a full page reload, which is
 * fine: a reload re-fetches the data anyway.
 */
let clientId: string | undefined;

export function getClientId(): string {
    return (clientId ??= crypto.randomUUID());
}
