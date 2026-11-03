<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class County extends Model
{
    protected $fillable = ['name', 'slug', 'code'];

    public function constituencies(): HasMany
    {
        return $this->hasMany(Constituency::class);
    }

    public function wards(): HasManyThrough
    {
        return $this->hasManyThrough(Ward::class, Constituency::class);
    }
}
