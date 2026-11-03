<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ward_id' => fn () => Ward::query()->value('id') ?? Ward::factory(),
            'kind' => Submission::KIND_REPORT,
            'channel' => 'app',
            'body' => 'Hakuna maji kwa wiki moja sasa',
            'processing_status' => Submission::STATUS_PENDING,
        ];
    }

    public function processed(string $category = 'water', string $severity = 'serious'): static
    {
        return $this->state(fn () => [
            'category' => $category,
            'severity' => $severity,
            'original_language' => 'sw',
            'translation' => 'No water for a week now',
            'ai_title' => 'No water for a week',
            'ai_summary' => 'Residents report no water for a week.',
            'processing_status' => Submission::STATUS_PROCESSED,
            'processed_at' => now(),
        ]);
    }
}
