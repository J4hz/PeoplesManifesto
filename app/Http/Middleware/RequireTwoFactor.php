<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Leader, staff and admin accounts cannot do anything until an authenticator
 * app is set up and confirmed. SMS is not offered as a second factor.
 */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if ($user === null) {
            return $next($request);
        }

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => __('leader.auth.inactive')]);
        }

        if (! $user->hasConfirmedTwoFactor()) {
            return redirect()->route('staff.security');
        }

        return $next($request);
    }
}
