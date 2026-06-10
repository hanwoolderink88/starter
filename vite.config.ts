import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    // Fixed port bound to all interfaces so the Sail container can reach the
    // dev server for SSR (via INERTIA_SSR_HOT_URL). 5173 is held by Sail's
    // port mapping, so the host dev server uses 5174.
    server: {
        host: true,
        port: 5174,
        strictPort: true,
        // Browser-facing URL written to the hot file; without it the wildcard
        // bind above would leak "http://[::]:5174" into the hot file.
        origin: 'http://localhost:5174',
        // The Sail container reaches this dev server as host.docker.internal
        // for SSR; allow that Host header past Vite's rebinding protection.
        allowedHosts: ['host.docker.internal'],
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    esbuild: {
        jsx: 'automatic',
    },
});
