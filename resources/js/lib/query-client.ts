import { QueryClient } from '@tanstack/react-query';

/**
 * Factory function to create a new QueryClient instance with sensible defaults.
 *
 * This is NOT a singleton - each call creates a fresh instance.
 * - In app.tsx: Used with lazy initialization for singleton behavior across Inertia navigations
 * - In ssr.tsx: Called per-request to prevent cross-request state pollution
 */
export function makeQueryClient(): QueryClient {
    return new QueryClient({
        defaultOptions: {
            queries: {
                // 5 minutes: Prevents immediate refetch after Inertia page load
                // Gives users time to interact with data before background refresh
                staleTime: 1000 * 60 * 5,

                // 24 hours: Reasonable cache retention for offline support
                gcTime: 1000 * 60 * 60 * 24,

                // Refetch when window regains focus (user returns to tab)
                refetchOnWindowFocus: true,

                // Retry failed queries once before giving up
                retry: 1,
            },
            mutations: {
                // Do not retry mutations - they have side effects
                retry: 0,
            },
        },
    });
}
