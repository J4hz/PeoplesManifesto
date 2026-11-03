<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiBatch extends Model
{
    protected $fillable = ['provider_batch_id', 'kind', 'status', 'week_start', 'request_map', 'ended_at', 'processed_at'];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'request_map' => 'array',
            'ended_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
