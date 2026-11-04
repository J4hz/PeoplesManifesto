<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Responses\StaffHomeRedirect;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, StaffHomeRedirect::class);
        $this->app->singleton(TwoFactorLoginResponse::class, StaffHomeRedirect::class);
    }

    public function boot(): void
    {
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => view('staff.auth.login'));
        Fortify::twoFactorChallengeView(fn () => view('staff.auth.two-factor-challenge'));
        Fortify::requestPasswordResetLinkView(fn () => view('staff.auth.forgot-password'));
        Fortify::resetPasswordView(fn (Request $request) => view('staff.auth.reset-password', ['request' => $request]));
        Fortify::confirmPasswordView(fn () => view('staff.auth.confirm-password'));

        // Suspended accounts are refused at the door, not after login.
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', Str::lower((string) $request->input('email')))->first();

            if ($user && $user->is_active && Hash::check((string) $request->input('password'), $user->password)) {
                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return [
                Limit::perMinute(5)->by($throttleKey),
                Limit::perHour(30)->by('login-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by((string) $request->session()->get('login.id')));

        $this->auditAccountEvents();
    }

    private function auditAccountEvents(): void
    {
        Event::listen(Login::class, function (Login $event) {
            if ($event->guard === 'web' && $event->user instanceof User) {
                app(AuditLogger::class)->record('account.signed_in', $event->user, actor: $event->user);
            }
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->guard === 'web' && $event->user instanceof User) {
                app(AuditLogger::class)->record('account.signed_out', $event->user, actor: $event->user);
            }
        });

        Event::listen(TwoFactorAuthenticationConfirmed::class, fn ($e) => app(AuditLogger::class)->record('account.two_factor_confirmed', $e->user, actor: $e->user));
        Event::listen(TwoFactorAuthenticationDisabled::class, fn ($e) => app(AuditLogger::class)->record('account.two_factor_disabled', $e->user, actor: $e->user));
        Event::listen(RecoveryCodesGenerated::class, fn ($e) => app(AuditLogger::class)->record('account.recovery_codes_generated', $e->user, actor: $e->user));
    }
}
