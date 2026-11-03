<?php

namespace App\Services;

use App\Embeddings\Embedder;
use App\Embeddings\Vectors;
use App\Models\ClusterMembership;
use App\Models\IssueCluster;
use App\Models\Submission;
use Pgvector\Laravel\Vector;

class Clusterer
{
    public function __construct(private readonly Embedder $embedder) {}

    /**
     * Joins the submission to the closest open cluster in the same ward and
     * category, or starts a new one. Caller wraps this in a transaction.
     */
    public function assign(Submission $submission): IssueCluster
    {
        $embedding = $submission->embedding?->toArray();

        $match = $embedding === null ? null : $this->nearest($submission, $embedding);

        if ($match !== null && $match['similarity'] >= $this->embedder->similarityThreshold()) {
            /** @var IssueCluster $cluster */
            $cluster = $match['cluster'];
            $this->attach($cluster, $submission, $match['similarity'], $embedding);

            return $cluster;
        }

        $cluster = IssueCluster::create([
            'ward_id' => $submission->ward_id,
            'category' => $submission->category,
            'routed_office_id' => $submission->ward->officeOfType(config("civic.routing.{$submission->category}", 'mca'))?->id,
            'title' => $submission->ai_title ?: 'Report from residents',
            'summary' => $submission->ai_summary,
            'status' => IssueCluster::STATUS_OPEN,
            'severity' => $submission->severity ?? 'annoying',
            'first_reported_at' => $submission->created_at,
            'last_activity_at' => $submission->created_at,
        ]);

        $this->attach($cluster, $submission, 1.0, $embedding);

        return $cluster;
    }

    /**
     * "+" on an existing issue. No text, so no embedding and no AI.
     */
    public function addVoice(IssueCluster $cluster, Submission $voice): void
    {
        ClusterMembership::create([
            'cluster_id' => $cluster->id,
            'submission_id' => $voice->id,
            'similarity' => 1.0,
        ]);

        $cluster->forceFill(['last_activity_at' => now()])->save();
    }

    /**
     * @param  list<float>  $embedding
     * @return array{cluster: IssueCluster, similarity: float}|null
     */
    private function nearest(Submission $submission, array $embedding): ?array
    {
        $vector = (string) new Vector($embedding);

        $cluster = IssueCluster::query()
            ->select('issue_clusters.*')
            ->selectRaw('1 - (centroid <=> ?::vector) AS similarity', [$vector])
            ->where('ward_id', $submission->ward_id)
            ->where('category', $submission->category)
            ->whereIn('status', config('civic.clustering.match_statuses'))
            ->whereNotNull('centroid')
            ->orderByRaw('centroid <=> ?::vector', [$vector])
            ->first();

        if ($cluster === null) {
            return null;
        }

        return ['cluster' => $cluster, 'similarity' => (float) $cluster->getAttribute('similarity')];
    }

    /**
     * @param  list<float>|null  $embedding
     */
    private function attach(IssueCluster $cluster, Submission $submission, float $similarity, ?array $embedding): void
    {
        ClusterMembership::create([
            'cluster_id' => $cluster->id,
            'submission_id' => $submission->id,
            'similarity' => round($similarity, 4),
        ]);

        // The "similarity" alias from the neighbour query is not a real column.
        $cluster->offsetUnset('similarity');

        if ($embedding !== null) {
            $current = $cluster->centroid?->toArray();
            $weight = (int) $cluster->centroid_weight;
            $cluster->centroid = new Vector($current === null ? $embedding : Vectors::addToMean($current, $weight, $embedding));
            $cluster->centroid_weight = $weight + 1;
        }

        $cluster->last_activity_at = now();
        $cluster->save();
    }
}
