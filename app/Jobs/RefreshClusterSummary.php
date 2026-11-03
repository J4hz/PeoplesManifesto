<?php

namespace App\Jobs;

use App\Ai\Contracts\ClusterSummarizer;
use App\Ai\SpendGuard;
use App\Models\IssueCluster;
use App\Models\Submission;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshClusterSummary implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 600;

    public function __construct(public int $clusterId)
    {
        $this->onQueue('intake');
    }

    public function uniqueId(): string
    {
        return (string) $this->clusterId;
    }

    public function handle(ClusterSummarizer $summarizer, SpendGuard $spend): void
    {
        $cluster = IssueCluster::with('ward')->find($this->clusterId);

        if ($cluster === null || ! $spend->briefsAllowed()) {
            return;
        }

        // Reports flagged for personal details are left out so they cannot leak into public text.
        $reports = $cluster->submissions()
            ->where('kind', Submission::KIND_REPORT)
            ->where('processing_status', Submission::STATUS_PROCESSED)
            ->where('contains_personal_info', false)
            ->latest('submissions.created_at')
            ->limit(15)
            ->get()
            ->map(fn (Submission $s) => $s->normalisedText())
            ->filter()
            ->values()
            ->all();

        if ($reports === []) {
            return;
        }

        $result = $summarizer->summarise($cluster, $reports);

        $cluster->forceFill(array_filter([
            'title' => $result['title'] ?? null,
            'summary' => $result['summary'] ?? null,
            'summary_member_count' => $cluster->submission_count,
        ], fn ($v) => $v !== null))->save();
    }
}
