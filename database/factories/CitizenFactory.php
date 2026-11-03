<?php

namespace Database\Factories;

use App\Models\Citizen;
use App\Models\Ward;
use App\Services\IdentityHasher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Citizen>
 */
class CitizenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phone' => '+2547'.fake()->unique()->numerify('########'),
            'phone_hash' => fn (array $attributes) => app(IdentityHasher::class)->phone($attributes['phone']),
            'ward_id' => fn () => Ward::query()->value('id') ?? Ward::factory(),
            'locale' => 'en',
            'trust_score' => 1,
            'sms_notifications' => true,
            'verified_at' => now(),
        ];
    }

    public function inWard(Ward $ward): static
    {
        return $this->state(fn () => ['ward_id' => $ward->id]);
    }

    public function createdDaysAgo(int $days): static
    {
        return $this->state(fn () => ['created_at' => now()->subDays($days), 'updated_at' => now()->subDays($days)]);
    }
}
