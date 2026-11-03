<?php

namespace App\Policies;

use App\Models\Citizen;
use App\Models\ClusterUpdate;
use App\Models\User;

class ClusterUpdatePolicy
{
    public function __construct(private readonly IssueClusterPolicy $clusters) {}

    public function view(User|Citizen|null $actor, ClusterUpdate $update): bool
    {
        if ($update->isPublished()) {
            return true;
        }

        return $actor instanceof User && $this->clusters->respond($actor, $update->cluster);
    }

    /**
     * A staff draft goes public only when the leader it belongs to publishes it.
     */
    public function publish(User|Citizen $actor, ClusterUpdate $update): bool
    {
        if (! $actor instanceof User || $update->isPublished() || ! $actor->isLeader()) {
            return false;
        }

        if ($actor->leader_id !== $update->leader_id || ! $this->clusters->respond($actor, $update->cluster)) {
            return false;
        }

        return $update->status !== 'done' || $this->clusters->markDone($actor, $update->cluster);
    }

    /**
     * Drafts can be discarded by their author or the leader. Published updates are permanent.
     */
    public function delete(User|Citizen $actor, ClusterUpdate $update): bool
    {
        return $actor instanceof User
            && ! $update->isPublished()
            && $actor->leader_id === $update->leader_id
            && ($actor->id === $update->author_user_id || $actor->isLeader());
    }

    public function update(User|Citizen $actor, ClusterUpdate $update): bool
    {
        return false;
    }
}
