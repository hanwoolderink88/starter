<?php

/*
|--------------------------------------------------------------------------
| Security headers
|--------------------------------------------------------------------------
|
| Sent on every response by App\Http\Middleware\SecurityHeaders. The Content
| Security Policy allows scripts only from this origin and with the nonce of
| the request (Vite tags get it via Vite::useCspNonce(); an inline <script>
| in a Blade view needs nonce="{{ Vite::cspNonce() }}"). While `npm run dev`
| runs, the Vite dev server is allowed too.
|
*/

$reverbHost = env('VITE_REVERB_HOST');

return [

    'csp' => [
        'enabled' => (bool) env('CSP_ENABLED', true),

        // Report violations in the browser console instead of blocking them,
        // to try a stricter policy safely.
        'report_only' => (bool) env('CSP_REPORT_ONLY', false),

        // Extra origins the frontend may connect to, comma separated, such as
        // an error tracker.
        'connect_src' => array_values(array_filter(
            array_map(trim(...), explode(',', (string) env('CSP_CONNECT_SRC', ''))),
            fn (string $origin): bool => $origin !== '',
        )),

        // The websocket origin of Reverb, as the browser sees it.
        'reverb' => is_string($reverbHost) && $reverbHost !== ''
            ? sprintf(
                '%s://%s:%s',
                env('VITE_REVERB_SCHEME', 'https') === 'https' ? 'wss' : 'ws',
                $reverbHost,
                env('VITE_REVERB_PORT', 443),
            )
            : null,
    ],

    // Strict-Transport-Security, only sent over HTTPS.
    'hsts' => [
        'enabled' => (bool) env('HSTS_ENABLED', true),
        'max_age' => (int) env('HSTS_MAX_AGE', 31536000),
    ],

];
