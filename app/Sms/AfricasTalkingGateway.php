<?php

namespace App\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AfricasTalkingGateway implements SmsGateway
{
    public function __construct(
        private readonly string $username,
        private readonly string $apiKey,
        private readonly ?string $from,
        private readonly bool $sandbox,
    ) {}

    public function send(string $to, string $message): void
    {
        $payload = array_filter([
            'username' => $this->username,
            'to' => $to,
            'message' => $message,
            'from' => $this->from,
        ], fn ($v) => $v !== null && $v !== '');

        $response = Http::withHeaders(['apiKey' => $this->apiKey])
            ->acceptJson()
            ->asForm()
            ->timeout(20)
            ->post($this->endpoint(), $payload)
            ->throw()
            ->json();

        $status = $response['SMSMessageData']['Recipients'][0]['status'] ?? null;
        if ($status !== 'Success') {
            throw new RuntimeException('Africa\'s Talking did not accept the message: '.(is_string($status) ? $status : 'no status'));
        }
    }

    private function endpoint(): string
    {
        return $this->sandbox
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';
    }
}
