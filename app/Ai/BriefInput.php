<?php

namespace App\Ai;

use App\Models\IssueCluster;
use App\Models\Submission;
use App\Models\Ward;
use Carbon\CarbonInterface;

/**
 * The facts a weekly brief is written from. The model is told to use these
 * numbers and nothing else, and the template writer uses the same facts.
 */
class BriefInput
{
    /**
     * @return array<string, mixed>
     */
    public static function for(Ward $ward, CarbonInterface $weekStart): array
    {
        $weekEnd = $weekStart->copy()->addWeek();
        $previousStart = $weekStart->copy()->subWeek();

        $thisWeek = Submission::where('ward_id', $ward->id)->whereBetween('created_at', [$weekStart, $weekEnd])->count();
        $lastWeek = Submission::where('ward_id', $ward->id)->whereBetween('created_at', [$previousStart, $weekStart])->count();

        $byCategory = Submission::where('ward_id', $ward->id)
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->whereNotNull('category')
            ->selectRaw('category, COUNT(*) AS total')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->all();

        $clusterRow = fn (IssueCluster $c) => [
            'title' => $c->title,
            'category' => $c->category,
            'residents' => $c->resident_count,
            'severity' => $c->severity,
            'trend' => $c->trend,
            'status' => $c->status,
        ];

        $open = IssueCluster::where('ward_id', $ward->id)->visible()->openish()->ranked();

        return [
            'ward' => $ward->name,
            'week_starting' => $weekStart->toDateString(),
            'reports_this_week' => $thisWeek,
            'reports_last_week' => $lastWeek,
            'reports_by_category' => $byCategory,
            'top_open_issues' => (clone $open)->limit(8)->get()->map($clusterRow)->all(),
            'rising_issues' => (clone $open)->where('trend', 'rising')->limit(5)->get()->map($clusterRow)->all(),
            'new_issues' => IssueCluster::where('ward_id', $ward->id)->visible()
                ->whereBetween('first_reported_at', [$weekStart, $weekEnd])->ranked()->limit(5)->get()->map($clusterRow)->all(),
            'falling_issues' => (clone $open)->where('trend', 'falling')->limit(5)->get()->map($clusterRow)->all(),
            'marked_done_this_week' => IssueCluster::where('ward_id', $ward->id)
                ->whereBetween('done_at', [$weekStart, $weekEnd])->get()->map($clusterRow)->all(),
            'reopened_by_residents_this_week' => IssueCluster::where('ward_id', $ward->id)
                ->whereBetween('reopened_at', [$weekStart, $weekEnd])->get()->map($clusterRow)->all(),
        ];
    }
}
