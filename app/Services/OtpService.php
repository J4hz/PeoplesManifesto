<?php

namespace App\Services;

use App\Jobs\SendSms;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    public function __construct(private readonly IdentityHasher $hasher) {}

    /**
     * Returns the code so demo mode can show it on screen. Everywhere else it only travels by SMS.
     */
    public function issue(string $phone, string $locale = 'en'): string
    {
        $hash = $this->hasher->phone($phone);

        // Only the newest code works, so an old SMS arriving late cannot be used.
        OtpCode::where('phone_hash', $hash)->whereNull('consumed_at')->update(['consumed_at' => now()]);

        $length = (int) config('civic.otp.length');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        OtpCode::create([
            'phone_hash' => $hash,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('civic.otp.ttl_minutes')),
        ]);

        SendSms::dispatch($phone, __('sms.otp', [
            'code' => $code,
            'minutes' => config('civic.otp.ttl_minutes'),
        ], $locale));

        return $code;
    }

    public function verify(string $phone, string $code): bool
    {
        $otp = OtpCode::where('phone_hash', $this->hasher->phone($phone))
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if ($otp === null || ! $otp->isUsable()) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return true;
    }
}
