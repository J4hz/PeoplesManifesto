<?php

namespace App\Jobs;

use App\Models\IssueCluster;
use App\Models\Rating;
use App\Models\User;
use App\Notifications\IssueReopened;
use App\Services\AuditLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Runs daily. A Done issue is judged once its rating window closes: after
 * seven days, or as soon as twenty residents have rated. If more than half
 * of those who rated say "not fixed", the issue reopens.
 */
class CheckDoneClusters implements ShouldQueue
{
    use Queueable;

    public function handle(AuditLogger $audit): void
    {
        $rules = config('civic.rating');

        IssueCluster::query()
            ->where('status', IssueCluster::STATUS_DONE)
            ->whereNull('rating_window_closed_at')
            ->with(['ward', 'doneBy'])
            ->chunkById(100, function ($clusters) use ($rules, $audit) {
                foreach ($clusters as $cluster) {
                    $this->judge($cluster, $rules, $audit);
                }
            });
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    public function judge(IssueCluster $cluster, array $rules, AuditLogger $audit): void
    {
        $counts = Rating::query()
            ->where('cluster_id', $cluster->id)
            ->where('round', $cluster->done_round)
            ->selectRaw('verdict, COUNT(*) AS total')
            ->groupBy('verdict')
            ->pluck('total', 'verdict');

        $total = (int) $counts->sum();
        $notFixed = (int) ($counts[Rating::NOT_FIXED] ?? 0);

        $windowOver = $cluster->done_at !== null && $cluster->done_at->lte(now()->subDays($rules['window_days']));
        $enoughVotes = $total >= $rules['min_ratings_for_early_decision'];

        if (! $windowOver && ! $enoughVotes) {
            return;
        }

        $reopen = $total > 0 && $notFixed / $total > $rules['reopen_share'];

        DB::transaction(function () use ($cluster, $reopen) {
            $cluster->forceFill(array_filter([
                'rating_window_closed_at' => now(),
                'status' => $reopen ? IssueCluster::STATUS_REOPENED : null,
                'reopened_at' => $reopen ? now() : null,
                'last_activity_at' => $reopen ? now() : null,
            ]))->save();
        });

        $audit->record(
            $reopen ? 'cluster.reopened_by_residents' : 'cluster.fix_confirmed',
            $cluster,
            before: ['status' => IssueCluster::STATUS_DONE],
            after: ['status' => $cluster->status, 'ratings' => $total, 'not_fixed' => $notFixed],
            accountLeaderId: $cluster->done_by_leader_id,
        );

        if ($reopen) {
            $this->notifyLeaders($cluster, $notFixed, $total);
        }
    }

    private function notifyLeaders(IssueCluster $cluster, int $notFixed, int $total): void
    {
        $leaderIds = array_filter([$cluster->done_by_leader_id, $cluster->routedOffice?->currentLeader?->id]);

        $users = User::query()
            ->where('is_active', true)
            ->where('role', User::ROLE_LEADER)
            ->whereIn('leader_id', $leaderIds)
            ->get();

        Notification::send($users, new IssueReopened($cluster, $notFixed, $total));
    }
}
