<?php

test('every response carries the security headers', function () {
    $this->get(route('login'))
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertHeaderMissing('Strict-Transport-Security');
});

test('the content security policy only runs scripts carrying the request\'s nonce', function () {
    $response = $this->get(route('login'));

    $policy = (string) $response->headers->get('Content-Security-Policy');
    preg_match("/'nonce-([^']+)'/", $policy, $matches);

    expect($matches[1] ?? null)->not->toBeNull()
        ->and($policy)->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'")
        ->not->toContain("'unsafe-eval'")
        ->and($response->getContent())->toContain('<script nonce="'.$matches[1].'">');

    // A fresh nonce for every request.
    expect($this->get(route('login'))->headers->get('Content-Security-Policy'))->not->toContain($matches[1]);
});

test('HSTS is only sent over HTTPS', function () {
    $this->get('https://localhost/login')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

test('the policy can report instead of block, or be switched off', function () {
    config(['security.csp.report_only' => true]);

    $this->get(route('login'))
        ->assertHeaderMissing('Content-Security-Policy')
        ->assertHeader('Content-Security-Policy-Report-Only');

    config(['security.csp.enabled' => false]);

    $this->get(route('login'))->assertHeaderMissing('Content-Security-Policy-Report-Only');
});
