<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['job', 'model', 'ward_id', 'input_tokens', 'output_tokens', 'cache_creation_tokens', 'cache_read_tokens', 'is_batch', 'cost_usd'];

    protected function casts(): array
    {
        return [
            'is_batch' => 'boolean',
            'cost_usd' => 'float',
        ];
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }
}
