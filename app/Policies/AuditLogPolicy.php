<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\Citizen;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User|Citizen $actor): bool
    {
        return $actor instanceof User && $actor->is_active && ($actor->worksForLeader() || $actor->isAdmin());
    }

    public function view(User|Citizen $actor, AuditLog $log): bool
    {
        if (! $actor instanceof User || ! $actor->is_active) {
            return false;
        }

        return $actor->isAdmin()
            || ($actor->leader_id !== null && $log->account_leader_id === $actor->leader_id);
    }

    public function update(User|Citizen $actor, AuditLog $log): bool
    {
        return false;
    }

    public function delete(User|Citizen $actor, AuditLog $log): bool
    {
        return false;
    }
}
