<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\IssueCluster;
use App\Models\Rating;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RespondController extends Controller
{
    public function show(Request $request, IssueCluster $cluster): View
    {
        $this->authorize('respond', $cluster);

        // Only what intake judged free of personal details. Never who sent it.
        $samples = $cluster->submissions()
            ->where('kind', Submission::KIND_REPORT)
            ->where('processing_status', Submission::STATUS_PROCESSED)
            ->where('contains_personal_info', false)
            ->with('area')
            ->latest('submissions.created_at')
            ->limit(8)
            ->get();

        $ratings = $cluster->done_round > 0 ? $cluster->currentRatings()->get(['verdict']) : collect();

        return view('leader.respond', [
            'cluster' => $cluster->load(['ward', 'routedOffice']),
            'samples' => $samples,
            'hiddenSamples' => $cluster->submissions()->where('contains_personal_info', true)->count(),
            'updates' => $cluster->updates()->with(['leader', 'author'])->latest()->get(),
            'canMarkDone' => $request->user('web')->can('markDone', $cluster),
            'verdicts' => collect(Rating::VERDICTS)->mapWithKeys(fn ($v) => [$v => $ratings->where('verdict', $v)->count()]),
        ]);
    }
}
