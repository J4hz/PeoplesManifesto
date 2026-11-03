<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyBrief extends Model
{
    protected $fillable = ['ward_id', 'week_start', 'body', 'rising_topics', 'improving_topics', 'stats', 'source', 'model', 'ai_batch_id'];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'rising_topics' => 'array',
            'improving_topics' => 'array',
            'stats' => 'array',
        ];
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
