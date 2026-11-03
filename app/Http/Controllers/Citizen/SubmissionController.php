<?php

namespace App\Http\Controllers\Citizen;

use App\Actions\CreateSubmission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\StoreSubmissionRequest;
use Illuminate\Http\RedirectResponse;

class SubmissionController extends Controller
{
    public function store(StoreSubmissionRequest $request, CreateSubmission $create): RedirectResponse
    {
        $create->handle(
            $request->user('citizen'),
            $request->safe()->only(['body', 'category', 'severity']),
            $request->file('photo'),
            'app',
        );

        return redirect()->route('citizen.reports')->with('status', __('citizen.raise.thanks'));
    }
}
