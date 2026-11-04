<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Services\Jurisdiction;
use App\Services\ReportFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportFeedController extends Controller
{
    public function index(Request $request, ReportFeed $feed): View
    {
        $this->authorize('viewAnyAnonymous', Submission::class);

        $scope = new Jurisdiction($request->user('web'));
        $wards = $scope->wards();
        $wardFilter = $request->integer('ward') ?: null;
        if ($wardFilter !== null && ! $wards->contains('id', $wardFilter)) {
            $wardFilter = null;
        }

        return view('leader.reports', [
            'reports' => $feed->paginate($scope->wardIds(), $wardFilter),
            'wards' => $wards,
            'wardFilter' => $wardFilter,
            'filterRoute' => route('dashboard.reports'),
            'issueRoute' => 'dashboard.clusters.show',
            'active' => 'reports',
        ]);
    }
}
