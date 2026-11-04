<?php

use App\Http\Middleware\EnsureCitizenHasWard;
use App\Http\Middleware\RequireTwoFactor;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Responses\StaffHomeRedirect;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'citizen.ward' => EnsureCitizenHasWard::class,
            'two-factor' => RequireTwoFactor::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('dashboard*', 'admin*', 'staff*', 'horizon*')
            ? route('login')
            : route('citizen.sign-in'));

        $middleware->redirectUsersTo(fn (Request $request) => $request->user('web')
            ? StaffHomeRedirect::homeFor($request->user('web'))
            : route('home'));

        // Africa's Talking posts form data without a CSRF token. The route checks its own secret instead.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })->create();
