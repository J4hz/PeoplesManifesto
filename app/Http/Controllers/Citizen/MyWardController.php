<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\IssueCluster;
use App\Services\CitizenClusterState;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyWardController extends Controller
{
    public function show(Request $request): View
    {
        $citizen = $request->user('citizen')->load('ward');

        $open = IssueCluster::query()
            ->where('ward_id', $citizen->ward_id)
            ->visible()
            ->openish()
            ->ranked()
            ->paginate(25);

        $done = IssueCluster::query()
            ->where('ward_id', $citizen->ward_id)
            ->where('status', IssueCluster::STATUS_DONE)
            ->where('done_at', '>=', now()->subDays(60))
            ->latest('done_at')
            ->get();

        return view('citizen.my-ward', [
            'citizen' => $citizen,
            'open' => $open,
            'done' => $done,
            'state' => new CitizenClusterState($citizen),
        ]);
    }
}
