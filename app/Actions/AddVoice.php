<?php

namespace App\Actions;

use App\Jobs\RecalculateCluster;
use App\Models\Citizen;
use App\Models\IssueCluster;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Services\Clusterer;
use Illuminate\Support\Facades\DB;

class AddVoice
{
    public function __construct(private readonly Clusterer $clusterer) {}

    /**
     * Returns false when the resident is already counted on this issue.
     */
    public function handle(Citizen $citizen, IssueCluster $cluster, string $channel = 'app'): bool
    {
        if ($cluster->hasReporter($citizen)) {
            return false;
        }

        DB::transaction(function () use ($citizen, $cluster, $channel) {
            $voice = Submission::create([
                'ward_id' => $cluster->ward_id,
                'kind' => Submission::KIND_VOICE,
                'channel' => $channel,
                'category' => $cluster->category,
                'severity' => $cluster->severity,
                'processing_status' => Submission::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);

            SubmissionAuthor::create([
                'submission_id' => $voice->id,
                'citizen_ref' => $citizen->ref(),
                'citizen_id' => (string) $citizen->id,
            ]);

            $this->clusterer->addVoice($cluster, $voice);
        });

        RecalculateCluster::dispatch($cluster->id)->afterCommit();

        return true;
    }
}
