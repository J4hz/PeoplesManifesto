<?php

namespace Database\Factories;

use App\Models\Constituency;
use App\Models\County;
use App\Models\Ward;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ward>
 */
class WardFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Ward '.Str::upper(Str::random(5));

        return [
            'constituency_id' => fn () => Constituency::query()->value('id') ?? Constituency::create([
                'county_id' => County::firstOrCreate(['slug' => 'test-county'], ['name' => 'Test County'])->id,
                'name' => 'Test Constituency',
                'slug' => 'test-constituency-'.Str::lower(Str::random(6)),
            ])->id,
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ];
    }
}
