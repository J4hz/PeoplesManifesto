<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The brief's "Update". Named ClusterUpdate so it does not shadow the query builder's update().
 */
class ClusterUpdate extends Model
{
    protected $fillable = [
        'cluster_id', 'leader_id', 'author_user_id', 'status', 'message', 'expected_completion_on',
        'photo_path', 'notify_reporters', 'published_at', 'published_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'expected_completion_on' => 'date',
            'notify_reporters' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(IssueCluster::class, 'cluster_id');
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(Leader::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }
}
