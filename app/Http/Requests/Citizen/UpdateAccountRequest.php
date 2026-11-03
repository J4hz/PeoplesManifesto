<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('citizen') !== null;
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(config('civic.locales'))],
            'sms_notifications' => ['nullable', 'boolean'],
        ];
    }
}
