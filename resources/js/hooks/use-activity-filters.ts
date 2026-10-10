import { router } from '@inertiajs/react';
import { useCallback } from 'react';
import { index } from '@/routes/activity';
import type { ActivityFiltersData } from '@/types/generated';

export type ActivityFilterChange = Partial<{
    event: string | null;
    source: string | null;
    causer_id: number | null;
    subject_type: string | null;
    subject_id: number | null;
    from: string | null;
    to: string | null;
}>;

/**
 * Navigate the activity log to new filters. Every change keeps the other
 * filters and starts again on the first page.
 */
export function useActivityFilters(filters: ActivityFiltersData) {
    const applyFilters = useCallback(
        (change: ActivityFilterChange) => {
            const query = {
                event: filters.event,
                source: filters.source,
                causer_id: filters.causerId,
                subject_type: filters.subjectType,
                subject_id: filters.subjectId,
                from: filters.from,
                to: filters.to,
                ...change,
            };

            router.get(
                index().url,
                Object.fromEntries(
                    Object.entries(query).filter(
                        ([, value]) => value !== null && value !== undefined,
                    ),
                ),
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['entries', 'filters'],
                },
            );
        },
        [filters],
    );

    const clearFilters = useCallback(() => {
        router.get(
            index().url,
            {},
            {
                preserveState: true,
                replace: true,
                only: ['entries', 'filters'],
            },
        );
    }, []);

    return { applyFilters, clearFilters };
}
