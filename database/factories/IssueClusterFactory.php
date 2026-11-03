<?php

namespace Database\Factories;

use App\Models\IssueCluster;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssueCluster>
 */
class IssueClusterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ward_id' => fn () => Ward::query()->value('id') ?? Ward::factory(),
            'category' => 'water',
            'title' => 'No piped water for a week',
            'summary' => 'Residents say the taps have been dry since last Monday.',
            'status' => IssueCluster::STATUS_OPEN,
            'severity' => 'serious',
            'first_reported_at' => now()->subDays(3),
            'last_activity_at' => now()->subDay(),
        ];
    }

    public function done(int $daysAgo = 1): static
    {
        return $this->state(fn () => [
            'status' => IssueCluster::STATUS_DONE,
            'done_at' => now()->subDays($daysAgo),
            'done_round' => 1,
        ]);
    }
}
