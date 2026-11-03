<?php

namespace App\Http\Requests\Citizen;

use App\Models\Rating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('citizen')?->can('rate', $this->route('cluster')) ?? false;
    }

    public function rules(): array
    {
        return [
            'verdict' => ['required', 'string', Rule::in(Rating::VERDICTS)],
            'stars' => ['required', 'integer', 'between:1,5'],
            'praise_tags' => ['nullable', 'array', 'max:4'],
            'praise_tags.*' => ['string', 'distinct', Rule::in(config('civic.rating.praise_tags'))],
            'thanks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
