<?php

namespace App\Embeddings;

use App\Ai\UsageRecorder;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VoyageEmbedder implements Embedder
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $modelName,
        private readonly int $dimensions,
        private readonly UsageRecorder $usage,
    ) {}

    public function embed(string $text): array
    {
        $response = Http::baseUrl($this->baseUrl)
            ->withToken($this->apiKey)
            ->acceptJson()
            ->timeout(30)
            ->retry(2, 1000, throw: false)
            ->post('/v1/embeddings', [
                'input' => [$text],
                'model' => $this->modelName,
                'input_type' => 'document',
                'output_dimension' => $this->dimensions,
            ])
            ->throw()
            ->json();

        $vector = $response['data'][0]['embedding'] ?? null;
        if (! is_array($vector) || count($vector) !== $this->dimensions) {
            throw new RuntimeException('Embedding response did not contain a vector of the expected size.');
        }

        $this->usage->record('embedding', $this->modelName, [
            'input_tokens' => (int) ($response['usage']['total_tokens'] ?? 0),
        ]);

        return Vectors::normalise(array_map('floatval', $vector));
    }

    public function similarityThreshold(): float
    {
        return (float) config('civic.clustering.similarity_threshold');
    }

    public function model(): string
    {
        return $this->modelName;
    }
}
