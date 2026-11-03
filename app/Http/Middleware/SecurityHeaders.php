<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->policy($nonce));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure() || app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function policy(string $nonce): string
    {
        $turnstile = 'https://challenges.cloudflare.com';

        // The Vite dev server serves assets and hot reload from another port during development.
        $dev = '';
        if (app()->isLocal() && Vite::isRunningHot()) {
            $hot = rtrim((string) file_get_contents(public_path('hot')));
            $ws = preg_replace('#^http#', 'ws', $hot);
            $dev = " {$hot} {$ws}";
        }

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' {$turnstile}{$dev}",
            "style-src 'self'{$dev}",
            "img-src 'self' data:",
            "font-src 'self'{$dev}",
            "connect-src 'self'{$dev}",
            "frame-src {$turnstile}",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
        ]);
    }
}
