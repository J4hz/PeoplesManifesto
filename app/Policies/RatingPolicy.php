<?php

namespace App\Policies;

use App\Models\Citizen;
use App\Models\Rating;
use App\Models\User;

/**
 * Ratings are the residents' verdict on a leader's work. Once given, nobody can
 * change or remove one. Creating a rating is checked by IssueClusterPolicy::rate.
 */
class RatingPolicy
{
    public function update(User|Citizen $actor, Rating $rating): bool
    {
        return false;
    }

    public function delete(User|Citizen $actor, Rating $rating): bool
    {
        return false;
    }

    public function hide(User|Citizen $actor, Rating $rating): bool
    {
        return false;
    }
}
