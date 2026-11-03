<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyReportsController extends Controller
{
    public function index(Request $request): View
    {
        $citizen = $request->user('citizen');

        $submissions = $citizen->submissions()
            ->with(['cluster', 'ward'])
            ->latest()
            ->paginate(20);

        $notifications = $citizen->notifications()->latest()->limit(10)->get();
        $citizen->unreadNotifications()->update(['read_at' => now()]);

        return view('citizen.my-reports', [
            'submissions' => $submissions,
            'notifications' => $notifications,
        ]);
    }
}
