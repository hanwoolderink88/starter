import { createInertiaApp } from '@inertiajs/react';
import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { initializeTheme } from './hooks/use-appearance';
import { makeQueryClient } from './lib/query-client';

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
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        // Lazy initialization: create QueryClient once and reuse across Inertia navigations
        if (!queryClient) {
            queryClient = makeQueryClient();
        }

        const root = createRoot(el);

        root.render(
            <StrictMode>
                <QueryClientProvider client={queryClient}>
                    <App {...props} />
                    <ReactQueryDevtools initialIsOpen={false} />
                </QueryClientProvider>
            </StrictMode>,
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
