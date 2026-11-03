<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor_type', 'actor_id', 'account_leader_id', 'action', 'subject_type', 'subject_id', 'before', 'after', 'ip_address'];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actorUser(): ?User
    {
        return $this->actor_type === 'user' ? User::find($this->actor_id) : null;
    }
}
