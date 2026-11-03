<?php

namespace App\Ai;

use App\Ai\Contracts\ClusterSummarizer;
use App\Models\IssueCluster;

/**
 * Without a model there is nothing better to say than the first report's title.
 */
class LocalClusterSummarizer implements ClusterSummarizer
{
    public function summarise(IssueCluster $cluster, array $reports): ?array
    {
        return null;
    }
}
