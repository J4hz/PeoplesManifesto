<?php

namespace App\Jobs;

use App\Sms\SmsGateway;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Encrypted on the queue because the payload carries a phone number.
 */
class SendSms implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public function __construct(public string $to, public string $message)
    {
        $this->onQueue('sms');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(SmsGateway $gateway): void
    {
        $gateway->send($this->to, $this->message);
    }
}
