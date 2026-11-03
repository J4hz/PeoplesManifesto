<?php

namespace App\Notifications;

use App\Models\IssueCluster;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IssueReopened extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public IssueCluster $cluster, public int $notFixed, public int $total) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('leader.mail.reopened_subject', ['title' => $this->cluster->title]))
            ->greeting(__('leader.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('leader.mail.reopened_line', [
                'title' => $this->cluster->title,
                'ward' => $this->cluster->ward->name,
                'not_fixed' => $this->notFixed,
                'total' => $this->total,
            ]))
            ->action(__('leader.mail.open_issue'), route('dashboard.clusters.show', $this->cluster))
            ->line(__('leader.mail.reopened_next'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'kind' => 'issue_reopened',
            'cluster_id' => $this->cluster->id,
            'title' => $this->cluster->title,
            'not_fixed' => $this->notFixed,
            'total' => $this->total,
        ];
    }
}
