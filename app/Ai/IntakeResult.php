<?php

namespace App\Ai;

/**
 * Model output that has already passed IntakeSchema::validate().
 */
final class IntakeResult
{
    public function __construct(
        public readonly string $language,
        public readonly string $translation,
        public readonly string $category,
        public readonly string $severity,
        public readonly ?string $wardName,
        public readonly ?string $areaName,
        public readonly string $title,
        public readonly string $summary,
        public readonly bool $containsPersonalInfo,
        public readonly bool $isActionable,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromValidated(array $data): self
    {
        return new self(
            language: $data['language'],
            translation: $data['translation_en'],
            category: $data['category'],
            severity: $data['severity'],
            wardName: $data['ward'] !== '' ? $data['ward'] : null,
            areaName: $data['area'] !== '' ? $data['area'] : null,
            title: $data['title'],
            summary: $data['summary'],
            containsPersonalInfo: $data['contains_personal_info'],
            isActionable: $data['is_actionable'],
        );
    }
}
