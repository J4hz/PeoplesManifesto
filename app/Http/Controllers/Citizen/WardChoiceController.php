<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\ChooseWardRequest;
use App\Models\Constituency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WardChoiceController extends Controller
{
    public function create(Request $request): View
    {
        $constituencies = Constituency::with(['county', 'wards' => fn ($q) => $q->active()->orderBy('name')])
            ->whereHas('wards', fn ($q) => $q->active())
            ->orderBy('name')
            ->get();

        return view('citizen.ward-choice', [
            'constituencies' => $constituencies,
            'current' => $request->user('citizen')->ward_id,
        ]);
    }

    public function store(ChooseWardRequest $request): RedirectResponse
    {
        $citizen = $request->user('citizen');

        // A resident belongs to one ward. Changing it later does not move their past reports.
        $citizen->forceFill(['ward_id' => $request->integer('ward_id')])->save();

        return redirect()->intended(route('home'))->with('status', __('citizen.ward.saved'));
    }
}
