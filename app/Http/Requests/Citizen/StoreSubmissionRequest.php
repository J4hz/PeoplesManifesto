<?php

namespace App\Http\Requests\Citizen;

use App\Models\Submission;
use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('citizen')?->can('create', Submission::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:8', 'max:2000'],
            'category' => ['nullable', 'string', Rule::in(config('civic.categories'))],
            'severity' => ['nullable', 'string', Rule::in(array_keys(config('civic.severities')))],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('civic.photos.max_kilobytes'), 'dimensions:max_width=8000,max_height=8000'],
            'cf-turnstile-response' => [new Turnstile],
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => __('citizen.raise.body_attribute'),
        ];
    }
}
