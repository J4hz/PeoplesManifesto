<?php

namespace App\Http\Controllers\Leader;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $user = $request->user('web');

        $logs = AuditLog::query()
            ->where('account_leader_id', $user->leader_id)
            ->latest('id')
            ->paginate(50);

        return view('leader.audit', [
            'logs' => $logs,
            'actors' => User::whereIn('id', $logs->pluck('actor_id')->filter()->unique())->pluck('name', 'id'),
        ]);
    }
}
