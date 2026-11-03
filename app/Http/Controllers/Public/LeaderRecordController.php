<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Leader;
use App\Services\LeaderRecord;
use Illuminate\View\View;

class LeaderRecordController extends Controller
{
    public function show(Leader $leader, LeaderRecord $record): View
    {
        return view('public.leader', [
            'leader' => $leader->load('office.jurisdiction'),
            'record' => $record->for($leader),
        ]);
    }
}
