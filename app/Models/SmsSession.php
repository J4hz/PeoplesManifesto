<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class SmsSession extends Model
{
    use Prunable;

    protected $fillable = ['phone_hash', 'step', 'data', 'expires_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now()->subDay());
    }
}
