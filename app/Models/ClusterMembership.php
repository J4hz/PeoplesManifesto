<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClusterMembership extends Model
{
    protected $fillable = ['cluster_id', 'submission_id', 'similarity'];

    protected function casts(): array
    {
        return ['similarity' => 'float'];
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(IssueCluster::class, 'cluster_id');
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
