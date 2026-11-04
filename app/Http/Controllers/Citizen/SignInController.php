<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\RequestOtpRequest;
use App\Http\Requests\Citizen\VerifyOtpRequest;
use App\Models\Citizen;
use App\Services\IdentityHasher;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SignInController extends Controller
{
    public function create(): View
    {
        return view('citizen.auth.phone');
    }

    public function store(RequestOtpRequest $request, OtpService $otp): RedirectResponse
    {
        $phone = $request->phone();
        $code = $otp->issue($phone, app()->getLocale());

        $request->session()->put('otp_phone', $phone);

        if (config('civic.demo.enabled')) {
            $request->session()->put('demo_code', $code);
        }

        return redirect()->route('citizen.sign-in.verify');
    }

    public function verifyForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('otp_phone')) {
            return redirect()->route('citizen.sign-in');
        }

        return view('citizen.auth.verify', [
            'maskedPhone' => PhoneNumber::mask((string) $request->session()->get('otp_phone')),
            'demoCode' => config('civic.demo.enabled') ? $request->session()->get('demo_code') : null,
        ]);
    }

    public function verify(VerifyOtpRequest $request, OtpService $otp, IdentityHasher $hasher): RedirectResponse
    {
        $phone = (string) $request->session()->get('otp_phone');

        if (! $otp->verify($phone, $request->code())) {
            return back()->withErrors(['code' => __('citizen.auth.code_wrong')]);
        }

        $citizen = Citizen::firstOrCreate(
            ['phone_hash' => $hasher->phone($phone)],
            ['phone' => $phone, 'locale' => app()->getLocale()],
        );
        $citizen->forceFill(['verified_at' => $citizen->verified_at ?? now(), 'last_seen_at' => now()])->save();

        $request->session()->forget(['otp_phone', 'demo_code']);
        Auth::guard('citizen')->login($citizen, remember: true);
        $request->session()->regenerate();

        return $citizen->ward_id === null
            ? redirect()->route('citizen.ward.choose')
            : redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('citizen')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
