<?php

beforeEach(fn () => pilot());

it('sends a content security policy with a nonce and no unsafe-eval', function () {
    $response = $this->get('/');

    $csp = $response->headers->get('Content-Security-Policy');
    expect($csp)->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'none'")
        ->toContain("object-src 'none'")
        ->not->toContain('unsafe-eval')
        ->not->toContain('unsafe-inline');
    expect($csp)->toMatch("/script-src 'self' 'nonce-[A-Za-z0-9]+'/");

    $response->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('sends HSTS over HTTPS', function () {
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
});

it('puts a CSRF token in every form that changes something', function () {
    // Laravel skips the CSRF check while running tests, so check the views instead.
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
    $forms = 0;

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }
        preg_match_all('/<form\b[^>]*method="POST"[^>]*>(.*?)<\/form>/is', file_get_contents($file->getPathname()), $matches);
        foreach ($matches[1] as $body) {
            $forms++;
            expect($body)->toContain('@csrf');
        }
    }

    expect($forms)->toBeGreaterThan(20);
});

it('fails Turnstile closed in production when no secret is set', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->post('/sign-in', ['phone' => '0712345678'])
        ->assertSessionHasErrors('cf-turnstile-response');
});

it('rejects form posts without a CSRF token outside of tests', function () {
    // Laravel only skips the CSRF check when the environment is "testing".
    app()->detectEnvironment(fn () => 'production');

    $this->post('/sign-in', ['phone' => '0712345678'])->assertStatus(419);
    $this->post('/webhooks/africastalking/wrong-token/sms', ['from' => '+254711222333'])->assertForbidden();
});

it('checks the Turnstile token with Cloudflare when a secret is set', function () {
    config(['services.turnstile.secret' => 'secret']);
    \Illuminate\Support\Facades\Http::fake(['challenges.cloudflare.com/*' => \Illuminate\Support\Facades\Http::response(['success' => false])]);

    $this->post('/sign-in', ['phone' => '0712345678', 'cf-turnstile-response' => 'token'])->assertSessionHasErrors('cf-turnstile-response');
});
