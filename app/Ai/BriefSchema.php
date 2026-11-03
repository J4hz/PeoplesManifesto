<?php

namespace App\Ai;

use Illuminate\Support\Facades\Validator;

class BriefSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['brief', 'rising_topics', 'improving_topics'],
            'properties' => [
                'brief' => ['type' => 'string'],
                'rising_topics' => ['type' => 'array', 'items' => ['type' => 'string']],
                'improving_topics' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
        ];
    }

    /**
     * @return array{brief: string, rising_topics: list<string>, improving_topics: list<string>}
     */
    public static function validate(mixed $data): array
    {
        if (! is_array($data) || array_diff(array_keys($data), ['brief', 'rising_topics', 'improving_topics']) !== []) {
            throw new InvalidAiOutput('Brief output has the wrong shape.');
        }

        $validator = Validator::make($data, [
            'brief' => ['required', 'string', 'min:40', 'max:3000'],
            'rising_topics' => ['present', 'array', 'max:6'],
            'rising_topics.*' => ['string', 'max:120'],
            'improving_topics' => ['present', 'array', 'max:6'],
            'improving_topics.*' => ['string', 'max:120'],
        ]);

        if ($validator->fails()) {
            throw new InvalidAiOutput('Brief output failed validation: '.implode(' ', $validator->errors()->all()));
        }

        /** @var array{brief: string, rising_topics: list<string>, improving_topics: list<string>} */
        return $validator->validated();
    }
}
