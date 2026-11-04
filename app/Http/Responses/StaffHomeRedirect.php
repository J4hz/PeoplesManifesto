<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;

/**
 * Admins and leaders have different home pages. Sending everyone to the
 * leader dashboard left admins on a "not open to you" page after signing in.
 */
class StaffHomeRedirect implements LoginResponse, TwoFactorLoginResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->intended(self::homeFor($request->user('web')));
    }

    public static function homeFor(?User $user): string
    {
        return match (true) {
            $user === null => route('login'),
            $user->isAdmin() => route('admin'),
            $user->worksForLeader() => route('dashboard'),
            default => route('staff.security'),
        };
    }
}
