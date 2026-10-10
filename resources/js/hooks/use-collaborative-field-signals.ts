import { router } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { getClientId } from '@/lib/client-id';
import type { ResourceChangedData } from '@/types/generated';

/**
 * Keep a page's collaborative fields current: whenever someone else claims,
 * saves or releases one of the record's fields, quietly reload the props
 * that show them. No toast: an autosave every second would drown the page.
 * A field this tab is editing ignores the new prop (useCollaborativeField).
 */
export function useCollaborativeFieldSignals({
    channel,
    only,
    invalidateTags,
}: {
    /** The record's channel, without the `private-` prefix. */
    channel: string;
    /** The props that show the fields. */
    only: string[];
    /** Prefetch cache tags to flush, such as the record's edit page. */
    invalidateTags?: (payload: ResourceChangedData) => string[];
}): void {
    const clientId = getClientId();

    useEcho<ResourceChangedData>(
        channel,
        '.CollaborativeFieldChanged',
        (payload) => {
            if (payload.origin === clientId) {
                return;
            }

            const tags = invalidateTags?.(payload);

            if (tags && tags.length > 0) {
                router.flushByCacheTags(tags);
            }

            router.reload({ only });
        },
        [channel, only.join(',')],
    );
}
