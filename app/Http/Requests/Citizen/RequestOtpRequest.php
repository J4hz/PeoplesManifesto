<?php

namespace App\Http\Requests\Citizen;

use App\Rules\Turnstile;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail) {
                if (PhoneNumber::normalise(is_string($value) ? $value : null) === null) {
                    $fail(__('validation.kenyan_phone'));
                }
            }],
            'cf-turnstile-response' => [new Turnstile],
        ];
    }

    public function phone(): string
    {
        return (string) PhoneNumber::normalise($this->string('phone')->toString());
    }
}
