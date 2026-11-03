<?php

namespace App\Jobs;

use App\Models\IssueCluster;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Growth and trend depend on the date, so scores drift even when nobody reports.
 * An hourly pass keeps the ranking honest.
 */
class RerankOpenClusters implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        IssueCluster::query()->openish()->select('id')->chunkById(500, function ($clusters) {
            foreach ($clusters as $cluster) {
                RecalculateCluster::dispatch($cluster->id);
            }
        });
    }
}
