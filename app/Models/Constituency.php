<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Constituency extends Model
{
    protected $fillable = ['county_id', 'name', 'slug'];

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class);
    }
}
