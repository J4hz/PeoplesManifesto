<?php

namespace App\Services;

use App\Models\Citizen;
use App\Models\ClusterMembership;
use App\Models\IssueCluster;
use App\Models\Rating;
use App\Models\SubmissionAuthor;
use Illuminate\Support\Collection;

/**
 * Which issues a resident is already counted on and which fixes they have rated,
 * so the ward list can show "+", "Counted" or "Rate fix" without extra queries per row.
 */
class CitizenClusterState
{
    /** @var Collection<int, int>|null */
    private ?Collection $reported = null;

    /** @var Collection<int, string>|null */
    private ?Collection $rated = null;

    public function __construct(private readonly Citizen $citizen) {}

    public function reported(IssueCluster $cluster): bool
    {
        $this->reported ??= ClusterMembership::query()
            ->whereIn('submission_id', SubmissionAuthor::where('citizen_ref', $this->citizen->ref())->select('submission_id'))
            ->pluck('cluster_id')
            ->unique();

        return $this->reported->contains($cluster->id);
    }

    public function rated(IssueCluster $cluster): bool
    {
        $this->rated ??= Rating::where('citizen_ref', $this->citizen->ref())
            ->get(['cluster_id', 'round'])
            ->map(fn (Rating $r) => $r->cluster_id.':'.$r->round);

        return $this->rated->contains($cluster->id.':'.$cluster->done_round);
    }
}
