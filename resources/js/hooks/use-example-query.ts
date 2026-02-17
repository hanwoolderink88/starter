import { useQuery, type UseQueryResult } from '@tanstack/react-query';
import { fetchClient } from '@/lib/fetch-client';

/**
 * Example React Query hook demonstrating the pattern for fetching data.
 *
 * This is a reference implementation showing:
 * - How to use useQuery with fetchClient
 * - Proper queryKey structure with cache keys
 * - Conditional fetching with the `enabled` option
 * - TypeScript typing for query results
 *
 * **How to replace this with a real use case:**
 *
 * 1. Import your Wayfinder route:
 *    ```typescript
 *    import { show } from '@/routes/your-feature';
 *    ```
 *
 * 2. Replace the placeholder endpoint with the Wayfinder route:
 *    ```typescript
 *    queryFn: () => fetchClient(show.url({ id }))
 *    ```
 *
 * 3. Update the queryKey to reflect your feature:
 *    ```typescript
 *    queryKey: ['your-feature', id]
 *    ```
 *
 * **Naming Convention:**
 * - File: `use-{feature}-query.ts` (e.g., `use-posts-query.ts`)
 * - Hook: `use{Feature}Query` (e.g., `usePostsQuery`)
 * - Location: `resources/js/hooks/` (or feature-specific hooks directory)
 *
 * **Delete this file** once you have a real use case. This is purely for reference.
 *
 * @param id - Example parameter (replace with your actual parameters)
 * @param enabled - Whether to enable the query (default: true). Set to false for conditional fetching.
 * @returns UseQueryResult with the fetched data
 */
export function useExampleQuery(
    id: number,
    enabled = true,
): UseQueryResult<unknown, Error> {
    return useQuery({
        queryKey: ['example', id],
        queryFn: () => fetchClient(`/api/example/${id}`),
        enabled,
    });
}
