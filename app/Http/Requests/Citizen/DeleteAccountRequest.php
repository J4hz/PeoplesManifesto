<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('citizen') !== null;
    }

    public function rules(): array
    {
        return [
            'confirm' => ['accepted'],
        ];
    }
}
