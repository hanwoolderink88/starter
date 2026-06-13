import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    // Host the browser is told to load Vite assets from (written to the hot
    // file). Defaults to localhost; set VITE_DEV_SERVER_HOST to your machine's
    // LAN IP (e.g. 192.168.1.187) to develop from another device on the network.
    // The hot file holds a single static URL, so localhost and a LAN IP can't be
    // served simultaneously — pick the one matching the device you develop on.
    const devHost = env.VITE_DEV_SERVER_HOST || 'localhost';
    const devPort = 5174;

    return {
        // Fixed port bound to all interfaces so the Sail container can reach the
        // dev server for SSR (via INERTIA_SSR_HOT_URL). 5173 is held by Sail's
        // port mapping, so the host dev server uses 5174.
        server: {
            host: true,
            port: devPort,
            strictPort: true,
            // Browser-facing URL written to the hot file; without it the wildcard
            // bind above would leak "http://[::]:5174" into the hot file.
            origin: `http://${devHost}:${devPort}`,
            // laravel-vite-plugin otherwise defaults cors.origin to server.origin
            // (a single static string), which makes Vite echo a single origin as
            // Access-Control-Allow-Origin for every request and blocks the browser
            // loading the app from a different origin. Match loopback and private
            // LAN origins instead so the requesting origin is reflected back.
            cors: {
                origin: /^https?:\/\/(?:(?:[^:]+\.)?localhost|127\.0\.0\.1|\[::1\]|(?:10|192\.168)(?:\.\d{1,3}){2,3}|172\.(?:1[6-9]|2\d|3[01])(?:\.\d{1,3}){2})(?::\d+)?$/,
            },
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
    };
});
