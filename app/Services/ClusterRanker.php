<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Citizen;
use App\Models\IssueCluster;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Turns a cluster's members into the numbers residents and leaders see, and
 * the score used to order them. Weights live in config/ranking.php.
 */
class ClusterRanker
{
    public function recalculate(IssueCluster $cluster): IssueCluster
    {
        $members = $this->members($cluster);
        $residents = $this->residents($members);

        $severity = $this->clusterSeverity($residents);
        $growth = $this->growth($residents);
        $wardAreas = Area::where('ward_id', $cluster->ward_id)->count();
        $areasCount = $members->pluck('area_id')->filter()->unique()->count();

        $cluster->fill([
            'resident_count' => $residents->count(),
            'submission_count' => $members->count(),
            'areas_count' => $areasCount,
            'severity' => $severity,
            'growth' => round($growth['ratio'], 3),
            'trend' => $this->trend($growth, $cluster->first_reported_at),
            'first_reported_at' => $members->min('created_at') ?? $cluster->first_reported_at,
        ]);

        $cluster->score = round($this->score(
            weightedResidents: $this->weightedResidents($residents),
            severity: $severity,
            category: $cluster->category,
            areasCount: $areasCount,
            wardAreas: $wardAreas,
            growthRatio: $growth['ratio'],
        ), 4);

        $cluster->save();

        return $cluster;
    }

    /**
     * Residents counted with the trust weighting applied, as used in the score.
     */
    public function weightedResidentCount(IssueCluster $cluster): float
    {
        return $this->weightedResidents($this->residents($this->members($cluster)));
    }

    /**
     * @return Collection<int, Submission>
     */
    private function members(IssueCluster $cluster): Collection
    {
        return Submission::query()
            ->join('cluster_memberships', 'cluster_memberships.submission_id', '=', 'submissions.id')
            ->where('cluster_memberships.cluster_id', $cluster->id)
            ->get(['submissions.id', 'submissions.area_id', 'submissions.severity', 'submissions.created_at']);
    }

    /**
     * Pure scoring function, kept separate so the weights can be tested directly.
     */
    public function score(float $weightedResidents, string $severity, string $category, int $areasCount, int $wardAreas, float $growthRatio): float
    {
        $w = config('ranking.weights');

        $severityPoints = (float) config("ranking.severity_points.{$severity}", 1.0);
        if ($severity === 'urgent') {
            $severityPoints *= (float) config("ranking.urgent_category_multiplier.{$category}", 1.0);
        }

        $spread = $wardAreas > 0 ? min(1.0, $areasCount / $wardAreas) : ($areasCount > 0 ? 1.0 : 0.0);
        $growth = min((float) config('ranking.growth.cap'), max(0.0, $growthRatio));

        return $w['residents'] * log(1 + max(0.0, $weightedResidents))
            + $w['severity'] * $severityPoints
            + $w['spread'] * $spread
            + $w['growth'] * $growth;
    }

    /**
     * One row per distinct resident: their first report time, severity and account age.
     *
     * @param  Collection<int, Submission>  $members
     * @return Collection<string, array{first: CarbonInterface, severity: ?string, created: ?CarbonInterface, trust: float}>
     */
    private function residents(Collection $members): Collection
    {
        if ($members->isEmpty()) {
            return collect();
        }

        $authors = SubmissionAuthor::whereIn('submission_id', $members->pluck('id'))->get()->keyBy('submission_id');

        $rows = [];
        foreach ($members->sortBy('created_at') as $member) {
            $author = $authors->get($member->id);
            if ($author === null) {
                continue;
            }
            $ref = $author->citizen_ref;
            if (! isset($rows[$ref])) {
                $rows[$ref] = ['first' => $member->created_at, 'severity' => $member->severity, 'citizen_id' => (int) ($author->citizen_id ?? 0)];
            } elseif ($member->severity !== null) {
                $rows[$ref]['severity'] = $this->higher($rows[$ref]['severity'], $member->severity);
            }
        }

        $citizens = Citizen::whereIn('id', array_column($rows, 'citizen_id'))->get(['id', 'created_at', 'trust_score'])->keyBy('id');

        return collect($rows)->map(fn ($row) => [
            'first' => $row['first'],
            'severity' => $row['severity'],
            'created' => $citizens->get($row['citizen_id'])?->created_at,
            'trust' => (float) ($citizens->get($row['citizen_id'])?->trust_score ?? 1.0),
        ]);
    }

    /**
     * Long-standing accounts count a little more. When a crowd of brand new
     * accounts lands on one issue in a short window, those new accounts count much less.
     *
     * @param  Collection<string, array{first: CarbonInterface, severity: ?string, created: ?CarbonInterface, trust: float}>  $residents
     */
    public function weightedResidents(Collection $residents): float
    {
        $t = config('ranking.trust');
        $now = now();
        $windowStart = $now->copy()->subHours($t['burst_window_hours']);

        // Deleted accounts have no creation date and count as an ordinary resident.
        $isNew = fn ($r) => $r['created'] !== null && $r['created']->diffInDays($now) < $t['new_account_days'];

        $recent = $residents->filter(fn ($r) => $r['first']->gte($windowStart));
        $recentNew = $recent->filter($isNew)->count();
        $burst = $recentNew >= $t['burst_min_new_accounts']
            && $recent->count() > 0
            && $recentNew / $recent->count() >= $t['burst_share'];

        return (float) $residents->sum(function ($r) use ($t, $now, $isNew, $burst, $windowStart) {
            $weight = 1.0;
            if ($r['created'] !== null && $r['created']->diffInDays($now) >= $t['long_standing_days']) {
                $weight = (float) $t['long_standing_weight'];
            } elseif ($burst && $isNew($r) && $r['first']->gte($windowStart)) {
                $weight = (float) $t['burst_new_account_weight'];
            }

            return $weight * max(0.0, min(2.0, $r['trust']));
        });
    }

    /**
     * The highest severity that at least a quarter of residents chose. Small
     * clusters take the highest, so a single urgent report is not watered down.
     *
     * @param  Collection<string, array{severity: ?string}>  $residents
     */
    private function clusterSeverity(Collection $residents): string
    {
        $levels = config('civic.severities');
        $counts = $residents->pluck('severity')->filter()->countBy();

        if ($counts->isEmpty()) {
            return 'annoying';
        }

        $total = $counts->sum();
        $ordered = collect($levels)->sortDesc()->keys();

        foreach ($ordered as $level) {
            $count = $counts->get($level, 0);
            if ($count > 0 && ($total < 4 || $count / $total >= 0.25)) {
                return $level;
            }
        }

        return 'annoying';
    }

    /**
     * @param  Collection<string, array{first: CarbonInterface}>  $residents
     * @return array{recent: int, previous: int, ratio: float}
     */
    private function growth(Collection $residents): array
    {
        $days = (int) config('ranking.growth.window_days');
        $now = now();
        $recentStart = $now->copy()->subDays($days);
        $previousStart = $now->copy()->subDays($days * 2);

        $recent = $residents->filter(fn ($r) => $r['first']->gte($recentStart))->count();
        $previous = $residents->filter(fn ($r) => $r['first']->gte($previousStart) && $r['first']->lt($recentStart))->count();

        $ratio = $previous === 0 ? (float) min($recent, (int) config('ranking.growth.cap')) : $recent / $previous;

        return ['recent' => $recent, 'previous' => $previous, 'ratio' => $ratio];
    }

    /**
     * @param  array{recent: int, previous: int, ratio: float}  $growth
     */
    private function trend(array $growth, ?CarbonInterface $firstReported): string
    {
        $g = config('ranking.growth');

        if ($firstReported === null || $firstReported->gte(now()->subDays($g['window_days']))) {
            return 'new';
        }
        if ($growth['previous'] === 0) {
            return $growth['recent'] > 0 ? 'rising' : 'steady';
        }
        if ($growth['ratio'] > $g['rising_above']) {
            return 'rising';
        }
        if ($growth['ratio'] < $g['falling_below']) {
            return 'falling';
        }

        return 'steady';
    }

    private function higher(?string $a, string $b): string
    {
        $levels = config('civic.severities');

        return $a === null || $levels[$b] > $levels[$a] ? $b : $a;
    }
}
