<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\IssueCluster;
use App\Models\Rating;
use App\Services\CitizenClusterState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClusterController extends Controller
{
    public function show(Request $request, IssueCluster $cluster): View
    {
        abort_if($cluster->resident_count === 0, 404);

        $citizen = $request->user('citizen');
        $counts = $this->counts($cluster);

        return view('public.cluster', [
            'cluster' => $cluster->load(['ward', 'routedOffice.currentLeader', 'doneBy']),
            'updates' => $cluster->publishedUpdates()->with('leader.office')->get(),
            'fixedCount' => $counts['fixed'],
            'totalCount' => $counts['total'],
            'citizen' => $citizen,
            'state' => $citizen ? new CitizenClusterState($citizen) : null,
        ]);
    }

    /**
     * Polled by the rating page so residents see the count move while they decide.
     */
    public function fixedCount(IssueCluster $cluster): JsonResponse
    {
        return response()->json($this->counts($cluster))
            ->header('Cache-Control', 'public, max-age=15');
    }

    /**
     * @return array{fixed: int, total: int}
     */
    private function counts(IssueCluster $cluster): array
    {
        if ($cluster->done_round === 0) {
            return ['fixed' => 0, 'total' => 0];
        }

        $ratings = $cluster->currentRatings();

        return [
            'fixed' => (clone $ratings)->where('verdict', Rating::FIXED)->count(),
            'total' => $ratings->count(),
        ];
    }
}
