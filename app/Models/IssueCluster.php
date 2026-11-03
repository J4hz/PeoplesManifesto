<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Pgvector\Laravel\Vector;

class IssueCluster extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_DONE = 'done';

    public const STATUS_REOPENED = 'reopened';

    protected $fillable = [
        'ward_id', 'routed_office_id', 'category', 'title', 'summary', 'status', 'severity',
        'resident_count', 'submission_count', 'areas_count', 'trend', 'growth', 'score',
        'centroid', 'centroid_weight', 'first_reported_at', 'last_activity_at', 'first_response_at',
        'done_at', 'done_by_leader_id', 'done_round', 'rating_window_closed_at', 'reopened_at',
        'summary_member_count',
    ];

    protected $hidden = ['centroid'];

    protected function casts(): array
    {
        return [
            'centroid' => Vector::class,
            'growth' => 'float',
            'score' => 'float',
            'first_reported_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'first_response_at' => 'datetime',
            'done_at' => 'datetime',
            'rating_window_closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function routedOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'routed_office_id');
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(Leader::class, 'done_by_leader_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ClusterMembership::class, 'cluster_id');
    }

    public function submissions(): BelongsToMany
    {
        return $this->belongsToMany(Submission::class, 'cluster_memberships', 'cluster_id', 'submission_id')
            ->withPivot('similarity')
            ->withTimestamps();
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ClusterUpdate::class, 'cluster_id');
    }

    public function publishedUpdates(): HasMany
    {
        return $this->updates()->whereNotNull('published_at')->latest('published_at');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'cluster_id');
    }

    public function currentRatings(): HasMany
    {
        return $this->ratings()->where('round', $this->done_round);
    }

    public function scopeVisible(Builder $query): void
    {
        $query->where('resident_count', '>', 0);
    }

    public function scopeRanked(Builder $query): void
    {
        $query->orderByDesc('score')->orderByDesc('last_activity_at');
    }

    public function scopeOpenish(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_ACKNOWLEDGED, self::STATUS_IN_PROGRESS, self::STATUS_REOPENED]);
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isRatingOpen(): bool
    {
        return $this->isDone() && $this->rating_window_closed_at === null;
    }

    /**
     * Distinct citizen references of everyone who reported or added their voice.
     *
     * @return Collection<int, string>
     */
    public function reporterRefs(): Collection
    {
        return SubmissionAuthor::query()
            ->whereIn('submission_id', $this->memberships()->select('submission_id'))
            ->distinct()
            ->pluck('citizen_ref');
    }

    public function hasReporter(Citizen $citizen): bool
    {
        return SubmissionAuthor::query()
            ->whereIn('submission_id', $this->memberships()->select('submission_id'))
            ->where('citizen_ref', $citizen->ref())
            ->exists();
    }

    /**
     * @return Collection<int, Citizen>
     */
    public function reporters(): Collection
    {
        $ids = SubmissionAuthor::query()
            ->whereIn('submission_id', $this->memberships()->select('submission_id'))
            ->whereNotNull('citizen_id')
            ->get()
            ->map(fn (SubmissionAuthor $a) => (int) $a->citizen_id)
            ->unique()
            ->values();

        return Citizen::whereIn('id', $ids)->get();
    }
}
