<?php

namespace App\Http\Controllers\Citizen;

use App\Actions\RecordRating;
use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreRatingRequest;
use App\Models\IssueCluster;
use App\Models\Rating;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RatingController extends Controller
{
    public function create(IssueCluster $cluster): View
    {
        $this->authorize('rate', $cluster);

        $ratings = $cluster->currentRatings();

        return view('citizen.rate', [
            'cluster' => $cluster->load('doneBy.office'),
            'fixedCount' => (clone $ratings)->where('verdict', Rating::FIXED)->count(),
            'totalCount' => $ratings->count(),
            'lastUpdate' => $cluster->publishedUpdates()->first(),
        ]);
    }

    public function store(StoreRatingRequest $request, IssueCluster $cluster, RecordRating $record): RedirectResponse
    {
        $record->handle($request->user('citizen'), $cluster, $request->validated(), 'app');

        return redirect()->route('clusters.show', $cluster)->with('status', __('citizen.rate.thanks'));
    }
}
