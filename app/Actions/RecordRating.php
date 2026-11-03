<?php

namespace App\Actions;

use App\Models\Citizen;
use App\Models\IssueCluster;
use App\Models\Rating;

class RecordRating
{
    /**
     * @param  array{verdict: string, stars?: ?int, praise_tags?: ?array<int, string>, thanks?: ?string}  $data
     */
    public function handle(Citizen $citizen, IssueCluster $cluster, array $data, string $channel): Rating
    {
        // The unique index on (cluster, round, citizen_ref) is the last line of defence against double votes.
        return Rating::create([
            'cluster_id' => $cluster->id,
            'round' => $cluster->done_round,
            'leader_id' => $cluster->done_by_leader_id,
            'citizen_ref' => $citizen->ref(),
            'verdict' => $data['verdict'],
            'stars' => $data['stars'] ?? null,
            'praise_tags' => array_values($data['praise_tags'] ?? []),
            'thanks' => filled($data['thanks'] ?? null) ? trim($data['thanks']) : null,
            'channel' => $channel,
        ]);
    }
}
