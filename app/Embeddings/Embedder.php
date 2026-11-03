<?php

namespace App\Embeddings;

interface Embedder
{
    /**
     * @return list<float> unit length vector of config('ai.embeddings.dimensions') floats
     */
    public function embed(string $text): array;

    /**
     * Cosine similarity needed to join an existing cluster. Different embedders
     * spread their scores differently, so the threshold travels with the embedder.
     */
    public function similarityThreshold(): float;

    public function model(): string;
}
