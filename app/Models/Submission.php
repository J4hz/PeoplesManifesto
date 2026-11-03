<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Pgvector\Laravel\Vector;

class Submission extends Model
{
    use HasFactory;

    public const KIND_REPORT = 'report';

    public const KIND_VOICE = 'voice';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'ward_id', 'area_id', 'kind', 'channel', 'body', 'stated_category', 'stated_severity', 'photo_path',
        'category', 'severity', 'original_language', 'translation', 'ai_title', 'ai_summary',
        'contains_personal_info', 'is_actionable', 'processing_status', 'processing_attempts',
        'processing_error', 'processed_at', 'embedding',
    ];

    protected $hidden = ['embedding'];

    protected function casts(): array
    {
        return [
            'contains_personal_info' => 'boolean',
            'is_actionable' => 'boolean',
            'processed_at' => 'datetime',
            'embedding' => Vector::class,
        ];
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function membership(): HasOne
    {
        return $this->hasOne(ClusterMembership::class);
    }

    public function cluster(): HasOneThrough
    {
        return $this->hasOneThrough(IssueCluster::class, ClusterMembership::class, 'submission_id', 'id', 'id', 'cluster_id');
    }

    public function author(): HasOne
    {
        return $this->hasOne(SubmissionAuthor::class);
    }

    public function isVoice(): bool
    {
        return $this->kind === self::KIND_VOICE;
    }

    /**
     * Text used for matching and for leaders: the English translation when there is one.
     */
    public function normalisedText(): string
    {
        return (string) ($this->translation ?: $this->body);
    }
}
