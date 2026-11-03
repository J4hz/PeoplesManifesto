<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    public const FIXED = 'fixed';

    public const PARTLY = 'partly';

    public const NOT_FIXED = 'not_fixed';

    public const VERDICTS = [self::FIXED, self::PARTLY, self::NOT_FIXED];

    protected $fillable = ['cluster_id', 'round', 'leader_id', 'citizen_ref', 'verdict', 'stars', 'praise_tags', 'thanks', 'channel'];

    protected $hidden = ['citizen_ref'];

    protected function casts(): array
    {
        return [
            'praise_tags' => 'array',
            'stars' => 'integer',
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
}
