<?php

namespace App\Policies;

use App\Models\Citizen;
use App\Models\IssueCluster;
use App\Models\Rating;
use App\Models\User;

class IssueClusterPolicy
{
    /**
     * Cluster pages show anonymous, aggregated data, so anyone may read them.
     */
    public function view(User|Citizen|null $actor, IssueCluster $cluster): bool
    {
        return true;
    }

    public function addVoice(User|Citizen $actor, IssueCluster $cluster): bool
    {
        return $actor instanceof Citizen
            && $actor->ward_id === $cluster->ward_id
            && ! $cluster->isDone();
    }

    /**
     * Only residents who reported or added their voice can rate, once per Done round.
     */
    public function rate(User|Citizen $actor, IssueCluster $cluster): bool
    {
        return $actor instanceof Citizen
            && $cluster->isRatingOpen()
            && $cluster->hasReporter($actor)
            && ! Rating::where('cluster_id', $cluster->id)
                ->where('round', $cluster->done_round)
                ->where('citizen_ref', $actor->ref())
                ->exists();
    }

    /**
     * Leaders and their staff, inside the leader's jurisdiction only.
     */
    public function respond(User|Citizen $actor, IssueCluster $cluster): bool
    {
        return $actor instanceof User
            && $actor->is_active
            && $actor->worksForLeader()
            && $actor->leader->coversWard($cluster->ward_id);
    }

    /**
     * Staff can draft, but only the elected leader can tell residents it is done.
     */
    public function markDone(User|Citizen $actor, IssueCluster $cluster): bool
    {
        return $actor instanceof User
            && $actor->isLeader()
            && $this->respond($actor, $cluster);
    }

    public function update(User|Citizen $actor, IssueCluster $cluster): bool
    {
        return $this->respond($actor, $cluster);
    }

    public function delete(User|Citizen $actor, IssueCluster $cluster): bool
    {
        return false;
    }
}
