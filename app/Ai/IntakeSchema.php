<?php

namespace App\Ai;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * One schema, used twice: sent to the API as the structured output format, and
 * enforced again here before anything touches the database. The model's output
 * is treated as untrusted until it passes validate().
 */
class IntakeSchema
{
    public const LANGUAGES = ['en', 'sw', 'sheng', 'kikuyu', 'other'];

    /**
     * @return array<string, mixed>
     */
    public static function jsonSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['language', 'translation_en', 'category', 'severity', 'ward', 'area', 'title', 'summary', 'contains_personal_info', 'is_actionable'],
            'properties' => [
                'language' => ['type' => 'string', 'enum' => self::LANGUAGES],
                'translation_en' => ['type' => 'string'],
                'category' => ['type' => 'string', 'enum' => config('civic.categories')],
                'severity' => ['type' => 'string', 'enum' => array_keys(config('civic.severities'))],
                'ward' => ['type' => 'string'],
                'area' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'contains_personal_info' => ['type' => 'boolean'],
                'is_actionable' => ['type' => 'boolean'],
            ],
        ];
    }

    /**
     * @param  mixed  $data  decoded JSON from the model
     * @param  list<string>  $wardNames
     * @param  list<string>  $areaNames
     */
    public static function validate(mixed $data, array $wardNames, array $areaNames): IntakeResult
    {
        if (! is_array($data) || array_is_list($data)) {
            throw new InvalidAiOutput('Intake output is not a JSON object.');
        }

        $expected = array_keys(self::jsonSchema()['properties']);
        $extra = array_diff(array_keys($data), $expected);
        if ($extra !== []) {
            throw new InvalidAiOutput('Intake output has unexpected keys: '.implode(', ', $extra));
        }

        $validator = Validator::make($data, [
            'language' => ['required', 'string', Rule::in(self::LANGUAGES)],
            'translation_en' => ['required', 'string', 'max:3000'],
            'category' => ['required', 'string', Rule::in(config('civic.categories'))],
            'severity' => ['required', 'string', Rule::in(array_keys(config('civic.severities')))],
            'ward' => ['present', 'string', Rule::in(array_merge([''], $wardNames))],
            'area' => ['present', 'string', Rule::in(array_merge([''], $areaNames))],
            'title' => ['required', 'string', 'max:100'],
            'summary' => ['required', 'string', 'max:400'],
            'contains_personal_info' => ['present', self::strictBoolean()],
            'is_actionable' => ['present', self::strictBoolean()],
        ]);

        if ($validator->fails()) {
            throw new InvalidAiOutput('Intake output failed validation: '.implode(' ', $validator->errors()->all()));
        }

        return IntakeResult::fromValidated($validator->validated());
    }

    /**
     * Laravel's boolean rule accepts "1" and "true" strings. The schema says JSON booleans only.
     */
    private static function strictBoolean(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_bool($value)) {
                $fail("{$attribute} must be a JSON boolean.");
            }
        };
    }
}
