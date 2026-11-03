<?php

namespace App\Http\Controllers\Leader;

use App\Actions\PostClusterUpdate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leader\StoreUpdateRequest;
use App\Models\ClusterUpdate;
use App\Models\IssueCluster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function store(StoreUpdateRequest $request, IssueCluster $cluster, PostClusterUpdate $post): RedirectResponse
    {
        $update = $post->handle(
            $request->user('web'),
            $cluster,
            [
                'status' => $request->validated('status'),
                'message' => $request->validated('message'),
                'expected_completion_on' => $request->validated('expected_completion_on'),
                'notify_reporters' => $request->boolean('notify_reporters'),
            ],
            $request->file('photo'),
        );

        return redirect()->route('dashboard.clusters.show', $cluster)
            ->with('status', $update->isPublished() ? __('leader.respond.published') : __('leader.respond.drafted'));
    }

    public function publish(Request $request, ClusterUpdate $update, PostClusterUpdate $post): RedirectResponse
    {
        $post->publish($update, $request->user('web'));

        return redirect()->route('dashboard.clusters.show', $update->cluster_id)->with('status', __('leader.respond.published'));
    }

    public function destroy(Request $request, ClusterUpdate $update, PostClusterUpdate $post): RedirectResponse
    {
        $clusterId = $update->cluster_id;
        $post->discard($update, $request->user('web'));

        return redirect()->route('dashboard.clusters.show', $clusterId)->with('status', __('leader.respond.discarded'));
    }
}
