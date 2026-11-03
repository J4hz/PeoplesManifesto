<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::latest('id')->paginate(50);

        return view('admin.audit', [
            'logs' => $logs,
            'actors' => User::whereIn('id', $logs->pluck('actor_id')->filter()->unique())->pluck('name', 'id'),
        ]);
    }
}
