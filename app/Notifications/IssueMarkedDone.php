<?php

namespace App\Notifications;

use App\Models\Citizen;
use App\Models\IssueCluster;
use App\Models\Leader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Goes to every resident counted on the issue, whatever their SMS setting for
 * routine updates, because the reopen rule depends on hearing from them.
 */
class IssueMarkedDone extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public IssueCluster $cluster, public Leader $leader) {}

    /**
     * @return list<string>
     */
    public function via(Citizen $notifiable): array
    {
        return ['database', 'sms'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Citizen $notifiable): array
    {
        return [
            'kind' => 'issue_done',
            'cluster_id' => $this->cluster->id,
            'title' => $this->cluster->title,
        ];
    }

    public function toSms(Citizen $notifiable): string
    {
        return __('sms.done', [
            'leader' => $this->leader->name,
            'title' => Str::limit($this->cluster->title, 50),
        ]);
    }
}
