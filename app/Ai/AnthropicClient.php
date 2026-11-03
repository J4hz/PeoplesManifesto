<?php

namespace App\Ai;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper over the Messages and Message Batches endpoints. Going through
 * Laravel's HTTP client keeps every call visible to Http::fake() in tests.
 */
class AnthropicClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $version,
        private readonly int $timeout,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function message(array $payload): array
    {
        return $this->http()->post('/v1/messages', $payload)->throw()->json();
    }

    /**
     * @param  list<array{custom_id: string, params: array<string, mixed>}>  $requests
     * @return array<string, mixed>
     */
    public function createBatch(array $requests): array
    {
        return $this->http()->post('/v1/messages/batches', ['requests' => $requests])->throw()->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveBatch(string $batchId): array
    {
        return $this->http()->get('/v1/messages/batches/'.rawurlencode($batchId))->throw()->json();
    }

    /**
     * Results come back as JSON Lines, in any order. Callers key them by custom_id.
     *
     * @return list<array<string, mixed>>
     */
    public function batchResults(string $resultsUrl): array
    {
        $body = $this->http()->accept('application/binary')->get($resultsUrl)->throw()->body();

        $rows = [];
        foreach (preg_split('/\r?\n/', trim($body)) as $line) {
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $rows[] = $decoded;
            }
        }

        return $rows;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->version,
            ])
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->retry(2, 1000, throw: false);
    }
}
