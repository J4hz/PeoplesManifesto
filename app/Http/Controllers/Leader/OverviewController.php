<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\ClusterMembership;
use App\Models\IssueCluster;
use App\Models\SubmissionAuthor;
use App\Models\WeeklyBrief;
use App\Services\Jurisdiction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user('web')->load('leader.office');
        $scope = new Jurisdiction($user);
        $wards = $scope->wards();

        $wardFilter = $request->integer('ward') ?: null;
        if ($wardFilter !== null && ! $wards->contains('id', $wardFilter)) {
            $wardFilter = null;
        }
        $routedOnly = $request->boolean('routed');

        $clusters = $scope->clusters()
            ->openish()
            ->with('ward')
            ->when($wardFilter, fn ($q) => $q->where('ward_id', $wardFilter))
            ->when($routedOnly, fn ($q) => $q->where('routed_office_id', $user->leader->office_id))
            ->ranked()
            ->paginate(30)
            ->withQueryString();

        $briefs = WeeklyBrief::whereIn('ward_id', $wardFilter ? [$wardFilter] : $wards->pluck('id'))
            ->with('ward')
            ->orderByDesc('week_start')
            ->get()
            ->unique('ward_id')
            ->values();

        return view('leader.overview', [
            'user' => $user,
            'wards' => $wards,
            'wardFilter' => $wardFilter,
            'routedOnly' => $routedOnly,
            'clusters' => $clusters,
            'briefs' => $briefs,
            'stats' => $this->stats($scope),
        ]);
    }

    /**
     * @return array{residents: int, open: int, response_days: ?float, resolved: int}
     */
    private function stats(Jurisdiction $scope): array
    {
        $wardIds = $scope->wardIds();

        $residents = SubmissionAuthor::query()
            ->whereIn('submission_id', ClusterMembership::query()
                ->whereIn('cluster_id', IssueCluster::whereIn('ward_id', $wardIds)->select('id'))
                ->where('created_at', '>=', now()->subDays(30))
                ->select('submission_id'))
            ->distinct()
            ->count('citizen_ref');

        $responseSeconds = IssueCluster::query()
            ->whereIn('ward_id', $wardIds)
            ->whereNotNull('first_response_at')
            ->where('first_response_at', '>=', now()->subDays(90))
            ->avg(DB::raw('EXTRACT(EPOCH FROM (first_response_at - first_reported_at))'));

        return [
            'residents' => $residents,
            'open' => $scope->clusters()->openish()->count(),
            'response_days' => $responseSeconds === null ? null : round((float) $responseSeconds / 86400, 1),
            'resolved' => IssueCluster::whereIn('ward_id', $wardIds)
                ->where('status', IssueCluster::STATUS_DONE)
                ->where('done_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }
}
