<?php

namespace App\Http\Controllers\Admin;

use App\Ai\SpendGuard;
use App\Http\Controllers\Controller;
use App\Models\Citizen;
use App\Models\IssueCluster;
use App\Models\Submission;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function index(SpendGuard $spend): View
    {
        return view('admin.overview', [
            'citizens' => Citizen::count(),
            'reportsThisWeek' => Submission::where('created_at', '>=', now()->subDays(7))->count(),
            'openClusters' => IssueCluster::visible()->openish()->count(),
            'unprocessed' => Submission::where('processing_status', '!=', Submission::STATUS_PROCESSED)
                ->where('created_at', '<', now()->subMinutes(10))
                ->count(),
            'spent' => $spend->spentThisMonth(),
            'cap' => $spend->cap(),
        ]);
    }
}
