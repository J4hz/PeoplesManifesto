<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Turnstile check on public forms. With no secret configured it
 * passes outside production and fails in production, so a missing setting
 * never silently switches protection off on the live site.
 */
class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret');

        if (blank($secret)) {
            if (app()->isProduction()) {
                $fail(__('validation.turnstile'));
            }

            return;
        }

        if (! is_string($value) || $value === '') {
            $fail(__('validation.turnstile'));

            return;
        }

        $response = Http::asForm()->timeout(10)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secret,
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (! $response->successful() || $response->json('success') !== true) {
            $fail(__('validation.turnstile'));
        }
    }
}
