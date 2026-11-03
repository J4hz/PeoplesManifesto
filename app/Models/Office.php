<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;

class Office extends Model
{
    protected $fillable = ['type', 'name', 'jurisdiction_type', 'jurisdiction_id'];

    public function jurisdiction(): MorphTo
    {
        return $this->morphTo();
    }

    public function leaders(): HasMany
    {
        return $this->hasMany(Leader::class);
    }

    public function currentLeader(): HasOne
    {
        return $this->hasOne(Leader::class)->where('is_current', true)->latestOfMany();
    }

    /**
     * @return Collection<int, int>
     */
    public function wardIds(): Collection
    {
        return once(function () {
            return match ($this->jurisdiction_type) {
                'ward' => collect([$this->jurisdiction_id]),
                'constituency' => Ward::where('constituency_id', $this->jurisdiction_id)->pluck('id'),
                'county' => Ward::whereIn(
                    'constituency_id',
                    Constituency::where('county_id', $this->jurisdiction_id)->select('id')
                )->pluck('id'),
                default => collect(),
            };
        });
    }

    public function coversWard(int $wardId): bool
    {
        return $this->wardIds()->contains($wardId);
    }
}
