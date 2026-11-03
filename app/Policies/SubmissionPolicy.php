<?php

namespace App\Policies;

use App\Models\Citizen;
use App\Models\Submission;
use App\Models\User;

/**
 * Nobody edits or deletes what a resident submitted. Not leaders, not staff,
 * not admins, not the resident. The database enforces the same rule.
 */
class SubmissionPolicy
{
    public function create(User|Citizen $actor): bool
    {
        return $actor instanceof Citizen && $actor->ward_id !== null;
    }

    public function view(User|Citizen $actor, Submission $submission): bool
    {
        return $actor instanceof Citizen && $this->isAuthor($actor, $submission);
    }

    public function viewPhoto(User|Citizen $actor, Submission $submission): bool
    {
        if ($actor instanceof Citizen) {
            return $this->isAuthor($actor, $submission);
        }

        return $actor->is_active
            && $actor->worksForLeader()
            && $actor->leader->coversWard($submission->ward_id);
    }

    public function update(User|Citizen $actor, Submission $submission): bool
    {
        return false;
    }

    public function delete(User|Citizen $actor, Submission $submission): bool
    {
        return false;
    }

    public function hide(User|Citizen $actor, Submission $submission): bool
    {
        return false;
    }

    private function isAuthor(Citizen $citizen, Submission $submission): bool
    {
        return $submission->author()->where('citizen_ref', $citizen->ref())->exists();
    }
}
