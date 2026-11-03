<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\IssueCluster;
use App\Models\Ward;
use Illuminate\View\View;

class WardController extends Controller
{
    public function show(Ward $ward): View
    {
        abort_unless($ward->is_active, 404);

        return view('public.ward', [
            'ward' => $ward->load('constituency.county'),
            'open' => IssueCluster::where('ward_id', $ward->id)->visible()->openish()->ranked()->paginate(25),
            'done' => IssueCluster::where('ward_id', $ward->id)
                ->where('status', IssueCluster::STATUS_DONE)
                ->where('done_at', '>=', now()->subDays(60))
                ->latest('done_at')
                ->get(),
            'leaders' => $ward->offices()->with('currentLeader')->get()->pluck('currentLeader')->filter(),
        ]);
    }
}
