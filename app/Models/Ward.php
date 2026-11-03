<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
    protected $fillable = ['constituency_id', 'name', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function constituency(): BelongsTo
    {
        return $this->belongsTo(Constituency::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class)->orderBy('name');
    }

    public function clusters(): HasMany
    {
        return $this->hasMany(IssueCluster::class);
    }

    public function citizens(): HasMany
    {
        return $this->hasMany(Citizen::class);
    }

    public function briefs(): HasMany
    {
        return $this->hasMany(WeeklyBrief::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Offices responsible for this ward, from MCA up to county level.
     */
    public function offices(): Builder
    {
        $constituency = $this->constituency;

        return Office::query()->where(function (Builder $q) use ($constituency) {
            $q->where(fn ($q) => $q->where('jurisdiction_type', 'ward')->where('jurisdiction_id', $this->id))
                ->orWhere(fn ($q) => $q->where('jurisdiction_type', 'constituency')->where('jurisdiction_id', $constituency->id))
                ->orWhere(fn ($q) => $q->where('jurisdiction_type', 'county')->where('jurisdiction_id', $constituency->county_id));
        });
    }

    public function officeOfType(string $type): ?Office
    {
        return $this->offices()->where('type', $type)->first();
    }
}
