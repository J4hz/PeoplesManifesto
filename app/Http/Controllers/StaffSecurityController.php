<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Where leaders, staff and admins set up their authenticator app. The forms
 * post to Fortify's own two-factor routes.
 */
class StaffSecurityController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user('web');

        $qr = null;
        if ($user->two_factor_secret !== null) {
            $qr = 'data:image/svg+xml;base64,'.base64_encode($user->twoFactorQrCodeSvg());
        }

        return view('staff.security', [
            'user' => $user,
            'qr' => $qr,
            'setupKey' => $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null
                ? decrypt($user->two_factor_secret)
                : null,
            'recoveryCodes' => $user->two_factor_recovery_codes !== null && session('status') === 'two-factor-authentication-confirmed'
                ? $user->recoveryCodes()
                : [],
        ]);
    }
}
