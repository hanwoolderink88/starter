import { router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { toast } from 'sonner';
import { getClientId } from '@/lib/client-id';
import { ResourceAction, type ResourceChangedData } from '@/types/generated';

interface RealtimeResourceOptions {
    /** Echo channel name without the `private-` prefix (e.g. `users`, `user.42`). */
    channel: string;
    /** Broadcast event name — the server `broadcastAs()` value with a leading dot. */
    event: string;
    /** Inertia partial-reload keys for the consuming page. Omit to reload everything. */
    only?: string[];
    /** `auto` reloads immediately; `ask` shows a toast with a Reload action. */
    mode?: 'auto' | 'ask';
    /** Toast message for the default (non-handled) path. */
    describe?: (event: ResourceChangedData) => string;
    /**
     * Prefetch cache tags to flush when a change arrives — keeps prefetched
     * detail/edit pages from serving stale data after a concurrent change.
     */
    invalidateTags?: (event: ResourceChangedData) => string[];
    /**
     * Escape hatch run for every event after self-suppression. Return `true` to
     * mark the event fully handled and skip the default toast + reload (used for
     * cases like redirecting away when the record you are viewing is deleted).
     */
    onChange?: (event: ResourceChangedData) => boolean | void;
}

const ACTION_VERB: Record<ResourceAction, string> = {
    [ResourceAction.Created]: 'created',
    [ResourceAction.Updated]: 'updated',
    [ResourceAction.Deleted]: 'deleted',
};

/**
 * Subscribe a page to a resource's co-working broadcasts: suppress the
 * originating tab's own echo, toast the change, and refresh data through an
 * Inertia partial reload. See the Real-Time & Co-Working rules.
 */
export function useRealtimeResource({
    channel,
    event,
    only,
    mode = 'auto',
    describe,
    invalidateTags,
    onChange,
}: RealtimeResourceOptions): void {
    const clientId = getClientId();

    useEcho<ResourceChangedData>(
        channel,
        event,
        (payload) => {
            // The tab that initiated the change already has the fresh result;
            // other tabs and surfaces (e.g. MCP) carry no/other origin and update.
            if (payload.origin === clientId) {
                return;
            }

            // Drop any prefetched pages that this change made stale.
            const tags = invalidateTags?.(payload);
            if (tags && tags.length > 0) {
                router.flushByCacheTags(tags);
            }

            if (onChange?.(payload) === true) {
                return;
            }

            const message =
                describe?.(payload) ??
                `${payload.actorName} ${ACTION_VERB[payload.action]} ${payload.label}`;
            const reload = () => router.reload(only ? { only } : {});

            if (mode === 'ask') {
                toast.warning(message, {
                    action: { label: 'Reload', onClick: reload },
                });

                return;
            }

            toast.info(message);
            reload();
        },
        [channel, event],
    );
}
