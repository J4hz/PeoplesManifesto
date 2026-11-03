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
        // Sign-in codes would end up in the log. Production must use the real gateway.
        if (app()->isProduction()) {
            throw new \RuntimeException('AT_API_KEY is not set. SMS cannot be sent in production without Africa\'s Talking.');
        }

        Log::channel(config('logging.default'))->info('SMS not sent, no gateway configured', [
            'to' => '...'.substr($to, -3),
            'message' => $message,
        ]);
    }
}
