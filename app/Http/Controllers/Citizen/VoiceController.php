<?php

namespace App\Http\Controllers\Citizen;

use App\Actions\AddVoice;
use App\Http\Controllers\Controller;
use App\Models\IssueCluster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VoiceController extends Controller
{
    public function store(Request $request, IssueCluster $cluster, AddVoice $addVoice): RedirectResponse
    {
        $this->authorize('addVoice', $cluster);

        $added = $addVoice->handle($request->user('citizen'), $cluster);

        return back()->with('status', $added ? __('citizen.ward.voice_added') : __('citizen.ward.voice_already'));
    }
}
