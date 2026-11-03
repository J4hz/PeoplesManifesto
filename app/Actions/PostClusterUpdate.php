<?php

namespace App\Actions;

use App\Models\ClusterUpdate;
use App\Models\IssueCluster;
use App\Models\User;
use App\Notifications\IssueMarkedDone;
use App\Notifications\IssueUpdated;
use App\Services\AuditLogger;
use App\Services\PhotoStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class PostClusterUpdate
{
    public function __construct(
        private readonly PhotoStore $photos,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Leaders publish straight away. Staff updates wait as drafts for the leader.
     *
     * @param  array{status?: ?string, message: string, expected_completion_on?: ?string, notify_reporters?: bool}  $data
     */
    public function handle(User $author, IssueCluster $cluster, array $data, ?UploadedFile $photo): ClusterUpdate
    {
        $photoPath = $photo ? $this->photos->store($photo, 'updates') : null;

        $update = ClusterUpdate::create([
            'cluster_id' => $cluster->id,
            'leader_id' => $author->leader_id,
            'author_user_id' => $author->id,
            'status' => $data['status'] ?? null,
            'message' => trim($data['message']),
            'expected_completion_on' => $data['expected_completion_on'] ?? null,
            'photo_path' => $photoPath,
            'notify_reporters' => (bool) ($data['notify_reporters'] ?? false),
        ]);

        if ($author->isLeader()) {
            return $this->publish($update, $author);
        }

        $this->audit->record('update.drafted', $update, after: $this->snapshot($update), actor: $author);

        return $update;
    }

    public function publish(ClusterUpdate $update, User $leader): ClusterUpdate
    {
        Gate::forUser($leader)->authorize('publish', $update);

        $cluster = $update->cluster;
        $before = $cluster->only(['status', 'done_round']);

        DB::transaction(function () use ($update, $leader, $cluster) {
            $update->forceFill(['published_at' => now(), 'published_by_user_id' => $leader->id])->save();

            $changes = ['last_activity_at' => now()];
            $changes['first_response_at'] = $cluster->first_response_at ?? now();

            if ($update->status !== null) {
                $changes['status'] = $update->status;
            }

            if ($update->status === IssueCluster::STATUS_DONE) {
                $changes['done_at'] = now();
                $changes['done_by_leader_id'] = $leader->leader_id;
                $changes['done_round'] = $cluster->done_round + 1;
                $changes['rating_window_closed_at'] = null;
            }

            $cluster->forceFill($changes)->save();
        });

        $this->audit->record('update.published', $update, before: $before, after: array_merge(
            $this->snapshot($update),
            $cluster->only(['status', 'done_round']),
        ), actor: $leader);

        $this->notify($update, $cluster);

        return $update;
    }

    public function discard(ClusterUpdate $update, User $actor): void
    {
        Gate::forUser($actor)->authorize('delete', $update);

        $this->audit->record('update.discarded', $update, before: $this->snapshot($update), actor: $actor);
        $update->delete();
    }

    private function notify(ClusterUpdate $update, IssueCluster $cluster): void
    {
        if ($update->status === IssueCluster::STATUS_DONE) {
            Notification::send($cluster->reporters(), new IssueMarkedDone($cluster, $update->leader));

            return;
        }

        if ($update->notify_reporters) {
            Notification::send($cluster->reporters(), new IssueUpdated($update));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(ClusterUpdate $update): array
    {
        return [
            'cluster_id' => $update->cluster_id,
            'status' => $update->status,
            'message' => $update->message,
            'expected_completion_on' => $update->expected_completion_on?->toDateString(),
            'has_photo' => $update->photo_path !== null,
            'notify_reporters' => $update->notify_reporters,
        ];
    }
}
