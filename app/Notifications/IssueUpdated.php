<?php

namespace App\Notifications;

use App\Models\Citizen;
use App\Models\ClusterUpdate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class IssueUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ClusterUpdate $update) {}

    /**
     * @return list<string>
     */
    public function via(Citizen $notifiable): array
    {
        return $notifiable->sms_notifications ? ['database', 'sms'] : ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Citizen $notifiable): array
    {
        return [
            'kind' => 'issue_updated',
            'cluster_id' => $this->update->cluster_id,
            'title' => $this->update->cluster->title,
        ];
    }

    public function toSms(Citizen $notifiable): string
    {
        return __('sms.update', [
            'title' => Str::limit($this->update->cluster->title, 50),
            'leader' => $this->update->leader->name,
            'message' => Str::limit($this->update->message, 70),
        ]);
    }
}
