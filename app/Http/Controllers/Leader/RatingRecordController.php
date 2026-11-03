<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\IssueCluster;
use App\Models\Rating;
use App\Services\LeaderRecord;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RatingRecordController extends Controller
{
    public function index(Request $request, LeaderRecord $record): View
    {
        $leader = $request->user('web')->leader->load('office');

        $clusters = IssueCluster::query()
            ->whereIn('id', Rating::where('leader_id', $leader->id)->select('cluster_id'))
            ->with('ward')
            ->withCount([
                'ratings as fixed_count' => fn ($q) => $q->where('leader_id', $leader->id)->where('verdict', Rating::FIXED),
                'ratings as partly_count' => fn ($q) => $q->where('leader_id', $leader->id)->where('verdict', Rating::PARTLY),
                'ratings as not_fixed_count' => fn ($q) => $q->where('leader_id', $leader->id)->where('verdict', Rating::NOT_FIXED),
            ])
            ->latest('done_at')
            ->paginate(25);

        return view('leader.ratings', [
            'leader' => $leader,
            'record' => $record->for($leader),
            'clusters' => $clusters,
        ]);
    }
}
