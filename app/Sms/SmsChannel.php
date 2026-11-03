<?php

namespace App\Sms;

use App\Jobs\SendSms;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $to = $notifiable->routeNotificationFor('sms', $notification);
        if (! is_string($to) || $to === '' || ! method_exists($notification, 'toSms')) {
            return;
        }

        SendSms::dispatch($to, $notification->toSms($notifiable));
    }
}
