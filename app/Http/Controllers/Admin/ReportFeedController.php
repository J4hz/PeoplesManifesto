<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Ward;
use App\Services\ReportFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportFeedController extends Controller
{
    public function index(Request $request, ReportFeed $feed): View
    {
        $this->authorize('viewAnyAnonymous', Submission::class);

        $wards = Ward::orderBy('name')->get();
        $wardFilter = $request->integer('ward') ?: null;

        // Admins are not leaders, so issue links go to the public issue page, not the respond page.
        return view('leader.reports', [
            'reports' => $feed->paginate(null, $wards->contains('id', $wardFilter) ? $wardFilter : null),
            'wards' => $wards,
            'wardFilter' => $wardFilter,
            'filterRoute' => route('admin.reports'),
            'issueRoute' => 'clusters.show',
            'active' => 'admin-reports',
        ]);
    }
}
