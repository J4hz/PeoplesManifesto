<?php

namespace App\Ai\Contracts;

use App\Models\IssueCluster;

interface ClusterSummarizer
{
    /**
     * @param  list<string>  $reports  English texts of a sample of member reports
     * @return array{title: string, summary: string}|null  null means keep what is there
     *
     * @throws \App\Ai\InvalidAiOutput
     */
    public function summarise(IssueCluster $cluster, array $reports): ?array;
}
