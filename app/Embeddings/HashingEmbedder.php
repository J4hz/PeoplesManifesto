<?php

namespace App\Embeddings;

use Illuminate\Support\Str;

/**
 * Bag-of-words feature hashing. Used when no embeddings key is set. Good enough
 * to group "no water in Kiuu" with "hakuna maji Kiuu" for local testing, nowhere
 * near as good as a trained model.
 */
class HashingEmbedder implements Embedder
{
    /** @var array<string, string> */
    private const SYNONYMS = [
        'maji' => 'water', 'barabara' => 'road', 'roads' => 'road', 'shimo' => 'pothole', 'potholes' => 'pothole',
        'stima' => 'electricity', 'power' => 'electricity', 'taka' => 'garbage', 'takataka' => 'garbage',
        'shule' => 'school', 'schools' => 'school', 'hospitali' => 'hospital', 'zahanati' => 'dispensary',
        'wezi' => 'theft', 'mwizi' => 'theft', 'thieves' => 'theft', 'usalama' => 'security', 'soko' => 'market',
        'siku' => 'days', 'wiki' => 'week', 'hakuna' => 'no', 'none' => 'no', 'taps' => 'tap', 'bomba' => 'pipe',
    ];

    private const STOPWORDS = ['the', 'a', 'an', 'in', 'on', 'at', 'of', 'for', 'to', 'and', 'is', 'are', 'has', 'have', 'we', 'our', 'it', 'this', 'that', 'there', 'na', 'ya', 'kwa', 'za', 'wa', 'la', 'ni', 'sisi', 'yetu', 'since', 'been', 'with', 'please', 'tafadhali'];

    public function __construct(private readonly int $dimensions) {}

    public function embed(string $text): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', Str::lower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_values(array_filter(
            array_map(fn ($t) => self::SYNONYMS[$t] ?? $t, $tokens),
            fn ($t) => mb_strlen($t) > 1 && ! in_array($t, self::STOPWORDS, true),
        ));

        $vector = array_fill(0, $this->dimensions, 0.0);
        foreach ($tokens as $token) {
            $hash = crc32($token);
            $vector[$hash % $this->dimensions] += ($hash & 1) ? 1.0 : -1.0;
        }

        // A tiny constant keeps an empty text from producing a zero vector, which cosine cannot handle.
        $vector[0] += 0.001;

        return Vectors::normalise($vector);
    }

    public function similarityThreshold(): float
    {
        return (float) config('civic.clustering.local_similarity_threshold', 0.3);
    }

    public function model(): string
    {
        return 'local-hashing';
    }
}
