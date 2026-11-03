<?php

namespace App\Http\Controllers\Admin;

use App\Ai\SpendGuard;
use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AiUsageController extends Controller
{
    public function index(SpendGuard $spend): View
    {
        $since = now()->subWeeks(8)->startOfWeek();

        $rows = AiUsageLog::query()
            ->where('created_at', '>=', $since)
            ->select([
                'ward_id',
                DB::raw("date_trunc('week', created_at)::date AS week"),
                DB::raw('SUM(cost_usd) AS cost'),
                DB::raw('SUM(input_tokens + cache_creation_tokens + cache_read_tokens) AS input_tokens'),
                DB::raw('SUM(output_tokens) AS output_tokens'),
                DB::raw('COUNT(*) AS calls'),
            ])
            ->groupBy('ward_id', 'week')
            ->orderByDesc('week')
            ->get();

        $byJob = AiUsageLog::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->select(['job', 'model', DB::raw('SUM(cost_usd) AS cost'), DB::raw('COUNT(*) AS calls'), DB::raw('SUM(cache_read_tokens) AS cache_read')])
            ->groupBy('job', 'model')
            ->orderByDesc('cost')
            ->get();

        return view('admin.ai-usage', [
            'rows' => $rows,
            'wards' => Ward::pluck('name', 'id'),
            'byJob' => $byJob,
            'spent' => $spend->spentThisMonth(),
            'cap' => $spend->cap(),
            'briefsPaused' => ! $spend->briefsAllowed(),
        ]);
    }
}
