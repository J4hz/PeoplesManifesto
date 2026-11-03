<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Leader extends Model
{
    protected $fillable = ['office_id', 'user_id', 'name', 'slug', 'term_started_on', 'term_ends_on', 'is_current'];

    protected function casts(): array
    {
        return [
            'term_started_on' => 'date',
            'term_ends_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Everyone who signs in on this leader's account, the leader included.
     */
    public function team(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ClusterUpdate::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    public function coversWard(int $wardId): bool
    {
        return $this->office->coversWard($wardId);
    }
}
