<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Constituency;
use App\Models\IssueCluster;
use App\Services\CitizenClusterState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $citizen = $request->user('citizen');

        if ($citizen === null) {
            return view('citizen.welcome', [
                'constituencies' => Constituency::with(['county', 'wards' => fn ($q) => $q->active()->orderBy('name')])
                    ->whereHas('wards', fn ($q) => $q->active())
                    ->orderBy('name')
                    ->get(),
            ]);
        }

        if ($citizen->ward_id === null) {
            return redirect()->route('citizen.ward.choose');
        }

        $clusters = IssueCluster::query()
            ->where('ward_id', $citizen->ward_id)
            ->visible()
            ->openish()
            ->ranked()
            ->limit(5)
            ->get();

        return view('citizen.home', [
            'citizen' => $citizen->load('ward'),
            'clusters' => $clusters,
            'state' => new CitizenClusterState($citizen),
        ]);
    }
}
