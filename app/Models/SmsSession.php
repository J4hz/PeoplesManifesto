<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSession extends Model
{
    protected $fillable = ['phone_hash', 'step', 'data', 'expires_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}
