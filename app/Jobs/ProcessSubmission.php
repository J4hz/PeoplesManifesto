<?php

namespace App\Jobs;

use App\Ai\Contracts\IntakeAnalyzer;
use App\Ai\InvalidAiOutput;
use App\Ai\IntakeResult;
use App\Ai\SpendGuard;
use App\Embeddings\Embedder;
use App\Models\Area;
use App\Models\Submission;
use App\Models\Ward;
use App\Services\Clusterer;
use App\Services\ClusterRanker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Pgvector\Laravel\Vector;
use Throwable;

/**
 * Intake for one report: Haiku tags it, the embedder places it, the clusterer
 * groups it and the ranker rescores the group. The report only becomes
 * "processed" once the model's output has passed the schema.
 */
class ProcessSubmission implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public function __construct(public int $submissionId)
    {
        $this->onQueue('intake');
        $this->tries = (int) config('ai.intake.max_attempts');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(IntakeAnalyzer $analyzer, Embedder $embedder, Clusterer $clusterer, ClusterRanker $ranker, SpendGuard $spend): void
    {
        $submission = Submission::with('ward.constituency')->find($this->submissionId);

        if ($submission === null || $submission->processing_status === Submission::STATUS_PROCESSED || $submission->isVoice()) {
            return;
        }

        if (! $spend->intakeAllowed()) {
            // Past the hard ceiling. Wait for next month's budget instead of failing the report.
            $this->release(3600);

            return;
        }

        $wardNames = Ward::query()->active()
            ->where('constituency_id', $submission->ward->constituency_id)
            ->orderBy('name')->pluck('name')->all();
        $areaNames = $submission->ward->areas()->pluck('name')->all();

        $submission->increment('processing_attempts');

        try {
            $result = $analyzer->analyse($submission, $wardNames, $areaNames);
        } catch (InvalidAiOutput $e) {
            $submission->forceFill([
                'processing_status' => Submission::STATUS_FAILED,
                'processing_error' => Str::limit($e->getMessage(), 250),
            ])->save();

            throw $e;
        }

        $this->apply($submission, $result);
        $submission->embedding = new Vector($embedder->embed($submission->normalisedText()));

        $cluster = DB::transaction(function () use ($submission, $clusterer) {
            $submission->forceFill([
                'processing_status' => Submission::STATUS_PROCESSED,
                'processing_error' => null,
                'processed_at' => now(),
            ])->save();

            return $clusterer->assign($submission);
        });

        $ranker->recalculate($cluster);

        if (in_array($cluster->submission_count, config('civic.clustering.summary_refresh_at'), true)) {
            RefreshClusterSummary::dispatch($cluster->id);
        }
    }

    public function failed(?Throwable $e): void
    {
        Submission::whereKey($this->submissionId)
            ->where('processing_status', '!=', Submission::STATUS_PROCESSED)
            ->update([
                'processing_status' => Submission::STATUS_FAILED,
                'processing_error' => Str::limit($e?->getMessage() ?? 'Intake failed', 250),
            ]);
    }

    /**
     * The resident's own choices win. The model fills only what they left blank.
     */
    private function apply(Submission $submission, IntakeResult $result): void
    {
        if ($result->wardName !== null && $result->wardName !== $submission->ward->name) {
            $ward = Ward::where('constituency_id', $submission->ward->constituency_id)->where('name', $result->wardName)->first();
            if ($ward !== null) {
                $submission->ward_id = $ward->id;
                $submission->setRelation('ward', $ward);
            }
        }

        $areaId = $result->areaName === null ? null
            : Area::where('ward_id', $submission->ward_id)->where('name', $result->areaName)->value('id');

        $submission->forceFill([
            'category' => $submission->stated_category ?? $result->category,
            'severity' => $submission->stated_severity ?? $result->severity,
            'original_language' => $result->language,
            'translation' => $result->translation,
            'ai_title' => $result->title,
            'ai_summary' => $result->summary,
            'contains_personal_info' => $result->containsPersonalInfo,
            'is_actionable' => $result->isActionable,
            'area_id' => $submission->area_id ?? $areaId,
        ]);
    }
}
