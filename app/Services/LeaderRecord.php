<?php

namespace App\Services;

use App\Models\IssueCluster;
use App\Models\Leader;
use App\Models\Rating;
use Illuminate\Support\Collection;

/**
 * The numbers shown on a leader's public record and on their ratings page.
 * Everything here is aggregated. No rating is tied to a resident.
 */
class LeaderRecord
{
    /**
     * @return array{rated_fixes: int, average_stars: ?float, ratings: int, confirmed_share: ?float, praise: Collection<string, int>, commendations: Collection<int, Rating>, recent_done: Collection<int, IssueCluster>}
     */
    public function for(Leader $leader): array
    {
        $ratings = Rating::where('leader_id', $leader->id);

        $total = (clone $ratings)->count();
        $fixed = (clone $ratings)->where('verdict', Rating::FIXED)->count();

        $praise = collect(config('civic.rating.praise_tags'))->mapWithKeys(fn ($tag) => [
            $tag => (clone $ratings)->whereJsonContains('praise_tags', $tag)->count(),
        ]);

        $average = (clone $ratings)->whereNotNull('stars')->avg('stars');

        return [
            'rated_fixes' => (clone $ratings)->distinct()->count('cluster_id'),
            'average_stars' => $average === null ? null : round((float) $average, 1),
            'ratings' => $total,
            'confirmed_share' => $total > 0 ? $fixed / $total : null,
            'praise' => $praise,
            'commendations' => (clone $ratings)->whereNotNull('thanks')->with('cluster.ward')->latest()->limit(10)->get(),
            'recent_done' => IssueCluster::where('done_by_leader_id', $leader->id)->latest('done_at')->limit(10)->get(),
        ];
    }
}
