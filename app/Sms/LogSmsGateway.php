<?php

namespace App\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Stands in when no Africa's Talking key is set. Writes the message to the log
 * with the number masked, so local testing does not leave numbers in log files.
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $to, string $message): void
    {
        Log::channel(config('logging.default'))->info('SMS not sent, no gateway configured', [
            'to' => '...'.substr($to, -3),
            'message' => $message,
        ]);
    }
}
