<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(string $action, ?Model $subject = null, ?array $before = null, ?array $after = null, ?User $actor = null): AuditLog
    {
        $actor ??= $this->request->user('web');

        return AuditLog::create([
            'actor_type' => $actor ? 'user' : 'system',
            'actor_id' => $actor?->id,
            'account_leader_id' => $actor?->leader_id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => $actor ? $this->request->ip() : null,
        ]);
    }
}
