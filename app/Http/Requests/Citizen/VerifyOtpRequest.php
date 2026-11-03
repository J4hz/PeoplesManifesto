<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->session()->has('otp_phone');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'regex:/^\s*\d{'.config('civic.otp.length').'}\s*$/'],
        ];
    }

    public function code(): string
    {
        return trim($this->string('code')->toString());
    }
}
