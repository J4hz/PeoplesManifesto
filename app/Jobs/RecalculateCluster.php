<?php

namespace App\Jobs;

use App\Models\IssueCluster;
use App\Services\ClusterRanker;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateCluster implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    public function __construct(public int $clusterId) {}

    public function uniqueId(): string
    {
        return (string) $this->clusterId;
    }

    public function handle(ClusterRanker $ranker): void
    {
        $cluster = IssueCluster::find($this->clusterId);

        if ($cluster !== null) {
            $ranker->recalculate($cluster);
        }
    }
}
