<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChooseWardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('citizen') !== null;
    }

    public function rules(): array
    {
        return [
            'ward_id' => ['required', 'integer', Rule::exists('wards', 'id')->where('is_active', true)],
        ];
    }
}
