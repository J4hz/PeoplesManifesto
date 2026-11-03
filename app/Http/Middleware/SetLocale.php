<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user('citizen')?->locale
            ?? $request->session()->get('locale')
            ?? config('app.locale');

        if (in_array($locale, config('civic.locales'), true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
