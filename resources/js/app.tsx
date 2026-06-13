import { createInertiaApp, http } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { LaravelReactI18nProvider } from 'laravel-react-i18n';
import '../css/app.css';
import { Toaster } from './components/ui/sonner';
import { initializeTheme } from './hooks/use-appearance';
import { getClientId } from './lib/client-id';
import { makeQueryClient } from './lib/query-client';

configureEcho({
    broadcaster: 'reverb',
});

// Tag every request with this tab's client id so co-working broadcasts can echo
// it back and the originating tab can suppress its own update. See
// `use-realtime-resource` and `ResourceChangedData::$origin`.
if (!import.meta.env.SSR) {
    http.onRequest((config) => {
        config.headers = { ...config.headers, 'X-Client-Id': getClientId() };

        return config;
    });
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

let queryClient: ReturnType<typeof makeQueryClient> | undefined;

function getTransitionType(
    currentPath: string,
    targetPath: string,
): 'fade' | 'slide-forward' | 'slide-back' {
    const currentSegments = currentPath.split('/').filter(Boolean);
    const targetSegments = targetPath.split('/').filter(Boolean);

    if (currentSegments[0] !== targetSegments[0]) {
        return 'fade';
    }

    if (targetSegments.length > currentSegments.length) {
        return 'slide-forward';
    }

    if (targetSegments.length < currentSegments.length) {
        return 'slide-back';
    }

    return 'fade';
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    strictMode: true,
    withApp(app) {
        // On the SSR server every request gets a fresh QueryClient; in the
        // browser a single client is reused across Inertia navigations.
        const client = import.meta.env.SSR
            ? makeQueryClient()
            : (queryClient ??= makeQueryClient());

        return (
            <LaravelReactI18nProvider
                locale="en"
                fallbackLocale="en"
                files={import.meta.glob('/lang/*.json', { eager: true })}
            >
                <QueryClientProvider client={client}>
                    {app}
                    <Toaster />
                    <ReactQueryDevtools initialIsOpen={false} />
                </QueryClientProvider>
            </LaravelReactI18nProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
    defaults: {
        visitOptions: (href, options) => {
            if (options.viewTransition !== undefined) {
                return {};
            }

            if (options.method && options.method !== 'get') {
                return {};
            }

            const targetPath = new URL(href, window.location.origin).pathname;

            if (targetPath === window.location.pathname) {
                return {};
            }

            const transitionType = getTransitionType(
                window.location.pathname,
                targetPath,
            );

            document.documentElement.dataset.transition = transitionType;

            return {
                viewTransition: (transition) => {
                    transition.finished.then(() => {
                        delete document.documentElement.dataset.transition;
                    });
                },
            };
        },
    },
});

// This will set light / dark mode on load...
initializeTheme();
